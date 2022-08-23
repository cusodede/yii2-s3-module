<?php
declare(strict_types = 1);

use cusodede\s3\models\cloud_storage\active_record\CloudStorageAR;
use yii\db\Migration;

/**
 * Class m000000_000002_cloud_storage_instance_field
 */
class m000000_000002_cloud_storage_instance_field extends Migration {
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
		$this->addColumn(self::mainTableName(), 'instance', $this->string(255)->null()->comment('Instance name'));

		$this->createIndex('idx_cloud_storage_instance', self::mainTableName(), 'instance');
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropIndex('idx_cloud_storage_instance', self::mainTableName());
		$this->dropColumn(self::mainTableName(), 'instance');
	}
}