<?php

$compiledPath = '/tmp/storage/framework/views';
if (!is_dir($compiledPath)) {
    mkdir($compiledPath, 0755, true);
}
putenv('VIEW_COMPILED_PATH=' . $compiledPath);
$_ENV['VIEW_COMPILED_PATH'] = $compiledPath;

require __DIR__ . '/../public/index.php';
