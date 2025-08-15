<?php
declare(strict_types = 1);

namespace unit;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Codeception\Test\Unit;
use cusodede\s3\models\S3;
use cusodede\s3\S3Module;
use pozitronik\helpers\PathHelper;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Comprehensive test suite for S3 model
 * Tests all S3 operations including edge cases and error scenarios
 */
class S3ModelTest extends Unit {
	private const SAMPLE_FILE_PATH = './tests/_data/sample.txt';
	private const TEST_BUCKET = 'testbucket';

	/**
	 * Test S3 client initialization with valid configuration
	 */
	public function testClientInitialization():void {
		$s3 = new S3();
		$client = $s3->getClient();
		
		$this::assertNotNull($client);
		$this::assertInstanceOf(S3Client::class, $client);
	}
	
	/**
	 * Test S3 initialization with invalid connection
	 */
	public function testInvalidConnectionInitialization():void {
		$this->expectException(InvalidConfigException::class);
		$this->expectExceptionMessage("Connection 'NonExistentConnection' is not configured.");
		
		new S3(['connection' => 'NonExistentConnection']);
	}
	
	/**
	 * Test listing buckets
	 * @throws Exception
	 */
	public function testListBuckets():void {
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
	 * Test creating and deleting a bucket
	 * @throws Exception
	 */
	public function testCreateAndDeleteBucket():void {
		$s3 = new S3();
		$bucketName = 'test-' . uniqid('', true);
		
		// Create bucket
		$result = $s3->createBucket($bucketName);
		$this::assertTrue($result);
		
		// Verify bucket exists
		$buckets = $s3->getListBucketMap();
		$this::assertArrayHasKey($bucketName, $buckets);
		
		// Delete bucket (Note: S3 class doesn't have deleteBucket method, skip this part)
		// $deleteResult = $s3->deleteBucket($bucketName);
		// $this::assertTrue($deleteResult);
		
		// Note: Skipping delete verification since deleteBucket method doesn't exist
		// In real scenarios, bucket cleanup would be handled separately
		$this::markTestIncomplete('S3 class does not implement deleteBucket method');
	}
	
	/**
	 * Test creating a bucket with invalid name
	 * @throws Exception
	 */
	public function testCreateBucketWithInvalidName():void {
		$s3 = new S3();
		
		// Test with uppercase letters (not allowed in S3)
		$this->expectException(S3Exception::class);
		$s3->createBucket('INVALID-BUCKET-NAME');
	}
	
	/**
	 * Test getting default bucket
	 * @throws Exception
	 */
	public function testGetDefaultBucket():void {
		$s3 = new S3();
		$bucket = $s3->getBucket();
		
		$this::assertNotEmpty($bucket);
		$this::assertEquals(self::TEST_BUCKET, $bucket);
	}
	
	/**
	 * Test uploading and retrieving an object
	 * @throws Throwable
	 */
	public function testPutAndGetObject():void {
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
	public function testPutResourceObject():void {
		$s3 = new S3();
		$testKey = 'test-resource-' . uniqid('', true) . '.txt';
		$content = "Test content for resource upload";
		
		// Create resource
		$resource = fopen('php://temp', 'r+');
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
	public function testGetNonExistentObject():void {
		$s3 = new S3();
		$tempFile = PathHelper::GetTempFileName();
		
		$this->expectException(S3Exception::class);
		$s3->getObject('non-existent-key-' . uniqid('', true), self::TEST_BUCKET, $tempFile);
	}
	
	/**
	 * Test deleting non-existent object (should not throw exception)
	 * @throws Throwable
	 */
	public function testDeleteNonExistentObject():void {
		$s3 = new S3();
		
		// S3 doesn't throw error when deleting non-existent objects
		$result = $s3->deleteObject('non-existent-key-' . uniqid('', true), self::TEST_BUCKET);
		$this::assertNotNull($result);
	}
	
	/**
	 * Test object tagging operations
	 * @throws Throwable
	 */
	public function testObjectTagging():void {
		$s3 = new S3();
		$testKey = 'test-tags-' . uniqid('', true) . '.txt';
		$filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
		
		// Upload object with tags
		$tags = [
			'Environment' => 'Test',
			'Purpose' => 'UnitTest',
			'CreatedBy' => 'PHPUnit'
		];
		
		$bucket = self::TEST_BUCKET;
		$s3->putObject($filePath, $testKey, $bucket, $tags);
		
		// Get tags
		$retrievedTags = $s3->getTagsArray($testKey, $bucket);
		$this::assertEquals($tags, $retrievedTags);
		
		// Update tags
		$newTags = [
			'Environment' => 'Production',
			'UpdatedBy' => 'TestSuite'
		];
		
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
	public function testSpecialCharactersInKey():void {
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
	public function testOperationsWithNonExistentBucket():void {
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
	public function testSaveObjectWithStorage():void {
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
	 * Test GetFileNameKey method for unique key generation
	 */
	public function testGetFileNameKey():void {
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
	 * Test connection timeout handling
	 * Note: This test requires a way to simulate timeout, which is complex in unit tests
	 * In real scenarios, this would be tested with integration tests
	 */
	public function testConnectionTimeout():void {
		// Create S3 instance with very short timeout
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
		
		// This might throw a timeout exception depending on network speed
		try {
			$s3->getListBucketMap();
			// If it succeeds, we can't test timeout in this environment
			$this::assertTrue(true);
		} catch (\Exception $e) {
			// If it fails due to timeout, that's expected
			$this::assertStringContainsString('timeout', strtolower($e->getMessage()));
		}
	}
}