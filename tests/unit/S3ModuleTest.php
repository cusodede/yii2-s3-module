<?php
declare(strict_types = 1);

namespace unit;

use Codeception\Test\Unit;
use cusodede\s3\helpers\S3Helper;
use Yii;

/**
 * Class S3ModuleTest
 */
class S3ModuleTest extends Unit {

	public function testUploadDownload():void {
		$uploadFilePath = './tests/_data/sample.txt';

		$storage = S3Helper::FileToStorage(Yii::getAlias($uploadFilePath));

		$downloadFilePath = S3Helper::StorageToFile($storage->id);
		$this::assertFileEquals($uploadFilePath, $downloadFilePath);

	}

}