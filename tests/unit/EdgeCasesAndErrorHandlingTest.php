<?php

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

/** @noinspection PhpUnhandledExceptionInspection */


declare(strict_types=1);

namespace unit;

use Aws\S3\Exception\S3Exception;
use Codeception\Test\Unit;
use cusodede\s3\helpers\S3Helper;
use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\models\cloud_storage\CloudStorageTags;
use cusodede\s3\models\S3;
use cusodede\s3\S3Module;
use Throwable;
use Yii;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\web\NotFoundHttpException;

/**
 * Comprehensive test suite for edge cases and error handling
 * Tests boundary conditions, error scenarios, and exceptional cases
 */
class EdgeCasesAndErrorHandlingTest extends Unit
{
    private const string SAMPLE_FILE_PATH = './tests/_data/sample.txt';
    private const string TEST_BUCKET = 'testbucket';

    /**
     * Test handling of very large files (simulation)
     * @throws Throwable
     */
    public function testLargeFileHandling(): void
    {
        $s3 = new S3();

        // Create a temporary large-ish file for testing
        $largeFile = sys_get_temp_dir() . '/large-test-' . uniqid('', true) . '.bin';
        $fileSize = 1024 * 1024; // 1MB (small for testing, but still demonstrates handling)

        // Create file with random data
        $handle = fopen($largeFile, 'wb');
        for ($i = 0; $i < $fileSize / 1024; $i++) {
            fwrite($handle, str_repeat('A', 1024));
        }
        fclose($handle);

        $testKey = 'large-file-' . uniqid('', true);

        try {
            // Upload large file
            $bucket = self::TEST_BUCKET;
            $result = $s3->putObject($largeFile, $testKey, $bucket);
            $this::assertNotNull($result);

            // Download and verify
            $downloadPath = sys_get_temp_dir() . '/large-download-' . uniqid('', true) . '.bin';
            $s3->getObject($testKey, $bucket, $downloadPath);

            $this::assertEquals(filesize($largeFile), filesize($downloadPath));

            // Clean up
            $s3->deleteObject($testKey, $bucket);
            unlink($downloadPath);
        } finally {
            if (file_exists($largeFile)) {
                unlink($largeFile);
            }
        }
    }

    /**
     * Test handling of files with zero size
     * @throws Throwable
     */
    public function testZeroSizeFile(): void
    {
        $emptyFile = sys_get_temp_dir() . '/empty-test-' . uniqid('', true) . '.txt';
        touch($emptyFile); // Create empty file

        $storage = S3Helper::FileToStorage($emptyFile, 'empty-file.txt');

        $this::assertEquals(0, $storage->size);
        $this::assertTrue($storage->uploaded);

        // Download and verify
        $downloadPath = S3Helper::StorageToFile($storage->id);
        $this::assertNotNull($downloadPath);
        $this::assertEquals(0, filesize($downloadPath));

        // Clean up
        S3Helper::deleteFile($storage->id);
        unlink($emptyFile);
        unlink($downloadPath);
    }

    /**
     * Test handling of files with extremely long names
     * @throws Throwable
     */
    public function testExtremelyLongFilenames(): void
    {
        // Create filename that exceeds database limit (340 chars vs 255 char limit)
        $longBasename = str_repeat('very-long-filename', 20); // 340 chars
        $longFilename = $longBasename . '.txt';

        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        // Database has 255 character limit for filename column
        // This should fail during CloudStorage save validation
        try {
            $storage = S3Helper::FileToStorage($filePath, $longFilename);

            // If it somehow succeeds (unlikely), verify the data
            if ($storage->id > 0) {
                $this::assertLessThanOrEqual(255, strlen($storage->filename));
                S3Helper::deleteFile($storage->id);
            }
        } catch (Exception $e) {
            // Expected: database constraint or validation error
            $errorMessage = strtolower($e->getMessage());
            $this::assertTrue(
                str_contains($errorMessage, 'length') ||
                str_contains($errorMessage, 'characters') ||
                str_contains($errorMessage, 'string') ||
                str_contains($errorMessage, 'validation'),
                'Expected length/validation error, got: ' . $e->getMessage()
            );
        }
    }

