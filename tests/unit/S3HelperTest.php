<?php

declare(strict_types=1);

namespace unit;

use app\models\Users;
use Aws\S3\Exception\S3Exception;
use Codeception\Test\Unit;
use cusodede\s3\helpers\S3Helper;
use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\models\cloud_storage\CloudStorageTags;
use cusodede\s3\models\S3;
use Exception;
use Throwable;
use Yii;

/**
 * Comprehensive test suite for S3Helper class
 * Tests all helper methods including edge cases and error scenarios
 */
class S3HelperTest extends Unit
{
    private const SAMPLE_FILE_PATH = './tests/_data/sample.txt';
    private const TEST_BUCKET = 'testbucket';

    /**
     * Test FileToStorage method with valid file
     * @throws Throwable
     */
    public function testFileToStorageBasic(): void
    {
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        $storage = S3Helper::FileToStorage($filePath);

        $this::assertNotNull($storage->id);
        $this::assertGreaterThan(0, $storage->id);
        $this::assertNotEmpty($storage->key);
        $this::assertEquals(self::TEST_BUCKET, $storage->bucket);
        $this::assertEquals('sample.txt', $storage->filename);
        $this::assertEquals(filesize($filePath), $storage->size);
        $this::assertTrue($storage->uploaded);
        $this::assertEmpty($storage->tags);

        // Verify file was actually uploaded to S3
        $downloadPath = S3Helper::StorageToFile($storage->id);
        $this::assertNotNull($downloadPath);
        $this::assertFileEquals($filePath, $downloadPath);

        // Clean up
        S3Helper::deleteFile($storage->id);
        unlink($downloadPath);
    }

    /**
     * Test FileToStorage method with custom parameters
     * @throws Throwable
     */
    public function testFileToStorageWithCustomParameters(): void
    {
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $customFileName = 'custom-name-' . uniqid('', true) . '.txt';
        $customBucket = 'first-bucket';
        $customTags = [
            'environment' => 'test',
            'purpose' => 'unittest',
            'created_by' => 'S3HelperTest'
        ];

        $storage = S3Helper::FileToStorage($filePath, $customFileName, $customBucket, $customTags);

        $this::assertEquals($customFileName, $storage->filename);
        $this::assertEquals($customBucket, $storage->bucket);
        $this::assertEquals($customTags, $storage->tags);

        // Verify tags in S3
        $s3 = new S3();
        $s3Tags = $s3->getTagsArray($storage->key, $storage->bucket);
        $this::assertEquals($customTags, $s3Tags);

        // Clean up
        S3Helper::deleteFile($storage->id);
    }

    /**
     * Test FileToStorage with non-existent file
     * @throws Throwable
     */
    public function testFileToStorageNonExistentFile(): void
    {
        $nonExistentPath = '/path/to/non/existent/file.txt';

        $this->expectException(Exception::class);
        S3Helper::FileToStorage($nonExistentPath);
    }

    /**
     * Test StorageToFile method with valid storage ID
     * @throws Throwable
     */
    public function testStorageToFileBasic(): void
    {
        // First create a storage
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $storage = S3Helper::FileToStorage($filePath);

        // Download the file
        $downloadPath = S3Helper::StorageToFile($storage->id);

        $this::assertNotNull($downloadPath);
        $this::assertFileExists($downloadPath);
        $this::assertFileEquals($filePath, $downloadPath);

        // Clean up
        S3Helper::deleteFile($storage->id);
        unlink($downloadPath);
    }

    /**
     * Test StorageToFile method with custom file path
     * @throws Throwable
     */
    public function testStorageToFileWithCustomPath(): void
    {
        // First create a storage
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $storage = S3Helper::FileToStorage($filePath);

        // Download to custom path
        $customPath = sys_get_temp_dir() . '/custom-download-' . uniqid('', true) . '.txt';
        $downloadPath = S3Helper::StorageToFile($storage->id, $customPath);

        $this::assertEquals($customPath, $downloadPath);
        $this::assertFileExists($customPath);
        $this::assertFileEquals($filePath, $customPath);

        // Clean up
        S3Helper::deleteFile($storage->id);
        unlink($customPath);
    }

