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
    * [Requirements](#requirements)
    * [Install the Package](#install-the-package)
    * [Publish the Config](#publish-the-config)
- [Usage](#usage)
    * [Fetching Favicons](#fetching-favicons)
        + [Using the `fetch` Method](#using-the-fetch-method)
        + [Using the `fetchOr` Method](#using-the-fetchor-method)
        + [Using the `fetchAll` Method](#using-the-fetchall-method)
        + [Using the `fetchAllOr` Method](#using-the-fetchallor-method)
    * [Exceptions](#exceptions)
    * [Drivers](#drivers)
    * [Available Drivers](#available-drivers)
        + [How to Choose a Driver](#how-to-choose-a-driver)
    * [Choosing a Driver](#choosing-a-driver)
        + [Fallback Drivers](#fallback-drivers)
        + [Adding Your Own Driver](#adding-your-own-driver)
    * [Storing Favicons](#storing-favicons)
        + [Using `store`](#using-store)
        + [Using `storeAs`](#using-storeas)
    * [Caching Favicons](#caching-favicons)
    * [Favicon Types](#favicon-types)
    * [Favicon Sizes](#favicon-sizes)
- [Testing](#testing)
- [Security](#security)
- [Contribution](#contribution)
- [Changelog](#changelog)
- [Upgrading](#upgrading)
- [Credits](#credits)
- [License](#license)

## Overview

A Laravel package that can be used for fetching favicons from websites.

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

### Publish the Config
You can then publish the package's config file by using the following command:

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

This method also accepts a `Closure` as the second argument if you'd prefer to run some custom logic. The `url` field passed as the first argument to the `fetchOr` method is available to use in the closure. For example, to use a closure, your code could look something like this:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::fetchOr('https://ashallendesign.co.uk', function ($url) {
    // Run extra logic here...

    return 'https://example.com/favicon.ico';
});
```

#### Using the `fetchAll` Method

There may be times when you want to retrieve the different sized favicons for a given website. To get the different sized favicons, you can use the `fetchAll` method which will return an instance of `AshAllenDesign\FaviconFetcher\Collections\FaviconCollection`. This collection contains instances of `AshAllenDesign\FaviconFetcher\Favicon`. For example, to get all the favicons for a site, you can use the `fetchAll` method like so:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicons = Favicon::fetchAll('https://ashallendesign.co.uk');
```

The `FaviconCollection` class extends the `Illuminate\Support\Collection` class, so you can use all the methods available on the `Collection` class.

It also includes a `largest` method that you can use to get the favicon with the largest dimensions. It's worth noting that if the size of the favicon is unknown, it will be treated as if it has a size of `0x0px` when determining which is the largest.  For example, you can use the `largest` method like this:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$largestFavicon = Favicon::fetchAll('https://ashallendesign.co.uk')->largest();
```

Note: Only the `http` driver supports retrieving all the favicons for a given website. For this reason, the `fetchAll` method does not support fallbacks. Support may be added for other drivers and fallbacks in the future. 

#### Using the `fetchAllOr` Method

If you'd like to provide a default value to be used if all the favicons for a site cannot be found, you can use the `fetchAllOr` method.

For example, if you wanted to use a default icon (`https://example.com/favicon.ico`) if the favicons could not be found, your code could look something like this:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::fetchAllOr('https://ashallendesign.co.uk', 'https://example.com/favicon.ico');
```

This method also accepts a `Closure` as the second argument if you'd prefer to run some custom logic. The `url` field passed as the first argument to the `fetchAllOr` method is available to use in the closure. For example, to use a closure, your code could look something like this:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::fetchAllOr('https://ashallendesign.co.uk', function ($url) {
    // Run extra logic here...

    return 'https://example.com/favicon.ico';
});
```

### Exceptions

By default, if a favicon can't be found for a URL, the `fetch` method will return `null`. However, if you'd prefer an exception to be thrown, you can use the `throw` method available on the `Favicon` facade. This means that if a favicon can't be found, an `AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException` will be thrown.

To enable exceptions to be thrown, your code could look something like this:


```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::throw()->fetch('https://ashallendesign.co.uk');
```

### Drivers

Favicon Fetcher provides the functionality to use different drivers for retrieving favicons from websites.

### Available Drivers

By default, Favicon Fetcher ships with 5 drivers out-the-box: `http`, `google-shared-stuff`, `favicon-kit`, `unavatar`, `favicon-grabber`.

The `http` driver fetches favicons by attempting to parse "icon" and "shortcut icon" link elements from the returned HTML of a webpage. If it can't find one, it will attempt to guess the URL of the favicon based on common defaults.

The `google-shared-stuff` driver fetches favicons using the [Google Shared Stuff](https://google.com) API.

The `favicon-kit` driver fetches favicons using the [Favicon Kit](https://faviconkit.com) API.

The `unavatar` driver fetches favicons using the [Unavatar](https://unavatar.io) API.

The `favicon-grabber` driver fetches favicons using the [Favicon Grabber](https://favicongrabber.com) API.

#### How to Choose a Driver

It's important to remember that the `google-shared-stuff`, `favicon-kit`, and `unavatar` drivers interact with third-party APIs to retrieve the favicons. So, this means that some data will be shared to external services.

However, the `http` driver does not use any external services and directly queries the website that you are trying to fetch the favicon for. Due to the fact that this package is new, it is likely that the `http` driver may not be 100% accurate when trying to fetch favicons from websites. So, theoretically, the `http` driver should provide you with better privacy, but may not be as accurate as the other drivers. 

### Choosing a Driver

You can select which driver to use by default by changing the `default` field in the `favicon-fetcher` config file after you've published it. The package originally ships with the `http` driver enabled as the default driver.

For example, if you wanted to change your default driver to `favicon-kit`, you could update your `favicon-fetcher` config like so:

```php
return [

    // ...
        
    'default' => 'favicon-kit',
            
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

After fetching favicons, you might want to store them in your filesystem so that you don't need to fetch them again in the future. Favicon Fetcher provides two methods that you can use for storing the favicons: `store` and `storeAs`.

#### Using `store`

If you use the `store` method, a filename will automatically be generated for the favicon before storing. The method's first parameter accepts a string and is the directory that the favicon will be stored in. You can store a favicon using your default filesystem disk like so:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$faviconPath = Favicon::fetch('https://ashallendesign.co.uk')->store('favicons');

// $faviconPath is now equal to: "/favicons/abc-123.ico"
```

If you'd like to use a different storage disk, you can pass it as an optional second argument to the `store` method. For example, to store the favicon on S3, your code use the following:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$faviconPath = Favicon::fetch('https://ashallendesign.co.uk')->store('favicons', 's3');

// $faviconPath is now equal to: "/favicons/abc-123.ico"
```

#### Using `storeAs`

If you use the `storeAs` method, you will be able to define the filename that the file will be stored as. The method's first parameter accepts a string and is the directory that the favicon will be stored in. The second parameter specifies the favicon filename (excluding the file extension). You can store a favicon using your default filesystem disk like so:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$faviconPath = Favicon::fetch('https://ashallendesign.co.uk')->storeAs('favicons', 'ashallendesign');

// $faviconPath is now equal to: "/favicons/ashallendesign.ico"
```

If you'd like to use a different storage disk, you can pass it as an optional third argument to the `storeAs` method. For example, to store the favicon on S3, your code use the following:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$faviconPath = Favicon::fetch('https://ashallendesign.co.uk')->storeAs('favicons', 'ashallendesign', 's3');

// $faviconPath is now equal to: "/favicons/ashallendesign.ico"
```

### Caching Favicons

As well as being able to store favicons, the package also allows you to cache the favicon URLs. This can be extremely useful if you don't want to store a local copy of the file and want to use the external version of the favicon that the website uses.

As a basic example, if you have a page displaying 50 websites and their favicons, we would need to find the favicon's URL on each page load. As can imagine, this would drastically increase the page load time. So, by retrieving the URLs from the cache, it would majorly improve up the page speed.

To cache a favicon, you can use the `cache` method available on the `Favicon` class. The first parameter accepts a `Carbon\CarbonInterface` as the cache lifetime. For example, to cache the favicon URL of `https://ashallendesign.co.uk` for 1 day, your code might look something like:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::fetch('https://ashallendesign.co.uk')->cache(now()->addDay());
```

By default, the package will always try and resolve the favicon from the cache before attempting to retrieve a fresh version. However, if you want to disable the cache and always retrieve a fresh version, you can use the `useCache` method like so:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$favicon = Favicon::useCache(false)->fetch('https://ashallendesign.co.uk');
```

The package uses `favicon-fetcher` as a prefix for all the cache keys. If you'd like to change this, you can do so by changing the `cache.prefix` field in the `favicon-fethcher` config file. For example, to change the prefix to `my-awesome-prefix`, you could update your config file like so:

```php
return [

    // ...
        
    'cache' => [
        'prefix' => 'my-awesome-prefix',
    ]
            
    // ...

]
```

The package also provides the functionality for you to cache collections of favicons that have been retrieved using the `fetchAll` method. You can do this by calling the `cache` method on the `FaviconCollection` class like so:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$faviconCollection = Favicon::fetchAll('https://ashallendesign.co.uk')->cache(now()->addDay());
```

### Favicon Types

When attempting to retrieve favicons using the `http` driver, we may be able to determine the favicons' type (such as `icon`, `shortcut icon`, or `apple-touch-icon`). To get the type of the favicon, you can use the `getIconType` method like so:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$faviconPath = Favicon::fetch('https://ashallendesign.co.uk')->getIconType();
```

This method can return one of four constants defined on the `Favicon` class: `TYPE_ICON`, `TYPE_SHORTCUT_ICON`, `TYPE_APPLE_TOUCH_ICON`, and `TYPE_ICON_UNKNOWN`.

You can make use of these constants for things like filtering. For example, if you wanted to get all the icons except the `apple-touch-icon`, you could do the following:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$faviconCollection = Favicon::fetchAll('https://ashallendesign.co.uk');

$faviconCollection->filter(function ($favicon) {
    return $favicon->getIconType() !== Favicon::TYPE_APPLE_TOUCH_ICON;
});
```

### Favicon Sizes

When attempting to retrieve favicons using the `http` driver, we may be able to determine the favicons' sizes. To get the size of the favicon, you can use the `getIconSize` method like so:

```php
use AshAllenDesign\FaviconFetcher\Facades\Favicon;

$faviconSize = Favicon::fetch('https://ashallendesign.co.uk')->getIconSize();
```

It's assumed that the icons are square, so only a single integer will be returned. For example, if a favicon is 16x16px, then the `getIconSize` method will return `16`. If the size is unknown, `null` will be returned.

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

To contribute to this package, please use the following guidelines before submitting your pull request:

- Write tests for any new functions that are added. If you are updating existing code, make sure that the existing tests
  pass and write more if needed.
- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.
- Make all pull requests to the `master` branch.

## Changelog

Check the [CHANGELOG](CHANGELOG.md) to get more information about the latest changes.

## Upgrading

Check the [UPGRADE](UPGRADE.md) guide to get more information on how to update this library to newer versions.

## Credits

- [Ash Allen](https://ashallendesign.co.uk)
- [Jess Pickup](https://jesspickup.co.uk) (Logo)
- [All Contributors](https://github.com/ash-jc-allen/short-url/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support Me

If you've found this package useful, please consider buying a copy of [Battle Ready Laravel](https://battle-ready-laravel.com) to support me and my work.

Every sale makes a huge difference to me and allows me to spend more time working on open-source projects and tutorials.

To say a huge thanks, you can use the code **BATTLE20** to get a 20% discount on the book.

[👉 Get Your Copy!](https://battle-ready-laravel.com)

[![Battle Ready Laravel](https://ashallendesign.co.uk/images/custom/sponsors/battle-ready-laravel-horizontal-banner.png)](https://battle-ready-laravel.com)
