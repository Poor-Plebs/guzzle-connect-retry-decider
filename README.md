# poor-plebs/guzzle-connect-retry-decider

[![CI](https://github.com/Poor-Plebs/guzzle-connect-retry-decider/actions/workflows/ci.yml/badge.svg)](https://github.com/Poor-Plebs/guzzle-connect-retry-decider/actions/workflows/ci.yml)

**[What is it for?](#what-is-it-for)** |
**[What are the requirements?](#what-are-the-requirements)** |
**[How to install it?](#how-to-install-it)** |
**[How to use it?](#how-to-use-it)** |
**[How to contribute?](#how-to-contribute)**

A guzzle retry middleware decider that re-attempts requests whenever a
connection fails to be established. Always retries up to x times for GET
requests and under specific conditions also for other HTTP methods.

## What is it for?

To be more resilient against all kind of connectivity issues, it is a good
practice to just simply retry the request. The guzzle http package already comes
with a generic retry middleware out of the box that accepts a decider callable.

This package provides a decider that will re attempt a request up to x times
when ever a guzzle connect exception is thrown. For GET requests, the decider
will always retry. For other HTTP methods, the decider will only retry, when
no connection could be established yet (no data sent and for HTTPS no handshake
done) to prevent potential double send incidents.

## What are the requirements?

- PHP 8.1 or above

## How to install it?

```bash
composer require poor-plebs/guzzle-connect-retry-decider
```

## How to use it?

```php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

$handlerStack = HandlerStack::create();

// Where to put this middleware in the middleware stack depends on your usecase.
// Usually just before the handler on top or before a log middleware.
$handlerStack->push(
    Middleware::retry(new ConnectRetryDecider(
        maxRetries: 3,
        onBeforeRetry: function (
            int $retries,
            RequestInterface $request,
            Throwable $exception
        ): void {
            /* Optional closure that is executed just before the retry is done.
             * At this point it is already decided that we will retry.
             *
             * Can be used to log the following retry or do some other action.
             */
        }
    )),
    'connect_retry',
);

$client = new Client([
    'base_uri' => 'https://sometest.com/',
    'handler' => $handlerStack,
]);

$client->getAsync('information')->wait();
```

The `maxRetries` and `onBeforeRetry` are both optional. Max retries defaults to
3 retries. If provided, the `onBeforeRetry` will be executed right before a
retry. The callback receives the number of retries already done, the request
instance and the exception that caused the previous attempt to fail.

## How to contribute?

`poor-plebs/guzzle-connect-retry-decider` follows semantic versioning. Read more
on [semver.org][1].

Create issues to report problems or requests. Fork and create pull requests to
propose solutions and ideas. Always add a CHANGELOG.md entry in the unreleased
section.

[1]: https://semver.org
