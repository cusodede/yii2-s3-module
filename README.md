# yii2-s3-module

Модуль поддержки S3

# Установка

# Подключение и настройки

```php
$config = [
    'modules' => [
        's3' => [
            'class' => app\modules\s3\S3Module::class,
            'params' => [
                'connection' => [
                    'host' => 'minio_host',
                    'login' => 'minio_user',
                    'password' => 'minio_password',
                    'connect_timeout' => 10, /* http connection timeout */,
                    'timeout' => 10, /* http timeout */,
                    'cert_path' => '@app/config/cert.crt', /*path to ssl certificate, set null to disable*/
                    'cert_password' => 'ssl_sertificate_password /*set null, if sertificate has no password*/
                ],
                'maxUploadFileSize' => null, /*file size limit for uploaded file, set null to disable*/
                'defaultBucket' => 'bucket', /*name of bucket, used by default, if null, alphabetically first bucket will be used*/,
                'mimeTypes' => [
                    'apk' => 'application/vnd.android.package-archive',
                ],/* mime types list (ext => mime), used for downloaded files mime substitution. Note: that list overrides a magic.mime file information. */
                'defaultMimeType' => 'application/octet-stream' /*mime type, that be used for any file, which extension has not included in mimeTypes parameter or in magic.mime*/
            ]
        ] 
    ]
]
