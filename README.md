# PHP Template Repository

[![Build Status](https://github.com/gsteel/listless-octopus/workflows/Continuous%20Integration/badge.svg)](https://github.com/gsteel/listless-octopus/actions?query=workflow%3A"Continuous+Integration")

[![codecov](https://codecov.io/gh/gsteel/listless-octopus/branch/main/graph/badge.svg)](https://codecov.io/gh/gsteel/listless-octopus)
[![Psalm Type Coverage](https://shepherd.dev/github/gsteel/listless-octopus/coverage.svg)](https://shepherd.dev/github/gsteel/listless-octopus)

[![Latest Stable Version](https://poser.pugx.org/gsteel/listless-octopus/v/stable)](https://packagist.org/packages/gsteel/listless-octopus)
[![Total Downloads](https://poser.pugx.org/gsteel/listless-octopus/downloads)](https://packagist.org/packages/gsteel/listless-octopus)

## Introduction

This is a template repo.

## Installation

This is a template repo.

## Weird stuff about the Email Octopus API

* Attempt to get `/lists/invalid-list-id` and you get 403 Unauthorised instead of the documented error response. This also holds true if you fabricate a UUID that you know is wrong.
* Attempt to get/post/put/delete a completely invalid uri like `/not-here` and you get an HTML 404 page instead of a JSON error response.

## License

[MIT Licensed](LICENSE.md).

## Changelog

See [`CHANGELOG.md`](CHANGELOG.md).
