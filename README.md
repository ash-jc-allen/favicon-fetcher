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

To fetch a favicon from a website, you can use the `fetch` method which will return an instance of `AshAllenDesign\FaviconFetcher\Favicon`:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::fetch('https://ashallendesign.co.uk');
```

#### Using the `fetchOr` Method

If you'd like to provide a default value to be used if a favicon cannot be found, you can use the `fetchOr` method.

For example, if you wanted to use a default icon (`https://example.com/favicon.ico`) if a favicon could not be found, your code could look something like this:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::fetchOr('https://ashallendesign.co.uk', 'https://example.com/favicon.ico');
```

This method also accept a `Closure` as the second argument if you'd prefer to run some custom logic. The `url` field passed as the first argument to the `fetchOr` method is available to use in the closure. For example, to use a `Closure`, your code could look something like this:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::fetchOr('https://ashallendesign.co.uk', function ($url) {
    // Run extra logic here...

    return 'https://example.com/favicon.ico';
});
```

### Exceptions



### Drivers

Favicon Fetcher provides the functionality to use different drivers for retrieving favicons from websites.

### Available Drivers

By default, Favicon Fetcher ships with 3 drivers out-the-box: `http`, `google-shared-stuff`, `favicon-kit`.

The `http` driver fetches favicons by attempting to parse "icon" and "shortcut icon" link elements from the returned HTML of a webpage. If it can't find one, it will attempt to guess the URL of the favicon based on common defaults.

The `google-shared-stuff` driver fetches favicons using the [Google Shared Stuff](https://google.com) API.

The `favicon-kit` driver fetches favicons using the [Favicon Kit](https://faviconkit.com) API.

#### How to Choose a Driver

It's important to remember that the `google-shared-stuff` and `favicon-kit` drivers both interact with third-party APIs to retrieve the favicons. So, this means that some data will be shared to external services.

However, the `http` driver does not use any external services and directly queries the website that you are trying to fetch the favicon for. Due to the fact that this package is new, it is likely that the `http` driver may not be 100% accurate when trying to fetch favicons from websites. So, theoretically, the `http` driver should provide you with better privacy, but may not be as accurate as the other drivers. 

### Choosing a Driver

You can select which driver to use by default by changing the `default` field in the `favicon-fetcher` config file after you've published it. The package originally ships with the `http` driver enabled as the default driver.

For example, if you wanted to change your default driver to `favicon-kit`, you could update your `favicon-fetcher` config like so:

```php
return [

    // ...
        
    'default' => 'favicon-kit'
            
    // ...

]
```

If you'd like to set the driver on-the-fly, you can do so by using the `driver` method on the `Favicon` facade. For example, if you wanted to use the `google-shared-stuff` driver, you could do so like this:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::driver('google-shared-stuff')->fetch('https://ashallendesign.co.uk');
```

#### Fallback Drivers

There may be times when a particular driver cannot find a favicon for a website. If this happens, you can fall back and attempt to find it again using a different driver.

For example, if we wanted to try and fetch the favicon using the `http` driver and then fall back to the `google-shared-stuff` driver if we can't find it, your code could look something like this:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::withFallback('google-shared-stuff')->fetch('https://ashallendesign.co.uk');
```

#### Adding Your Own Driver

There might be times when you want to provide your own custom logic for fetching favicons. To do this, you can build your driver and register it with the package for using.

First, you'll need to create your own class and make sure that it implements the `AshAllenDesign\FaviconFetcher\Contracts\Fetcher` interface. For example, your class could like this:

```php
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Favicon;

class MyCustomDriver implements Fetcher
{
    public function fetch(string $url): ?Favicon
    {
        // Add logic here that attempts to fetch a favicon...
    }

    public function fetchOr(string $url, mixed $default): mixed
    {
        // Add logic here that attempts to fetch a favicon or return a default...
    }
}
```

After you've created your new driver, you'll be able to register it with the package using the `extend` method available through the `Favicon` facade. You may want to do this in a service provider so that it is set up and available in the rest of your application.

You can register your custom driver like so:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

Favicon::extend('my-custom-driver', new MyCustomDriver());
```

Now that you've registered your custom driver, you'll be able to use it for fetching favicons like so:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::driver('my-custom-driver')->fetch('https://ashallendesign.co.uk');
```

### Storing Favicons

### Caching Favicons

CONFIG

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
