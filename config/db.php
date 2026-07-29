<?php

use app\components\Environment;

require_once dirname(__DIR__) . '/components/Environment.php';

return [
    'class' => 'yii\db\Connection',
    'dsn' => Environment::string('DB_DSN', 'mysql:host=localhost;dbname=sistema_inversiones'),
    'username' => Environment::string('DB_USERNAME', 'root'),
    'password' => Environment::string('DB_PASSWORD'),
    'charset' => 'utf8mb4',
    'enableSchemaCache' => Environment::string('APP_ENV', 'dev') === 'prod',
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];
