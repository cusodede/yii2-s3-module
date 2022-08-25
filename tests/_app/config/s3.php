<?php
declare(strict_types = 1);

/*При наличии одноимённого файла в подкаталоге /local конфигурация будет взята оттуда*/

use cusodede\s3\S3Module;

return [
	'class' => S3Module::class,
	'defaultRoute' => 'index',
	'params' => [
		'connection' => [
			'host' => $_ENV['MINIO_HOST'],
			'login' => $_ENV['MINIO_ROOT_USER'],
			'password' => $_ENV['MINIO_ROOT_PASSWORD'],
			'connect_timeout' => 10, /* http connection timeout */
			'timeout' => 10, /* http timeout */
			'cert_path' => null, /* path to ssl certificate, set null to disable */
			'cert_password' => null /* certificate password, set null, if certificate has no password */
		],
		'tableName' => 'sys_cloud_storage', /* table with storage data info*/
		'tagsTableName' => 'sys_cloud_storage_tags', /* table with local tags */
		'viewPath' => './src/views/index', /* path to view templates, if you want to customize them */
		'maxUploadFileSize' => null, /* file size limit for uploaded file, set null to disable */
		'defaultBucket' => 'testbucket', /* name of bucket, used by default, if null, alphabetically first bucket will be used */
		'mimeTypes' => [
			'apk' => 'application/vnd.android.package-archive',
		],/* mime types list (ext => mime), used for downloaded files mime substitution. Note: that list overrides a magic.mime file information. */
		'defaultMimeType' => 'application/octet-stream', /* mime type, that be used for any file, which extension has not included in mimeTypes parameter or in magic.mime */
		'deleteTempFiles' => true, /* delete php temp files after upload */
	]
];