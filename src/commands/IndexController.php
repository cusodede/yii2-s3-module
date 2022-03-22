<?php
declare(strict_types = 1);

namespace cusodede\s3\commands;

use cusodede\s3\models\S3;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Class IndexController
 */
class IndexController extends Controller {

	/** @var S3 $s3 */
	private $s3 = null;

	/**
	 * Сохраняет файл в Minio
	 * command yii s3/index/put /my/path/file.txt fileKey [my-bucket]
	 * @param string $filepath
	 * @param null|string $bucket
	 * @param string $key
	 * @return void
	 */
	public function actionPut(string $filepath, string $key, ?string $bucket = null):void {
		try {
			$res = $this->s3->client->putObject(['Bucket' => $this->s3->getBucket($bucket), 'Key' => $key, 'Body' => fopen($filepath, 'rb')]);
			$this->outputResult($res->toArray());
		} catch (Throwable $e) {
			$this->outputResult($e->getMessage(), true);
		}
	}

	/**
	 * Скачивает и сохраняет файл из Minio
	 * command yii s3/index/get fileKey [my-bucket] [/path/to/save]
	 * @param string $filepath
	 * @param null|string $bucket
	 * @param string $key
	 * @return void
	 */
	public function actionGet(string $key, ?string $bucket = null, string $filepath = '/tmp'):void {
		try {
			$savePath = implode('/', [$filepath, $key]);
			$res = $this->s3->client->getObject(['Bucket' => $this->s3->getBucket($bucket), 'Key' => $key, 'SaveAs' => $savePath]);
			Console::output('Saving file in path:'.$savePath);
			$this->outputResult($res->toArray());
		} catch (Throwable $e) {
			$this->outputResult($e->getMessage(), true);
		}
	}

	/**
	 * Инфо о файле
	 * command yii s3/index/head fileKey [my-bucket]
	 * @param null|string $bucket
	 * @param string $key
	 * @return void
	 */
	public function actionHead(string $key, ?string $bucket = null):void {
		try {
			$res = $this->s3->client->headObject(['Bucket' => $this->s3->getBucket($bucket), 'Key' => $key]);
			$this->outputResult($res->toArray());
		} catch (Throwable $e) {
			$this->outputResult($e->getMessage(), true);
		}
	}

	/**
	 * Удаляет файл в Minio
	 * command yii s3/index/delete my-bucket fileKey
	 * @param string $bucket
	 * @param string $key
	 * @return void
	 */
	public function actionDelete(string $bucket, string $key):void {
		try {
			$res = $this->s3->client->deleteObject(['Bucket' => $bucket, 'Key' => $key]);
			$this->outputResult($res->toArray());
		} catch (Throwable $e) {
			$this->outputResult($e->getMessage(), true);
		}
	}

	/**
	 * Показать все объекты (макс 1000)
	 * command yii s3/index/list [my-bucket]
	 * @param null|string $bucket
	 * @return void
	 */
	public function actionList(?string $bucket = null):void {
		try {
			$res = $this->s3->client->listObjects(['Bucket' => $this->s3->getBucket($bucket)])->toArray();
			$this->outputResult('Quantity of objects '.count($res['Contents']));
			foreach ($res['Contents'] as $content) {
				Console::output("{$content['Key']} {$content['Size']} bytes");
			}
			Yii::info(json_encode($res), S3::CONSOLE_LOG);
		} catch (Throwable $e) {
			$this->outputResult($e->getMessage(), true);

		}
	}

	/**
	 * Показать все ведерки
	 * command yii s3/index/listBuckets
	 * @return void
	 */
	public function actionListBuckets():void {
		try {
			$res = $this->s3->client->listBuckets()->toArray();
			$this->outputResult('Quantity of buckets '.count($res['Buckets']));
			foreach ($res['Buckets'] as $bucket) {
				Console::output("{$bucket['Name']} created {$bucket['CreationDate']}");
			}
			Yii::info(json_encode($res), S3::CONSOLE_LOG);
		} catch (Throwable $e) {
			$this->outputResult($e->getMessage(), true);
		}
	}

	/**
	 * @param array $data
	 */
	private function outputResult(mixed $data, bool $isError = false):void {
		if ($isError) {
			Yii::error(is_string($data)?$data:json_encode($data), S3::CONSOLE_LOG);
		} else {
			Yii::info(is_string($data)?$data:json_encode($data), S3::CONSOLE_LOG);
		}
		Console::output(is_string($data)?$data:json_encode($data, JSON_PRETTY_PRINT));
	}

	/**
	 * @inheritdoc
	 */
	public function beforeAction($action):bool {
		$this->s3 = new S3();
		return parent::beforeAction($action);
	}
}