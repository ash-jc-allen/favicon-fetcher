<?php

namespace AshAllenDesign\FaviconFetcher\Tests\Feature;

use AshAllenDesign\FaviconFetcher\Drivers\FaviconGrabberDriver;
use AshAllenDesign\FaviconFetcher\Drivers\FaviconKitDriver;
use AshAllenDesign\FaviconFetcher\Drivers\GoogleSharedStuffDriver;
use AshAllenDesign\FaviconFetcher\Drivers\HttpDriver;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconFetcherException;
use AshAllenDesign\FaviconFetcher\Facades\Favicon;
use AshAllenDesign\FaviconFetcher\FetcherManager;
use AshAllenDesign\FaviconFetcher\Tests\Feature\_data\CustomDriver;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;

class FetcherManagerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function default_driver_can_be_returned(): void
    {
        config(['favicon-fetcher.default' => 'http']);

        self::assertInstanceOf(HttpDriver::class, FetcherManager::driver());
    }

    /** @test */
    public function http_driver_can_be_returned(): void
    {
        self::assertInstanceOf(HttpDriver::class, FetcherManager::driver('http'));
    }

    /** @test */
    public function google_shared_stuff_driver_can_be_returned(): void
    {
        self::assertInstanceOf(GoogleSharedStuffDriver::class, FetcherManager::driver('google-shared-stuff'));
    }

    /** @test */
    public function favicon_kit_driver_can_be_returned(): void
    {
        self::assertInstanceOf(FaviconKitDriver::class, FetcherManager::driver('favicon-kit'));
    }

    /** @test */
    public function favicon_grabber_driver_can_be_returned(): void
    {
        self::assertInstanceOf(FaviconGrabberDriver::class, FetcherManager::driver('favicon-grabber'));
    }

    /** @test */
    public function custom_driver_can_be_returned(): void
    {
        FetcherManager::extend('custom-driver', new CustomDriver());
        self::assertInstanceOf(CustomDriver::class, FetcherManager::driver('custom-driver'));
    }

    /** @test */
    public function exception_is_thrown_if_the_driver_is_invalid(): void
    {
        $this->expectException(FaviconFetcherException::class);
        $this->expectExceptionMessage('invalid is not a valid driver');

        FetcherManager::driver('invalid');
    }

    /** @test */
    public function method_calls_to_the_manager_are_forwarded_to_the_driver(): void
    {
        $mock = tap(
            Mockery::mock(CustomDriver::class),
            function (Mockery\MockInterface $mock): void {
                $mock->shouldReceive('fetch')
                    ->once()
                    ->withArgs(['https://example.com']);
            }
        );

        FetcherManager::extend('custom-driver', $mock);

        config(['favicon-fetcher.default' => 'custom-driver']);

        (new FetcherManager())->fetch('https://example.com');
    }

    /** @test */
    public function method_calls_to_the_manager_are_forwarded_to_the_driver_using_the_facade(): void
    {
        $mock = tap(
            Mockery::mock(CustomDriver::class),
            function (Mockery\MockInterface $mock): void {
                $mock->shouldReceive('fetch')
                    ->once()
                    ->withArgs(['https://example.com']);
            }
        );

        FetcherManager::extend('custom-driver', $mock);

        config(['favicon-fetcher.default' => 'custom-driver']);

        Favicon::fetch('https://example.com');
    }

    /** @test */
    public function driver_can_be_returned_using_the_facade(): void
    {
        self::assertInstanceOf(FaviconKitDriver::class, Favicon::driver('favicon-kit'));
    }
}
