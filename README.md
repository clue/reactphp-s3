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
$loop = React\EventLoop\Factory::create();
$browser = new React\Http\Browser($loop);

$s3 = new Clue\React\S3\Client($browser, $key, $secret, $bucket, $region, $endpoint);

$s3->read('example.txt')->then(function (string $contents) {
    echo $contents;
});

$loop->run();
```

## Install

[![A clue·access project](https://raw.githubusercontent.com/clue-access/clue-access/main/clue-access.png)](https://github.com/clue-access/clue-access)

*This project is currently under active development,
you're looking at a temporary placeholder repository.*

Do you want early access to my unreleased projects?
You can either be patient and wait for general availability or
consider becoming a [sponsor on GitHub](https://github.com/sponsors/clue) for early access.

Do you sponsor me on GitHub? Thank you for supporting sustainable open-source, you're awesome!
The prototype is available here: [https://github.com/clue-access/reactphp-s3](https://github.com/clue-access/reactphp-s3).

Support open-source and join [**clue·access**](https://github.com/clue-access/clue-access) ❤️

## License

This project will be released under the permissive [MIT license](LICENSE).

> Did you know that I offer custom development services and issuing invoices for
  sponsorships of releases and for contributions? Contact me (@clue) for details.
