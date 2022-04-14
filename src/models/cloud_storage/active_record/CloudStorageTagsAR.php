<?php
declare(strict_types = 1);

namespace cusodede\s3\models\cloud_storage\active_record;

use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\S3Module;
use pozitronik\traits\traits\ActiveRecordTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "sys_cloud_storage_tags".
 *
 * @property int $id
 * @property string $cloud_storage_id ID загруженного файла
 * @property string $tag_label Название тега
 * @property string $tag_key Значение тега
 *
 * @property-read CloudStorage $relatedStorage
 */
class CloudStorageTagsAR extends ActiveRecord {
	use ActiveRecordTrait;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName():string {
		return S3Module::param('tagsTableName', 'sys_cloud_storage_tags');
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['cloud_storage_id', 'tag_label', 'tag_key'], 'required'],
			[['cloud_storage_id'], 'integer'],
			[['tag_label', 'tag_key'], 'string', 'max' => 255],
			[['cloud_storage_id', 'tag_label'], 'unique', 'targetAttribute' => ['cloud_storage_id', 'tag_label']]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'id' => 'ID',
			'cloud_storage_id' => 'Модель загруженного файла',
			'tag_label' => 'Название тега',
			'tag_key' => 'Значение тега',
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedStorage():ActiveQuery {
		return $this->hasOne(CloudStorage::class, ['id' => 'cloud_storage_id']);
	}
}