    /**
     * Test StorageToFile with non-existent storage ID
     * @throws Throwable
     */
    public function testStorageToFileNonExistentId(): void
    {
        $result = S3Helper::StorageToFile(99999);

        $this::assertNull($result);
    }

    /**
     * Test StorageToFile with deleted storage
     * @throws Throwable
     */
    public function testStorageToFileDeletedStorage(): void
    {
        // Create and immediately delete storage
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $storage = S3Helper::FileToStorage($filePath);
        $storage->deleted = true;
        $storage->save();

        $result = S3Helper::StorageToFile($storage->id);

        $this::assertNull($result);

        // Clean up
        $storage->delete();
    }

    /**
     * Test uploadFileFromModel method
     * @throws Throwable
     */
    public function testUploadFileFromModel(): void
    {
        // Create a test user model
        $username = 'testuser-' . uniqid('', true);
        $user = new Users([
            'username' => $username,
            'login' => $username,
            'password' => 'testpass'
        ]);
        $this::assertTrue($user->save());

        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $fileName = 'user-file-' . uniqid('', true) . '.txt';
        $tags = ['user_id' => (string)$user->id, 'type' => 'document'];

        S3Helper::uploadFileFromModel($user, $filePath, $fileName, self::TEST_BUCKET, $tags);

        // Find the created storage
        $storage = CloudStorage::find()
            ->where(['filename' => $fileName])
            ->andWhere(['model_name' => Users::class])
            ->andWhere(['model_key' => $user->id])
            ->one();

        $this::assertNotNull($storage);
        $this::assertEquals($fileName, $storage->filename);
        $this::assertEquals(self::TEST_BUCKET, $storage->bucket);
        $this::assertEquals(Users::class, $storage->model_name);
        $this::assertEquals($user->id, $storage->model_key);
        $this::assertEquals(filesize($filePath), $storage->size);
        $this::assertTrue($storage->uploaded);
        $this::assertEquals($tags, $storage->tags);

        // Clean up
        S3Helper::deleteFile($storage->id);
        CloudStorageTags::deleteAll(['cloud_storage_id' => $storage->id]);
        $storage->delete();
        $user->delete();
    }

    /**
     * Test uploadFileFromModel with non-existent file
     * @throws Throwable
     */
    public function testUploadFileFromModelNonExistentFile(): void
    {
        $username = 'testuser2-' . uniqid('', true);
        $user = new Users([
            'username' => $username,
            'login' => $username,
            'password' => 'testpass'
        ]);
        $this::assertTrue($user->save());

        $this->expectException(Exception::class);
        S3Helper::uploadFileFromModel($user, '/non/existent/file.txt', 'test.txt');

        // Clean up
        $user->delete();
    }

    /**
     * Test deleteFile method with storage ID
     * @throws Throwable
     */
    public function testDeleteFileWithId(): void
    {
        // Create a storage
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $storage = S3Helper::FileToStorage($filePath);
        $storageId = $storage->id;

        // Verify file exists in S3
        $downloadPath = S3Helper::StorageToFile($storageId);
        $this::assertNotNull($downloadPath);
        unlink($downloadPath);

        // Delete the file
        $result = S3Helper::deleteFile($storageId);

        $this::assertEquals($storageId, $result);

        // Verify storage is marked as deleted
        $storage->refresh();
        $this::assertTrue($storage->deleted);

        // Verify file no longer accessible
        $downloadPath2 = S3Helper::StorageToFile($storageId);
        $this::assertNull($downloadPath2);

        // Clean up
        $storage->delete();
    }

    /**
     * Test deleteFile method with CloudStorage object
     * @throws Throwable
     */
    public function testDeleteFileWithStorageObject(): void
    {
        // Create a storage
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $storage = S3Helper::FileToStorage($filePath);

        // Delete using storage object
        $result = S3Helper::deleteFile($storage);

        $this::assertEquals($storage->id, $result);
        $this::assertTrue($storage->deleted);

        // Clean up
        $storage->delete();
    }

