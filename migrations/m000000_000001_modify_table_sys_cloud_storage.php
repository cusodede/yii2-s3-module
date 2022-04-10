<?php
declare(strict_types = 1);

use yii\db\Migration;

/**
 * Class m000000_000001_modify_table_sys_cloud_storage
 */
class m000000_000001_modify_table_sys_cloud_storage extends Migration {

	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
        $this->addColumn('sys_cloud_storage', 'label', $this->string(255)->null()->comment('Метка файла'));
    }

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
        $this->dropColumn('sys_cloud_storage', 'label');
	}

}
