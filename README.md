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

use Yoozi\LBS\Query\Place;
use Yoozi\LBS\Search;

$place = new Place;
$place->set('method', 'suggestion');
$place->set('q', '尚德大厦');
$place->set('region', '广州市');

$search = new Search('E4805d16520de693a3fe707cdc962045', $place);

var_dump($search->run()->toArray());
```

Data returned:

```php
array(3) {
  ["status"]=>
  int(0)
  ["message"]=>
  string(2) "ok"
  ["result"]=>
  array(4) {
    [0]=>
    array(5) {
      ["name"]=>
      string(12) "尚德大厦"
      ["city"]=>
      string(9) "广州市"
      ["district"]=>
      string(9) "天河区"
      ["business"]=>
      string(0) ""
      ["cityid"]=>
      string(3) "257"
    }
    [1]=>
    array(5) {
      ["name"]=>
      string(16) "尚德大厦a座"
      ["city"]=>
      string(0) ""
      ["district"]=>
      string(0) ""
      ["business"]=>
      string(0) ""
      ["cityid"]=>
      string(1) "0"
    }
    [2]=>
    array(5) {
      ["name"]=>
      string(22) "尚德大厦-停车场"
      ["city"]=>
      string(9) "广州市"
      ["district"]=>
      string(9) "天河区"
      ["business"]=>
      string(0) ""
      ["cityid"]=>
      string(3) "257"
    }
    [3]=>
    array(5) {
      ["name"]=>
      string(18) "西安尚德大厦"
      ["city"]=>
      string(9) "西安市"
      ["district"]=>
      string(9) "新城区"
      ["business"]=>
      string(0) ""
      ["cityid"]=>
      string(3) "233"
    }
  }
}
```