<?php

namespace AshAllenDesign\FaviconFetcher\Tests\Feature;

use AshAllenDesign\FaviconFetcher\Favicon;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;

class FaviconTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function favicon_url_can_be_returned(): void
    {
        $favicon = new Favicon(
           'https://example.com',
           'https://example.com/favicon.ico',
       );

        self::assertSame('https://example.com/favicon.ico', $favicon->getFaviconUrl());
    }

    /** @test */
    public function favicon_contents_can_be_returned(): void
    {
        Http::fake([
            'https://example.com/favicon.ico' => Http::response('favicon contents here'),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = new Favicon(
            'https://example.com',
            'https://example.com/favicon.ico',
        );

        self::assertSame('favicon contents here', $favicon->content());
    }

    /** @test */
    public function url_can_be_returned(): void
    {
        $favicon = new Favicon(
            'https://example.com',
            'https://example.com/favicon.ico',
        );

        self::assertSame('https://example.com', $favicon->getUrl());
    }

    /** @test */
    public function retrieved_from_cache_value_can_be_returned_if_the_favicon_was_retrieved_from_the_cache(): void
    {
        $favicon = Favicon::makeFromCache(
            'https://example.com',
            'https://example.com/favicon.ico',
        );

        self::assertTrue($favicon->retrievedFromCache());
    }

    /** @test */
    public function retrieved_from_cache_value_can_be_returned_if_the_favicon_was_not_retrieved_from_the_cache(): void
    {
        $favicon = new Favicon(
            'https://example.com',
            'https://example.com/favicon.ico',
        );

        self::assertFalse($favicon->retrievedFromCache());
    }

    /** @test */
    public function favicon_can_be_cached_if_it_is_not_already_cached(): void
    {
        Carbon::setTestNow(now());

        $expectedTtl = now()->addMinute();

        Cache::shouldReceive('put')
            ->withArgs([
                'favicon-fetcher.example.com',
                'https://example.com/favicon.ico',
                Mockery::on(fn (CarbonInterface $ttl): bool => $ttl->is($expectedTtl)),
            ])
            ->once();

        (new Favicon(
            'https://example.com',
            'https://example.com/favicon.ico',
        ))->cache($expectedTtl);
    }

    /** @test */
    public function favicon_cannot_be_cached_if_it_is_already_cached(): void
    {
        Carbon::setTestNow(now());

        $expectedTtl = now()->addMinute();

        Cache::shouldReceive('put')->never();

        Favicon::makeFromCache(
            'https://example.com',
            'https://example.com/favicon.ico',
        )->cache($expectedTtl);
    }

    /** @test */
    public function favicon_can_be_cached_if_it_is_already_cached_and_the_force_flag_is_passed(): void
    {
        Carbon::setTestNow(now());

        $expectedTtl = now()->addMinute();

        Cache::shouldReceive('put')
            ->withArgs([
                'favicon-fetcher.example.com',
                'https://example.com/favicon.ico',
                Mockery::on(fn (CarbonInterface $ttl): bool => $ttl->is($expectedTtl)),
            ])
            ->once();

        Favicon::makeFromCache(
            'https://example.com',
            'https://example.com/favicon.ico',
        )->cache(now()->addMinute(), true);
    }

    /** @test */
    public function favicon_contents_be_stored(): void
    {
        Storage::fake();

        Http::fake([
            'https://example.com/favicon.ico' => Http::response('favicon contents here'),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = new Favicon(
            'https://example.com',
            'https://example.com/favicon.ico',
        );

        $path = $favicon->store('favicons');

        self::assertSame('favicon contents here', Storage::get($path));
    }

    /** @test */
    public function favicon_contents_be_stored_if_the_favicon_url_does_not_have_an_image_extension(): void
    {
        Storage::fake();

        Http::fake([
            'https://example.com/favicon.ico' => Http::response('favicon contents here'),
            'https://example.com/favicon.com' => Http::response(body: 'favicon contents here', headers: ['content-type' => 'image/png']),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = new Favicon(
            'https://example.com',
            'https://example.com/favicon.com',
        );

        $path = $favicon->store('favicons');

        self::assertSame('favicon contents here', Storage::get($path));
        self::assertTrue(Str::of($path)->endsWith('.png'));
    }

    /** @test */
    public function favicon_contents_can_be_stored_with_a_custom_file_name(): void
    {
        Storage::fake();

        Http::fake([
            'https://example.com/favicon.ico' => Http::response('favicon contents here'),
            '*' => Http::response('should not hit here'),
        ]);

        (new Favicon(
            'https://example.com',
            'https://example.com/favicon.ico',
        ))->storeAs('favicons', 'fetched');

        self::assertSame('favicon contents here', Storage::get('favicons/fetched.ico'));
    }
}
