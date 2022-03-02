<?php

require __DIR__ . '/../vendor/autoload.php';

(Dotenv\Dotenv::create(__DIR__))->load();

$browser = new React\Http\Browser();
$s3 = new Clue\React\S3\S3Client(getenv('S3_KEY'), getenv('S3_SECRET'), getenv('S3_BUCKET'), getenv('S3_REGION'), getenv('S3_ENDPOINT'), $browser);

function loga($msg) {
    // prepend message with date/time with millisecond precision
    $time = microtime(true);
    echo date('Y-m-d H:i:s', (int)$time) . sprintf('.%03d ', ($time - (int)$time) * 1000) . $msg . PHP_EOL;
}

for ($i = 1; isset($argv[$i]); ++$i) {
    $url = $argv[$i];
    loga('Downloading ' . $url);
    $browser->get($url)->then(function (Psr\Http\Message\ResponseInterface $response) use ($url, $s3) {
        loga('Downloaded ' . $url . ' (' . $response->getBody()->getSize() . ' bytes)');
        $s3->write(basename($url), (string)$response->getBody())->then(function () use ($url) {
            loga('Uploaded ' . $url);
        });
    });
}
