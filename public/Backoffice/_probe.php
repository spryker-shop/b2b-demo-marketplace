<?php
if (isset($_GET['__probe'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    header('Content-Type: text/plain');

    $target = __DIR__ . '/errorpage/5xx.html';

    echo "__DIR__            = " . __DIR__ . PHP_EOL;
    echo "PHP realpath(file)  = " . var_export(realpath($target), true) . PHP_EOL;
    echo "file_exists?        = " . (file_exists($target) ? 'YES' : 'NO') . PHP_EOL;
    echo "is_readable?        = " . (is_readable($target) ? 'YES' : 'NO') . PHP_EOL;
    echo "open_basedir        = " . ini_get('open_basedir') . PHP_EOL;
    echo "DOCUMENT_ROOT       = " . ($_SERVER['DOCUMENT_ROOT'] ?? '') . PHP_EOL;
    echo "SCRIPT_FILENAME     = " . ($_SERVER['SCRIPT_FILENAME'] ?? '') . PHP_EOL;
    echo "readlink(/app)      = " . (@readlink('/app') ?: 'n/a') . PHP_EOL;

    echo PHP_EOL . "scandir(__DIR__.'/errorpage'):" . PHP_EOL;
    $list = @scandir(__DIR__ . '/errorpage');
    var_export($list);
    exit;
}
