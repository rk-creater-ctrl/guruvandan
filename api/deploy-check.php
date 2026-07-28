<?php

header('Content-Type: application/json');

$env = static fn (string $key): ?string => getenv($key) !== false ? (string) getenv($key) : null;
$present = static fn (string $key): bool => filled($env($key));

$database = [
    'configured' => $present('DB_CONNECTION') && $present('DB_HOST') && $present('DB_DATABASE') && $present('DB_USERNAME') && $present('DB_PASSWORD'),
    'connection' => $env('DB_CONNECTION'),
    'host' => $env('DB_HOST'),
    'port' => $env('DB_PORT'),
    'database' => $env('DB_DATABASE'),
    'username_present' => $present('DB_USERNAME'),
    'password_present' => $present('DB_PASSWORD'),
    'reachable' => false,
    'error' => null,
];

if ($database['configured'] && $database['connection'] === 'pgsql') {
    try {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            $env('DB_HOST'),
            $env('DB_PORT') ?: '5432',
            $env('DB_DATABASE'),
            $env('DB_SSLMODE') ?: 'require',
        );
        $pdo = new PDO($dsn, $env('DB_USERNAME'), $env('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $database['reachable'] = (bool) $pdo->query('select 1')->fetchColumn();
    } catch (Throwable $exception) {
        $database['error'] = $exception->getMessage();
    }
}

echo json_encode([
    'ok' => true,
    'php_version' => PHP_VERSION,
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_pgsql' => extension_loaded('pdo_pgsql'),
        'pgsql' => extension_loaded('pgsql'),
        'openssl' => extension_loaded('openssl'),
        'mbstring' => extension_loaded('mbstring'),
        'fileinfo' => extension_loaded('fileinfo'),
        'dom' => extension_loaded('dom'),
        'zip' => extension_loaded('zip'),
    ],
    'app' => [
        'app_env' => $env('APP_ENV'),
        'app_key_present' => $present('APP_KEY'),
        'app_url' => $env('APP_URL'),
        'debug' => $env('APP_DEBUG'),
        'view_compiled_path' => $env('VIEW_COMPILED_PATH'),
    ],
    'database' => $database,
    'storage' => [
        'disk' => $env('FILESYSTEM_DISK'),
        'bucket_present' => $present('AWS_BUCKET'),
        'endpoint_present' => $present('AWS_ENDPOINT'),
        'access_key_present' => $present('AWS_ACCESS_KEY_ID'),
        'secret_key_present' => $present('AWS_SECRET_ACCESS_KEY'),
    ],
], JSON_PRETTY_PRINT);

function filled(?string $value): bool
{
    return $value !== null && trim($value) !== '';
}
