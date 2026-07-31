<?php

declare(strict_types=1);

require_once __DIR__ . '/../components/Environment.php';
\app\components\Environment::load(__DIR__ . '/../.env');

$environment = getenv('APP_ENV') ?: 'dev';
$debugValue = getenv('APP_DEBUG');
$debug = $debugValue === false
    ? $environment !== 'prod'
    : filter_var($debugValue, FILTER_VALIDATE_BOOL);

defined('YII_DEBUG') or define('YII_DEBUG', $debug);
defined('YII_ENV') or define('YII_ENV', $environment);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
