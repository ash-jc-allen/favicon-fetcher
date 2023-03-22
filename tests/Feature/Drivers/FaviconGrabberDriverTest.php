<?php

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\Drivers;

use AshAllenDesign\FaviconFetcher\Drivers\FaviconGrabberDriver;
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

class FaviconGrabberDriverTest extends TestCase
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
            'https://favicongrabber.com/api/grab/aws.amazon.com' => Http::response($this->successfulResponseBody()),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new FaviconGrabberDriver())->fetch($protocol.'://aws.amazon.com');

        self::assertSame('https://a0.awsstatic.com/libra-css/images/site/fav/favicon.ico', $favicon->getFaviconUrl());
    }

    /** @test */
    public function favicon_can_be_fetched_from_the_cache_if_it_already_exists(): void
    {
        Cache::put(
            'favicon-fetcher.aws.amazon.com',
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

        $favicon = (new FaviconGrabberDriver())->fetch('https://aws.amazon.com');

        self::assertSame('url-goes-here', $favicon->getFaviconUrl());
    }

    /** @test */
    public function favicon_is_not_fetched_from_the_cache_if_it_exists_but_the_use_cache_flag_is_false(): void
    {
        Cache::put(
            'favicon-fetcher.https://aws.amazon.com',
            'url-goes-here',
            now()->addHour()
        );

        Http::fake([
            'https://favicongrabber.com/api/grab/aws.amazon.com' => Http::response($this->successfulResponseBody()),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new FaviconGrabberDriver())->useCache(false)->fetch('https://aws.amazon.com');

        self::assertSame('https://a0.awsstatic.com/libra-css/images/site/fav/favicon.ico', $favicon->getFaviconUrl());
    }

    /** @test */
    public function null_is_returned_if_the_driver_cannot_find_the_favicon(): void
    {
        Http::fake([
            'https://favicongrabber.com/api/grab/empty.com' => Http::response($this->successfulEmptyResponseBody()),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new FaviconGrabberDriver())->useCache(true)->fetch('https://empty.com');

        self::assertNull($favicon);
    }

    /** @test */
    public function null_is_returned_if_the_domain_is_invalid(): void
    {
        Http::fake([
            'https://favicongrabber.com/api/grab/invalid.com' => Http::response($this->domainNotFoundResponseBody(), 400),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new FaviconGrabberDriver())->useCache(true)->fetch('https://invalid.com');

        self::assertNull($favicon);
    }

    /** @test */
    public function fallback_is_attempted_if_the_driver_cannot_find_the_favicon(): void
    {
        Http::fake([
            'https://favicongrabber.com/api/grab/invalid.com' => Http::response($this->domainNotFoundResponseBody(), 400),
            '*' => Http::response('should not hit here'),
        ]);

        FetcherManager::extend('custom-driver', new CustomDriver());

        $favicon = (new FaviconGrabberDriver())
            ->withFallback('custom-driver')
            ->useCache(true)
            ->fetch('https://invalid.com');

        self::assertSame('favicon-from-default', $favicon->getFaviconUrl());
    }

    /** @test */
    public function exception_is_thrown_if_the_driver_cannot_find_the_favicon_and_the_throw_on_not_found_flag_is_true(): void
    {
        Http::fake([
            'https://favicongrabber.com/api/grab/invalid.com' => Http::response($this->domainNotFoundResponseBody(), 400),
            '*' => Http::response('should not hit here'),
        ]);

        $exception = null;

        try {
            (new FaviconGrabberDriver())
                ->throw()
                ->useCache(true)
                ->fetch('https://invalid.com');
        } catch (\Exception $e) {
            $exception = $e;
        }

        self::assertInstanceOf(FaviconNotFoundException::class, $exception);
        self::assertSame('A favicon cannot be found for https://invalid.com', $exception->getMessage());
    }

    /** @test */
    public function default_value_can_be_returned_using_fetchOr_method(): void
    {
        Http::fake([
            'https://favicongrabber.com/api/grab/invalid.com' => Http::response($this->domainNotFoundResponseBody(), 400),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new FaviconGrabberDriver())
            ->useCache(true)
            ->fetchOr('https://invalid.com', 'fallback-to-this');

        self::assertSame('fallback-to-this', $favicon);
    }

    /** @test */
    public function default_value_can_be_returned_using_fetchOr_method_with_a_closure(): void
    {
        Http::fake([
            'https://favicongrabber.com/api/grab/invalid.com' => Http::response($this->domainNotFoundResponseBody(), 400),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new FaviconGrabberDriver())
            ->fetchOr('https://invalid.com', function () {
                return 'fallback-to-this';
            });

        self::assertSame('fallback-to-this', $favicon);
    }

    /** @test */
    public function exception_can_be_thrown_after_attempting_a_fallback(): void
    {
        Http::fake([
            'https://favicongrabber.com/api/grab/invalid.com' => Http::response($this->domainNotFoundResponseBody(), 400),
            '*' => Http::response('should not hit here'),
        ]);

        FetcherManager::extend('custom-driver', new NullDriver());

        $exception = null;

        try {
            (new FaviconGrabberDriver())
                ->throw()
                ->withFallback('custom-driver')
                ->fetch('https://invalid.com');
        } catch (\Exception $e) {
            $exception = $e;
        }

        self::assertInstanceOf(FaviconNotFoundException::class, $exception);
        self::assertSame('A favicon cannot be found for https://invalid.com', $exception->getMessage());

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
            (new FaviconGrabberDriver())->fetch('example.com');
        } catch (\Exception $e) {
            $exception = $e;
        }

        self::assertInstanceOf(InvalidUrlException::class, $exception);
        self::assertSame('example.com is not a valid URL', $exception->getMessage());
    }

    private function successfulResponseBody(): array
    {
        return [
            'domain' => 'aws.amazon.com',
            'icons' => [
                [
                    'src' => 'https://a0.awsstatic.com/libra-css/images/site/fav/favicon.ico',
                    'type' => 'image/ico',
                ],
                [
                    'src' => 'https://a0.awsstatic.com/libra-css/images/site/fav/favicon.ico',
                    'type' => 'image/ico',
                ],
                [
                    'sizes' => '57x57',
                    'src' => 'https://a0.awsstatic.com/libra-css/images/site/touch-icon-iphone-114-smile.png',
                ],
                [
                    'sizes' => '72x72',
                    'src' => 'https://a0.awsstatic.com/libra-css/images/site/touch-icon-ipad-144-smile.png',
                ],
                [
                    'sizes' => '114x114',
                    'src' => 'https://a0.awsstatic.com/libra-css/images/site/touch-icon-iphone-114-smile.png',
                ],
                [
                    'sizes' => '144x144',
                    'src' => 'https://a0.awsstatic.com/libra-css/images/site/touch-icon-ipad-144-smile.png',
                ],
                [
                    'src' => 'https://aws.amazon.com/favicon.ico',
                    'type' => 'image/x-icon',
                ],
            ],
        ];
    }

    private function successfulEmptyResponseBody(): array
    {
        return [
            'domain' => 'empty.com',
            'icons' => [],
        ];
    }

    private function domainNotFoundResponseBody(): array
    {
        return [
            'error' => 'Unresolved domain name.',
        ];
    }
}
