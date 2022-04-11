<?php
declare(strict_types = 1);

use cusodede\s3\models\cloud_storage\active_record\CloudStorageAR;
use cusodede\s3\models\cloud_storage\active_record\CloudStorageTagsAR;
use yii\db\Migration;

class m000000_000000_cloud_storage_tags_table extends Migration {
	/**
	 * @return string
	 */
	public static function mainTableName():string {
		return CloudStorageTagsAR::tableName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
		$this->createTable(self::mainTableName(), [
			'id' => $this->primaryKey(),
			'cloud_storage_id' => $this->integer()->notNull()->comment('ID загруженного файла'),
			'tag_label' => $this->string()->notNull()->comment('Название тега'),
			'tag_key' => $this->string()->notNull()->comment('Значение тега')
		]);

		$this->addForeignKey('fk_storage_tag_to_storage', self::mainTableName(), 'cloud_storage_id', CloudStorageAR::tableName(), 'id', 'CASCADE', 'CASCADE');
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropTable(self::mainTableName());
	}
}