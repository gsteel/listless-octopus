# Email Octopus PHP API Client

[![Build Status](https://github.com/list-interop/listless-octopus/workflows/Continuous%20Integration/badge.svg)](https://github.com/list-interop/listless-octopus/actions?query=workflow%3A"Continuous+Integration")

[![codecov](https://codecov.io/gh/list-interop/listless-octopus/branch/main/graph/badge.svg)](https://codecov.io/gh/list-interop/listless-octopus)
[![Psalm Type Coverage](https://shepherd.dev/github/list-interop/listless-octopus/coverage.svg)](https://shepherd.dev/github/list-interop/listless-octopus)

[![Latest Stable Version](https://poser.pugx.org/list-interop/listless-octopus/v/stable)](https://packagist.org/packages/list-interop/listless-octopus)
[![Total Downloads](https://poser.pugx.org/list-interop/listless-octopus/downloads)](https://packagist.org/packages/list-interop/listless-octopus)

## Introduction

This is an API Client for the [Email Octopus](https://emailoctopus.com) mailing list service for PHP versions 7.4 and currently 8.0.

There are other clients around, written in PHP, which I haven't evaluated. They might be awesome. This client was born out of a desire to have a handy abstraction around mailing lists in general, so it implements interfaces found in [`list-interop/listless`](https://github.com/list-interop/listless). The other motivation for working on this client was to incorporate psalm and infection into my testing regime, so I needed something to work on to get to grips with these tools.

The client uses PSR17 and 18 standards, so you can bring your own preferred libs.

## Implemented Features

- [x] Create List
- [x] Retrieve list by id
- [x] Delete List
- [x] Add Contact to list _(Subscribe)_
- [x] Change subscription status of contact
- [x] Delete contact from list

## Roadmap

It'd be nice to work up the rest of the available features in the API, but it probably won't happen very quickly, I'm more likely to work on different implementations first to firm up the spec there so that stable releases can be made. Shipping a caching client using a psr cache pool would be handy for those aspects of the API that rarely change. It would also be quite trivial to implement.

## Installation

Composer is the only supported installation method…

As previously mentioned, you'll need a [PSR-18 HTTP Client](https://packagist.org/providers/psr/http-client-implementation) first and also [PSR-7 and PSR-17 implementations](https://packagist.org/providers/psr/http-factory-implementation). For example:

```shell
composer require php-http/curl-client
composer require laminas/laminas-diactoros
```

You'll then be able to install this with:

```shell
composer require list-interop/listless-octopus
```

## Usage

Docs are admittedly thin on the ground.

The lib ships with a PSR11 factory that you can integrate with your container of choice. It falls back to discovery for whatever PSR-7/17/18 stuff that you have installed.

Ultimately, you'll need an API Key to get going, and assuming you can provide the `BaseClient` constructor with its required constructor dependencies, you'll be able to start issuing commands and getting results:

### Add a subscriber…

```php
use ListInterop\Octopus\Client;
use ListInterop\Value\EmailAddress;

assert($client instanceof Client);

$listId = $client->createMailingList('My Most Excellent List');
$mailingList = $client->findMailingListById($listId);

$newEmail = EmailAddress::fromString('someone@example.com');

if (! $client->isSubscribed($newEmail)) {
    $result = $client->subscribe($newEmail, $listId);
    assert($result->isSuccess());
}

$client->unsubscribe($newEmail);

// And so on…

```

You should find that exceptions are consistent and meaningful, but for now, to find out what those are, you'll need to look at the source.

## Weird stuff about the Email Octopus API

* Attempt to get `/lists/invalid-list-id` and you get 403 Unauthorised instead of the documented error response. This also holds true if you fabricate a UUID that you know is wrong.
* Attempt to get/post/put/delete a completely invalid uri like `/not-here` and you get an HTML 404 page instead of a JSON error response.

## Contributions

Are most welcome, but please make sure that pull requests include relevant tests. There's a handy composer script you can run locally:

```shell
composer check
```

… which will check coding standards, run psalm, phpunit and infection in order.

There's also some smoke tests that you can run by executing

```shell
OCTOPUS_API_KEY=my-secret-api-key
phpunit --testsuite=Smoke
```

## License

[MIT Licensed](LICENSE.md).

## Changelog

See [`CHANGELOG.md`](CHANGELOG.md).
