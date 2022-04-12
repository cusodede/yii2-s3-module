<?php
declare(strict_types = 1);

namespace unit;

use Codeception\Test\Unit;
use cusodede\s3\helpers\S3Helper;
use cusodede\s3\models\S3;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class S3ModuleTest
 */
class S3ModuleTest extends Unit {
	private const SAMPLE_FILE_PATH = './tests/_data/sample.txt';

	/**
	 * @return void
	 * @throws Throwable
	 * @throws Exception
	 */
	public function testUploadDownload():void {

		$storage = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE_FILE_PATH));

		$downloadFilePath = S3Helper::StorageToFile($storage->id);
		$this::assertFileEquals(self::SAMPLE_FILE_PATH, $downloadFilePath);

	}

	/**
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testTagsBinding():void {

		$storage = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE_FILE_PATH), null, null, [
			'tag1' => 'tag1value', 'tag2', 'emptyTag' => null
		]);

		/*Проверим присвоение тегов объекту*/
		$this::assertEquals($storage->tags, [
			'tag1' => 'tag1value', 'tag2' => 'tag2', 'emptyTag' => 'emptyTag'
		]);

		/*Проверим соответствие тегов в S3*/
		$result = (new S3())->getTagsArray($storage->key);
		$this::assertEquals($result, [
			'tag1' => 'tag1value', 'tag2' => 'tag2', 'emptyTag' => 'emptyTag'
		]);

		$this::assertEquals($result['tag1'], 'tag1value');
		$this::assertEquals($result['tag2'], 'tag2');
		$this::assertEquals($result['emptyTag'], 'emptyTag');

		/*Проверим соответствие тегов в БД*/
		$tags = ArrayHelper::map($storage->relatedTags, 'tag_label', 'tag_key');

		$this::assertEquals($tags, [
			'tag1' => 'tag1value', 'tag2' => 'tag2', 'emptyTag' => 'emptyTag'
		]);
	}

	/**
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testEmptyTags():void {
		$storage = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE_FILE_PATH));

		$this::assertEquals($storage->tags, []);

		$result = (new S3())->getTagsArray($storage->key);
		$this::assertEquals($result, []);
		$this::assertEquals(ArrayHelper::map($storage->relatedTags, 'tag_label', 'tag_key'), []);
	}
}