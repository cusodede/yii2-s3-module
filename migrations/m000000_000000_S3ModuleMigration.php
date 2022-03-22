<?php
declare(strict_types = 1);

use cusodede\s3\models\cloud_storage\CloudStorage;
use yii\db\Migration;

/**
 * Class m000000_000000_S3ModuleMigration
 */
class m000000_000000_S3ModuleMigration extends Migration {
	/**
	 * @return string
	 */
	public static function mainTableName():string {
		return CloudStorage::tableName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
		$this->createTable(self::mainTableName(), [
			'id' => $this->primaryKey(),
			'bucket' => $this->string()->notNull()->comment('Корзина в облаке'),
			'key' => $this->string()->notNull()->comment('Ключ файла в облаке'),
			'filename' => $this->string()->notNull()->comment('Название файла'),
			'storage' => $this->integer()->null()->comment('Облачное хранилище'),
			'size' => $this->bigInteger()->comment('Размер загрузки')
		]);

		$this->createIndex('bucket', self::mainTableName(), 'bucket');
		$this->createIndex('key', self::mainTableName(), 'key');
		$this->createIndex('filename', self::mainTableName(), 'filename');
		$this->createIndex('storage', self::mainTableName(), 'storage');

	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropTable(self::mainTableName());
	}

}
