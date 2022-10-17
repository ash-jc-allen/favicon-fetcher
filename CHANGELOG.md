# Changelog

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
