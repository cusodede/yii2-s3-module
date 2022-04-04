<?php
declare(strict_types = 1);

use yii\db\Connection;

return [
	'class' => Connection::class,
    'dsn' => getenv('DB_DSN'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PASS'),
	'enableSchemaCache' => false,
];