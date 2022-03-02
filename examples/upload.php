<?php

// $ php examples/upload.php demo.txt
// $ php examples/upload.php demo.txt folder/target.txt

if (DIRECTORY_SEPARATOR === '\\') {
    echo 'Error: File I/O not supported on Windows' . PHP_EOL;
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';

(Dotenv\Dotenv::create(__DIR__))->load();

$s3 = new Clue\React\S3\S3Client(getenv('S3_KEY'), getenv('S3_SECRET'), getenv('S3_BUCKET'), getenv('S3_REGION'), getenv('S3_ENDPOINT'));

$source = new React\Stream\ReadableResourceStream($resource = fopen($argv[1] ?? 'demo.txt', 'r'));

$stream = $s3->writeStream($argv[2] ?? basename($argv[1] ?? 'demo.txt'), fstat($resource)['size']);
$source->pipe($stream);

$stream->on('error', function (Exception $e) { echo $e->getMessage() . PHP_EOL; });
$stream->on('close', function () { echo '[CLOSED output]' . PHP_EOL; });

$source->on('error', function (Exception $e) { echo $e->getMessage() . PHP_EOL; });
$source->on('close', function () { echo '[CLOSED input]' . PHP_EOL; });
