<?php
declare(strict_types = 1);

use cusodede\s3\models\cloud_storage_tags\active_record\CloudStorageTagsAR;
use yii\db\Migration;

/**
 * Class m000000_000001_sys_cloud_storage_tags
 */
class m000000_000001_sys_cloud_storage_tags extends Migration {

    /**
     * {@inheritdoc}
     */
    public function safeUp() {
        $this->createTable(CloudStorageTagsAR::tableName(), [
            'id' => $this->primaryKey(),
            'cloud_storage_id' => $this->integer()->notNull(),
            'tag' => $this->string(255)->notNull()
        ]);

        $this->createIndex('idx-cloud_storage_id-tag', CloudStorageTagsAR::tableName(), ['cloud_storage_id', 'tag'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown() {
        $this->dropIndex('idx-cloud_storage_id-tag', CloudStorageTagsAR::tableName());
        $this->dropTable(CloudStorageTagsAR::tableName());
    }
}
