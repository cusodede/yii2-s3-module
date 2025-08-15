<?php
declare(strict_types = 1);

namespace unit;

use Codeception\Test\Unit;
use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\models\cloud_storage\CloudStorageTags;
use Exception;
use Throwable;

/**
 * Comprehensive test suite for CloudStorageTags model
 * Tests tag assignment, retrieval, and management operations
 */
class CloudStorageTagsTest extends Unit {
	private const TEST_BUCKET = 'testbucket';
	
	/**
	 * Test basic tag assignment and retrieval
	 * @throws Throwable
	 */
	public function testBasicTagOperations():void {
		// Create test storage
		$storage = $this->createTestStorage();
		
		$tags = [
			'category' => 'documents',
			'priority' => 'high',
			'department' => 'IT'
		];
		
		// Assign tags
		CloudStorageTags::assignTags($storage->id, $tags);
		
		// Retrieve tags
		$retrievedTags = CloudStorageTags::retrieveTags($storage->id);
		
		$this::assertEquals($tags, $retrievedTags);
		
		// Clean up
		$this->cleanupStorage($storage);
	}
	
	/**
	 * Test tag assignment with empty array
	 * @throws Throwable
	 */
	public function testEmptyTagAssignment():void {
		$storage = $this->createTestStorage();
		
		// Assign empty tags
		CloudStorageTags::assignTags($storage->id, []);
		
		// Should return empty array
		$retrievedTags = CloudStorageTags::retrieveTags($storage->id);
		$this::assertEquals([], $retrievedTags);
		
		$this->cleanupStorage($storage);
	}
	
	/**
	 * Test tag assignment with null values
	 * @throws Throwable
	 */
	public function testTagAssignmentWithNulls():void {
		$storage = $this->createTestStorage();
		
		$tags = [
			'tag1' => 'value1',
			'tag2' => null,
			'tag3' => '',
			'tag4' => 'value4'
		];
		
		CloudStorageTags::assignTags($storage->id, $tags);
		
		$retrievedTags = CloudStorageTags::retrieveTags($storage->id);
		
		// Check how null and empty values are handled
		$this::assertArrayHasKey('tag1', $retrievedTags);
		$this::assertEquals('value1', $retrievedTags['tag1']);
		$this::assertArrayHasKey('tag4', $retrievedTags);
		$this::assertEquals('value4', $retrievedTags['tag4']);
		
		// tag2 and tag3 might be handled differently based on implementation
		if (array_key_exists('tag2', $retrievedTags)) {
			$this::assertEquals('tag2', $retrievedTags['tag2']); // null becomes key name
		}
		if (array_key_exists('tag3', $retrievedTags)) {
			$this::assertEquals('tag3', $retrievedTags['tag3']); // empty becomes key name
		}
		
		$this->cleanupStorage($storage);
	}
	
	/**
	 * Test tag overwriting
	 * @throws Throwable
	 */
	public function testTagOverwriting():void {
		$storage = $this->createTestStorage();
		
		// First set of tags
		$initialTags = [
			'environment' => 'development',
			'project' => 'test-project'
		];
		CloudStorageTags::assignTags($storage->id, $initialTags);
		
		// Verify initial tags
		$retrievedTags = CloudStorageTags::retrieveTags($storage->id);
		$this::assertEquals($initialTags, $retrievedTags);
		
		// Overwrite with new tags
		$newTags = [
			'environment' => 'production',
			'version' => '2.0'
		];
		CloudStorageTags::assignTags($storage->id, $newTags);
		
		// Verify tags were overwritten
		$finalTags = CloudStorageTags::retrieveTags($storage->id);
		$this::assertEquals($newTags, $finalTags);
		$this::assertArrayNotHasKey('project', $finalTags);
		
		$this->cleanupStorage($storage);
	}
	
	/**
	 * Test tag retrieval for non-existent storage
	 */
	public function testRetrieveTagsNonExistentStorage():void {
		$tags = CloudStorageTags::retrieveTags(99999);
		$this::assertEquals([], $tags);
	}
	
	/**
	 * Test tag assignment for non-existent storage
	 * This should either create tags anyway or handle gracefully
	 */
	public function testAssignTagsNonExistentStorage():void {
		$tags = ['test' => 'value'];
		
		try {
			CloudStorageTags::assignTags(99999, $tags);
			
			// If no exception, verify tags were not created or handled gracefully
			$retrievedTags = CloudStorageTags::retrieveTags(99999);
			$this::assertEquals([], $retrievedTags);
		} catch (Exception $e) {
			// It's acceptable to throw an exception for non-existent storage
			$this::assertNotEmpty($e->getMessage());
		}
	}
	
