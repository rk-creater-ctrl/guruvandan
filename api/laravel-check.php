<?php

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
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $result['step'] = 'database_query';
    $result['database'] = [
        'connection' => config('database.default'),
        'users' => Illuminate\Support\Facades\DB::table('users')->count(),
        'teachers' => Illuminate\Support\Facades\DB::table('teachers')->count(),
        'settings' => Illuminate\Support\Facades\DB::table('settings')->count(),
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
