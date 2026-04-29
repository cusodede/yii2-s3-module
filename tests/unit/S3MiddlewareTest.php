<?php

declare(strict_types=1);

namespace unit;

use Aws\S3\S3Client;
use Codeception\Test\Unit;
use cusodede\s3\models\S3;
use cusodede\s3\models\S3MiddlewareDTO;
use Yii;

class S3MiddlewareTest extends Unit
{
    public function testGetAttemptMiddlewareReturnsDefaultWhenNotConfigured(): void
    {
        $s3 = new S3();
        $dto = $s3->getAttemptMiddleware();

        $this::assertInstanceOf(S3MiddlewareDTO::class, $dto);
        $this::assertNull($dto->middleware);
        $this::assertEquals('attemptMiddleware', $dto->name);
    }

    public function testGetSignMiddlewareReturnsDefaultWhenNotConfigured(): void
    {
        $s3 = new S3();
        $dto = $s3->getSignMiddleware();

        $this::assertInstanceOf(S3MiddlewareDTO::class, $dto);
        $this::assertNull($dto->middleware);
        $this::assertEquals('signMiddleware', $dto->name);
    }

    public function testGetBuildMiddlewareReturnsDefaultWhenNotConfigured(): void
    {
        $s3 = new S3();
        $dto = $s3->getBuildMiddleware();

        $this::assertInstanceOf(S3MiddlewareDTO::class, $dto);
        $this::assertNull($dto->middleware);
        $this::assertEquals('buildMiddleware', $dto->name);
    }

    public function testGetInitMiddlewareReturnsDefaultWhenNotConfigured(): void
    {
        $s3 = new S3();
        $dto = $s3->getInitMiddleware();

        $this::assertInstanceOf(S3MiddlewareDTO::class, $dto);
        $this::assertNull($dto->middleware);
        $this::assertEquals('initMiddleware', $dto->name);
    }

    public function testGetValidateMiddlewareReturnsDefaultWhenNotConfigured(): void
    {
        $s3 = new S3();
        $dto = $s3->getValidateMiddleware();

        $this::assertInstanceOf(S3MiddlewareDTO::class, $dto);
        $this::assertNull($dto->middleware);
        $this::assertEquals('validateMiddleware', $dto->name);
    }

    public function testGetAttemptMiddlewareReturnsConfiguredMiddleware(): void
    {
        $module = Yii::$app->getModule('s3');
        $original = $module->params['attemptMiddleware'] ?? null;

        $middleware = static fn() => null;
        $module->params['attemptMiddleware'] = [
            'middleware' => $middleware,
            'name' => 'customAttempt',
        ];

        try {
            $s3 = new S3();
            $dto = $s3->getAttemptMiddleware();

            $this::assertSame($middleware, $dto->middleware);
            $this::assertEquals('customAttempt', $dto->name);
        } finally {
            if ($original === null) {
                unset($module->params['attemptMiddleware']);
            } else {
                $module->params['attemptMiddleware'] = $original;
            }
        }
    }

    public function testGetSignMiddlewareReturnsConfiguredMiddleware(): void
    {
        $module = Yii::$app->getModule('s3');
        $original = $module->params['signMiddleware'] ?? null;

        $middleware = static fn() => null;
        $module->params['signMiddleware'] = [
            'middleware' => $middleware,
            'name' => 'customSign',
        ];

        try {
            $s3 = new S3();
            $dto = $s3->getSignMiddleware();

            $this::assertSame($middleware, $dto->middleware);
            $this::assertEquals('customSign', $dto->name);
        } finally {
            if ($original === null) {
                unset($module->params['signMiddleware']);
            } else {
                $module->params['signMiddleware'] = $original;
            }
        }
    }

    public function testGetBuildMiddlewareReturnsConfiguredMiddleware(): void
    {
        $module = Yii::$app->getModule('s3');
        $original = $module->params['buildMiddleware'] ?? null;

        $middleware = static fn() => null;
        $module->params['buildMiddleware'] = [
            'middleware' => $middleware,
            'name' => 'customBuild',
        ];

        try {
            $s3 = new S3();
            $dto = $s3->getBuildMiddleware();

            $this::assertSame($middleware, $dto->middleware);
            $this::assertEquals('customBuild', $dto->name);
        } finally {
            if ($original === null) {
                unset($module->params['buildMiddleware']);
            } else {
                $module->params['buildMiddleware'] = $original;
            }
        }
    }