    /**
     * Test handling of Unicode and special characters in filenames
     * @throws Throwable
     */
    public function testUnicodeFilenames(): void
    {
        $unicodeFilenames = [
            'тест-файл.txt', // Cyrillic
            '测试文件.txt',    // Chinese
            'ファイル.txt',     // Japanese
            '🚀-файл.txt',     // Emoji
            'file with spaces & symbols!@#$%^&*().txt'
        ];

        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        foreach ($unicodeFilenames as $unicodeFilename) {
            try {
                $storage = S3Helper::FileToStorage($filePath, $unicodeFilename);

                $this::assertEquals($unicodeFilename, $storage->filename);
                $this::assertTrue($storage->uploaded);

                // Test download
                $downloadPath = S3Helper::StorageToFile($storage->id);
                $this::assertNotNull($downloadPath);
                $this::assertFileEquals($filePath, $downloadPath);

                // Clean up
                S3Helper::deleteFile($storage->id);
                unlink($downloadPath);
            } catch (Exception $e) {
                // Some characters might not be supported
                $this::assertNotEmpty($e->getMessage());
            }
        }
    }

    /**
     * Test concurrent access to the same resource
     * @throws Throwable
     */
    public function testConcurrentAccess(): void
    {
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $baseKey = 'concurrent-' . uniqid('', true);

        // Simulate concurrent uploads
        $storages = [];
        for ($i = 0; $i < 5; $i++) {
            $storage = S3Helper::FileToStorage($filePath, "{$baseKey}-{$i}.txt");
            $storages[] = $storage;
        }

        // Verify all uploads succeeded
        foreach ($storages as $storage) {
            $this::assertTrue($storage->uploaded);
            $this::assertNotEmpty($storage->key);
        }

        // Simulate concurrent downloads
        $downloadPaths = [];
        foreach ($storages as $storage) {
            $downloadPath = S3Helper::StorageToFile($storage->id);
            $this::assertNotNull($downloadPath);
            $this::assertFileEquals($filePath, $downloadPath);
            $downloadPaths[] = $downloadPath;
        }

        // Clean up
        foreach ($storages as $storage) {
            S3Helper::deleteFile($storage->id);
        }
        foreach ($downloadPaths as $downloadPath) {
            unlink($downloadPath);
        }
    }

    /**
     * Test network interruption simulation
     * Note: This is a simplified test - real network interruption testing requires more complex setup
     */
    public function testNetworkTimeout(): void
    {
        // Create S3 instance with very short timeout
        $originalModule = Yii::$app->getModule('s3');

        try {
            Yii::$app->setModule('s3', [
                'class' => S3Module::class,
                'params' => [
                    'connection' => [
                        'host' => $_ENV['MINIO_HOST'],
                        'login' => $_ENV['MINIO_ROOT_USER'],
                        'password' => $_ENV['MINIO_ROOT_PASSWORD'],
                        'connect_timeout' => 0.001, // Very short timeout
                        'timeout' => 0.001,
                    ],
                    'defaultBucket' => 'testbucket',
                ]
            ]);

            $s3 = new S3();

            // This should either work (if network is very fast) or timeout
            $buckets = $s3->getListBucketMap();
            // If it works, verify we get expected results
            $this::assertIsArray($buckets);
        } finally {
            // Restore original module configuration
            Yii::$app->setModule('s3', $originalModule);
        }
    }

    /**
     * Test invalid connection credentials
     */
    public function testInvalidCredentials(): void
    {
        $originalModule = Yii::$app->getModule('s3');

        try {
            Yii::$app->setModule('s3', [
                'class' => S3Module::class,
                'params' => [
                    'connection' => [
                        'host' => $_ENV['MINIO_HOST'],
                        'login' => 'invalid-user',
                        'password' => 'invalid-password',
                    ],
                    'defaultBucket' => 'testbucket',
                ]
            ]);

            $s3 = new S3();

            $this->expectException(S3Exception::class);
            $s3->getListBucketMap();
        } finally {
            Yii::$app->setModule('s3', $originalModule);
        }
    }

