<?php

foreach ([
    '/tmp/guruvandan',
    '/tmp/guruvandan/cache',
    '/tmp/guruvandan/logs',
    '/tmp/guruvandan/sessions',
    '/tmp/guruvandan/views',
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

putenv('VIEW_COMPILED_PATH=/tmp/guruvandan/views');
$_ENV['VIEW_COMPILED_PATH'] = '/tmp/guruvandan/views';
$_SERVER['VIEW_COMPILED_PATH'] = '/tmp/guruvandan/views';

foreach ([
    'APP_PACKAGES_CACHE' => '/tmp/guruvandan/cache/packages.php',
    'APP_SERVICES_CACHE' => '/tmp/guruvandan/cache/services.php',
    'APP_CONFIG_CACHE' => '/tmp/guruvandan/cache/config.php',
    'APP_ROUTES_CACHE' => '/tmp/guruvandan/cache/routes.php',
    'APP_EVENTS_CACHE' => '/tmp/guruvandan/cache/events.php',
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../public/index.php';
