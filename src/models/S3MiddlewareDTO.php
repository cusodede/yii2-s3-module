<?php

declare(strict_types=1);

namespace cusodede\s3\models;

use Closure;

final class S3MiddlewareDTO
{
    public function __construct(
        public ?Closure $middleware,
        public string $name
    ) {
    }
}
