<?php

declare(strict_types=1);

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\Collections;

use AshAllenDesign\FaviconFetcher\Collections\FaviconCollection;
use AshAllenDesign\FaviconFetcher\Favicon;
use AshAllenDesign\FaviconFetcher\Tests\Feature\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

final class FaviconCollectionTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function favicon_collection_can_be_cached_if_the_collection_was_not_retrieved_from_the_cache(): void
    {
        $collection = FaviconCollection::make([
            (new Favicon('https://example.com', 'https://example.com/images/apple-icon-180x180.png'))->setIconSize(180)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
            (new Favicon('https://example.com', 'https://example.com/images/favicon.ico'))->setIconType(Favicon::TYPE_SHORTCUT_ICON),
        ]);

        $collection->cache(now()->addDay());

        $cachedItems = Cache::get('favicon-fetcher.example.com.collection');

        self::assertSame(
            expected: [
                [
                    'favicon_url' => 'https://example.com/images/apple-icon-180x180.png',
                    'icon_size' => 180,
                    'icon_type' => 'apple_touch_icon',
                ],
                [
                    'favicon_url' => 'https://example.com/images/favicon.ico',
                    'icon_size' => null,
                    'icon_type' => 'shortcut_icon',
                ],
            ],
            actual: $cachedItems
        );
    }

    /** @test */
    public function favicon_collection_can_be_cached_if_the_collection_was_retrieved_from_the_cache_and_the_force_flag_is_true(): void
    {
        Cache::put(
            key: 'favicon-fetcher.example.com.collection',
            value: 'Dummy value here that should be overridden',
            ttl: now()->addDay(),
        );

        FaviconCollection::makeFromCache([
            (new Favicon('https://example.com', 'https://example.com/images/apple-icon-180x180.png'))->setIconSize(180)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
            (new Favicon('https://example.com', 'https://example.com/images/favicon.ico'))->setIconType(Favicon::TYPE_SHORTCUT_ICON),
        ])->cache(now()->addDay(), true);

        // Assert that the items in the database were overridden.
        self::assertSame(
            expected: [
                [
                    'favicon_url' => 'https://example.com/images/apple-icon-180x180.png',
                    'icon_size' => 180,
                    'icon_type' => 'apple_touch_icon',
                ],
                [
                    'favicon_url' => 'https://example.com/images/favicon.ico',
                    'icon_size' => null,
                    'icon_type' => 'shortcut_icon',
                ],
            ],
            actual: Cache::get('favicon-fetcher.example.com.collection')
        );
    }

    /** @test */
    public function favicon_collection_is_not_cached_if_the_collection_was_retrieved_from_the_cache_and_the_force_flag_is_false(): void
    {
        Cache::put(
            key: 'favicon-fetcher.example.com.collection',
            value: 'Dummy value here that should not be overridden',
            ttl: now()->addDay(),
        );

        FaviconCollection::makeFromCache([
            (new Favicon('https://example.com', 'https://example.com/images/apple-icon-180x180.png'))->setIconSize(180)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
            (new Favicon('https://example.com', 'https://example.com/images/favicon.ico'))->setIconType(Favicon::TYPE_SHORTCUT_ICON),
        ])->cache(now()->addDay());

        // Assert that the items in the database were not overridden.
        self::assertSame(
            expected: 'Dummy value here that should not be overridden',
            actual: Cache::get('favicon-fetcher.example.com.collection')
        );
    }

    /** @test */
    public function favicon_collection_is_not_cached_if_the_collection_is_empty(): void
    {
        Cache::shouldReceive('put')->never();

        $collection = new FaviconCollection();

        $collection->cache(now()->addDay());
    }

    /** @test */
    public function largest_favicon_can_be_retrieved(): void
    {
        $largest = FaviconCollection::make([
            (new Favicon('https://example.com', 'https://example.com/favicon/favicon-32x32.png'))->setIconSize(null)->setIconType(Favicon::TYPE_ICON),
            (new Favicon('https://example.com', 'https://example.com/favicon/apple-icon-57x57.png'))->setIconSize(57)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
            (new Favicon('https://example.com', 'https://example.com/favicon/apple-icon-60x60.png'))->setIconSize(60)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
            (new Favicon('https://example.com', 'https://example.com/favicon/apple-icon-72x72.png'))->setIconSize(72)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
            (new Favicon('https://example.com', 'https://example.com/favicon/apple-icon-72x72.png'))->setIconSize(76)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
            (new Favicon('https://example.com', 'https://example.com/favicon/apple-icon-76x76.png'))->setIconSize(114)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
            (new Favicon('https://example.com', 'https://example.com/favicon/apple-icon-120x120.png'))->setIconSize(120)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
            (new Favicon('https://example.com', 'https://example.com/favicon/apple-icon-144x144.png'))->setIconSize(144)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
            (new Favicon('https://example.com', 'https://example.com/favicon/apple-icon-152x152.png'))->setIconSize(152)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
            (new Favicon('https://example.com', 'https://example.com/favicon/apple-icon-180x180.png'))->setIconSize(180)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
            (new Favicon('https://example.com', 'https://example.com/favicon/android-icon-192x192.png'))->setIconSize(192)->setIconType(Favicon::TYPE_ICON),
        ])->largest();

        self::assertSame('https://example.com/favicon/android-icon-192x192.png', $largest->getFaviconUrl());
    }

    /** @test */
    public function largest_favicon_can_be_retrieved_if_there_are_only_null_sizes(): void
    {
        $largest = FaviconCollection::make([
            (new Favicon('https://example.com', 'https://example.com/favicon/favicon-32x32.png'))->setIconSize(null)->setIconType(Favicon::TYPE_ICON),
            (new Favicon('https://example.com', 'https://example.com/favicon/favicon-64x64.png'))->setIconSize(null)->setIconType(Favicon::TYPE_ICON),
        ])->largest();

        self::assertSame('https://example.com/favicon/favicon-32x32.png', $largest->getFaviconUrl());
    }
}
