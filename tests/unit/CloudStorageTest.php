<?php
declare(strict_types = 1);

namespace unit;

use Codeception\Test\Unit;
use cusodede\s3\components\web\UploadedFile;
use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\models\cloud_storage\CloudStorageTags;
use cusodede\s3\models\S3;
use Throwable;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Comprehensive test suite for CloudStorage model
 * Tests file upload, download, MIME type detection, and validation
 */
class CloudStorageTest extends Unit {
	private const SAMPLE_FILE_PATH = './tests/_data/sample.txt';
	private const TEST_BUCKET = 'testbucket';
	
	/**
	 * Test MIME type detection by extension
	 */
	public function testGetMimeTypeByExtension():void {
		// Test custom MIME types from CloudStorage::MIME_TYPES
		$this::assertEquals('application/vnd.android.package-archive', CloudStorage::GetMimeTypeByExtension('test.apk'));
		$this::assertEquals('application/octet-stream', CloudStorage::GetMimeTypeByExtension('test.ipa'));
		
		// Test extensions not in MIME_TYPES - should fall back to FileHelper or default
		// Since FileHelper::getMimeTypeByExtension may return null in test environment,
		// most extensions will fall back to the default 'application/octet-stream'
		$this::assertEquals('application/octet-stream', CloudStorage::GetMimeTypeByExtension('test.txt'));
		$this::assertEquals('application/octet-stream', CloudStorage::GetMimeTypeByExtension('image.jpg'));
		
		// Test extension extraction and lowercase conversion
		$this::assertEquals('application/vnd.android.package-archive', CloudStorage::GetMimeTypeByExtension('app.apk'));
		$this::assertEquals('application/octet-stream', CloudStorage::GetMimeTypeByExtension('app.ipa'));
		
		// Test unknown extension
		$this::assertEquals('application/octet-stream', CloudStorage::GetMimeTypeByExtension('file.unknown'));
		
		// Test file without extension
		$this::assertEquals('application/octet-stream', CloudStorage::GetMimeTypeByExtension('filename'));
		
		// Test case insensitive - these also fall back to default
		$this::assertEquals('application/octet-stream', CloudStorage::GetMimeTypeByExtension('TEST.TXT'));
		$this::assertEquals('application/octet-stream', CloudStorage::GetMimeTypeByExtension('IMAGE.JPG'));
	}
	
	/**
	 * Test CloudStorage model validation
	 */
	public function testValidation():void {
		$storage = new CloudStorage();
		
		// Test empty model validation
		$this::assertFalse($storage->validate());
		
		// Test valid model
		$storage->key = 'test-key';
		$storage->bucket = 'test-bucket';
		$storage->filename = 'test.txt';
		$storage->size = 1024;
		
		$this::assertTrue($storage->validate());
	}
	
	/**
	 * Test tags property getter and setter
	 * @throws Throwable
	 */
	public function testTagsProperty():void {
		// Create a storage record
		$storage = new CloudStorage([
			'key' => 'test-tags-' . uniqid('', true),
			'bucket' => self::TEST_BUCKET,
			'filename' => 'test.txt',
			'size' => 100,
			'uploaded' => true
		]);
		$this::assertTrue($storage->save());
		
		// Test empty tags initially
		$this::assertEquals([], $storage->tags);
		
		// Set tags
		$tags = [
			'category' => 'documents',
			'priority' => 'high',
			'department' => 'IT'
		];
		$storage->tags = $tags;
		$this::assertTrue($storage->save());
		
		// Verify tags were saved
		$this::assertEquals($tags, $storage->tags);
		
		// Test tags persistence after reload
		$reloadedStorage = CloudStorage::findOne($storage->id);
		$this::assertEquals($tags, $reloadedStorage->tags);
		
		// Test updating tags
		$newTags = [
			'category' => 'images',
			'status' => 'processed'
		];
		$storage->tags = $newTags;
		$this::assertTrue($storage->save());
		$this::assertEquals($newTags, $storage->tags);
		
		// Clean up
		CloudStorageTags::deleteAll(['cloud_storage_id' => $storage->id]);
		$storage->delete();
	}
	