    /**
     * Test deleteFile with non-existent storage ID
     * @throws Throwable
     */
    public function testDeleteFileNonExistentId(): void
    {
        $result = S3Helper::deleteFile(99999);

        $this::assertNull($result);
    }

    /**
     * Test deleteFile with already deleted storage
     * @throws Throwable
     */
    public function testDeleteFileAlreadyDeleted(): void
    {
        // Create and delete a storage
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $storage = S3Helper::FileToStorage($filePath);
        $storage->deleted = true;
        $storage->save();

        $result = S3Helper::deleteFile($storage->id);

        $this::assertNull($result);

        // Clean up
        $storage->delete();
    }

    /**
     * deleteFile() must throw when CloudStorage save() fails — and crucially,
     * must NOT proceed to delete the S3 object when DB persistence failed.
     * Otherwise the DB row stays "active" while S3 has lost the file, causing
     * the inverse of saveObject's orphan bug: dangling references.
     * @throws Throwable
     */
    public function testDeleteFileThrowsOnSaveFailure(): void
    {
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $storage = S3Helper::FileToStorage($filePath, 'delete-save-fail-' . uniqid() . '.txt');
        $originalBucket = $storage->bucket;
        $key = $storage->key;

        // Force CloudStorageAR's `bucket required` rule to fail at save time.
        $storage->bucket = null;

        try {
            S3Helper::deleteFile($storage);
            $this::fail('Expected exception when CloudStorage save fails');
        } catch (Exception $e) {
            $this::assertStringContainsString('CloudStorage', $e->getMessage());
        }

        // S3 object must still exist — a failed DB save must not delete from S3.
        $head = new S3()->client->headObject(['Bucket' => $originalBucket, 'Key' => $key]);
        $this::assertNotNull($head);

        // Clean up: restore bucket so we can hard-delete via the helper, plus
        // remove the surviving S3 object.
        $storage->bucket = $originalBucket;
        new S3()->deleteObject($key, $originalBucket);
        $storage->delete();
    }

