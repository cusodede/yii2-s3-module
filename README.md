# yii2-s3-module

S3 support module (file-manager and stuff).

# Installation with Composer

Add

```
{
	"type": "vcs",
	"url": "https://github.com/cusodede/yii2-s3-module"
}
```

to repositories section of your `composer.json` file, then run

```
php composer.phar require cusodede/yii2-s3-module "^1.0.0"
```

or add

```
"cusodede/yii2-s3-module": "^1.0.0"
```

to the require section of your `composer.json` file.

# Module migration

Module needs to store file data in database table, which will be created by

```
php yii migrate/up --migrationPath=@vendor/cusodede/yii2-s3-module/migrations
```

command. You can customize table name by define `tableName` parameter in module configuration.

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
                'tableName' => 'sys_cloud_storage', /* table with storage data, see Module migration section*/
                'viewPath' => '@vendor/cusodede/yii2-s3-module/src/views/index', /* path to view templates, if you want to customize them */
                'maxUploadFileSize' => null, /* file size limit for uploaded file, set null to disable */
                'defaultBucket' => 'bucket', /* name of bucket, used by default, if null, alphabetically first bucket will be used */
                'mimeTypes' => [
                    'apk' => 'application/vnd.android.package-archive',
                ],/* mime types list (ext => mime), used for downloaded files mime substitution. Note: that list overrides a magic.mime file information. */
                'defaultMimeType' => 'application/octet-stream', /* mime type, that be used for any file, which extension has not included in mimeTypes parameter or in magic.mime */
                'deleteTempFiles' => true /* delete php temp files after upload */
            ]
        ]
    ]
    // ...
]
```

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

that's all. Now it is possible to do stream uploads via `PUT` method. You can use a any proper JS-based
widget (like `limion/yii2-jquery-fileupload-widget`) to do this. See also views/index/put.php for example.

# Running local tests

Copy `.env.example` to `.env`, and set configuration corresponding to your local environment. Then run `php vendor/bin/codecept run` command.