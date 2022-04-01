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
			'size' => $this->bigInteger()->comment('Размер загрузки'),
			'created_at' => $this->dateTime()->comment('Дата создания'),
			'uploaded' => $this->boolean()->notNull()->defaultValue(false)->comment('Загрузка завершена'),
			'deleted' => $this->boolean()->notNull()->defaultValue(false)->comment('Флаг удаления'),
			'model_name' => $this->string()->null()->comment('Связанный класс'),
			'model_key' => $this->integer()->null()->comment('Ключ модели'),
		]);

		$this->createIndex('bucket', self::mainTableName(), 'bucket');
		$this->createIndex('key', self::mainTableName(), 'key');
		$this->createIndex('filename', self::mainTableName(), 'filename');
		$this->createIndex('storage', self::mainTableName(), 'storage');
		$this->createIndex('uploaded', self::mainTableName(), 'uploaded');
		$this->createIndex('deleted', self::mainTableName(), 'deleted');
		$this->createIndex(self::mainTableName().'_model_name', self::mainTableName(), 'model_name');
		$this->createIndex(self::mainTableName().'_model_key', self::mainTableName(), 'model_key');

	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropTable(self::mainTableName());
	}

}
