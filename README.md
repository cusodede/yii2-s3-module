# yii2-s3-module

[![Build Status](https://github.com/cusodede/yii2-s3-module/actions/workflows/ci.yml/badge.svg)](https://github.com/cusodede/yii2-s3-module/actions)
[![Linter Status](https://github.com/cusodede/yii2-s3-module/actions/workflows/linter.yml/badge.svg)](https://github.com/cusodede/yii2-s3-module/actions)
[![Coverage Status](https://codecov.io/gh/cusodede/yii2-s3-module/graph/badge.svg)](https://codecov.io/gh/cusodede/yii2-s3-module)

S3 support module (file-manager and stuff).

# Installation with Composer

Run

```
php composer.phar require cusodede/yii2-s3-module "^1.0.0"
```

or add

```
"cusodede/yii2-s3-module": "^1.0.0"
```

to the require section of your `composer.json` file.

# Module migration

Module needs to store file data in database tables, which will be created by

```
php yii migrate/up --migrationPath=@vendor/cusodede/yii2-s3-module/migrations
```

command. You can customize table names by define `tableName` and `tagsTableName` parameters in module
configuration.

# Configuration parameters

See example config below:

```php
return [
    // ...
    'modules' => [
        's3' => [
            'class' => cusodede\s3\S3Module::class,
            'defaultRoute' => 'index',
            'params' => [
                'connection' => [
                    'host' => 'minio_host',
                    'login' => 'minio_user',
                    'password' => 'minio_password',
                    'connect_timeout' => 10, /* http connection timeout */
                    'timeout' => 10, /* http timeout */
                    'cert_path' => null, /* path to ssl certificate, set null to disable */
                    'cert_password' => null /* certificate password, set null, if certificate has no password */
                ],
                'tableName' => 'sys_cloud_storage', /* the table with storage data info, see Module migration section */
                'tagsTableName' => 'sys_cloud_storage_tags', /* the table with local tags, see Module migration section */
                'viewPath' => '@vendor/cusodede/yii2-s3-module/src/views/index', /* path to view templates, if you want to customize them */
                'maxUploadFileSize' => null, /* a file size limit for uploaded file, set null to disable */
                'defaultBucket' => 'bucket', /* a name of bucket, used by default, if null, an alphabetically first bucket will be used */
                'mimeTypes' => [
                    'apk' => 'application/vnd.android.package-archive',
                ],/* mime types list (ext => mime), used for downloaded files mime substitution. Note: that list overrides a magic.mime file information. */
                'defaultMimeType' => 'application/octet-stream', /* mime type, that be used for any file, which extension aren't included in mimeTypes parameter or in magic.mime */
                'deleteTempFiles' => true /* delete php temp files after upload */
                'instance' => null, /* String: an additional instance name (useful for connection definition, if several connections are used. Null: disabled */
                'dateFormat' => 'Y-m-d H:i:s' /* timestamp date format for created_at field. Also can be set via closure like 'dateFormat' => function() => DateTimeImmutable::createFromFormat('Y-m-d H:i:s.uO', '2009-02-15') */
            ]
        ]
    ]
    // ...
]
```

# How to handle multiple connections to different S3 servers?

It is possible to configure multiple named connections in `params.connection` section:

```php
return [
    // ...
    'modules' => [
        's3' => [
            'class' => cusodede\s3\S3Module::class,
            'defaultRoute' => 'index',
            'params' => [
                'connection' => [
                    'FirstS3Connection' => [
                        'host' => 'minio_host_one',
                        'login' => 'minio_user',
                        'password' => 'minio_password',
                        'connect_timeout' => 10,
                        'timeout' => 10,
                        'cert_path' => null,
                        'cert_password' => null,
                        'defaultBucket' => 'first_host_bucket' /* note that you can set default bucket for each connection separately */
                    ],
                    'SecondS3Connection' => [
                        'host' => 'minio_host_two',
                        'login' => 'minio_user',
                        'password' => 'minio_password',
                        'connect_timeout' => 10,
                        'timeout' => 10,
                        'cert_path' => null,
                        'cert_password' => null,
                        'defaultBucket' => 'second_host_bucket'
                    ]
                ],
                'tableName' => 'sys_cloud_storage',
                'tagsTableName' => 'sys_cloud_storage_tags',
                'viewPath' => './src/views/index',
                'defaultBucket' => 'testbucket',
                'maxUploadFileSize' => null,
                'deleteTempFiles' => true,
            ]
        ]
    ]
    // ...
]
```

and use them like this:

```php
S3Helper::FileToStorage($filePath, connection: 'FirstS3Connection');
```

or

```php
$s3 = new S3(['connection' => 'SecondS3Connection']);
```

`connection` parameter can be skipped, even for multiple connections configurations. In that case first
connection in list will be used.

# How to handle stream uploads via multipart/form-data?

At first,
configure [MultipartFormDataParser](https://www.yiiframework.com/doc/api/2.0/yii-web-multipartformdataparser)
as request parser for multipart/form-data:

```php
return [
    'components' => [
        'request' => [
            'parsers' => [
                'multipart/form-data' => yii\web\MultipartFormDataParser::class
            ],
        ],
        // ...
    ],
    // ...
];
```

that's all. Now it is possible to do stream uploads via `PUT` method. You can use an any proper JS-based
widget (like `limion/yii2-jquery-fileupload-widget`) to do this. See also views/index/put.php for example.

# Local tagging

It is possible to mark uploads with tags, what may be used for quick searches. Tags will be stored in the
local table and also will be assigned to S3 object. But it works only to one side: tags from S3 objects will
not be synchronized to local table. It is possible to sync local and remote tags, see
`CloudStorage::syncTagsFromS3()` and `CloudStorage::syncTagsToS3()` methods.

# Development and Testing

## Prerequisites

- Docker and Docker Compose
- PHP 8.4+ (for local development)

## Running Tests

This project supports testing with PHP 8.4 and PHP 8.5 using Docker containers.

### Docker Testing (Recommended)

The project uses a unified Docker environment for both development and testing. Services are started once and reused between test runs for maximum efficiency.

**Quick Start:**
```bash
# Start the development environment (PostgreSQL + MinIO + PHP containers)
make up

# Run all tests (PHP 8.4 and 8.5)
make test

# Stop the environment when done
make down
```

**Detailed Commands:**
```bash
# Environment management
make up            # Start all services
make down          # Stop all services  
make restart       # Restart all services
make status        # Show container status

# Testing
make test          # Run tests on both PHP versions
make test84        # Run tests on PHP 8.4 only
make test85        # Run tests on PHP 8.5 only
make quick-test    # Quick tests (no composer install)
make coverage      # Run tests with code coverage report (PHP 8.4)

# Development
make shell84       # Access PHP 8.4 container shell
make shell85       # Access PHP 8.5 container shell
make composer-install  # Install dependencies in both containers

# Setup
make build         # Build Docker images
make rebuild       # Force rebuild (after Dockerfile changes)
```

### Windows Testing

Windows users can use the same `make` commands if they have Docker Desktop and Git Bash, or use Docker Compose directly:

```cmd
# Start environment
docker compose up -d

# Run tests
docker compose exec php-8.4 vendor/bin/codecept run -v --debug
docker compose exec php-8.5 vendor/bin/codecept run -v --debug

# Stop environment  
docker compose down
```

### Local Testing (Without Docker)

Requirements:
- PHP 8.4+
- PostgreSQL
- MinIO server

1. Copy and configure environment:
   ```bash
   cp tests/.env.example tests/.env
   # Edit tests/.env with your local database and MinIO settings
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Run tests:
   ```bash
   php vendor/bin/codecept run
   
   # Run specific test suites
   php vendor/bin/codecept run unit
   php vendor/bin/codecept run functional
   php vendor/bin/codecept run console
   ```

## Test Environment

### Continuous Integration

Tests run automatically on GitHub Actions for PHP 8.4 and 8.5 with:
- **PostgreSQL 13.4** database service
- **MinIO** S3-compatible storage service
- All required PHP extensions (zip, pdo_pgsql, sockets, bcmath, pcntl, intl, mbstring)

### Local Docker Testing

The Docker setup includes:
- **PHP 8.4/8.5 containers** with all required extensions
- **PostgreSQL** for database testing
- **MinIO** S3-compatible storage server

Test configuration is defined in:
- `codeception.yml` - Main test configuration
- `tests/*.suite.yml` - Test suite configurations
- `tests/.env` - Test environment variables
- `tests/.env.ci` - CI environment variables
- `docker-compose.yml` - Unified Docker environment
- `docker/` - PHP container definitions