	/**
	 * Test special characters in tag keys and values
	 * @throws Throwable
	 */
	public function testSpecialCharactersInTags():void {
		$storage = $this->createTestStorage();
		
		$specialTags = [
			'unicode-тест' => 'значение',
			'emoji-🚀' => 'rocket',
			'spaces in key' => 'spaces in value',
			'symbols!@#$%' => 'symbols!@#$%',
			'quotes"test' => "quotes'test",
			'html<tag>' => '<script>alert("test")</script>'
		];
		
		CloudStorageTags::assignTags($storage->id, $specialTags);
		
		$retrievedTags = CloudStorageTags::retrieveTags($storage->id);
		
		// Verify all special characters are preserved
		foreach ($specialTags as $key => $value) {
			$this::assertArrayHasKey($key, $retrievedTags);
			$this::assertEquals($value, $retrievedTags[$key]);
		}
		
		$this->cleanupStorage($storage);
	}
	
	/**
	 * Test very long tag keys and values
	 * @throws Throwable
	 */
	public function testLongTagKeysAndValues():void {
		$storage = $this->createTestStorage();
		
		$longKey = str_repeat('very-long-key-', 50); // 700 characters
		$longValue = str_repeat('very-long-value-', 50); // 800 characters
		
		$tags = [
			$longKey => $longValue,
			'normal-key' => 'normal-value'
		];
		
		// The database has a 255 character limit for tag_label and tag_key
		// The assignTags method doesn't throw exceptions on validation failure
		// So tags that exceed the limit will silently fail to save
		CloudStorageTags::assignTags($storage->id, $tags);
		
		$retrievedTags = CloudStorageTags::retrieveTags($storage->id);
		
		// Only the normal-key should be saved (the long ones exceed 255 char limit)
		$expectedTags = ['normal-key' => 'normal-value'];
		$this::assertEquals($expectedTags, $retrievedTags);
		
		$this->cleanupStorage($storage);
	}
	
	/**
	 * Test numeric keys and values
	 * @throws Throwable
	 */
	public function testNumericKeysAndValues():void {
		$storage = $this->createTestStorage();
		
		$numericTags = [
			123 => '456',        // Numeric key will use value as both name and value
			'float' => '3.14159', // String key works normally
			'zero' => '0',
			'negative' => '-42'
		];
		
		CloudStorageTags::assignTags($storage->id, $numericTags);
		
		$retrievedTags = CloudStorageTags::retrieveTags($storage->id);
		
		// ArrayTagAdapter treats numeric keys specially:
		// For numeric key 123 => '456', it stores '456' => '456'
		// For string keys, it stores normally
		$expectedTags = [
			'456' => '456',      // Numeric key 123 became value-based
			'float' => '3.14159',
			'zero' => '0',
			'negative' => '-42'
		];
		
		$this::assertEquals($expectedTags, $retrievedTags);
		
		$this->cleanupStorage($storage);
	}
	
	/**
	 * Test concurrent tag operations
	 * @throws Throwable
	 */
	public function testConcurrentTagOperations():void {
		$storage = $this->createTestStorage();
		
		// Simulate concurrent tag assignments
		$tags1 = ['concurrent' => 'operation1', 'test' => 'value1'];
		$tags2 = ['concurrent' => 'operation2', 'test' => 'value2'];
		$tags3 = ['concurrent' => 'operation3', 'test' => 'value3'];
		
		// Rapid successive assignments
		CloudStorageTags::assignTags($storage->id, $tags1);
		CloudStorageTags::assignTags($storage->id, $tags2);
		CloudStorageTags::assignTags($storage->id, $tags3);
		
		// Final state should be consistent
		$finalTags = CloudStorageTags::retrieveTags($storage->id);
		$this::assertEquals($tags3, $finalTags);
		
		$this->cleanupStorage($storage);
	}
	
	/**
	 * Test tag deletion via empty assignment
	 * @throws Throwable
	 */
	public function testTagDeletion():void {
		$storage = $this->createTestStorage();
		
		// Assign initial tags
		$initialTags = [
			'to-delete' => 'will-be-deleted',
			'to-keep' => 'will-be-kept'
		];
		CloudStorageTags::assignTags($storage->id, $initialTags);
		
		// Verify initial assignment
		$retrievedTags = CloudStorageTags::retrieveTags($storage->id);
		$this::assertEquals($initialTags, $retrievedTags);
		
		// Clear all tags
		CloudStorageTags::assignTags($storage->id, []);
		
		// Verify tags are cleared
		$clearedTags = CloudStorageTags::retrieveTags($storage->id);
		$this::assertEquals([], $clearedTags);
		
		$this->cleanupStorage($storage);
	}
	
