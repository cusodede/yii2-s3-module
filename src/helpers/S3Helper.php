<?php
declare(strict_types = 1);

namespace cusodede\s3\helpers;

use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\models\S3;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\PathHelper;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\web\UploadedFile;

/**
 * S3Helper: обёртка над S3 для упрощения работы
 */
class S3Helper {

	/**
	 * Загрузить файл из облака по указанному ID
	 * @param int $storageId ID записи в хранилище
	 * @param string|null $filePath Путь сохранения, если null - во временный файл
	 * @return string|null Путь к полученному файлу, null при ошибке.
	 * @throws Throwable
	 */
	public static function StorageToFile(int $storageId, ?string $filePath = null):?string {
		if (null === $storage = CloudStorage::findModel($storageId)) return null;
		$filePath = $filePath??PathHelper::GetTempFileName(sprintf('%s_%s_%s', $storage->key, Yii::$app->security->generateRandomString(6), $storage->filename));
		(new S3())->getObject($storage->key, $storage->bucket, $filePath);
		return $filePath;
	}

	/**
	 * Загрузить локальный файл в облако, вернуть объект хранилища
	 * @param string $filePath
	 * @param string|null $fileName Имя файла в облаке (null - оставить локальное)
	 * @param string|null $bucket
	 * @param string[]|null $tags
	 * @return CloudStorage
	 * @throws Exception
	 * @throws Throwable
	 */
	public static function FileToStorage(string $filePath, ?string $fileName = null, ?string $bucket = null, ?array $tags = null):CloudStorage {
		$s3 = new S3();
		$s3->saveObject($filePath, $bucket, $fileName??PathHelper::ExtractBaseName($filePath)??PathHelper::GetRandomTempFileName(), $tags);
		return $s3->storage;
	}

	/**
	 * Загрузка файла из модели
	 * @param $model
	 * @param UploadedFile $uploadedFile
	 * @param string $bucket Ведро в которое будет производится загрузка
	 * @return bool
	 * @throws Exception
	 * @throws Throwable
	 */
	public static function uploadFileFromModel($model, UploadedFile $uploadedFile, string $bucket):bool {
		$randomKey = Yii::$app->security->generateRandomString(10);
		$storageResponse = (new S3())->client->putObject([
			'Bucket' => $bucket,
			'Key' => $randomKey,
			'Body' => fopen($uploadedFile->tempName, 'rb')
		]);

		$cloudStorage = new CloudStorage();
		$cloudStorage->key = $randomKey;
		$cloudStorage->filename = $uploadedFile->baseName;
		$cloudStorage->bucket = $bucket;
		$cloudStorage->uploaded = null !== ArrayHelper::getValue($storageResponse->toArray(), 'ObjectURL');
		$cloudStorage->model_name = get_class($model);
		$cloudStorage->model_key = $model->id;

		return $cloudStorage->save();
	}
}