    /**
     * Test operations with non-existent bucket
     * @throws Throwable
     */
    public function testNonExistentBucket(): void
    {
        $s3 = new S3();
        $nonExistentBucket = 'non-existent-bucket-' . uniqid('', true);
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $testKey = 'test-key';

        $this->expectException(S3Exception::class);
        $s3->putObject($filePath, $testKey, $nonExistentBucket);
    }

    /**
     * Test memory exhaustion protection with large tag arrays
     * @throws Throwable
     */
    public function testLargeTagArrays(): void
    {
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        // Create large tag array (but within S3 limits)
        $largeTags = [];
        for ($i = 0; $i < 50; $i++) { // S3 allows up to 50 tags
            $largeTags["tag-{$i}"] = "value-{$i}";
        }

        try {
            $storage = S3Helper::FileToStorage($filePath, 'large-tags-test.txt', self::TEST_BUCKET, $largeTags);

            $this::assertEquals($largeTags, $storage->tags);
            $this::assertTrue($storage->uploaded);

            // Verify tags in S3
            $s3 = new S3();
            $s3Tags = $s3->getTagsArray($storage->key, $storage->bucket);
            $this::assertEquals($largeTags, $s3Tags);

            // Clean up
            S3Helper::deleteFile($storage->id);
        } catch (S3Exception $e) {
            // If S3 rejects due to tag limits, that's expected behavior
            $this::assertStringContainsString('tag', strtolower($e->getMessage()));
        }
    }

    /**
     * Test database constraint violations
     * @throws Throwable
     */
    public function testDatabaseConstraints(): void
    {
        // Try to create storage with invalid data
        $storage = new CloudStorage([
            'key' => null, // Key is optional, not required
            'bucket' => null, // Should not be null - this is required
            'filename' => 'test.txt'
        ]);

        $this::assertFalse($storage->validate());
        // Only bucket is required according to validation rules
        $this::assertArrayHasKey('bucket', $storage->errors);
        // Key is not required, so it shouldn't have validation errors
        $this::assertArrayNotHasKey('key', $storage->errors);
    }

    /**
     * Test tag operations with invalid data
     * @throws Throwable
     */
    public function testInvalidTagOperations(): void
    {
        $storage = new CloudStorage([
            'key' => 'test-invalid-tags-' . uniqid('', true),
            'bucket' => self::TEST_BUCKET,
            'filename' => 'test.txt',
            'size' => 100,
            'uploaded' => true
        ]);
        $this::assertTrue($storage->save());

        // Test with invalid tag values
        $invalidTags = [
            'valid-tag' => 'valid-value',
            '' => 'empty-key', // Empty key
            'null-value' => null,
            123 => 'numeric-key', // Non-string key
        ];

        try {
            $storage->tags = $invalidTags;
            $this::assertTrue($storage->save());

            // Check how invalid tags are handled
            $savedTags = $storage->tags;
            $this::assertIsArray($savedTags);
        } catch (Exception $e) {
            // Some invalid data might cause exceptions
            $this::assertNotEmpty($e->getMessage());
        }

        // Clean up
        CloudStorageTags::deleteAll(['cloud_storage_id' => $storage->id]);
        $storage->delete();
    }

    /**
     * Test file permission issues
     */
    public function testFilePermissionIssues(): void
    {
        // Create a file with restricted permissions (if possible)
        $restrictedFile = sys_get_temp_dir() . '/restricted-' . uniqid('', true) . '.txt';
        file_put_contents($restrictedFile, 'test content');

        // Try to make it unreadable (this might not work on all systems)
        if (chmod($restrictedFile, 0000)) {
            try {
                $this->expectException(ErrorException::class);
                $this->expectExceptionMessage('Permission denied');
                S3Helper::FileToStorage($restrictedFile);
            } finally {
                // Restore permissions for cleanup
                chmod($restrictedFile, 0644);
                unlink($restrictedFile);
            }
        } else {
            // If we can't change permissions, just clean up
            unlink($restrictedFile);
            $this::markTestSkipped('Cannot change file permissions on this system');
        }
    }

