<?php

// $ dd if=/dev/zero bs=1M count=2k status=progress | php examples/write-stream.php 2g.null 2147483648
//
// Note that S3 requires a `$contentLength` argument to be known upfront and
// match the complete stream contents size in total number of bytes that will be
// written to this stream.

if (DIRECTORY_SEPARATOR === '\\') {
    echo 'Error: Console I/O not supported on Windows' . PHP_EOL;
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';

(Dotenv\Dotenv::create(__DIR__))->load();

$s3 = new Clue\React\S3\S3Client(getenv('S3_KEY'), getenv('S3_SECRET'), getenv('S3_BUCKET'), getenv('S3_REGION'), getenv('S3_ENDPOINT'));

$stream = $s3->writeStream($argv[1] ?? 'demo.txt', $argv[2] ?? null);

$stream->on('error', function (Exception $e) { echo fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL); });
$stream->on('close', function () { fwrite(STDERR, '[CLOSED]' . PHP_EOL); });

$stdin = new React\Stream\ReadableResourceStream(STDIN);
$stdin->pipe($stream);
