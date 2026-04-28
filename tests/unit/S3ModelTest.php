<?php

declare(strict_types=1);

namespace unit;

use Aws\S3\Exception\S3Exception;
use Codeception\Test\Unit;
use cusodede\s3\models\S3;
use cusodede\s3\S3Module;
use Exception;
use pozitronik\helpers\PathHelper;
use Throwable;
use Yii;
use yii\base\Exception as BaseException;
use yii\base\InvalidConfigException;

/**
 * Comprehensive test suite for S3 model
 * Tests all S3 operations including edge cases and error scenarios
 */
class S3ModelTest extends Unit
{
    private const string SAMPLE_FILE_PATH = './tests/_data/sample.txt';
    private const string TEST_BUCKET = 'testbucket';

    /**
     * Test S3 client initialization with valid configuration
     */
    public function testClientInitialization(): void
    {
        $s3 = new S3();
        $client = $s3->getClient();

        $this::assertNotNull($client->getApi());
    }

    /**
     * Test S3 initialization with invalid connection
     */
    public function testInvalidConnectionInitialization(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("Connection 'NonExistentConnection' is not configured.");

        new S3(['connection' => 'NonExistentConnection']);
    }

    /**
     * Test listing buckets
     */
    public function testListBuckets(): void
    {
        $s3 = new S3();
        $buckets = $s3->getListBucketMap();

        $this::assertIsArray($buckets);
        $this::assertNotEmpty($buckets);
        // Check that our test buckets exist
        $this::assertArrayHasKey('testbucket', $buckets);
        $this::assertArrayHasKey('first-bucket', $buckets);
        $this::assertArrayHasKey('second-bucket', $buckets);
    }

    /**
     * Test creating a bucket with invalid name
     * @throws Throwable
     */
    public function testCreateBucketWithInvalidName(): void
    {
        $s3 = new S3();

        // Test with uppercase letters (not allowed in S3)
        $this->expectException(S3Exception::class);
        $s3->createBucket('INVALID-BUCKET-NAME');
    }

    /**
     * Test getting default bucket
     * @throws Throwable
     */
    public function testGetDefaultBucket(): void
    {
        $s3 = new S3();
        $bucket = $s3->getBucket();

        $this::assertNotEmpty($bucket);
        $this::assertEquals(self::TEST_BUCKET, $bucket);
    }

    /**
     * Test uploading and retrieving an object
     * @throws Throwable
     */
    public function testPutAndGetObject(): void
    {
        $s3 = new S3();
        $testKey = 'test-file-' . uniqid('', true) . '.txt';
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        // Upload object
        $bucket = self::TEST_BUCKET;
        $putResult = $s3->putObject($filePath, $testKey, $bucket);
        $this::assertNotNull($putResult);
        $this::assertArrayHasKey('ObjectURL', $putResult->toArray());

        // Download object
        $tempFile = PathHelper::GetTempFileName();
        $s3->getObject($testKey, $bucket, $tempFile);

        // Verify content
        $this::assertFileExists($tempFile);
        $this::assertFileEquals($filePath, $tempFile);

        // Clean up
        $s3->deleteObject($testKey, $bucket);
        unlink($tempFile);
    }

    /**
     * Test uploading object with resource
     * @throws Throwable
     */
    public function testPutResourceObject(): void
    {
        $s3 = new S3();
        $testKey = 'test-resource-' . uniqid('', true) . '.txt';
        $content = 'Test content for resource upload';

        // Create resource
        $resource = fopen('php://temp', 'rb+');
        fwrite($resource, $content);
        rewind($resource);

        // Upload resource
        $bucket = self::TEST_BUCKET;
        $putResult = $s3->putResource($resource, 'test.txt', $testKey, $bucket);
        $this::assertNotNull($putResult);

        // Download and verify
        $tempFile = PathHelper::GetTempFileName();
        $s3->getObject($testKey, $bucket, $tempFile);

        $this::assertEquals($content, file_get_contents($tempFile));

        // Clean up
        $s3->deleteObject($testKey, $bucket);
        unlink($tempFile);
        fclose($resource);
    }

    /**
     * Test getting non-existent object
     * @throws Throwable
     */
    public function testGetNonExistentObject(): void
    {
        $s3 = new S3();
        $tempFile = PathHelper::GetTempFileName();

        $this->expectException(S3Exception::class);
        $s3->getObject('non-existent-key-' . uniqid('', true), self::TEST_BUCKET, $tempFile);
    }

