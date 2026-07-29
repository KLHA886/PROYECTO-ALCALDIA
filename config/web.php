<?php

use app\components\Environment;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$mailerDsn = Environment::string('MAILER_DSN');
$isProduction = Environment::string('APP_ENV', 'dev') === 'prod';
$mailerConfig = [
    'class' => \yii\symfonymailer\Mailer::class,
    'useFileTransport' => $mailerDsn === '',
    'viewPath' => '@app/mail',
];
if ($mailerDsn !== '') {
    $mailerConfig['transport'] = $mailerDsn;
}

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'language' => 'es-EC',
    'bootstrap' => ['log'],
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => $mailerConfig,
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => Environment::string(
                'APP_COOKIE_VALIDATION_KEY',
                'r1WQX_VezjaQ5hxwheUOIc6eA_s9SWfu',
            ),
            'csrfCookie' => [
                'httpOnly' => true,
                'sameSite' => \yii\web\Cookie::SAME_SITE_LAX,
                'secure' => $isProduction,
            ],
        ],
        'response' => [
            'on beforeSend' => static function (\yii\base\Event $event) use ($isProduction): void {
                $headers = $event->sender->headers;
                $headers->set('X-Content-Type-Options', 'nosniff');
                $headers->set('X-Frame-Options', 'SAMEORIGIN');
                $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
                $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
                if ($isProduction && Yii::$app->request->isSecureConnection) {
                    $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
                }
            },
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => true,
            'identityCookie' => [
                'name' => '_identity',
                'httpOnly' => true,
                'sameSite' => \yii\web\Cookie::SAME_SITE_LAX,
                'secure' => $isProduction,
            ],
        ],
        'session' => [
            'cookieParams' => [
                'httponly' => true,
                'samesite' => \yii\web\Cookie::SAME_SITE_LAX,
                'secure' => $isProduction,
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        /*
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        */
    ],
    'modules' => [
        'inversion' => [
            'class' => \app\modules\inversion\Module::class,
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
