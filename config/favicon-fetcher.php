<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    |
    | The driver that should be used by default for fetching the favicons.
    | By default, the package comes with support for several drivers,
    | but you can define your own if needed.
    |
    | Supported drivers: "http", "google-shared-stuff", "favicon-kit",
    |                    "unavatar", "favicon-grabber"
    |
    */
    'default' => 'http',

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | The package provides support for caching the fetched favicon's URLs.
    | Here, you can specify the different options for caching, such as
    | cache prefix that is prepended to all the cache keys.
    |
    */
    'cache' => [
        'prefix' => 'favicon-fetcher',
    ],
];
