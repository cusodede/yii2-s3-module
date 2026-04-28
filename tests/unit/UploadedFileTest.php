<?php

declare(strict_types=1);

namespace unit;

use Codeception\Test\Unit;
use cusodede\s3\components\web\UploadedFile;
use cusodede\s3\models\cloud_storage\CloudStorage;
use ReflectionException;
use yii\base\UnknownClassException;

/**
 * Test suite for the cusodede\s3 UploadedFile override.
 * Pins the resource accessor (used by streamed PUT/multipart uploads), the
 * temp-file cleanup helper, and the type-narrowed getInstance() return.
 */
class UploadedFileTest extends Unit
{
    /**
     * With no streamed upload, getResource() returns null. This is the path
     * CloudStorage::uploadInstance falls into when dispatching to putObject.
     * @return void
     * @throws UnknownClassException
     * @throws ReflectionException
     */
    public function testGetResourceReturnsNullWhenAbsent(): void
    {
        $this::assertNull(new UploadedFile()->getResource());
    }

    /**
     * When constructed with a tempResource (the way Yii sets up streamed PUT
     * uploads internally), getResource() exposes that same resource verbatim.
     * This is the path CloudStorage::uploadInstance dispatches to putResource.
     * @return void
     * @throws UnknownClassException
     * @throws ReflectionException
     */
    public function testGetResourceReturnsTheResourceWhenPresent(): void
    {
        $resource = fopen('php://temp', 'rb+');
        fwrite($resource, 'streamed payload');
        rewind($resource);

        $file = new UploadedFile(['tempResource' => $resource]);

        $this::assertSame($resource, $file->getResource());

        fclose($resource);
    }

    /**
     * deleteTempFile removes an existing temp file and reports success.
     * @return void
     */
    public function testDeleteTempFileSucceedsForExistingFile(): void
    {
        $path = sys_get_temp_dir() . '/uploaded-file-test-' . uniqid('', true) . '.tmp';
        file_put_contents($path, 'payload');

        $file = new UploadedFile(['tempName' => $path]);

        $this::assertTrue($file->deleteTempFile());
        $this::assertFileDoesNotExist($path);
    }

    /**
     * deleteTempFile returns false (rather than raising) when the temp file is
     * already gone — it must be safe to call from cleanup paths.
     * @return void
     */
    public function testDeleteTempFileReturnsFalseForMissingFile(): void
    {
        $missing = sys_get_temp_dir() . '/uploaded-file-test-missing-' . uniqid('', true) . '.tmp';

        $file = new UploadedFile(['tempName' => $missing]);

        $this::assertFalse($file->deleteTempFile());
    }

    /**
     * getInstance() returns null when the model has no uploaded file on the
     * given attribute. Pins the type-narrowed ?self return: callers can rely on
     * the instance (when present) being a cusodede\s3 UploadedFile, not the
     * Yii base class.
     * @return void
     */
    public function testGetInstanceReturnsNullWhenNoUpload(): void
    {
        $model = new CloudStorage();

        $this::assertNull(UploadedFile::getInstance($model, 'file'));
    }
}
