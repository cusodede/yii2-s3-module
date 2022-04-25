<?php
declare(strict_types = 1);

namespace unit;

use Aws\S3\Exception\S3Exception;
use Codeception\Test\Unit;
use cusodede\s3\helpers\S3Helper;
use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\models\cloud_storage\CloudStorageTags;
use cusodede\s3\models\S3;
use pozitronik\helpers\PathHelper;
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

		/** @var CloudStorage $newStorage */
		$newStorage = CloudStorage::find()->where(['key' => $storage->key])->one();
		/*Перепроверим присвоение сохранённых тегов после переинициализации объекта*/
		$this::assertEquals($newStorage->tags, [
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

	/**
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testSyncFromS3():void {
		$s3 = new S3();
		/*Закинем файл без тегов*/
		$s3->saveObject(Yii::getAlias(self::SAMPLE_FILE_PATH));
		$this::assertEmpty($s3->getTagsArray());

		/*Установим в S3 тег, и проверим, что он присвоился*/
		$s3->setObjectTagging(null, null, ['someTag' => 'someTagValue']);
		$this::assertEquals($s3->getTagsArray(), ['someTag' => 'someTagValue']);

		/*Убедимся, что локальные теги пустые*/
		$this::assertEmpty($s3->storage->tags);

		/*Синхронизируем из S3*/
		$s3->storage->syncTagsFromS3();

		$this::assertEquals($s3->storage->tags, ['someTag' => 'someTagValue']);

		/*Дропнем теги в хранилище, проверим*/
		$s3->setObjectTagging();
		$this::assertEmpty($s3->getTagsArray());

		/*Синхронизируем из S3, проверим, что локальные теги опустели*/
		$s3->storage->syncTagsFromS3();
		$this::assertEmpty($s3->storage->tags);
	}

	/**
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testSyncToS3():void {
		$s3 = new S3();
		/*Закинем файл без тегов*/
		$s3->saveObject(Yii::getAlias(self::SAMPLE_FILE_PATH));
		$this::assertEmpty($s3->getTagsArray());

		/*Установим локальный тег, и убедимся, что он присвоился*/
		CloudStorageTags::assignTags($s3->storage->id, ['someTag' => 'someTagValue']);
		$this::assertEquals(CloudStorageTags::retrieveTags($s3->storage->id), ['someTag' => 'someTagValue']);

		/*Синхронизируем в S3 (теги уйдут в облако, но не будут присвоены объекту $storage, это нормально в нашем тесте)*/
		$s3->storage->syncTagsToS3();

		/*Проверяем, что тег установился в облаке*/
		$this::assertEquals($s3->getTagsArray(), ['someTag' => 'someTagValue']);
	}

	/**
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testTagsManipulations():void {
		$storage = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE_FILE_PATH), null, null, ['tag1' => 'tag1value']);
		$s3 = new S3(['storage' => $storage]);
		$result = $s3->getTagsArray();
		$this::assertEquals($result, ['tag1' => 'tag1value']);

		$storage->tags = ['newTagName' => 'newTagValue'];
		$storage->save();

		$this::assertEquals($storage->tags, ['newTagName' => 'newTagValue']);

		$result = $s3->getTagsArray();

		/*Прямое изменение тегов в хранилище НЕ МЕНЯЕТ теги в S3*/
		$this::assertEquals($result, ['tag1' => 'tag1value']);
		/*После синхронизации теги появятся в S3*/
		$storage->syncTagsToS3();
		$result = $s3->getTagsArray();
		$this::assertEquals($result, ['newTagName' => 'newTagValue']);

		/*Добавим тег в S3 + перезапишем имеющийся*/
		$s3->setObjectTagging(null, null, ['someTag' => 'someTagValue', 'newTagName' => 'otherTagValue']);
		$result = $s3->getTagsArray();

		$this::assertEquals($result, ['someTag' => 'someTagValue', 'newTagName' => 'otherTagValue']);
		$s3->storage->syncTagsFromS3();

		$this::assertEquals($s3->storage->tags, ['someTag' => 'someTagValue', 'newTagName' => 'otherTagValue']);

	}

	/**
	 * @return void
	 * @throws Throwable
	 */
	public function testDeleteFile():void {
		$storage = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE_FILE_PATH));
		$result = S3Helper::deleteFile($storage->id);
		$result2 = S3Helper::deleteFile(10);
		$result3 = S3Helper::StorageToFile($result);

		$this::assertEquals($storage->id, $result);
		$this::assertNull($result2);
		$this::assertNull($result3);

		$s3 = new S3(['storage' => $storage]);
		$this->expectException(S3Exception::class);
		$s3->getObject(null, null, PathHelper::GetTempFileName());

	}

}