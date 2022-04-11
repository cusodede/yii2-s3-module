<?php
declare(strict_types = 1);

namespace unit;

use Codeception\Test\Unit;
use cusodede\s3\helpers\S3Helper;
use Throwable;
use Yii;
use yii\base\Exception;

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
	 * @throws Throwable
	 */
	public function testDeleteFile():void {
		$storage = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE_FILE_PATH));
		$result = S3Helper::deleteFile($storage->id);
		$result2 = S3Helper::deleteFile(rand());

		$this::assertEquals($storage->id, $result);
		$this::assertNull($result2);
	}

}