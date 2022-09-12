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
use yii\base\Model;

/**
 * S3Helper: обёртка над S3 для упрощения работы
 */
class S3Helper {

	/**
	 * Загрузить файл из облака по указанному ID
	 * @param int $storageId ID записи в хранилище
	 * @param string|null $filePath Путь сохранения, если null - во временный файл
	 * @return string|null Путь к полученному файлу, null при ошибке.
	 * @throws Exception
	 * @throws Throwable
	 */
	public static function StorageToFile(int $storageId, ?string $filePath = null):?string {
		/** @var CloudStorage $storage */
		if (null === $storage = CloudStorage::find()->where(['id' => $storageId])->active()->one()) return null;
		$filePath = $filePath??PathHelper::GetTempFileName(sprintf('%s_%s_%s', $storage->key, Yii::$app->security->generateRandomString(6), $storage->filename));
		(new S3(['connection' => $storage->connection]))->getObject($storage->key, $storage->bucket, $filePath);
		return $filePath;
	}

	/**
	 * Загрузить локальный файл в облако, вернуть объект хранилища
	 * @param string $filePath
	 * @param string|null $fileName Имя файла в облаке (null - оставить локальное)
	 * @param string|null $bucket
	 * @param string[]|null $tags
	 * @param string|null $connection Имя соединения (при использовании конфигурации с несколькими соединениями)
	 * @return CloudStorage
	 * @throws Exception
	 * @throws Throwable
	 */
	public static function FileToStorage(string $filePath, ?string $fileName = null, ?string $bucket = null, ?array $tags = null, ?string $connection = null):CloudStorage {
		$s3 = new S3(['connection' => $connection]);
		$s3->saveObject($filePath, $bucket, $fileName??PathHelper::ExtractBaseName($filePath)??PathHelper::GetRandomTempFileName(), $tags);
		return $s3->storage;
	}

	/**
	 * Загрузка файла из модели
	 * @param Model $model
	 * @param string $filePath
	 * @param string $fileName
	 * @param string|null $bucket
	 * @param array|null $tags
	 * @param string|null $connection Имя соединения (при использовании конфигурации с несколькими соединениями)
	 * @return bool
	 * @throws Throwable
	 * @throws Exception
	 */
	public static function uploadFileFromModel(Model $model, string $filePath, string $fileName, ?string $bucket = null, ?array $tags = null, ?string $connection = null):bool {
		$key = S3::GetFileNameKey($fileName);
		$storageResponse = (new S3(['connection' => $connection]))->putObject($filePath, $key, $bucket, $tags);

		$cloudStorage = new CloudStorage([
			'bucket' => $bucket,
			'key' => $key,
			'filename' => $fileName,
			'uploaded' => null !== ArrayHelper::getValue($storageResponse->toArray(), 'ObjectURL'),
			'size' => (false === $filesize = filesize($filePath))?null:$filesize,
			'model_name' => get_class($model),
			'model_key' => ArrayHelper::getValue($model, 'id'),//Скорее всего, это будет ActiveRecord, но если нет - загрузим без конкретной связи
			'connection' => $connection
		]);
		$cloudStorage->tags = $tags??[];
		return $cloudStorage->save();
	}

	/**
	 * Метод для удаления файлов
	 * @param CloudStorage|int $storage
	 * @param string|null $bucket
	 * @return int|null
	 * @throws Throwable
	 */
	public static function deleteFile(CloudStorage|int $storage, ?string $bucket = null):?int {
		if (null === $storageModel = (is_int($storage))
				?CloudStorage::find()->where(['id' => $storage])->active()->one()
				:$storage) return null;
		$storageModel->deleted = true;
		$storageModel->save();

		(new S3(['connection' => $storageModel->connection]))->deleteObject($storageModel->key, $bucket);
		return $storageModel->id;
	}
}
