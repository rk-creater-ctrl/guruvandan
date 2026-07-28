<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

header('Content-Type: application/json');

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

$result = [
    'ok' => false,
    'step' => 'start',
    'php_version' => PHP_VERSION,
];

try {
    $result['step'] = 'autoload';
    require __DIR__.'/../vendor/autoload.php';

    $result['step'] = 'bootstrap_app';
    $app = require __DIR__.'/../bootstrap/app.php';

    $result['step'] = 'console_bootstrap';
    $app->make(Kernel::class)->bootstrap();

    $result['step'] = 'database_query';
    $result['database'] = [
        'connection' => config('database.default'),
        'users' => DB::table('users')->count(),
        'teachers' => DB::table('teachers')->count(),
        'settings' => DB::table('settings')->count(),
    ];

    $result['step'] = 'view_render';
    view('public.home', [
        'teachers' => collect(),
        'teacherTotal' => 0,
        'tributeTotal' => 0,
        'featuredTributes' => collect(),
        'event' => null,
        'isRevealed' => false,
    ])->render();

    $result['ok'] = true;
    $result['step'] = 'complete';
} catch (Throwable $exception) {
    $result['error'] = [
        'class' => $exception::class,
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT);
