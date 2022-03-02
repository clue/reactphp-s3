<?php

// $ php examples/write.php

require __DIR__ . '/../vendor/autoload.php';

(Dotenv\Dotenv::create(__DIR__))->load();

$s3 = new Clue\React\S3\S3Client(getenv('S3_KEY'), getenv('S3_SECRET'), getenv('S3_BUCKET'), getenv('S3_REGION'), getenv('S3_ENDPOINT'));

$s3->write($argv[1] ?? 'demo.txt', $argv[2] ?? 'hello wörld')->then(function (int $bytes) {
    echo $bytes . ' bytes written' . PHP_EOL;
}, function ($e) {
    echo $e->getMessage() . PHP_EOL;
});
