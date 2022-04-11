<?php
declare(strict_types = 1);

namespace unit;

use Codeception\Test\Unit;
use cusodede\s3\components\PutObjectMethodParams;
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
		$params = new PutObjectMethodParams();
		$params->addTag('tag1', 'tag1value');
		$params->addTag('tag2');

		$storage = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE_FILE_PATH), null, null, $params);

		$result = (new S3())->getObjectTagging($storage->key);

		$tagSet = ArrayHelper::map($result->get('TagSet'), 'Key', 'Value');

		$this::assertEquals('tag1value', $tagSet['tag1'] ?? '');
		$this::assertEquals('tag2', $tagSet['tag2'] ?? '');

		$tags = ArrayHelper::map($storage->relatedTags, 'tag_label', 'tag_key');

		$this::assertArrayHasKey('tag1', $tags);
		$this::assertArrayHasKey('tag2', $tags);
	}
}