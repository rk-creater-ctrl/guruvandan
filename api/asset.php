<?php

$path = isset($_GET['path']) ? (string) $_GET['path'] : '';
$path = str_replace('\\', '/', $path);

if ($path === '' || str_contains($path, '..') || ! preg_match('/\A(?:css|js)\/[A-Za-z0-9._-]+\.(?:css|js)\z/', $path)) {
    http_response_code(404);
    exit;
}

$file = __DIR__.'/../public/assets/'.$path;

if (! is_file($file)) {
    http_response_code(404);
    exit;
}

$extension = pathinfo($file, PATHINFO_EXTENSION);
header('Content-Type: '.($extension === 'css' ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8'));
header('Cache-Control: public, max-age=31536000, immutable');
readfile($file);
