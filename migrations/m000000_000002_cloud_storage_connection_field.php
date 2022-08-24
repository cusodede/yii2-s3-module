<?php
declare(strict_types = 1);

use cusodede\s3\models\cloud_storage\active_record\CloudStorageAR;
use yii\db\Migration;

/**
 * Class m000000_000002_cloud_storage_connection_field
 */
class m000000_000002_cloud_storage_connection_field extends Migration {
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
		$this->addColumn(self::mainTableName(), 'connection', $this->string(255)->null()->comment('Connection name'));

		$this->createIndex('idx_cloud_storage_connection', self::mainTableName(), 'connection');
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropIndex('idx_cloud_storage_connection', self::mainTableName());
		$this->dropColumn(self::mainTableName(), 'connection');
	}
}