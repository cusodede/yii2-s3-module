<?php
declare(strict_types = 1);

namespace cusodede\s3\helpers;

use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\models\S3;
use pozitronik\helpers\PathHelper;
use Throwable;
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
		if (null === $filePath) {
			$filePath = PathHelper::GetTempFileName($storage->key);
		}
		(new S3())->getObject($storage->key, $storage->bucket, $filePath);
		return $filePath;
	}

	/**
	 * Загрузить локальный файл в облако, вернуть объект хранилища
	 * @param string $filePath
	 * @param string|null $fileName Имя файла в облаке (null - оставить локальное)
	 * @param string|null $bucket
	 * @return CloudStorage
	 * @throws Exception
	 * @throws Throwable
	 */
	public static function FileToStorage(string $filePath, ?string $fileName = null, ?string $bucket = null):CloudStorage {
		$s3 = new S3();
		$s3->saveObject($filePath, $bucket, $fileName??PathHelper::ExtractBaseName($filePath));
		return $s3->storage;
	}

	/**
	 * @param UploadedFile $instance
	 * @param string|null $bucket
	 * @return CloudStorage|null
	 * @throws Exception
	 * @throws Throwable
	 */
	public static function UploadInstance(UploadedFile $instance, ?string $bucket = null):?CloudStorage {
		$s3 = new S3();
		$s3->saveObject($instance->tempName, $bucket, $instance->name);
		return $s3->storage;
	}
}