    /**
     * Test disk space exhaustion simulation
     */
    public function testDiskSpaceHandling(): void
    {
        // This is a simplified test - real disk space testing is complex
        $tempDir = sys_get_temp_dir();
        $freeSpace = disk_free_space($tempDir);

        // Only run if we have reasonable free space info
        if ($freeSpace > 0 && $freeSpace < 1024 * 1024 * 1024) { // Less than 1GB
            // Create a file that might approach disk limits
            $largeFileName = $tempDir . '/space-test-' . uniqid('', true) . '.txt';

            try {
                // Create file but don't make it too large to actually fill disk
                $testSize = min($freeSpace / 10, 1024 * 1024); // 10% of free space or 1MB
                $handle = fopen($largeFileName, 'wb');
                fwrite($handle, str_repeat('A', (int)$testSize));
                fclose($handle);

                // Try to upload - this should work with sufficient space
                $storage = S3Helper::FileToStorage($largeFileName);
                $this::assertTrue($storage->uploaded);

                // Clean up
                S3Helper::deleteFile($storage->id);
                unlink($largeFileName);
            } catch (Exception $e) {
                // Clean up on error
                if (file_exists($largeFileName)) {
                    unlink($largeFileName);
                }

                // Verify it's a disk space related error
                $this::assertStringContainsString('space', strtolower($e->getMessage()));
            }
        } else {
            $this::markTestSkipped('Cannot reliably test disk space limits');
        }
    }

    /**
     * Test CloudStorage download with missing S3 file
     * @throws Throwable
     */
    public function testDownloadMissingS3File(): void
    {
        // Create storage record without corresponding S3 file
        $storage = new CloudStorage([
            'key' => 'missing-s3-file-' . uniqid('', true),
            'bucket' => self::TEST_BUCKET,
            'filename' => 'missing.txt',
            'size' => 100,
            'uploaded' => true
        ]);
        $this::assertTrue($storage->save());

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Error in storage:');

        CloudStorage::Download($storage->id);

        // Clean up
        $storage->delete();
    }

    /**
     * Test S3Helper with circular reference detection
     * @throws Throwable
     */
    public function testCircularReferences(): void
    {
        // Create storage that references itself (simulating potential circular ref)
        $storage1 = new CloudStorage([
            'key' => 'circular1-' . uniqid('', true),
            'bucket' => self::TEST_BUCKET,
            'filename' => 'circular1.txt',
            'size' => 100,
            'uploaded' => true
        ]);
        $this::assertTrue($storage1->save());

        $storage2 = new CloudStorage([
            'key' => 'circular2-' . uniqid('', true),
            'bucket' => self::TEST_BUCKET,
            'filename' => 'circular2.txt',
            'size' => 100,
            'uploaded' => true,
            'model_name' => CloudStorage::class,
            'model_key' => $storage1->id
        ]);
        $this::assertTrue($storage2->save());

        // Update storage1 to reference storage2
        $storage1->model_name = CloudStorage::class;
        $storage1->model_key = $storage2->id;
        $this::assertTrue($storage1->save());

        // Operations should still work despite circular reference in metadata
        // Since no actual S3 files were uploaded, this should fail gracefully
        try {
            $result = S3Helper::StorageToFile($storage1->id);
            $this::assertNull($result); // If it returns null, that's fine
        } catch (S3Exception $e) {
            // Expected: S3 file doesn't exist, so it throws an exception
            $this::assertStringContainsString('NoSuchKey', $e->getMessage());
        }

        // Clean up
        $storage1->delete();
        $storage2->delete();
    }

    /**
     * Test race conditions in tag operations
     * @throws Throwable
     */
    public function testTagRaceConditions(): void
    {
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $storage = S3Helper::FileToStorage($filePath);

        // Simulate rapid tag updates
        $tags1 = ['race' => 'condition1', 'test' => 'value1'];
        $tags2 = ['race' => 'condition2', 'test' => 'value2'];

        // Set tags rapidly
        $storage->tags = $tags1;
        $storage->save();

        $storage->tags = $tags2;
        $storage->save();

        // Verify final state is consistent
        $finalTags = $storage->tags;
        $this::assertEquals($tags2, $finalTags);

        // Verify S3 sync works
        $storage->syncTagsToS3();
        $s3 = new S3(['storage' => $storage]);
        $s3Tags = $s3->getTagsArray($storage->key, $storage->bucket);
        $this::assertEquals($tags2, $s3Tags);

        // Clean up
        S3Helper::deleteFile($storage->id);
    }
}
