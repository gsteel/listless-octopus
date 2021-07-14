# Email Octopus PHP API Client

[![Build Status](https://github.com/gsteel/listless-octopus/workflows/Continuous%20Integration/badge.svg)](https://github.com/gsteel/listless-octopus/actions?query=workflow%3A"Continuous+Integration")

[![codecov](https://codecov.io/gh/gsteel/listless-octopus/branch/main/graph/badge.svg)](https://codecov.io/gh/gsteel/listless-octopus)
[![Psalm Type Coverage](https://shepherd.dev/github/gsteel/listless-octopus/coverage.svg)](https://shepherd.dev/github/gsteel/listless-octopus)

[![Latest Stable Version](https://poser.pugx.org/gsteel/listless-octopus/v/stable)](https://packagist.org/packages/gsteel/listless-octopus)
[![Total Downloads](https://poser.pugx.org/gsteel/listless-octopus/downloads)](https://packagist.org/packages/gsteel/listless-octopus)

## Introduction

This is an API Client for the [Email Octopus](https://emailoctopus.com) mailing list service for PHP versions 7.4 and currently 8.0.

There are other clients around, written in PHP, which I haven't evaluated. They might be awesome. This client was born out of a desire to have a handy abstraction around mailing lists in general, so it implements interfaces found in [`gsteel/listless`](https://github.com/gsteel/listless). The other motivation for working on this client was to incorporate psalm and infection into my testing regime, so I needed something to work on to get to grips with these tools.

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
composer require gsteel/listless-octopus
```

## Weird stuff about the Email Octopus API

* Attempt to get `/lists/invalid-list-id` and you get 403 Unauthorised instead of the documented error response. This also holds true if you fabricate a UUID that you know is wrong.
* Attempt to get/post/put/delete a completely invalid uri like `/not-here` and you get an HTML 404 page instead of a JSON error response.

## License

[MIT Licensed](LICENSE.md).

## Changelog

See [`CHANGELOG.md`](CHANGELOG.md).
