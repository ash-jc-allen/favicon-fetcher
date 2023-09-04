# Changelog

**v3.0.0 (released 2023-09-04):**

- Added `connect_timeout` and `timeout` config fields. ([#67](https://github.com/ash-jc-allen/favicon-fetcher/pull/67))
- Use `symfony/dom-crawler` in the `HttpDriver` to parse the HTML. ([#56](https://github.com/ash-jc-allen/favicon-fetcher/pull/56))
- Updated all files to use strict types for improved type safety. ([#62](https://github.com/ash-jc-allen/favicon-fetcher/pull/62))
- Throw package-specific exceptions instead of vendor exceptions. ([#67](https://github.com/ash-jc-allen/favicon-fetcher/pull/67))
- Fixed a bug that prevented an exception from being thrown when using `fetchAll` if no favicons were found when using the `throw` method. ([#56](https://github.com/ash-jc-allen/favicon-fetcher/pull/50))
- Fixed a bug that prevented the `fetchAll` method from trying to guess the default icon if no favicons were found. ([#56](https://github.com/ash-jc-allen/favicon-fetcher/pull/50))
- Fixed a bug that stripped the port from the base URL. Thanks for the fix, @mhoffmann777! ([#50](https://github.com/ash-jc-allen/favicon-fetcher/pull/50))
- Dropped support for PHP 8.0. ([#59](https://github.com/ash-jc-allen/favicon-fetcher/pull/59))
- Dropped support for Laravel 8. ([#59](https://github.com/ash-jc-allen/favicon-fetcher/pull/59))
- Dropped support for PHPUnit 8.* and Larastan 1.*. ([#59](https://github.com/ash-jc-allen/favicon-fetcher/pull/59))

**v2.0.0 (released 2023-03-23):**
- Added driver for the [Favicon Grabber API](https://favicongrabber.com/). ([#24](https://github.com/ash-jc-allen/favicon-fetcher/pull/24))
- Added `fetchAll` implementation to the `HttpDriver` for fetching all the icons for a URL. ([#29](https://github.com/ash-jc-allen/favicon-fetcher/pull/29), [#31](https://github.com/ash-jc-allen/favicon-fetcher/pull/31))
- Added `fetchAll` method to the `AshAllenDesign\FaviconFetcher\Contracts\Fetcher` interface. ([#29](https://github.com/ash-jc-allen/favicon-fetcher/pull/29))
- Added support to get a favicons size and type. ([#29](https://github.com/ash-jc-allen/favicon-fetcher/pull/29), [#31](https://github.com/ash-jc-allen/favicon-fetcher/pull/31))
- Changed visibility of the `buildCacheKey` method in the `BuildsCacheKey` trait from `protected` to `public`. ([#31](https://github.com/ash-jc-allen/favicon-fetcher/pull/31))
- Changed the values that are used when caching a favicon. ([#31](https://github.com/ash-jc-allen/favicon-fetcher/pull/31))
- Removed the `makeFromCache` method from the `Favicon` class. ([#31](https://github.com/ash-jc-allen/favicon-fetcher/pull/31))

**v1.3.0 (released 2023-01-12):**
- Added support for Laravel 10. (([#22](https://github.com/ash-jc-allen/favicon-fetcher/pull/22)))

**v1.2.1 (released 2022-11-08):**
- Fixed bug that prevented a favicon URL from being detected using the `HttpDriver` if the favicon URL was using single quotes (instead of double quotes). ([#20](https://github.com/ash-jc-allen/favicon-fetcher/pull/20))

**v1.2.0 (released 2022-10-17):**
- Added support for PHP 8.2. ([#21](https://github.com/ash-jc-allen/favicon-fetcher/pull/21))

**v1.1.3 (released 2022-09-03):**
- Removed an incorrect mime type from the file extension detection. ([#19](https://github.com/ash-jc-allen/favicon-fetcher/pull/19))

**v1.1.2 (released 2022-07-23):**
- Fixed bug that was using the incorrect file extension when storing favicons retrieved using the "google-shared-stuff", "unavatar", and "favicon-kit" drivers. ([#17](https://github.com/ash-jc-allen/favicon-fetcher/pull/17))

**v1.1.1 (released 2022-05-10):**
- Fixed bug that was returning the incorrect favicon URL in the `HttpDriver` if multiple `<link>` elements existed on the same line in the webpage's HTML. ([#13](https://github.com/ash-jc-allen/favicon-fetcher/pull/13))

**v1.1.0 (released 2022-04-27):**
- Added driver for [Unavatar](https://unavatar). ([#8](https://github.com/ash-jc-allen/favicon-fetcher/pull/8))

**v1.0.0 (released 2022-04-26):**
- Initial release.
