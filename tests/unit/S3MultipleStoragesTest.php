<?php

declare(strict_types=1);

namespace unit;

use Aws\S3\Exception\S3Exception;
use Codeception\Test\Unit;
use cusodede\s3\helpers\S3Helper;
use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\models\cloud_storage\CloudStorageTags;
use cusodede\s3\models\S3;
use cusodede\s3\S3Module;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Module;

/**
 * Class S3MultipleStoragesTest
 */
class S3MultipleStoragesTest extends Unit
{
    private const string SAMPLE_FILE_PATH = './tests/_data/sample.txt';
    private const string SAMPLE2_FILE_PATH = './tests/_data/sample2.txt';
    private const string SAMPLE3_FILE_PATH = './tests/_data/sample3.txt';

    private ?Module $originalModule = null;

    /**
     * @return void
     */
    protected function _setUp(): void
    {
        parent::_setUp();

        $this->originalModule = Yii::$app->getModule('s3');

        Yii::$app->setModule('s3', [
            'class' => S3Module::class,
            'defaultRoute' => 'index',
            'params' => [
                'connection' => [
                    'FirstS3Connection' => [
                        'host' => $_ENV['MINIO_HOST'],
                        'login' => $_ENV['MINIO_ROOT_USER'],
                        'password' => $_ENV['MINIO_ROOT_PASSWORD'],
                        'connect_timeout' => 10,
                        'timeout' => 10,
                        'cert_path' => null,
                        'cert_password' => null,
                        'defaultBucket' => 'first-bucket',
                    ],
                    'SecondS3Connection' => [
                        'host' => $_ENV['MINIO_HOST'],//Same server used
                        'login' => $_ENV['MINIO_ROOT_USER'],
                        'password' => $_ENV['MINIO_ROOT_PASSWORD'],
                        'connect_timeout' => 10,
                        'timeout' => 10,
                        'cert_path' => null,
                        'cert_password' => null,
                        'defaultBucket' => 'second-bucket',
                    ]
                ],
                'tableName' => 'sys_cloud_storage',
                'tagsTableName' => 'sys_cloud_storage_tags',
                'viewPath' => './src/views/index',
                'defaultBucket' => 'testbucket',
                'maxUploadFileSize' => null,
                'deleteTempFiles' => true,
            ]
        ]);
    }

    /**
     * @return void
     */
    protected function _tearDown(): void
    {
        if (null !== $this->originalModule) {
            Yii::$app->setModule('s3', $this->originalModule);
        }
        parent::_tearDown();
    }