    /**
     * Test deleting non-existent object (should not throw exception)
     * @throws Throwable
     */
    public function testDeleteNonExistentObject(): void
    {
        $s3 = new S3();

        // S3 doesn't throw error when deleting non-existent objects
        $result = $s3->deleteObject('non-existent-key-' . uniqid('', true), self::TEST_BUCKET);
        $this::assertNotNull($result);
    }

    /**
     * Test object tagging operations
     * @throws Throwable
     */
    public function testObjectTagging(): void
    {
        $s3 = new S3();
        $testKey = 'test-tags-' . uniqid('', true) . '.txt';
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        // Upload object with tags
        $tags = ['Environment' => 'Test', 'Purpose' => 'UnitTest', 'CreatedBy' => 'PHPUnit'];

        $bucket = self::TEST_BUCKET;
        $s3->putObject($filePath, $testKey, $bucket, $tags);

        // Get tags
        $retrievedTags = $s3->getTagsArray($testKey, $bucket);
        $this::assertEquals($tags, $retrievedTags);

        // Update tags
        $newTags = ['Environment' => 'Production', 'UpdatedBy' => 'TestSuite'];

        $s3->setObjectTagging($testKey, $bucket, $newTags);
        $updatedTags = $s3->getTagsArray($testKey, $bucket);
        $this::assertEquals($newTags, $updatedTags);

        // Delete tags
        $s3->setObjectTagging($testKey, $bucket, []);
        $emptyTags = $s3->getTagsArray($testKey, $bucket);
        $this::assertEmpty($emptyTags);

        // Clean up
        $s3->deleteObject($testKey, $bucket);
    }

    /**
     * Test uploading object with special characters in key
     * @throws Throwable
     */
    public function testSpecialCharactersInKey(): void
    {
        $s3 = new S3();
        // S3 supports special characters, but they need to be URL-encoded
        $testKey = 'test/folder/file with spaces & special!@#$%.txt';
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        // Upload object
        $bucket = self::TEST_BUCKET;
        $putResult = $s3->putObject($filePath, $testKey, $bucket);
        $this::assertNotNull($putResult);

        // Download object
        $tempFile = PathHelper::GetTempFileName();
        $s3->getObject($testKey, $bucket, $tempFile);

        // Verify content
        $this::assertFileEquals($filePath, $tempFile);

        // Clean up
        $s3->deleteObject($testKey, $bucket);
        unlink($tempFile);
    }

    /**
     * Test bucket operations with non-existent bucket
     * @throws Throwable
     */
    public function testOperationsWithNonExistentBucket(): void
    {
        $s3 = new S3();
        $nonExistentBucket = 'non-existent-bucket-' . uniqid('', true);
        $testKey = 'test-file.txt';
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);

