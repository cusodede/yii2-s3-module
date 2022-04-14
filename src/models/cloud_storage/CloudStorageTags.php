<?php
declare(strict_types = 1);

namespace cusodede\s3\models\cloud_storage;

use cusodede\s3\models\ArrayTagAdapter;
use cusodede\s3\models\cloud_storage\active_record\CloudStorageTagsAR;
use pozitronik\helpers\ArrayHelper;

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
	 * @param int $cloud_storage_id
	 * @param string[]|null $tags
	 * @return void
	 */
	public static function assignTags(int $cloud_storage_id, ?array $tags):void {
		$tags = (new ArrayTagAdapter($tags))->getTags();
		static::clearTags($cloud_storage_id);
		foreach ($tags as $tag_label => $tag_key) {
			(new CloudStorageTags(compact('cloud_storage_id', 'tag_label', 'tag_key')))->save();
		}
	}

	/**
	 * @param int $cloud_storage_id
	 * @return string[]
	 */
	public static function retrieveTags(int $cloud_storage_id):array {
		return ArrayHelper::map(self::find()->where(['cloud_storage_id' => $cloud_storage_id])->all(), 'tag_label', 'tag_key');
	}

	/**
	 * @param int $cloud_storage_id
	 * @return void
	 */
	public static function clearTags(int $cloud_storage_id):void {
		self::deleteAll(['cloud_storage_id' => $cloud_storage_id]);
	}

}