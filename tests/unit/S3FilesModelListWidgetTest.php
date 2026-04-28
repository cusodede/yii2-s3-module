<?php

declare(strict_types=1);

namespace unit;

use app\models\Users;
use Codeception\Test\Unit;
use cusodede\s3\models\cloud_storage\CloudStorage;
use cusodede\s3\widgets\files_model_list\S3FilesModelListWidget;
use Throwable;
use yii\base\InvalidConfigException;

/**
 * Test suite for S3FilesModelListWidget.
 * Pins the configuration validation contract and the (model_name + model_key)
 * filter driving the file list query.
 */
class S3FilesModelListWidgetTest extends Unit
{
    private const string TEST_BUCKET = 'testbucket';

    /**
     * Without a model parameter, the widget must refuse to run with a clear error.
     * @return void
     */
    public function testRunWithoutModelThrows(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Model parameter is required');
        new S3FilesModelListWidget()->run();
    }

    /**
     * A model whose primary key has not been assigned (e.g. an unsaved AR instance)
     * is rejected — the widget can only filter CloudStorage by a concrete model_key.
     * @return void
     */
    public function testRunWithModelMissingIdThrows(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Model must have an non-null id attribute');
        new S3FilesModelListWidget(['model' => new Users()])->run();
    }

    /**
     * Files attached to the given model render in the list; files attached to a
     * different owner do not. Verifies the model_key + model_name filter pair.
     * @return void
     * @throws Throwable
     */
    public function testRunRendersOnlyMatchingFiles(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();

        $ownedOne = $this->createStorage('owned-one.txt', $owner);
        $ownedTwo = $this->createStorage('owned-two.txt', $owner);
        $unrelated = $this->createStorage('unrelated.txt', $other);

        $output = new S3FilesModelListWidget(['model' => $owner])->run();

        $this::assertStringContainsString('owned-one.txt', $output);
        $this::assertStringContainsString('owned-two.txt', $output);
        $this::assertStringNotContainsString('unrelated.txt', $output);

        $ownedOne->delete();
        $ownedTwo->delete();
        $unrelated->delete();
        $owner->delete();
        $other->delete();
    }

    /**
     * When the model has no attached files, the widget renders nothing rather
     * than an empty list shell.
     * @return void
     * @throws Throwable
     */
    public function testRunWithNoMatchingFilesProducesEmptyOutput(): void
    {
        $owner = $this->createUser();

        $output = new S3FilesModelListWidget(['model' => $owner])->run();

        $this::assertSame('', trim($output));

        $owner->delete();
    }

    /**
     * @return Users
     * @throws Throwable
     */
    private function createUser(): Users
    {
        $username = 'wt-' . uniqid('', true);
        $user = new Users(['username' => $username, 'login' => $username, 'password' => 'pw']);
        $user->save();
        return $user;
    }

    /**
     * @param string $filename
     * @param Users $owner
     * @return CloudStorage
     * @throws Throwable
     */
    private function createStorage(string $filename, Users $owner): CloudStorage
    {
        $storage = new CloudStorage([
            'key' => 'wt-' . uniqid('', true),
            'bucket' => self::TEST_BUCKET,
            'filename' => $filename,
            'size' => 100,
            'uploaded' => true,
            'model_name' => Users::class,
            'model_key' => $owner->id,
        ]);
        $storage->save();
        return $storage;
    }
}