    /**
     * @return void
     * @throws Throwable
     * @throws Exception
     */
    public function testMultipleConnection(): void
    {
        $storageOne = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE_FILE_PATH), connection: 'FirstS3Connection');
        $storageTwo = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE2_FILE_PATH), connection: 'SecondS3Connection');

        $this::assertFileEquals(self::SAMPLE_FILE_PATH, S3Helper::StorageToFile($storageOne->id));
        $this::assertEquals('FirstS3Connection', $storageOne->connection);

        $this::assertFileEquals(self::SAMPLE2_FILE_PATH, S3Helper::StorageToFile($storageTwo->id));
        $this::assertEquals('SecondS3Connection', $storageTwo->connection);
    }

    /**
     * @return void
     * @throws Exception
     * @throws Throwable
     */
    public function testDefaultConnection(): void
    {
        //Соединение не указано - будет использоваться первое в списке
        $defaultStorage = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE3_FILE_PATH));
        $this::assertFileEquals(self::SAMPLE3_FILE_PATH, S3Helper::StorageToFile($defaultStorage->id));
        $this::assertEquals('FirstS3Connection', $defaultStorage->connection);
    }

    /**
     * @return void
     * @throws Exception
     * @throws Throwable
     */
    public function testUnknownConnection(): void
    {
        $this->expectExceptionObject(new InvalidConfigException("Connection 'ThisConnectionNotExists' is not configured."));
        $defaultStorage = S3Helper::FileToStorage(Yii::getAlias(self::SAMPLE3_FILE_PATH), connection: 'ThisConnectionNotExists');
        $this::assertFileEquals(self::SAMPLE3_FILE_PATH, S3Helper::StorageToFile($defaultStorage->id));
        $this::assertEquals('FirstS3Connection', $defaultStorage->connection);
    }

    /**
     * CloudStorage::Download must route through $model->connection rather than
     * defaulting to the first connection in the map. Sabotages FirstS3Connection's
     * password so any wrongly-routed call auth-fails; uploads via SecondS3Connection
     * (un-sabotaged) and verifies the download still succeeds.
     * @return void
     * @throws Throwable
     * @throws Exception
     */
    public function testDownloadUsesStorageConnection(): void
    {
        $storage = S3Helper::FileToStorage(
            Yii::getAlias(self::SAMPLE_FILE_PATH),
            connection: 'SecondS3Connection'
        );
        $module = Yii::$app->getModule('s3');
        $originalPassword = $module->params['connection']['FirstS3Connection']['password'];
        $module->params['connection']['FirstS3Connection']['password'] = 'sabotaged-' . uniqid();

        try {
            $response = CloudStorage::Download($storage->id);

            $this::assertNotNull($response);
            $this::assertEquals(
                file_get_contents(Yii::getAlias(self::SAMPLE_FILE_PATH)),
                $response->content
            );
        } finally {
            $module->params['connection']['FirstS3Connection']['password'] = $originalPassword;
            new S3(['connection' => 'SecondS3Connection'])->deleteObject($storage->key, $storage->bucket);
            $storage->delete();
        }
    }

    /**
     * CloudStorage::syncTagsFromS3 must route through $this->connection. Same
     * sabotage pattern as testDownloadUsesStorageConnection.
     * @return void
     * @throws Throwable
     * @throws Exception
     */
    public function testSyncTagsFromS3UsesStorageConnection(): void
    {
        $s3 = new S3(['connection' => 'SecondS3Connection']);
        $s3->saveObject(Yii::getAlias(self::SAMPLE_FILE_PATH));
        $storage = $s3->storage;
        $s3->setObjectTagging($storage->key, $storage->bucket, ['env' => 'remote']);

        $module = Yii::$app->getModule('s3');
        $originalPassword = $module->params['connection']['FirstS3Connection']['password'];
        $module->params['connection']['FirstS3Connection']['password'] = 'sabotaged-' . uniqid();

        try {
            $storage->syncTagsFromS3();
            $this::assertEquals(['env' => 'remote'], $storage->tags);
        } finally {
            $module->params['connection']['FirstS3Connection']['password'] = $originalPassword;
            new S3(['connection' => 'SecondS3Connection'])->deleteObject($storage->key, $storage->bucket);
            CloudStorageTags::deleteAll(['cloud_storage_id' => $storage->id]);
            $storage->delete();
        }
    }

    /**
     * CloudStorage::syncTagsToS3 must route through $this->connection. Same
     * sabotage pattern as testDownloadUsesStorageConnection.
     * @return void
     * @throws Throwable
     * @throws Exception
     */
    public function testSyncTagsToS3UsesStorageConnection(): void
    {
        $s3 = new S3(['connection' => 'SecondS3Connection']);
        $s3->saveObject(Yii::getAlias(self::SAMPLE_FILE_PATH));
        $storage = $s3->storage;
        CloudStorageTags::assignTags($storage->id, ['env' => 'local']);

        $module = Yii::$app->getModule('s3');
        $originalPassword = $module->params['connection']['FirstS3Connection']['password'];
        $module->params['connection']['FirstS3Connection']['password'] = 'sabotaged-' . uniqid();

        try {
            $storage->syncTagsToS3();
            $remoteTags = new S3(['connection' => 'SecondS3Connection'])
                ->getTagsArray($storage->key, $storage->bucket);
            $this::assertEquals(['env' => 'local'], $remoteTags);
        } finally {
            $module->params['connection']['FirstS3Connection']['password'] = $originalPassword;
            new S3(['connection' => 'SecondS3Connection'])->deleteObject($storage->key, $storage->bucket);
            CloudStorageTags::deleteAll(['cloud_storage_id' => $storage->id]);
            $storage->delete();
        }
    }

    /**
     * S3Helper::deleteFile must delete from the storage row's actual bucket,
     * not from the connection's default bucket. Uploads via FirstS3Connection
     * to second-bucket (NOT the connection's default of first-bucket), then
     * verifies that after deleteFile the object is actually gone from the
     * bucket where it lived.
     * @return void
     * @throws Throwable
     * @throws Exception
     */
    public function testDeleteFileUsesStorageBucket(): void
    {
        $storage = S3Helper::FileToStorage(
            Yii::getAlias(self::SAMPLE_FILE_PATH),
            bucket: 'second-bucket',
            connection: 'FirstS3Connection'
        );
        $this::assertEquals('second-bucket', $storage->bucket);
        $this::assertEquals('FirstS3Connection', $storage->connection);

        $s3 = new S3(['connection' => 'FirstS3Connection']);
        $head = $s3->client->headObject(['Bucket' => 'second-bucket', 'Key' => $storage->key]);
        $this::assertNotNull($head);

        try {
            S3Helper::deleteFile($storage);

            try {
                $s3->client->headObject(['Bucket' => 'second-bucket', 'Key' => $storage->key]);
                $this::fail('Expected S3 object to be deleted from second-bucket');
            } catch (S3Exception $e) {
                $this::assertEquals(404, $e->getStatusCode());
            }
        } finally {
            // Best-effort: with the buggy code the object would still be in
            // second-bucket; ensure cleanup either way.
            try {
                $s3->client->deleteObject(['Bucket' => 'second-bucket', 'Key' => $storage->key]);
            } catch (Throwable) {
            }
            $storage->delete();
        }
    }
}
