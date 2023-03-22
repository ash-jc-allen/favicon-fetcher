<?php

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\Drivers;

use AshAllenDesign\FaviconFetcher\Drivers\UnavatarDriver;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\Favicon;
use AshAllenDesign\FaviconFetcher\FetcherManager;
use AshAllenDesign\FaviconFetcher\Tests\Feature\_data\CustomDriver;
use AshAllenDesign\FaviconFetcher\Tests\Feature\_data\NullDriver;
use AshAllenDesign\FaviconFetcher\Tests\Feature\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UnavatarDriverTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @test
     *
     * @testWith ["https"]
     *           ["http"]
     */
    public function favicon_can_be_fetched_from_driver(string $protocol): void
    {
        Http::fake([
            'https://unavatar.io/example.com' => Http::response('favicon contents here'),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new UnavatarDriver())->fetch($protocol.'://example.com');

        self::assertSame('https://unavatar.io/example.com?fallback=false', $favicon->getFaviconUrl());
    }

    /** @test */
    public function favicon_can_be_fetched_from_the_cache_if_it_already_exists(): void
    {
        Cache::put(
            'favicon-fetcher.example.com',
            [
                'favicon_url' => 'url-goes-here',
                'icon_size' => null,
                'icon_type' => Favicon::TYPE_ICON_UNKNOWN,
            ],
            now()->addHour()
        );

        Http::fake([
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new UnavatarDriver())->fetch('https://example.com');

        self::assertSame('url-goes-here', $favicon->getFaviconUrl());
    }

    /** @test */
    public function favicon_is_not_fetched_from_the_cache_if_it_exists_but_the_use_cache_flag_is_false(): void
    {
        Cache::put(
            'favicon-fetcher.https://example.com',
            'url-goes-here',
            now()->addHour()
        );

        Http::fake([
            'https://unavatar.io/example.com' => Http::response('favicon contents here'),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new UnavatarDriver())->useCache(false)->fetch('https://example.com');

        self::assertSame('https://unavatar.io/example.com?fallback=false', $favicon->getFaviconUrl());
    }

    /** @test */
    public function null_is_returned_if_the_driver_cannot_find_the_favicon(): void
    {
        Http::fake([
            'https://unavatar.io/example.com?fallback=false' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new UnavatarDriver())->useCache(true)->fetch('https://example.com');

        self::assertNull($favicon);
    }

    /** @test */
    public function fallback_is_attempted_if_the_driver_cannot_find_the_favicon(): void
    {
        Http::fake([
            'https://unavatar.io/example.com?fallback=false' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        FetcherManager::extend('custom-driver', new CustomDriver());

        $favicon = (new UnavatarDriver())
            ->withFallback('custom-driver')
            ->useCache(true)
            ->fetch('https://example.com');

        self::assertSame('favicon-from-default', $favicon->getFaviconUrl());
    }

    /** @test */
    public function exception_is_thrown_if_the_driver_cannot_find_the_favicon_and_the_throw_on_not_found_flag_is_true(): void
    {
        Http::fake([
            'https://unavatar.io/example.com?fallback=false' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        $exception = null;

        try {
            (new UnavatarDriver())
                ->throw()
                ->useCache(true)
                ->fetch('https://example.com');
        } catch (\Exception $e) {
            $exception = $e;
        }

        self::assertInstanceOf(FaviconNotFoundException::class, $exception);
        self::assertSame('A favicon cannot be found for https://example.com', $exception->getMessage());
    }

    /** @test */
    public function default_value_can_be_returned_using_fetchOr_method(): void
    {
        Http::fake([
            'https://unavatar.io/example.com?fallback=false' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new UnavatarDriver())
            ->useCache(true)
            ->fetchOr('https://example.com', 'fallback-to-this');

        self::assertSame('fallback-to-this', $favicon);
    }

    /** @test */
    public function default_value_can_be_returned_using_fetchOr_method_with_a_closure(): void
    {
        Http::fake([
            'https://unavatar.io/example.com?fallback=false' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new UnavatarDriver())
            ->fetchOr('https://example.com', function () {
                return 'fallback-to-this';
            });

        self::assertSame('fallback-to-this', $favicon);
    }

    /** @test */
    public function exception_can_be_thrown_after_attempting_a_fallback(): void
    {
        Http::fake([
            'https://unavatar.io/example.com?fallback=false' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        FetcherManager::extend('custom-driver', new NullDriver());

        $exception = null;

        try {
            (new UnavatarDriver())
                ->throw()
                ->withFallback('custom-driver')
                ->fetch('https://example.com');
        } catch (\Exception $e) {
            $exception = $e;
        }

        self::assertInstanceOf(FaviconNotFoundException::class, $exception);
        self::assertSame('A favicon cannot be found for https://example.com', $exception->getMessage());

        self::assertTrue(NullDriver::$flag);
    }

    /** @test */
    public function exception_is_thrown_if_the_url_is_invalid(): void
    {
        Http::fake([
            '*' => Http::response('should not hit here'),
        ]);

        $exception = null;

        try {
            (new UnavatarDriver())->fetch('example.com');
        } catch (\Exception $e) {
            $exception = $e;
        }

        self::assertInstanceOf(InvalidUrlException::class, $exception);
        self::assertSame('example.com is not a valid URL', $exception->getMessage());
    }
}
