<?php

// $ php examples/download.php 2g.null
// $ php examples/download.php 2g.null dest/2g.null

if (DIRECTORY_SEPARATOR === '\\') {
    echo 'Error: File I/O not supported on Windows' . PHP_EOL;
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';

(Dotenv\Dotenv::create(__DIR__))->load();

$s3 = new Clue\React\S3\S3Client(getenv('S3_KEY'), getenv('S3_SECRET'), getenv('S3_BUCKET'), getenv('S3_REGION'), getenv('S3_ENDPOINT'));

$stream = $s3->readStream($argv[1] ?? 'demo.txt');

$target = new React\Stream\WritableResourceStream(fopen($argv[2] ?? basename($argv[1] ?? 'demo.txt'), 'w'));
$stream->pipe($target);

$stream->on('error', function (Exception $e) { echo $e->getMessage() . PHP_EOL; });
$stream->on('close', function () { echo '[CLOSED input]' . PHP_EOL; });

$target->on('error', function (Exception $e) { echo $e->getMessage() . PHP_EOL; });
$target->on('close', function () { echo '[CLOSED output]' . PHP_EOL; });
