<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                'useFileTransport' => true,
                'viewPath' => '@app/mail',
            ],
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'db' => $db,
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@app/mail',
            'useFileTransport' => false,  // false для реальной отправки, true для теста (письма в файл)
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.gmail.com',       // SMTP сервер
                'username' => 'ibaleksandrov1988@gmail.com',  // Твой email
                'password' => 'kafs rloj qvhb dnjv',     // Пароль приложения (16 цифр)
                'port' => '587',                       // Порт (587 для TLS, 465 для SSL)
                'encryption' => 'tls',                 // tls или ssl
            ],
        ],
        // ===== ДОБАВЛЯЕМ ЛОГИРОВАНИЕ =====
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning', 'info'],
                    'logFile' => '@runtime/logs/app.log',
                ],
            ],
        ],

        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'request' => [
            'cookieValidationKey' => 'asdasdasdad',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        // ===== ПЕРЕМЕЩАЕМ urlManager ВНУТРЬ components =====
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                ['class' => 'yii\rest\UrlRule', 'controller' => 'character'],
                ['class' => 'yii\rest\UrlRule', 'controller' => 'inventory'],
                ['class' => 'yii\rest\UrlRule', 'controller' => 'auction'],
                'auth/register' => 'auth/register',
                'auth/login' => 'auth/login',
                'auth/check' => 'auth/check',
                'inventory/drop' => 'inventory/drop',
                'character/levelup' => 'character/levelup',

'AUCTION/list' => 'auction/list',
'AUCTION/my' => 'auction/my',
'AUCTION/create' => 'auction/create',
'AUCTION/buy' => 'auction/buy',
'AUCTION/cancel' => 'auction/cancel',
'talents' => 'talent/index',
        'talents/learn' => 'talent/learn',
        'talents/reset' => 'talent/reset',
                'blacksmith/items' => 'blacksmith/items',
'blacksmith/gems' => 'blacksmith/gems',
'blacksmith/insert' => 'blacksmith/insert',
'blacksmith/remove' => 'blacksmith/remove',
'blacksmith/all-sockets' => 'blacksmith/all-sockets',
            ],
        ],
    ],
    'params' => [
        'jwt.issuer' => 'exorcist',
        'jwt.audience' => 'exorcist',
    ],
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
    ];
}

return $config;