        $this->expectException(S3Exception::class);
        $s3->putObject($filePath, $testKey, $nonExistentBucket);
    }

    /**
     * Test saveObject method with CloudStorage integration
     * @throws Throwable
     */
    public function testSaveObjectWithStorage(): void
    {
        $s3 = new S3();
        $filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
        $fileName = 'test-save-' . uniqid('', true) . '.txt';
        $tags = ['TestTag' => 'TestValue'];

        // Save object
        $s3->saveObject($filePath, self::TEST_BUCKET, $fileName, $tags);

        // Verify storage was created
        $this::assertNotNull($s3->storage);
        $this::assertEquals($fileName, $s3->storage->filename);
        $this::assertEquals(self::TEST_BUCKET, $s3->storage->bucket);
        $this::assertNotEmpty($s3->storage->key);
        $this::assertTrue($s3->storage->uploaded);
        $this::assertEquals($tags, $s3->storage->tags);

        // Verify file size
        $expectedSize = filesize($filePath);
        $this::assertEquals($expectedSize, $s3->storage->size);

        // Clean up
        $s3->deleteObject($s3->storage->key, self::TEST_BUCKET);
        $s3->storage->delete();
    }

    /**
     * saveObject() throws when the CloudStorage row fails validation, rather
     * than silently swallowing the save() failure. The exception message
     * carries the validation error so the caller can diagnose what failed.
     * @return void
     * @throws Throwable
     */
    public function testSaveObjectThrowsOnValidationFailure(): void
    {
        $s3 = new S3();
        // CloudStorageAR enforces 255-char max on `filename`; this trips the
        // string validator on save(). The S3 putObject itself would succeed
        // because the object key is an MD5 hash, independent of filename length.
        $longFilename = str_repeat('x', 300) . '.txt';

        $this->expectException(BaseException::class);
        $this->expectExceptionMessage('Failed to persist CloudStorage row');

        $s3->saveObject(Yii::getAlias(self::SAMPLE_FILE_PATH), self::TEST_BUCKET, $longFilename);
    }

    /**
     * When CloudStorage validation fails, saveObject() rolls back the S3
     * upload before throwing — the bucket must not accumulate orphaned
     * objects that the database has no record of.
     * @return void
     * @throws Throwable
     */
    public function testSaveObjectRollsBackUploadOnValidationFailure(): void
    {
        $s3 = new S3();
        $longFilename = str_repeat('x', 300) . '.txt';

        try {
            $s3->saveObject(Yii::getAlias(self::SAMPLE_FILE_PATH), self::TEST_BUCKET, $longFilename);
        } catch (BaseException) {
            // expected — see testSaveObjectThrowsOnValidationFailure
        }

        $this::assertNotNull($s3->storage);
        $this->expectException(S3Exception::class);
        $s3->getObject($s3->storage->key, self::TEST_BUCKET);
    }

    /**
     * Test GetFileNameKey method for unique key generation
     * @throws BaseException
     */
    public function testGetFileNameKey(): void
    {
        $fileName1 = 'test.txt';
        $fileName2 = 'test.txt';

        $key1 = S3::GetFileNameKey($fileName1);
        $key2 = S3::GetFileNameKey($fileName2);

        // Keys should be unique even for same filename
        $this::assertNotEquals($key1, $key2);

        // Keys should be MD5 hashes (32 character hex strings)
        $this::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key1);
        $this::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key2);

        // Keys should be non-empty
        $this::assertNotEmpty($key1);
        $this::assertNotEmpty($key2);
    }

    /**
     * getBucket() resolves a bucket through a four-step fallback:
     *   (1) the $bucket argument if non-null,
     *   (2) $this->storage->bucket if a CloudStorage is linked,
     *   (3) the configured defaultBucket (per-connection, then global),
     *   (4) the last bucket returned by listBuckets().
     * This test pins step 4 — the safety net used when nothing else is
     * available — so a refactor doesn't silently drop it.
     * @return void
     * @throws Throwable
     */
    public function testGetBucketFallsBackToLastBucketWhenNoneConfigured(): void
    {
        $module = Yii::$app->getModule('s3');
        $originalDefault = $module->params['defaultBucket'] ?? null;

        try {
            // Null out the global default. The connection block in the test
            // config has no per-connection defaultBucket either, so the
            // constructor's _defaultBucket will resolve to null and getBucket()
            // will fall through to the listBuckets() branch.
            $module->params['defaultBucket'] = null;

            $s3 = new S3();
            $bucket = $s3->getBucket();

            $this::assertIsString($bucket);
            $this::assertNotEmpty($bucket);
            $this::assertArrayHasKey($bucket, $s3->getListBucketMap());
        } finally {
            if ($originalDefault === null) {
                unset($module->params['defaultBucket']);
            } else {
                $module->params['defaultBucket'] = $originalDefault;
            }
        }
    }

    /**
     * Test connection timeout handling
     * Note: This test requires a way to simulate timeout, which is complex in unit tests
     * In real scenarios, this would be tested with integration tests
     */
    public function testConnectionTimeout(): void
    {
        $originalModule = Yii::$app->getModule('s3');

        try {
            Yii::$app->setModule('s3', [
                'class' => S3Module::class,
                'params' => [
                    'connection' => [
                        'host' => $_ENV['MINIO_HOST'],
                        'login' => $_ENV['MINIO_ROOT_USER'],
                        'password' => $_ENV['MINIO_ROOT_PASSWORD'],
                        'connect_timeout' => 0.001,
                        'timeout' => 0.001,
                    ],
                    'defaultBucket' => 'testbucket',
                ]
            ]);

            $s3 = new S3();

            try {
                $s3->getListBucketMap();
                $this::assertTrue(true);
            } catch (Exception $e) {
                $this::assertStringContainsString('timeout', strtolower($e->getMessage()));
            }
        } finally {
            Yii::$app->setModule('s3', $originalModule);
        }
    }
}
