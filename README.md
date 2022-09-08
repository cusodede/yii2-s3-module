# yii2-s3-module

![GitHub Workflow Status](https://img.shields.io/github/workflow/status/cusodede/yii2-s3-module/CI%20with%20PostgreSQL)

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

# Running local tests

Copy `tests/.env.example` to `tests/.env`, and set configuration corresponding to your local environment. Then
run `php vendor/bin/codecept run` command.

# Running test in docker

Copy `tests/.env.example` to `tests/.env`, then run `make build && make test` command.