    public function testGetInitMiddlewareReturnsConfiguredMiddleware(): void
    {
        $module = Yii::$app->getModule('s3');
        $original = $module->params['initMiddleware'] ?? null;

        $middleware = static fn() => null;
        $module->params['initMiddleware'] = [
            'middleware' => $middleware,
            'name' => 'customInit',
        ];

        try {
            $s3 = new S3();
            $dto = $s3->getInitMiddleware();

            $this::assertSame($middleware, $dto->middleware);
            $this::assertEquals('customInit', $dto->name);
        } finally {
            if ($original === null) {
                unset($module->params['initMiddleware']);
            } else {
                $module->params['initMiddleware'] = $original;
            }
        }
    }

    public function testGetValidateMiddlewareReturnsConfiguredMiddleware(): void
    {
        $module = Yii::$app->getModule('s3');
        $original = $module->params['validateMiddleware'] ?? null;

        $middleware = static fn() => null;
        $module->params['validateMiddleware'] = [
            'middleware' => $middleware,
            'name' => 'customValidate',
        ];

        try {
            $s3 = new S3();
            $dto = $s3->getValidateMiddleware();

            $this::assertSame($middleware, $dto->middleware);
            $this::assertEquals('customValidate', $dto->name);
        } finally {
            if ($original === null) {
                unset($module->params['validateMiddleware']);
            } else {
                $module->params['validateMiddleware'] = $original;
            }
        }
    }

    public function testMiddlewareWithPartialConfigFallsBackToDefaultName(): void
    {
        $module = Yii::$app->getModule('s3');
        $original = $module->params['attemptMiddleware'] ?? null;

        $middleware = static fn() => null;
        $module->params['attemptMiddleware'] = [
            'middleware' => $middleware,
        ];

        try {
            $s3 = new S3();
            $dto = $s3->getAttemptMiddleware();

            $this::assertSame($middleware, $dto->middleware);
            $this::assertEquals('attemptMiddleware', $dto->name);
        } finally {
            if ($original === null) {
                unset($module->params['attemptMiddleware']);
            } else {
                $module->params['attemptMiddleware'] = $original;
            }
        }
    }

    public function testMiddlewareWithPartialConfigFallsBackToNullMiddleware(): void
    {
        $module = Yii::$app->getModule('s3');
        $original = $module->params['attemptMiddleware'] ?? null;

        $module->params['attemptMiddleware'] = [
            'name' => 'onlyName',
        ];

        try {
            $s3 = new S3();
            $dto = $s3->getAttemptMiddleware();

            $this::assertNull($dto->middleware);
            $this::assertEquals('onlyName', $dto->name);
        } finally {
            if ($original === null) {
                unset($module->params['attemptMiddleware']);
            } else {
                $module->params['attemptMiddleware'] = $original;
            }
        }
    }

    public function testSetMiddlewareAppendsConfiguredMiddlewaresToClient(): void
    {
        $module = Yii::$app->getModule('s3');
        $originals = [
            'attemptMiddleware' => $module->params['attemptMiddleware'] ?? null,
            'initMiddleware' => $module->params['initMiddleware'] ?? null,
            'validateMiddleware' => $module->params['validateMiddleware'] ?? null,
            'signMiddleware' => $module->params['signMiddleware'] ?? null,
            'buildMiddleware' => $module->params['buildMiddleware'] ?? null,
        ];

        $attemptMw = static fn() => null;
        $initMw = static fn() => null;
        $validateMw = static fn() => null;
        $signMw = static fn() => null;
        $buildMw = static fn() => null;

        $module->params['attemptMiddleware'] = ['middleware' => $attemptMw, 'name' => 'testAttempt'];
        $module->params['initMiddleware'] = ['middleware' => $initMw, 'name' => 'testInit'];
        $module->params['validateMiddleware'] = ['middleware' => $validateMw, 'name' => 'testValidate'];
        $module->params['signMiddleware'] = ['middleware' => $signMw, 'name' => 'testSign'];
        $module->params['buildMiddleware'] = ['middleware' => $buildMw, 'name' => 'testBuild'];

        try {
            $s3 = new S3();
            $client = $s3->getClient();

            $this::assertInstanceOf(S3Client::class, $client);

            $handlerList = $client->getHandlerList();
            $this::assertStringContainsString('testAttempt', $handlerList->__toString());
            $this::assertStringContainsString('testInit', $handlerList->__toString());
            $this::assertStringContainsString('testValidate', $handlerList->__toString());
            $this::assertStringContainsString('testSign', $handlerList->__toString());
            $this::assertStringContainsString('testBuild', $handlerList->__toString());
        } finally {
            foreach ($originals as $key => $original) {
                if ($original === null) {
                    unset($module->params[$key]);
                } else {
                    $module->params[$key] = $original;
                }
            }
        }
    }

