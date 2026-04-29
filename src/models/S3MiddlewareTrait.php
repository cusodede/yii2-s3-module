<?php

declare(strict_types=1);

namespace cusodede\s3\models;

use Aws\S3\S3Client;
use Closure;
use cusodede\s3\S3Module;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;

trait S3MiddlewareTrait
{
    /**
     * Attempt stage — Direct execution of the HTTP request and receiving the response
     * @return S3MiddlewareDTO
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function getAttemptMiddleware(): S3MiddlewareDTO
    {
        /** @var array{middleware?:Closure, name?:string} $param */
        $param = S3Module::param('attemptMiddleware');

        return new S3MiddlewareDTO($param['middleware'] ?? null, $param['name'] ?? 'attemptMiddleware');
    }

    /**
      * Sign stage — Signing the HTTP request (SigV4)
      * @return S3MiddlewareDTO
      * @throws InvalidConfigException
      * @throws Throwable
      */
    public function getSignMiddleware(): S3MiddlewareDTO
    {
        /** @var array{middleware?:Closure, name?:string} $param */
        $param = S3Module::param('signMiddleware');

        return new S3MiddlewareDTO($param['middleware'] ?? null, $param['name'] ?? 'signMiddleware');
    }

    /**
      * Build stage — Serializing the command into an HTTP request
      * @return S3MiddlewareDTO
      * @throws InvalidConfigException
      * @throws Throwable
      */
    public function getBuildMiddleware(): S3MiddlewareDTO
    {
        /** @var array{middleware?:Closure, name?:string} $param */
        $param = S3Module::param('buildMiddleware');

        return new S3MiddlewareDTO($param['middleware'] ?? null, $param['name'] ?? 'buildMiddleware');
    }

    /**
      * Init stage — Initializing the command, adding default parameters
      * @return S3MiddlewareDTO
      * @throws InvalidConfigException
      * @throws Throwable
      */
    public function getInitMiddleware(): S3MiddlewareDTO
    {
        /** @var array{middleware?:Closure, name?:string} $param */
        $param = S3Module::param('initMiddleware');

        return new S3MiddlewareDTO($param['middleware'] ?? null, $param['name'] ?? 'initMiddleware');
    }

    /**
      * Validate stage — Validating the command's input parameters
      * @return S3MiddlewareDTO
      * @throws InvalidConfigException
      * @throws Throwable
      */
    public function getValidateMiddleware(): S3MiddlewareDTO
    {
        /** @var array{middleware?:Closure, name?:string} $param */
        $param = S3Module::param('validateMiddleware');

        return new S3MiddlewareDTO($param['middleware'] ?? null, $param['name'] ?? 'validateMiddleware');
    }

    protected function setMiddleware(S3Client $client): void
    {
        try {
            $attemptMiddleware = $this->getAttemptMiddleware();
            if (null !== $attemptMiddleware->middleware) {
                $client->getHandlerList()->appendAttempt($attemptMiddleware->middleware, $attemptMiddleware->name);
            }

            $initMiddleware = $this->getInitMiddleware();
            if (null !== $initMiddleware->middleware) {
                $client->getHandlerList()->appendInit($initMiddleware->middleware, $initMiddleware->name);
            }

            $validateMiddleware = $this->getValidateMiddleware();
            if (null !== $validateMiddleware->middleware) {
                $client->getHandlerList()->appendValidate($validateMiddleware->middleware, $validateMiddleware->name);
            }

            $signMiddleware = $this->getSignMiddleware();
            if (null !== $signMiddleware->middleware) {
                $client->getHandlerList()->appendSign($signMiddleware->middleware, $signMiddleware->name);
            }

            $buildMiddleware = $this->getBuildMiddleware();
            if (null !== $buildMiddleware->middleware) {
                $client->getHandlerList()->appendBuild($buildMiddleware->middleware, $buildMiddleware->name);
            }
        } catch (Throwable $throwable) {
            Yii::error($throwable);
        }
    }
}
