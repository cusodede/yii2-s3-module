<?php
declare(strict_types = 1);
use Codeception\Test\Unit;
use cusodede\s3\models\S3;
use cusodede\s3\S3Module;

/**
 * Class S3MultipleStoragesTest
 */
class S3MultipleStoragesTest extends Unit {

	private const SAMPLE_FILE_PATH = './tests/_data/sample.txt';
	private const SAMPLE2_FILE_PATH = './tests/_data/sample2.txt';

	/**
	 * @return void
	 */
	public function _setUp() {
		parent::_setUp();

		Yii::$app->setModule('FirstS3Connection', [
			'class' => S3Module::class,
			'defaultRoute' => 'index',
			'params' => [
				'connection' => [
					'host' => $_ENV['MINIO_HOST'],
					'login' => $_ENV['MINIO_ROOT_USER'],
					'password' => $_ENV['MINIO_ROOT_PASSWORD'],
					'connect_timeout' => 10,
					'timeout' => 10,
					'cert_path' => null,
					'cert_password' => null
				],
				'tableName' => 'sys_cloud_storage',
				'tagsTableName' => 'sys_cloud_storage_tags',
				'viewPath' => './src/views/index',
				'maxUploadFileSize' => null,
				'defaultBucket' => 'testbucket',
				'deleteTempFiles' => true,
				'instance' => 'FirstS3Connection',
			]
		]);

		Yii::$app->setModule('SecondS3Connection', [
			'class' => S3Module::class,
			'defaultRoute' => 'index',
			'params' => [
				'connection' => [
					'host' => $_ENV['MINIO_HOST'],//Same server used
					'login' => $_ENV['MINIO_ROOT_USER'],
					'password' => $_ENV['MINIO_ROOT_PASSWORD'],
					'connect_timeout' => 10,
					'timeout' => 10,
					'cert_path' => null,
					'cert_password' => null
				],
				'tableName' => 'sys_cloud_storage',
				'tagsTableName' => 'sys_cloud_storage_tags',
				'viewPath' => './src/views/index',
				'maxUploadFileSize' => null,
				'defaultBucket' => 'testbucket',
				'deleteTempFiles' => true,
				'instance' => 'SecondS3Connection',
			]
		]);
	}

	public function testMultipleConnection():void {
		/** @var S3 $firstConnection */
		$firstConnection = Yii::$app->module->FirstS3Connection;
		self::assertIsObject($firstConnection);
		/** @var S3 $secondConnection */
		$secondConnection = Yii::$app->module->SecondS3Connection;
		self::assertIsObject($secondConnection);


	}
}