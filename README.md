# clue/reactphp-s3

Async S3 filesystem API (supporting Amazon S3, Ceph, MiniIO, DigitalOcean Spaces and others),
built on top of [ReactPHP](https://reactphp.org/).

**Table of contents**

* [Support us](#support-us)
* [Quickstart example](#quickstart-example)
* [Usage](#usage)
    * [Methods](#methods)
    * [Promises](#promises)
    * [Cancellation](#cancellation)
    * [Timeouts](#timeouts)
    * [Blocking](#blocking)
    * [Streaming](#streaming)
* [API](#api)
    * [S3Client](#s3client)
        * [ls()](#ls)
        * [read()](#read)
        * [readStream()](#readstream)
        * [write()](#write)
        * [writeStream()](#writestream)
        * [delete()](#delete)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Support us

[![A clue·access project](https://raw.githubusercontent.com/clue-access/clue-access/main/clue-access.png)](https://github.com/clue-access/clue-access)

*This project is currently under active development,
you're looking at a temporary placeholder repository.*

The code is available in early access to my sponsors here: https://github.com/clue-access/reactphp-s3

Do you sponsor me on GitHub? Thank you for supporting sustainable open-source, you're awesome! ❤️ Have fun with the code! 🎉

Seeing a 404 (Not Found)? Sounds like you're not in the early access group. Consider becoming a [sponsor on GitHub](https://github.com/sponsors/clue) for early access. Check out [clue·access](https://github.com/clue-access/clue-access) for more details.

This way, more people get a chance to take a look at the code before the public release.

## Quickstart example

Once [installed](#install), you can use the following code to read a file from
your S3 file storage:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$s3 = new Clue\React\S3\S3Client($key, $secret, $bucket, $region, $endpoint);

$s3->read('example.txt')->then(function (string $contents) {
    echo $contents;
}, function (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
});
```

See also the [examples](examples).

## Usage

### Methods

Most importantly, this project provides a [`S3Client`](#s3client) object that offers
several methods that resemble a filesystem-like API to access your files and
directories:

```php
$s3 = new Clue\React\S3\S3Client($key, $secret, $bucket, $region, $endpoint);

$s3->ls($path);
$s3->read($path);
$s3->write($path, $contents);
$s3->delete($path);
```

Each of the above methods supports async operation and either *fulfills* with
its result or *rejects* with an `Exception`.
Please see the following chapter about [promises](#promises) for more details.

### Promises

Sending requests is async (non-blocking), so you can actually send multiple requests in parallel.
S3 will respond to each request with a response message, the order is not guaranteed.
Sending requests uses a [Promise](https://github.com/reactphp/promise)-based interface
that makes it easy to react to when a request is completed (i.e. either successfully fulfilled or rejected with an error).

```php
$s3->read($path)->then(
    function (string $contents) {
        // file contents received
    },
    function (Exception $e) {
        // an error occured while executing the request
    }
});
```

If this looks strange to you, you can also use the more traditional [blocking API](#blocking).

### Cancellation

The returned Promise is implemented in such a way that it can be cancelled
when it is still pending.
Cancelling a pending promise will reject its value with an Exception and
clean up any underlying resources.

```php
$promise = $s3->read('example.txt');

Loop::addTimer(2.0, function () use ($promise) {
    $promise->cancel();
});
```

### Timeouts

This library uses a very efficient HTTP implementation, so most S3 requests
should usually be completed in mere milliseconds. However, when sending S3
requests over an unreliable network (the internet), there are a number of things
that can go wrong and may cause the request to fail after a time. As such,
timeouts are handled by the underlying HTTP library and this library respects
PHP's `default_socket_timeout` setting (default 60s) as a timeout for sending the
outgoing S3 request and waiting for a successful response and will otherwise
cancel the pending request and reject its value with an `Exception`.

Note that this timeout value covers creating the underlying transport connection,
sending the request, waiting for the remote service to process the request
and receiving the full response. To use a custom timeout value, you can
pass the timeout to the [underlying `Browser`](https://github.com/reactphp/http#timeouts)
like this:

```php
$browser = new React\Http\Browser();
$browser = $browser->withTimeout(10.0);

$s3 = new Clue\React\S3\S3Client($key, $secret, $bucket, $region, $endpoint, $browser);

$s3->read('example.txt')->then(function (string $contents) {
    // contents received within 10 seconds maximum
    var_dump($contents);
});
```

Similarly, you can use a negative timeout value to not apply a timeout at all
or use a `null` value to restore the default handling. Note that the underlying
connection may still impose a different timeout value. See also the underlying
[timeouts documentation](https://github.com/reactphp/http#timeouts) for more details.

### Blocking

As stated above, this library provides you a powerful, async API by default.

If, however, you want to integrate this into your traditional, blocking environment,
you should look into also using [clue/reactphp-block](https://github.com/clue/reactphp-block).

The resulting blocking code could look something like this:

```php
use Clue\React\Block;

$s3 = new Clue\React\S3\S3Client($key, $secret, $bucket, $region, $endpoint);

$promise = $s3->read($path);

try {
    $contents = Block\await($promise, Loop::get());
    // file contents received
} catch (Exception $e) {
    // an error occured while executing the request
}
```

Similarly, you can also process multiple requests concurrently and await an array of results:

```php
$promises = [
    $s3->read('example.txt'),
    $s3->read('folder/demo.txt')
];

$results = Block\awaitAll($promises, Loop::get());
```

Please refer to [clue/reactphp-block](https://github.com/clue/reactphp-block#readme) for more details.

### Streaming

The following API endpoints expose the file contents as a string:

```php
$s3->read($path);
$s3->write($path, $contents);
````

Keep in mind that this means the whole string has to be kept in memory.
This is easy to get started and works reasonably well for smaller files.

For bigger files it's usually a better idea to use a streaming approach,
where only small chunks have to be kept in memory.
This works for (any number of) files of arbitrary sizes.

The [`S3Client::readStream()`](#readstream) method complements the default
Promise-based [`S3Client::read()`](#read) API and returns an instance implementing
[`ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface) instead:

```php
$stream = $s3->readStream($path);

$stream->on('data', function (string $chunk) {
    echo $chunk;
});

$stream->on('error', function (Exception $error) {
    echo 'Error: ' . $error->getMessage() . PHP_EOL;
});

$stream->on('close', function () {
    echo '[DONE]' . PHP_EOL;
});
```

The [`S3Client::writeStream()`](#writestream) method complements the default
Promise-based [`S3Client::write()`](#write) API and returns an instance implementing
[`WritableStreamInterface`](https://github.com/reactphp/stream#writablestreaminterface) instead:

```php
$stream = $s3->writeStream('folder/image.jpg', 10);

$stream->write('hello');
$stream->end('world');

$stream->on('error', function (Exception $error) {
    echo 'Error: ' . $error->getMessage() . PHP_EOL;
});

$stream->on('close', function () {
    echo '[CLOSED]' . PHP_EOL;
});
```

## API

### S3Client

The `S3Client` class is responsible for communication with your S3 file storage
and assembling and sending HTTP requests. It requires your S3 credentials in order to
authenticate your requests:

```php
$s3 = new Clue\React\S3\S3Client($key, $secret, $bucket, $region, $endpoint);
```

This class takes an optional `Browser|null $browser` parameter that can be used to
pass the browser instance to use for this object.
If you need custom connector settings (DNS resolution, TLS parameters, timeouts,
proxy servers etc.), you can explicitly pass a custom instance of the
[`ConnectorInterface`](https://github.com/reactphp/socket#connectorinterface)
to the [`Browser`](https://github.com/reactphp/http#browser) instance
and pass it as an additional argument to the `S3Client` like this:

```php
$connector = new React\Socket\Connector([
    'dns' => '127.0.0.1',
    'tcp' => [
        'bindto' => '192.168.10.1:0'
    ],
    'tls' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$browser = new React\Http\Browser($connector);
$s3 = new Clue\React\S3\S3Client($key, $secret, $bucket, $region, $endpoint, $browser);
```

#### ls()

The `ls(string $path): PromiseInterface<string[], Exception>` method can be used to
list all objects (files and directories) in the given directory `$path`.

```php
$s3->ls('folder/')->then(function (array $files) {
    foreach ($files as $file) {
        echo $file . PHP_EOL;
    }
});
```

Similarly, you can use an empty `$path` to list all objects in the root
path (the bucket itself).

Note that S3 doesn't have a concept of "files" and "directories", but
this API aims to work more filesystem-like. All objects sharing a common
prefix delimited by a slash (e.g. "folder/") are considered a "directory"
entry. This method will only report objects directly under `$path` prefix
and will not recurse into deeper directory paths. All file objects will
always be returned as the file name component relative to the given
`$path`, all directory objects will always be returned with a trailing
slash (e.g. "folder/").

#### read()

The `read(string $path): PromiseInterface<string, Exception>` method can be used to
read (download) the given object located at `$path`.

```php
$s3->read('folder/image.jpg')->then(function (string $contents) {
    echo 'file is ' . strlen($contents) . ' bytes' . PHP_EOL;
});
```

Keep in mind that due to resolving with the file contents as a `string`
variable, this API has to keep the complete file contents in memory when
the Promise resolves. This is easy to get started and works reasonably
well for smaller files. If you're dealing with bigger files (or files
with unknown sizes), it's usually a better idea to use a streaming
approach, where only small chunks have to kept in memory. See also
`readStream()` for more details.

#### readStream()

The `readStream(string $path): ReadableStreamInterface<string>` method can be used to
read (download) the given object located at `$path` as a readable stream.

```php
$stream = $s3->readStream('folder/image.jpg');

$stream->on('data', function (string $chunk) {
    echo $chunk;
});

$stream->on('error', function (Exception $error) {
    echo 'Error: ' . $error->getMessage() . PHP_EOL;
});

$stream->on('close', function () {
    echo '[CLOSED]' . PHP_EOL;
});
```

This works for files of arbitrary sizes as only small chunks have to
be kept in memory. The resulting stream is a well-behaving readable stream
that will emit the normal stream events, see also
[`ReadableStreamInterface`](https://github.com/reactphp/stream#readablestreaminterface).

#### write()

The `write(string $path, string $contents): PromiseInterface<int, Exception>` method can be used to
write (upload) the given `$contents` to the object located at `$path`.

```php
$s3->write('folder/image.jpg', $contents)->then(function (int $bytes) {
    echo $bytes . ' bytes written' . PHP_EOL;
});
```

Keep in mind that due to accepting the file contents as a `string`
variable, this API has to keep the complete file contents in memory when
starting the upload. This is easy to get started and works reasonably
well for smaller files. If you're dealing with bigger files (or files
with unknown sizes), it's usually a better idea to use a streaming
approach, where only small chunks have to kept in memory. See also
`writeStream()` for more details.

Note that S3 will always overwrite anything that already exists under the
given `$path`. If this information is useful to you, you may want to
check `ls()` before writing.

#### writeStream()

The `writeStream(string $path, int $contentLength): WritableStreamInterface<string>` method can be used to
write (upload) contents to the object located at `$path` as a writable stream.

```php
$stream = $s3->writeStream('folder/image.jpg', 10);

$stream->write('hello');
$stream->end('world');

$stream->on('error', function (Exception $error) {
    echo 'Error: ' . $error->getMessage() . PHP_EOL;
});

$stream->on('close', function () {
    echo '[CLOSED]' . PHP_EOL;
});
```

This works for files of arbitrary sizes as only small chunks have to
be kept in memory. The resulting stream is a well-behaving writable stream
that will emit the normal stream events, see also
[`WritableStreamInterface`](https://github.com/reactphp/stream#writablestreaminterface).

Note that S3 requires a `$contentLength` argument to be known upfront and
match the complete stream contents size in total number of bytes that
will be written to this stream.

Note that S3 will always overwrite anything that already exists under the
given `$path`. If this information is useful to you, you may want to
check `ls()` before writing.

#### delete()

The `delete(string $path): PromiseInterface<void, Exception>` method can be used to
delete the given object located at `$path`.

```php
$s3->delete('folder/image.jpg')->then(function () {
    echo 'deleted' . PHP_EOL;
});
```

This method will resolve (with no value) when deleting completes, whether
the given `$path` existed or not. If this information is useful to you,
you may want to check `ls()` before deleting.

Note that S3 doesn't have a concept of "directories", so this method will
not recurse into deeper path components. If you want to delete multiple
objects with the same prefix (e.g. "folder/"), you may want to use `ls()`
and individually delete all objects.

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org/).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This project does not yet follow [SemVer](https://semver.org/).
This will install the latest supported version:

While in [early access](#support-us), you first have to manually change your
`composer.json` to include these lines to access the supporters-only repository:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/clue-access/reactphp-s3"
        }
    ]
}
```

Then install this package as usual:

```bash
$ composer require clue/reactphp-s3:dev-main
```

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on PHP 7.0 through current PHP 8+.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org/):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ vendor/bin/phpunit
```

## License

This project is released under the permissive [MIT license](LICENSE).

> Did you know that I offer custom development services and issuing invoices for
  sponsorships of releases and for contributions? Contact me (@clue) for details.