	/**
	 * Test tag operations with multiple storages
	 * @throws Throwable
	 */
	public function testMultipleStorageTags():void {
		$storage1 = $this->createTestStorage('test1.txt');
		$storage2 = $this->createTestStorage('test2.txt');
		$storage3 = $this->createTestStorage('test3.txt');
		
		// Assign different tags to each storage
		$tags1 = ['storage' => 'first', 'type' => 'document'];
		$tags2 = ['storage' => 'second', 'type' => 'image'];
		$tags3 = ['storage' => 'third', 'type' => 'video'];
		
		CloudStorageTags::assignTags($storage1->id, $tags1);
		CloudStorageTags::assignTags($storage2->id, $tags2);
		CloudStorageTags::assignTags($storage3->id, $tags3);
		
		// Verify each storage has correct tags
		$this::assertEquals($tags1, CloudStorageTags::retrieveTags($storage1->id));
		$this::assertEquals($tags2, CloudStorageTags::retrieveTags($storage2->id));
		$this::assertEquals($tags3, CloudStorageTags::retrieveTags($storage3->id));
		
		// Clean up
		$this->cleanupStorage($storage1);
		$this->cleanupStorage($storage2);
		$this->cleanupStorage($storage3);
	}
	
	/**
	 * Test case sensitivity in tag keys and values
	 * @throws Throwable
	 */
	public function testCaseSensitivity():void {
		$storage = $this->createTestStorage();
		
		$caseSensitiveTags = [
			'LowerCase' => 'value',
			'lowercase' => 'VALUE',
			'UPPERCASE' => 'Value',
			'MixedCase' => 'MiXeD'
		];
		
		CloudStorageTags::assignTags($storage->id, $caseSensitiveTags);
		
		$retrievedTags = CloudStorageTags::retrieveTags($storage->id);
		
		// All keys should be preserved exactly as entered
		foreach ($caseSensitiveTags as $key => $value) {
			$this::assertArrayHasKey($key, $retrievedTags);
			$this::assertEquals($value, $retrievedTags[$key]);
		}
		
		// Verify exact count (no merging of case variants)
		$this::assertCount(4, $retrievedTags);
		
		$this->cleanupStorage($storage);
	}
	
	/**
	 * Test whitespace handling in tags
	 * @throws Throwable
	 */
	public function testWhitespaceHandling():void {
		$storage = $this->createTestStorage();
		
		$whitespaceTags = [
			' leading-space' => 'value',
			'trailing-space ' => 'value',
			' both-spaces ' => 'value',
			'tab\ttag' => 'tab\tvalue',
			'newline\ntag' => 'newline\nvalue',
			'multiple   spaces' => 'multiple   spaces'
		];
		
		CloudStorageTags::assignTags($storage->id, $whitespaceTags);
		
		$retrievedTags = CloudStorageTags::retrieveTags($storage->id);
		
		// Verify whitespace is preserved (or handled consistently)
		foreach ($whitespaceTags as $key => $value) {
			// The exact behavior depends on implementation
			// Either whitespace is preserved or consistently trimmed
			$found = false;
			foreach ($retrievedTags as $retrievedKey => $retrievedValue) {
				if ($retrievedKey === $key || trim($retrievedKey) === trim($key)) {
					$found = true;
					break;
				}
			}
			$this::assertTrue($found, "Tag with key '$key' not found");
		}
		
		$this->cleanupStorage($storage);
	}
	
	/**
	 * Helper method to create test storage
	 * @param string $filename
	 * @return CloudStorage
	 * @throws Throwable
	 */
	private function createTestStorage(string $filename = 'test.txt'): CloudStorage {
		$storage = new CloudStorage([
			'key' => 'tags-test-' . uniqid('', true),
			'bucket' => self::TEST_BUCKET,
			'filename' => $filename,
			'size' => 100,
			'uploaded' => true
		]);
		
		$this::assertTrue($storage->save());
		return $storage;
	}
	
	/**
	 * Helper method to clean up test storage
	 * @param CloudStorage $storage
	 */
	private function cleanupStorage(CloudStorage $storage): void {
		CloudStorageTags::deleteAll(['cloud_storage_id' => $storage->id]);
		$storage->delete();
	}
}