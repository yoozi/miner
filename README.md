Miner
==========

This library is part of [Project Golem](http://golem.yoozi.cn/), see [yoozi/golem](https://github.com/yoozi/golem) for more info.

Miner is a PHP library that extracting metadata and interesting text content (like author, summary, and etc.) from HTML pages.
It acts like a simplified [HTML metadata parser in Apache Tika](https://tika.apache.org/1.4/formats.html#HyperText_Markup_Language).

### WTF is Miner?

Ta-da! Consider the screenshot taken from LinkedIn below, Miner can be typically used to achieve such task like this.

![image](https://cloud.githubusercontent.com/assets/275750/2751070/1773aa32-c8ae-11e3-9de3-e022ddcb851f.png)

## Installation

The best and easy way to install the Golem package is via Composer.

1. Open your composer.json and add the following to the require array:

    ```
    "yoozi/miner": "1.0.*"
    ```

2. Run Composer to install or update the new package dependencies.

    ```
    php composer install
    ```

    or

    ```
    php composer update
    ```

## Usage

Miner can be used as a PHP parser to extract metadata from a HTML source, by parsing a local file or
a remote URL.

```php
<?php

use Yoozi\Miner\Extractor;
use Buzz\Client\Curl;

$extractor = new Extractor();

// Use the Hybrid Parser.
$extractor->getConfig()->set('parser', 'hybrid');
// Strip all HTML tags in the description returned.
$extractor->getConfig()->set('strip_tags', true);

$meta = $extractor->fromUrl('http://www.example.com/', new Curl)->run();
var_dump($meta);
```
