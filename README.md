Miner
==========

> This library is part of [Project Golem](http://golem.yoozi.cn/), see [yoozi/golem](https://github.com/yoozi/golem) for more info.

Miner is a PHP library that extracting metadata and interesting text content (like author, summary, and etc.) from HTML pages.
It acts like a simplified [HTML metadata parser](https://tika.apache.org/1.4/formats.html#HyperText_Markup_Language) in [Apache Tika](https://tika.apache.org/).

## WTF is Miner?

Ta-da! Consider the screenshot taken from LinkedIn below: 

![image](https://cloud.githubusercontent.com/assets/275750/2751070/1773aa32-c8ae-11e3-9de3-e022ddcb851f.png)

When you post a link to your connections on LinkedIn, it will automatically extract the title, summary, and even cover image for you. Miner can be typically used to achieve tasks like this.

## Installation

The best and easy way to install the Golem package is with [Composer](https://getcomposer.org).

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

### Parsers

* **Meta**: Summarize a webpage by parsing its HTML meta tags. In most cases it favors Open Graph (OG) markup, and will fall back to standard meta tags if necessary.
* **Readability**: Summarize a webpage using [Arc90's Readability alogrithm](https://code.google.com/p/arc90labs-readability/). All credit goes to [@feelinglucky's PHP Port](https://github.com/feelinglucky/php-readability).
* **Hybrid**: In combination with the above two parsers, it simply takes Readability as the primary parser, and Meta as its fallback.

Hybrid is enabled by default. You can change parsers to best fit your needs:
```php
// Use the Readability Parser.
$extractor->getConfig()->set('parser', 'readability');

// Or...use the Hybrid Parser.
// $extractor->getConfig()->set('parser', 'hybrid');
// Or...use the Meta Parser.
// $extractor->getConfig()->set('parser', 'meta');
```

### Example

We can parse a remote url and extract its metadata directly.
```php
<?php

use Yoozi\Miner\Extractor;
use Buzz\Client\Curl;

$extractor = new Extractor();

// Use the Hybrid Parser.
$extractor->getConfig()->set('parser', 'hybrid');
// Strip all HTML tags in the description we parsed.
$extractor->getConfig()->set('strip_tags', true);

$meta = $extractor->fromUrl('http://www.example.com/', new Curl)->run();
var_dump($meta);
```

Data returned:

```php
array(9) {
  ["title"]=>
  string(14) "Example Domain"
  ["author"]=>
  NULL
  ["keywords"]=>
  array(0) {
  }
  ["description"]=>
  string(220) "
    Example Domain
    This domain is established to be used for illustrative examples in documents. You may use this
    domain in examples without prior coordination or asking for permission.
    More information...
"
  ["image"]=>
  NULL
  ["url"]=>
  string(23) "http://www.example.com/"
  ["host"]=>
  string(22) "http://www.example.com"
  ["domain"]=>
  string(11) "example.com"
  ["favicon"]=>
  string(52) "http://www.google.com/s2/favicons?domain=example.com"
}
```