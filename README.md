# clue/reactphp-s3

Async S3 filesystem API (supporting Amazon S3, Ceph, MiniIO, DigitalOcean Spaces and others),
built on top of [ReactPHP](https://reactphp.org/).

**Table of contents**

* [Quickstart example](#quickstart-example)
* [Install](#install)
* [License](#license)

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

## Install

[![A clue·access project](https://raw.githubusercontent.com/clue-access/clue-access/main/clue-access.png)](https://github.com/clue-access/clue-access)

*This project is currently under active development,
you're looking at a temporary placeholder repository.*

The code is available in early access to my sponsors here: https://github.com/clue-access/reactphp-s3

Do you sponsor me on GitHub? Thank you for supporting sustainable open-source, you're awesome! ❤️ Have fun with the code! 🎉

Seeing a 404 (Not Found)? Sounds like you're not in the early access group. Consider becoming a [sponsor on GitHub](https://github.com/sponsors/clue) for early access. Check out [clue·access](https://github.com/clue-access/clue-access) for more details.

This way, more people get a chance to take a look at the code before the public release.

Rock on 🤘

## License

This project will be released under the permissive [MIT license](LICENSE).

> Did you know that I offer custom development services and issuing invoices for
  sponsorships of releases and for contributions? Contact me (@clue) for details.
