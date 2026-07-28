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

require __DIR__.'/../public/index.php';
