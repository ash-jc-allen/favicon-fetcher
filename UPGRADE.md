# Upgrade Guide

## Contents

- [Upgrading from 1.* to 2.0.0](#upgrading-from-1-to-200)

## Upgrading from 1.* to 2.0.0

### Method Visibility Changes

The visibility of the `buildCacheKey` method in the `AshAllenDesign\FaviconFetcher\Concerns\BuildsCacheKeys` trait has been changed from `protected` to `public`. If you are overriding this method anywhere in your code, you'll need to update the visibility to `public`.

### Added `fetchAll` and `fetchAllOr` Methods to `Fetcher` Interface

The `fetchAll` and `fetchAllOr` methods have been added to the `AshAllenDesign\FaviconFetcher\Interfaces\Fetcher` interface. If you are implementing this interface in your own code, you'll need to add these method to your implementation.

The signatures for the new methods are:

```php
public function fetchAll(string $url): FaviconCollection;
```

```php
public function fetchAllOr(string $url, mixed $default): mixed;
```

### Removed `makeFromCache` Method from `Favicon` Class

The `makeFromCache` method in the `AshAllenDesign\FaviconFetcher\Favicon` class has been removed. This method was originally intended as a helper method when first added, but it doesn't provide much value, so it has been removed. 

If you were making use of this method anywhere, you'll need to remove it from your code.

### Caching Changes

Previously, Favicon Fetcher only stored the URL of the favicon when calling the `cache` method. However, as of v2.0.0, Favicon Fetcher can determine the size and type of favicons, so this information is now stored in the cache as well.

This means that instead of a string being stored in the cache, an array is now stored instead.

The package has some minor backwards-compatible support to handle items cached before v2.0.0. If you are attempting to retrieve a cached favicon that was stored in the cache before v2.0.0, the `Favicon` class' type and size won't be set. The size and type will only be available on Favicons that were cached from v2.0.0 onwards.

In a future release (likely v3.0.0), the backwards-compatible support will be removed so that only arrays can be read from the cache.