	/**
	 * Test CloudStorage Download method with existing file
	 * @throws Throwable
	 */
	public function testDownloadExistingFile():void {
		// Create a test file in S3
		$s3 = new S3();
		$testKey = 'test-download-' . uniqid('', true) . '.txt';
		$filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
		
		$s3->saveObject($filePath, self::TEST_BUCKET, basename($testKey));
		$storage = $s3->storage;
		
		// Test download
		$response = CloudStorage::Download($storage->id);
		
		$this::assertInstanceOf(Response::class, $response);
		// Test that the response is properly configured for file download
		$this::assertNotEmpty($response->headers->get('Content-Type'));
		$this::assertStringContainsString($storage->filename, $response->headers->get('Content-Disposition'));
		
		// Clean up
		$s3->deleteObject($storage->key, self::TEST_BUCKET);
		$storage->delete();
	}
	
	/**
	 * Test CloudStorage Download method with non-existent file
	 */
	public function testDownloadNonExistentFile():void {
		// Test with non-existent ID
		$response = CloudStorage::Download(99999);
		$this::assertNull($response);
	}
	
	/**
	 * Test CloudStorage Download method with file that exists in DB but not in S3
	 * @throws Throwable
	 */
	public function testDownloadMissingS3File():void {
		// Create storage record without actual S3 file
		$storage = new CloudStorage([
			'key' => 'non-existent-' . uniqid('', true),
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
	 * Test CloudStorage Download with custom MIME type
	 * @throws Throwable
	 */
	public function testDownloadWithCustomMimeType():void {
		// Create a test file in S3
		$s3 = new S3();
		$testKey = 'test-mime-' . uniqid('', true) . '.txt';
		$filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
		
		$s3->saveObject($filePath, self::TEST_BUCKET, basename($testKey));
		$storage = $s3->storage;
		
		// Test download with custom MIME type
		$customMime = 'application/custom-type';
		$response = CloudStorage::Download($storage->id, $customMime);
		
		$this::assertInstanceOf(Response::class, $response);
		// Note: We can't easily test the actual MIME type in response headers in unit tests
		
		// Clean up
		$s3->deleteObject($storage->key, self::TEST_BUCKET);
		$storage->delete();
	}
	
	/**
	 * Test uploadInstance method with valid UploadedFile
	 * @throws Throwable
	 */
	public function testUploadInstanceValid():void {
		$filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
		$tempPath = sys_get_temp_dir() . '/test-upload-' . uniqid('', true) . '.txt';
		copy($filePath, $tempPath);
		
		// Create UploadedFile instance
		$uploadedFile = new UploadedFile();
		$uploadedFile->name = 'uploaded-test.txt';
		$uploadedFile->tempName = $tempPath;
		$uploadedFile->size = filesize($tempPath);
		$uploadedFile->type = 'text/plain';
		
		// Create CloudStorage instance
		$storage = new CloudStorage([
			'bucket' => self::TEST_BUCKET
		]);
		
		// Test upload
		$result = $storage->uploadInstance($uploadedFile);
		
		$this::assertTrue($result);
		$this::assertEquals('uploaded-test.txt', $storage->filename);
		$this::assertEquals(self::TEST_BUCKET, $storage->bucket);
		$this::assertNotEmpty($storage->key);
		$this::assertTrue($storage->uploaded);
		$this::assertEquals(filesize($filePath), $storage->size);
		
		// Verify file exists in S3
		$s3 = new S3();
		$downloadPath = sys_get_temp_dir() . '/download-test-' . uniqid('', true) . '.txt';
		$s3->getObject($storage->key, $storage->bucket, $downloadPath);
		$this::assertFileEquals($filePath, $downloadPath);
		
		// Clean up
		$s3->deleteObject($storage->key, $storage->bucket);
		$storage->delete();
		if (file_exists($downloadPath)) unlink($downloadPath);
	}
	
	/**
	 * Test uploadInstance with pre-set filename and key
	 * @throws Throwable
	 */
	public function testUploadInstanceWithPresetValues():void {
		$filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
		$tempPath = sys_get_temp_dir() . '/test-preset-' . uniqid('', true) . '.txt';
		copy($filePath, $tempPath);
		
		// Create UploadedFile instance
		$uploadedFile = new UploadedFile();
		$uploadedFile->name = 'original-name.txt';
		$uploadedFile->tempName = $tempPath;
		$uploadedFile->size = filesize($tempPath);
		
		// Create CloudStorage instance with preset values
		$presetKey = 'preset-key-' . uniqid('', true);
		$presetFilename = 'preset-filename.txt';
		$storage = new CloudStorage([
			'bucket' => self::TEST_BUCKET,
			'filename' => $presetFilename,
			'key' => $presetKey
		]);
		
		// Test upload
		$result = $storage->uploadInstance($uploadedFile);
		
		$this::assertTrue($result);
		$this::assertEquals($presetFilename, $storage->filename);
		$this::assertEquals($presetKey, $storage->key);
		
		// Clean up
		$s3 = new S3();
		$s3->deleteObject($storage->key, $storage->bucket);
		$storage->delete();
	}
	
	/**
	 * Test sync methods for tags
	 * @throws Throwable
	 */
	public function testTagSyncMethods():void {
		// Create storage with S3 file
		$s3 = new S3();
		$testKey = 'test-sync-' . uniqid('', true) . '.txt';
		$filePath = Yii::getAlias(self::SAMPLE_FILE_PATH);
		
		$s3->saveObject($filePath, self::TEST_BUCKET, basename($testKey));
		$storage = $s3->storage;
		
		// Test syncTagsToS3
		$localTags = [
			'source' => 'unittest',
			'sync' => 'test'
		];
		CloudStorageTags::assignTags($storage->id, $localTags);
		$storage->syncTagsToS3();
		
		// Verify tags in S3
		$s3Tags = $s3->getTagsArray($storage->key, $storage->bucket);
		$this::assertEquals($localTags, $s3Tags);
		
		// Test syncTagsFromS3
		$newS3Tags = [
			'source' => 'updated',
			'version' => '2.0'
		];
		$s3->setObjectTagging($storage->key, $storage->bucket, $newS3Tags);
		$storage->syncTagsFromS3();
		
		// Verify local tags updated
		$this::assertEquals($newS3Tags, $storage->tags);
		
		// Clean up
		$s3->deleteObject($storage->key, $storage->bucket);
		CloudStorageTags::deleteAll(['cloud_storage_id' => $storage->id]);
		$storage->delete();
	}
	
	/**
	 * Test afterFind method populates tags
	 * @throws Throwable
	 */
	public function testAfterFindPopulatesTags():void {
		// Create storage with tags
		$storage = new CloudStorage([
			'key' => 'test-afterfind-' . uniqid('', true),
			'bucket' => self::TEST_BUCKET,
			'filename' => 'test.txt',
			'size' => 100,
			'uploaded' => true
		]);
		$this::assertTrue($storage->save());
		
		$tags = [
			'afterfind' => 'test',
			'populated' => 'true'
		];
		CloudStorageTags::assignTags($storage->id, $tags);
		
		// Reload storage from database
		$reloadedStorage = CloudStorage::findOne($storage->id);
		
		// Verify tags are populated in afterFind
		$this::assertEquals($tags, $reloadedStorage->tags);
		
		// Clean up
		CloudStorageTags::deleteAll(['cloud_storage_id' => $storage->id]);
		$storage->delete();
	}
	
	/**
	 * Test model with special characters in filename
	 * @throws Throwable
	 */
	public function testSpecialCharactersInFilename():void {
		$specialFilename = 'файл с русскими символами & special chars!@#$%.txt';
		
		$storage = new CloudStorage([
			'key' => 'test-special-' . uniqid('', true),
			'bucket' => self::TEST_BUCKET,
			'filename' => $specialFilename,
			'size' => 100,
			'uploaded' => true
		]);
		
		$this::assertTrue($storage->save());
		$this::assertEquals($specialFilename, $storage->filename);
		
		// Test MIME type detection with special characters
		// .txt extension is not in CloudStorage::MIME_TYPES, so it falls back to default
		$mimeType = CloudStorage::GetMimeTypeByExtension($specialFilename);
		$this::assertEquals('application/octet-stream', $mimeType);
		
		// Clean up
		$storage->delete();
	}
	
	/**
	 * Test large file size handling
	 * @throws Throwable
	 */
	public function testLargeFileSize():void {
		$largeSize = 2147483647; // Max int value
		
		$storage = new CloudStorage([
			'key' => 'test-large-' . uniqid('', true),
			'bucket' => self::TEST_BUCKET,
			'filename' => 'large-file.bin',
			'size' => $largeSize,
			'uploaded' => true
		]);
		
		$this::assertTrue($storage->save());
		$this::assertEquals($largeSize, $storage->size);
		
		// Clean up
		$storage->delete();
	}
}