    /**
     * uploadFileFromModel() throws when the CloudStorage row fails validation,
     * rather than silently returning false and leaving an orphaned S3 upload.
     * @throws Throwable
     */
    public function testUploadFileFromModelThrowsOnValidationFailure(): void
    {
        $username = 'testuser-throw-' . uniqid('', true);
        $user = new Users(['username' => $username, 'login' => $username, 'password' => 'pw']);
        $this::assertTrue($user->save());

        // CloudStorageAR enforces 255-char max on `filename`; this trips the
        // string validator at save() time. The S3 putObject itself succeeds
        // because the object key is an MD5 hash, independent of filename length.
        $longFilename = str_repeat('x', 300) . '.txt';

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Failed to persist CloudStorage row');
            S3Helper::uploadFileFromModel($user, Yii::getAlias(self::SAMPLE_FILE_PATH), $longFilename, self::TEST_BUCKET);
        } finally {
            $user->delete();
        }
    }

    /**
     * When CloudStorage validation fails, uploadFileFromModel() rolls back the
     * S3 upload before throwing — the bucket must not accumulate orphaned
     * objects that the database has no record of.
     * @throws Throwable
     */
    public function testUploadFileFromModelRollsBackUploadOnValidationFailure(): void
    {
        $username = 'testuser-rollback-' . uniqid('', true);
        $user = new Users(['username' => $username, 'login' => $username, 'password' => 'pw']);
        $this::assertTrue($user->save());

        $s3 = new S3();
        $countBefore = count($s3->client->listObjects(['Bucket' => self::TEST_BUCKET])->get('Contents') ?? []);

        $longFilename = str_repeat('x', 300) . '.txt';

        try {
            S3Helper::uploadFileFromModel($user, Yii::getAlias(self::SAMPLE_FILE_PATH), $longFilename, self::TEST_BUCKET);
        } catch (Exception) {
            // expected — see testUploadFileFromModelThrowsOnValidationFailure
        }

        $countAfter = count($s3->client->listObjects(['Bucket' => self::TEST_BUCKET])->get('Contents') ?? []);
        $this::assertEquals($countBefore, $countAfter);

        $user->delete();
    }

    /**
     * Test FileToStorage with empty filename
     * @throws Throwable
     */
    public function testFileToStorageEmptyFilename(): void
    {
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        // When empty string is passed, it should keep the empty string (not null)
        $storage = S3Helper::FileToStorage($filePath, '');

        // Empty string should remain empty string (the fallback only works for null)
        $this::assertEquals('', $storage->filename);

        // Clean up
        S3Helper::deleteFile($storage->id);
    }

    /**
     * Test FileToStorage with null tags
     * @throws Throwable
     */
    public function testFileToStorageNullTags(): void
    {
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        $storage = S3Helper::FileToStorage($filePath);

        $this::assertEmpty($storage->tags);

        // Clean up
        S3Helper::deleteFile($storage->id);
    }

    /**
     * Test concurrent access to same file
     * @throws Throwable
     */
    public function testConcurrentFileOperations(): void
    {
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        // Upload same file multiple times concurrently (simulated)
        $storages = [];
        for ($i = 0; $i < 3; $i++) {
            $storage = S3Helper::FileToStorage($filePath, 'concurrent-' . $i . '.txt');
            $storages[] = $storage;
        }

        // Verify all uploads succeeded
        foreach ($storages as $i => $storage) {
            $this::assertEquals('concurrent-' . $i . '.txt', $storage->filename);
            $this::assertTrue($storage->uploaded);

            // Verify each can be downloaded
            $downloadPath = S3Helper::StorageToFile($storage->id);
            $this::assertNotNull($downloadPath);
            $this::assertFileEquals($filePath, $downloadPath);
            unlink($downloadPath);
        }

        // Clean up all
        foreach ($storages as $storage) {
            S3Helper::deleteFile($storage->id);
        }
    }

    /**
     * Test file operations with very long filename
     * @throws Throwable
     */
    public function testLongFilename(): void
    {
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        // Create a very long filename (but within reasonable limits)
        $longFilename = str_repeat('very-long-filename-', 10) . uniqid('', true) . '.txt';

        $storage = S3Helper::FileToStorage($filePath, $longFilename);

        $this::assertEquals($longFilename, $storage->filename);
        $this::assertTrue($storage->uploaded);

        // Download may fail due to filesystem filename length limits
        // The temp filename combines key + random string + original filename
        try {
            $downloadPath = S3Helper::StorageToFile($storage->id);
            $this::assertNotNull($downloadPath);
            $this::assertFileEquals($filePath, $downloadPath);
            unlink($downloadPath);
        } catch (S3Exception $e) {
            // Expected: filesystem filename too long
            $this::assertStringContainsString('File name too long', $e->getMessage());
        }

        // Clean up
        S3Helper::deleteFile($storage->id);
    }

    /**
     * Test operations with different bucket configurations
     * @throws Throwable
     */
    public function testDifferentBuckets(): void
    {
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        // Test with different existing buckets
        $buckets = ['testbucket', 'first-bucket', 'second-bucket'];
        $storages = [];

        foreach ($buckets as $bucket) {
            $storage = S3Helper::FileToStorage($filePath, 'bucket-test.txt', $bucket);
            $storages[$bucket] = $storage;

            $this::assertEquals($bucket, $storage->bucket);
            $this::assertTrue($storage->uploaded);
        }

        // Verify all can be downloaded
        foreach ($storages as $storage) {
            $downloadPath = S3Helper::StorageToFile($storage->id);
            $this::assertNotNull($downloadPath);
            $this::assertFileEquals($filePath, $downloadPath);
            unlink($downloadPath);
        }

        // Clean up
        foreach ($storages as $storage) {
            S3Helper::deleteFile($storage->id);
        }
    }
}