    public function testSetMiddlewareSkipsNullMiddlewares(): void
    {
        $s3 = new S3();
        $client = $s3->getClient();

        $this::assertInstanceOf(S3Client::class, $client);

        $handlerList = $client->getHandlerList()->__toString();
        $this::assertStringNotContainsString('attemptMiddleware', $handlerList);
        $this::assertStringNotContainsString('initMiddleware', $handlerList);
        $this::assertStringNotContainsString('validateMiddleware', $handlerList);
        $this::assertStringNotContainsString('signMiddleware', $handlerList);
        $this::assertStringNotContainsString('buildMiddleware', $handlerList);
    }

    public function testS3ClientWorksWithMiddlewares(): void
    {
        $module = Yii::$app->getModule('s3');
        $original = $module->params['buildMiddleware'] ?? null;

        $middleware = static fn(callable $handler): callable => static fn($command, $request) => $handler($command, $request);
        $module->params['buildMiddleware'] = ['middleware' => $middleware, 'name' => 'testBuildMw'];

        try {
            $s3 = new S3();
            $buckets = $s3->getListBucketMap();

            $this::assertIsArray($buckets);
            $this::assertNotEmpty($buckets);
        } finally {
            if ($original === null) {
                unset($module->params['buildMiddleware']);
            } else {
                $module->params['buildMiddleware'] = $original;
            }
        }
    }

    public function testSetMiddlewareCatchesThrowableAndStillReturnsClient(): void
    {
        $module = Yii::$app->getModule('s3');
        $originals = [
            'attemptMiddleware' => $module->params['attemptMiddleware'] ?? null,
            'initMiddleware' => $module->params['initMiddleware'] ?? null,
            'validateMiddleware' => $module->params['validateMiddleware'] ?? null,
            'signMiddleware' => $module->params['signMiddleware'] ?? null,
            'buildMiddleware' => $module->params['buildMiddleware'] ?? null,
        ];

        $middleware = static fn() => null;
        $module->params['attemptMiddleware'] = ['middleware' => $middleware, 'name' => 'testAttempt'];
        $module->params['initMiddleware'] = ['middleware' => $middleware, 'name' => 'testInit'];
        $module->params['validateMiddleware'] = ['middleware' => $middleware, 'name' => 'testValidate'];
        $module->params['signMiddleware'] = ['middleware' => $middleware, 'name' => 'testSign'];
        $module->params['buildMiddleware'] = ['middleware' => $middleware, 'name' => 'testBuild'];

        try {
            $s3 = new S3();

            $ref = new \ReflectionClass($s3);
            $method = $ref->getMethod('setMiddleware');

            $clientMock = $this->createMock(S3Client::class);
            $handlerList = $this->createMock(\Aws\HandlerList::class);
            $handlerList->method('appendAttempt')->willThrowException(new \RuntimeException('HandlerList error'));
            $handlerList->method('appendInit');
            $handlerList->method('appendValidate');
            $handlerList->method('appendBuild');
            $clientMock->method('getHandlerList')->willReturn($handlerList);

            $method->invoke($s3, $clientMock);

            $this::assertTrue(true);
        } finally {
            foreach ($originals as $key => $original) {
                if ($original === null) {
                    unset($module->params[$key]);
                } else {
                    $module->params[$key] = $original;
                }
            }
        }
    }
}
