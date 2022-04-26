<p align="center">
<img src="/docs/logo.png" alt="Favicon Fetcher" width="600">
</p>

<p align="center">
<a href="https://packagist.org/packages/ashallendesign/favicon-fetcher"><img src="https://img.shields.io/packagist/v/ashallendesign/favicon-fetcher.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://github.com/ash-jc-allen/favicon-fetcher"><img src="https://img.shields.io/github/workflow/status/ash-jc-allen/favicon-fetcher/run-tests?style=flat-square" alt="Build Status"></a>
<a href="https://packagist.org/packages/ashallendesign/favicon-fetcher"><img src="https://img.shields.io/packagist/dt/ashallendesign/favicon-fetcher.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/ashallendesign/favicon-fetcher"><img src="https://img.shields.io/packagist/php-v/ashallendesign/favicon-fetcher?style=flat-square" alt="PHP from Packagist"></a>
<a href="https://github.com/ash-jc-allen/favicon-fetcher/blob/master/LICENSE"><img src="https://img.shields.io/github/license/ash-jc-allen/favicon-fetcher?style=flat-square" alt="GitHub license"></a>
</p>

## Table of Contents

- [Overview](#overview)
- [Installation](#installation)
    - [Requirements](#requirements)
    - [Install the Package](#install-the-package)
    - [Publish the Config and Migrations](#publish-the-config-and-migrations)
    - [Migrate the Database](#migrate-the-database)
- [Usage](#usage)
    - [Building Shortened URLs](#building-shortened-urls)
        - [Quick Start](#quick-start)
        - [Custom Keys](#custom-keys)
        - [Tracking Visitors](#tracking-visitors)
            - [Enabling Tracking](#enabling-tracking)
            - [Tracking IP Address](#tracking-ip-address)
            - [Tracking Browser & Browser Version](#tracking-browser--browser-version)
            - [Tracking Operating System & Operating System Version](#tracking-operating-system--operating-system-version)
            - [Tracking Device Type](#tracking-device-type)
            - [Tracking Referer URL](#tracking-referer-url)
        - [Single Use](#single-use)
        - [Enforce HTTPS](#enforce-https)
        - [Forwarding Query Parameters](#forwarding-query-parameters)
        - [Redirect Status Code](#redirect-status-code)
        - [Activation and Deactivation Times](#activation-and-deactivation-times)
        - [Facade](#facade)
    - [Using the Shortened URLs](#using-the-shortened-urls)
        - [Default Route and Controller](#default-route-and-controller)
        - [Custom Route](#custom-route)
    - [Tracking](#tracking)
    - [Customisation](#customisation)
        - [Disabling the Default Route](#disabling-the-default-route)
        - [Default URL Key Length](#default-url-key-length)
        - [Tracking Visits](#tracking-visits)
            - [Default Tracking](#default-tracking)
            - [Tracking Fields](#tracking-fields)
        - [Config Validation](#config-validation)
    - [Helper Methods](#helper-methods)
        - [Visits](#visits)
        - [Find by URL Key](#find-by-url-key)
        - [Find by Destination URL](#find-by-destination-url)
        - [Tracking Enabled](#tracking-enabled)
        - [Tracked Fields](#tracked-fields)
    - [Events](#events)
        - [Short URL Visited](#short-url-visited)
- [Testing](#testing)
- [Security](#security)
- [Contribution](#contribution)
- [Credits](#credits)
- [Changelog](#changelog)
- [Upgrading](#upgrading)
- [License](#license)

## Overview

A Laravel package that can be used for adding fetching favicons from websites.

## Installation

### Requirements

The package has been developed and tested to work with the following minimum requirements:

- PHP 8.0
- Laravel 8.0

### Install the Package

You can install the package via Composer:

```bash
composer require ashallendesign/favicon-fetcher
```

### Publish the Config and Migrations
You can then publish the package's config file and database migrations by using the following command:

```bash
php artisan vendor:publish --provider="AshAllenDesign\FaviconFetcher\FaviconFetcherProvider"
```

## Usage

### Fetching Favicons

Now that you have the package installed, you can start fetching the favicons from different websites.

#### Using the `fetch` Method

To fetch a favicon from a website, you can use the `fetch` method:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::fetch('https://ashallendesign.co.uk');
```

The `fetch` method returns an instance of `AshAllenDesign\FaviconFetcher\Favicon`.

#### Using the `fetchOr` Method

### Drivers

#### Fallback Drivers

#### Adding Your Own Driver

### Storing Favicons

### Caching Favicons

## Examples

## Testing

To run the package's unit tests, run the following command:

``` bash
composer test
```

To run Larastan for the package, run the following command:

```bash
composer larastan
```

## Security

If you find any security related issues, please contact me directly at [mail@ashallendesign.co.uk](mailto:mail@ashallendesign.co.uk) to report it.

## Contribution

If you wish to make any changes or improvements to the package, feel free to make a pull request.

To contribute to this library, please use the following guidelines before submitting your pull request:

- Write tests for any new functions that are added. If you are updating existing code, make sure that the existing tests
  pass and write more if needed.
- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.
- Make all pull requests to the `master` branch.

## Credits

- [Ash Allen](https://ashallendesign.co.uk)
- [Jess Pickup](https://jesspickup.co.uk) (Logo)
- [All Contributors](https://github.com/ash-jc-allen/short-url/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
