<?php
declare(strict_types = 1);

namespace cusodede\s3\models\cloud_storage\active_record;

use cusodede\s3\S3Module;
use pozitronik\helpers\DateHelper;
use pozitronik\traits\traits\ActiveRecordTrait;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "sys_cloud_storage".
 *
 * @property int $id
 * @property string $bucket Корзина в облаке
 * @property string $key Ключ файла в облаке, сгенерируется случайным образом, если не указан.
 * @property string $filename Название файла, сгенерируется из имени файла, если не указано
 * @property null|int $size Размер файла
 * @property bool $uploaded Загружено
 * @property bool $deleted Удалено
 * @property bool $created_at Дата создания
 * @property null|string $model_name Связанный класс
 * @property null|int $model_key Ключ модели
 */
class CloudStorageAR extends ActiveRecord {
	use ActiveRecordTrait;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName():string {
		return S3Module::param('tableName', 'sys_cloud_storage');
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['bucket'], 'required'],
			[['uploaded', 'deleted'], 'boolean'],
			[['size', 'model_key',], 'integer'],
			['created_at', 'safe'],
			[['bucket', 'key', 'filename', 'model_name',], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'id' => 'ID',
			'key' => 'Ключ файла в облаке',
			'bucket' => 'Корзина в облаке',
			'filename' => 'Название файла',
			'uploaded' => 'Загружено',
			'deleted' => 'Удалено',
			'created_at' => 'Дата создания',
			'file' => 'Файл',
			'size' => 'Размер',
			'model_name' => 'Связанный класс',
			'model_key' => 'Ключ модели',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function behaviors():array {
		return [
			[
				'class' => TimestampBehavior::class,
				'updatedAtAttribute' => false,
				'value' => DateHelper::lcDate(),
			]
		];
	}

}
