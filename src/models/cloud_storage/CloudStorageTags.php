<?php
declare(strict_types = 1);

namespace cusodede\s3\models\cloud_storage;

use cusodede\s3\models\cloud_storage\active_record\CloudStorageTagsAR;

/**
 * Class CloudStorageTags
 */
class CloudStorageTags extends CloudStorageTagsAR {

	/**
	 * Получить все теги всех записей из S3
	 * @return void
	 */
	public static function SyncAllFromS3():void {
		//todo
	}

	/**
	 * Записать все теги всех записей в S3
	 * @return void
	 */
	public static function SyncAllToS3():void {
		//todo
	}

	/**
	 * Взять теги записи из S3
	 * @return void
	 */
	public function syncFromS3():void {
		//todo
	}

	/**
	 * Записать теги записи в S3
	 * @return void
	 */
	public function syncToS3():void {
		//todo
	}

}