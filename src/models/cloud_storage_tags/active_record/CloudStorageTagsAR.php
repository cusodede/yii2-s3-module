<?php
declare(strict_types = 1);

namespace cusodede\s3\models\cloud_storage_tags\active_record;

use cusodede\s3\models\cloud_storage\CloudStorage;
use pozitronik\traits\traits\ActiveRecordTrait;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "sys_cloud_storage_tags".
 * 
 * @property int $id
 * @property int $cloud_storage_id Идентификатор файла
 * @property string $tag Метка
 * @property-read CloudStorage $relatedCloudStorage Связь с таблицей файлов облачного хранилища
 */
class CloudStorageTagsAR extends ActiveRecord {
    use ActiveRecordTrait;

    /**
     * {@inheritDoc}
     */
    public static function tableName(): string {
        return 'sys_cloud_storage_tags';
    }
    
    /**
     * {@inheritdoc}
     */
    public function rules(): array {
        return [
            [['cloud_storage_id', 'tag'], 'required'],
            [['tag'], 'string'],
            [['cloud_storage_id'], 'integer'],
            [['cloud_storage_id', 'tag'], 'unique', 'targetAttribute' => ['cloud_storage_id', 'tag']]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array {
        return [
            'id' => 'ID',
            'cloud_storage_id' => 'Идентификатор файла',
            'tag' => 'Метка'
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getRelatedCloudStorage(): ActiveQuery {
        return $this->hasOne(CloudStorage::class, ['id' => 'cloud_storage_id']);
    }
}
