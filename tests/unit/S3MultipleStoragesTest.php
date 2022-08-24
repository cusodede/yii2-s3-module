<?php
declare(strict_types = 1);

namespace unit;
use Codeception\Test\Unit;
use cusodede\s3\helpers\S3Helper;
use cusodede\s3\S3Module;
use Throwable;
use Yii;
use yii\base\Exception;

/**
 * Class S3MultipleStoragesTest
 */
class S3MultipleStoragesTest extends Unit {

	private const SAMPLE_FILE_PATH = './tests/_data/sample.txt';
	private const SAMPLE2_FILE_PATH = './tests/_data/sample2.txt';

	/**
	 * @return void
	 */
	protected function _setUp():void {
		parent::_setUp();

		Yii::$app->setModule('s3', [
			'class' => S3Module::class,
			'defaultRoute' => 'index',
			'params' => [
				'connection' => [
					'FirstS3Connection' => [
						'host' => $_ENV['MINIO_HOST'],
						'login' => $_ENV['MINIO_ROOT_USER'],
						'password' => $_ENV['MINIO_ROOT_PASSWORD'],
						'connect_timeout' => 10,
						'timeout' => 10,
						'cert_path' => null,
						'cert_password' => null,
						'defaultBucket' => 'first_bucket'
					],
					'SecondS3Connection' => [
						'host' => $_ENV['MINIO_HOST'],//Same server used
						'login' => $_ENV['MINIO_ROOT_USER'],
						'password' => $_ENV['MINIO_ROOT_PASSWORD'],
						'connect_timeout' => 10,
						'timeout' => 10,
						'cert_path' => null,
						'cert_password' => null,
						'defaultBucket' => 'second_bucket'
					]
				],
				'tableName' => 'sys_cloud_storage',
				'tagsTableName' => 'sys_cloud_storage_tags',
				'viewPath' => './src/views/index',
				'defaultBucket' => 'testbucket',
				'maxUploadFileSize' => null,
				'deleteTempFiles' => true,
			]
		]);
	}

	/**
	 * @return void
	 * @throws Throwable
	 * @throws Exception
	 */
	public function testMultipleConnection():void {
		$storageOne = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE_FILE_PATH), connection: 'FirstS3Connection');
		$storageTwo = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE2_FILE_PATH), connection: 'SecondS3Connection');

		$this::assertFileEquals(self::SAMPLE_FILE_PATH, S3Helper::StorageToFile($storageOne->id));
		$this::assertEquals('FirstS3Connection', $storageOne->connection);

		$this::assertFileEquals(self::SAMPLE2_FILE_PATH, S3Helper::StorageToFile($storageTwo->id));
		$this::assertEquals('SecondS3Connection', $storageTwo->connection);

	}
}