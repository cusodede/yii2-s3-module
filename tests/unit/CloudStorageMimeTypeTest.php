<?php

declare(strict_types=1);

namespace unit;

use Codeception\Test\Unit;
use cusodede\s3\models\cloud_storage\CloudStorage;
use Throwable;
use Yii;

/**
 * Test suite for the params-based override behavior of CloudStorage::GetMimeTypeByExtension.
 *
 * The class constants MIME_TYPES + DEFAULT_MIME_TYPE act only as fallbacks;
 * the primary lookup is S3Module::param('mimeTypes') and param('defaultMimeType').
 * This override-first behavior is intentional — host apps need a way to fix
 * wrong system MIME mappings (e.g. .apk on systems where magic.mime resolves
 * it incorrectly).
 */
class CloudStorageMimeTypeTest extends Unit
{
    /**
     * @var array<string, mixed>
     */
    private array $savedParams = [];

    /**
     * @return void
     */
    protected function _setUp(): void
    {
        parent::_setUp();
        $module = Yii::$app->getModule('s3');
        $this->savedParams = [
            'mimeTypes' => $module->params['mimeTypes'] ?? null,
            'defaultMimeType' => $module->params['defaultMimeType'] ?? null,
        ];
    }

    /**
     * @return void
     */
    protected function _tearDown(): void
    {
        $module = Yii::$app->getModule('s3');
        foreach ($this->savedParams as $key => $value) {
            if ($value === null) {
                unset($module->params[$key]);
            } else {
                $module->params[$key] = $value;
            }
        }
        parent::_tearDown();
    }

    /**
     * params.mimeTypes wins over the class constant MIME_TYPES for an extension
     * present in both. Pins the override-first lookup contract.
     * @return void
     * @throws Throwable
     */
    public function testParamMimeTypesShadowsClassConstants(): void
    {
        Yii::$app->getModule('s3')->params['mimeTypes'] = ['apk' => 'application/x-overridden'];

        $this::assertSame('application/x-overridden', CloudStorage::GetMimeTypeByExtension('app.apk'));
    }

    /**
     * params.mimeTypes can introduce extensions not present in MIME_TYPES.
     * @return void
     * @throws Throwable
     */
    public function testParamMimeTypesAddsNewExtensions(): void
    {
        Yii::$app->getModule('s3')->params['mimeTypes'] = ['xyz' => 'application/x-custom'];

        $this::assertSame('application/x-custom', CloudStorage::GetMimeTypeByExtension('file.xyz'));
    }

    /**
     * When params.mimeTypes is unset, MIME_TYPES is consulted as the fallback
     * — pinning the two-tier lookup contract (param first, then class const).
     * @return void
     * @throws Throwable
     */
    public function testFallsBackToClassConstantsWhenParamUnset(): void
    {
        unset(Yii::$app->getModule('s3')->params['mimeTypes']);

        $this::assertSame(
            'application/vnd.android.package-archive',
            CloudStorage::GetMimeTypeByExtension('app.apk')
        );
    }

    /**
     * params.defaultMimeType overrides DEFAULT_MIME_TYPE for unknown extensions.
     * @return void
     * @throws Throwable
     */
    public function testParamDefaultMimeTypeShadowsClassConstant(): void
    {
        $module = Yii::$app->getModule('s3');
        $module->params['mimeTypes'] = [];
        $module->params['defaultMimeType'] = 'application/x-sentinel';

        $this::assertSame('application/x-sentinel', CloudStorage::GetMimeTypeByExtension('file.unknown'));
    }
}
