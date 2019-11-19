# middleware
[![Build Status](https://travis-ci.com/phoole/middleware.svg?branch=master)](https://travis-ci.com/phoole/middleware)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phoole/middleware/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phoole/middleware/?branch=master)
[![Code Climate](https://codeclimate.com/github/phoole/middleware/badges/gpa.svg)](https://codeclimate.com/github/phoole/middleware)
[![PHP 7](https://img.shields.io/packagist/php-v/phoole/middleware)](https://packagist.org/packages/phoole/middleware)
[![Latest Stable Version](https://img.shields.io/github/v/release/phoole/middleware)](https://packagist.org/packages/phoole/middleware)
[![License](https://img.shields.io/github/license/phoole/middleware)]()

Slim and simple PSR-15 compliant middleware runner library for PHP.

Installation
---
Install via the `composer` utility.

```bash
composer require "phoole/middleware"
```

or add the following lines to your `composer.json`

```json
{
    "require": {
       "phoole/middleware": "1.*"
    }
}
```

<a name="features"></a>Features
---

- Able to use PSR-15 compliant middlewares out there.

- Able to use a middleware [queue](#queue) (a group of middlewares) as a
  generic middleware in another(or the main) queue.

Usage
---

Create the middleware queue, then process all the middlewares.

```php
use Phoole\Middleware\Queue;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;

// create middleware queue with 404 response as default(last)
$mws = new Queue(new Response(404));

// add middlewares
$mws->add(
    new LoggerMiddleware(),
    new DispatcherMiddleware()
);

// process the request with middlewares
$response = $mws->handle(new ServerRequest('GET', 'http://bingo.com/get'));
```

Advanced
---

- <a name="queue"></a>Subqueue

  `Phoole\Middleware\Queue` implements the `Psr\Http\Server\MiddlewareInterface`,
  so the queue itself can be used as a generic middleware.

  ```php
  // subqueue may need no default response if not the last in the main queue
  $subQueue = (new Queue())->add(
      new ResponseTimeMiddleware(),
      new LoggingMiddleware()
  );

  // main middleware queue
  $mws = (new Queue(new Response(404)))->add(
      $subQueue,
      new DispatcherMiddleware()
  );
  ```
Testing
---

```bash
$ composer test
```

Dependencies
---

- PHP >= 7.2.0

- phoole/base 1.*

- A PSR-7 HTTP message implementation, such as [guzzle/psr7](https://github.com/guzzle/psr7).

License
---

- [Apache 2.0](https://www.apache.org/licenses/LICENSE-2.0)