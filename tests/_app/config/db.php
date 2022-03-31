<?php
declare(strict_types = 1);

use yii\db\Connection;

return [
	'class' => Connection::class,
	'dsn' => "pgsql:host=localhost;dbname=s3",
	'username' => "postgres",
	'password' => "postgres",
	'enableSchemaCache' => false,
];