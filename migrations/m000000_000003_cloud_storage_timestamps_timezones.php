<?php
declare(strict_types = 1);

use cusodede\s3\models\cloud_storage\active_record\CloudStorageAR;
use pozitronik\helpers\ArrayHelper;
use pozitronik\traits\traits\MigrationTrait;
use yii\db\Migration;

/**
 * Class m000000_000003_cloud_storage_timestamps_timezones
 */
class m000000_000003_cloud_storage_timestamps_timezones extends Migration {
	use MigrationTrait;

	/**
	 * @return string
	 */
	public static function mainTableName():string {
		return CloudStorageAR::tableName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
		if (null !== ArrayHelper::getValue($this->db->schema->typeMap, 'timestamptz')) {
			$this->alterColumn(self::mainTableName(), 'created_at', $this->timestamptz(0)->notNull()->comment('Дата и время создания.'));
		} else {
			Yii::info('timestamptz column type is not supported bu DB schema, migration not applied.');
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		if (null !== ArrayHelper::getValue($this->db->schema->typeMap, 'timestamptz')) {
			$this->alterColumn(self::mainTableName(), 'created_at', $this->timestamp(0)->notNull()->comment('Дата и время создания.'));
		} else {
			Yii::info('timestamptz column type is not supported bu DB schema, migration not applied.');
		}
	}
}