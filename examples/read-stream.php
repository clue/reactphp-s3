<?php

// $ php examples/read-stream.php 2g.null | dd of=/dev/null status=progress

require __DIR__ . '/../vendor/autoload.php';

(Dotenv\Dotenv::create(__DIR__))->load();

$s3 = new Clue\React\S3\S3Client(getenv('S3_KEY'), getenv('S3_SECRET'), getenv('S3_BUCKET'), getenv('S3_REGION'), getenv('S3_ENDPOINT'));

$stream = $s3->readStream($argv[1] ?? 'demo.txt');

$stream->on('data', function ($chunk) { echo $chunk; });
$stream->on('error', function (Exception $e) { fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL); });
$stream->on('close', function () { fwrite(STDERR, '[CLOSED]' . PHP_EOL); });
