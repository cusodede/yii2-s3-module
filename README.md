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
php composer.phar require cusodede/yii2-s3-module "dev-master"
```

or add

```
"cusodede/yii2-s3-module": "dev-master"
```

to the require section of your `composer.json` file.

# Configuration and options

See example config below:

```php
$config = [
    'modules' => [
        's3' => [
            'class' => app\modules\s3\S3Module::class,
            'defaultRoute' => 'index',
            'params' => [
                'connection' => [
                    'host' => 'minio_host',
                    'login' => 'minio_user',
                    'password' => 'minio_password',
                    'connect_timeout' => 10, /* http connection timeout */,
                    'timeout' => 10, /* http timeout */,
                    'cert_path' => null, /* path to ssl certificate, set null to disable */
                    'cert_password' => null /* sertificate password, set null, if sertificate has no password */
                ],
                'viewPath' => '@vendor/cusodede/yii2-s3-module/src/views/index', /* path to view templates, if you want to customize them */
                'maxUploadFileSize' => null, /* file size limit for uploaded file, set null to disable */
                'defaultBucket' => 'bucket', /* name of bucket, used by default, if null, alphabetically first bucket will be used */,
                'mimeTypes' => [
                    'apk' => 'application/vnd.android.package-archive',
                ],/* mime types list (ext => mime), used for downloaded files mime substitution. Note: that list overrides a magic.mime file information. */
                'defaultMimeType' => 'application/octet-stream' /* mime type, that be used for any file, which extension has not included in mimeTypes parameter or in magic.mime */
            ]
        ] 
    ]
]
