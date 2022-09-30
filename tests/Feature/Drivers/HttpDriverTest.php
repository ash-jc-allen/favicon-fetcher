<?php

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\Drivers;

use AshAllenDesign\FaviconFetcher\Drivers\HttpDriver;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\FetcherManager;
use AshAllenDesign\FaviconFetcher\Tests\Feature\_data\CustomDriver;
use AshAllenDesign\FaviconFetcher\Tests\Feature\_data\NullDriver;
use AshAllenDesign\FaviconFetcher\Tests\Feature\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HttpDriverTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @test
     * @dataProvider faviconLinksInHtmlProvider
     */
    public function favicon_can_be_fetched_using_link_element_in_html(string $html, string $expectedFaviconUrl): void
    {
        Http::fake([
            'https://example.com' => Http::response($html),
            $expectedFaviconUrl => Http::response('favicon contents here'),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())->fetch('https://example.com');

        self::assertSame($expectedFaviconUrl, $favicon->getFaviconUrl());
    }

    /** @test */
    public function favicon_can_be_fetched_from_guessed_url_if_it_cannot_be_found_in_response_html(): void
    {
        $responseHtml = <<<'HTML'
            <html lang="en">
                <link rel="localization" href="branding/brand.ftl" />
            </html>
        HTML;

        Http::fake([
            'https://example.com' => Http::response($responseHtml),
            'https://example.com/favicon.ico' => Http::response('favicon contents here'),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())->fetch('https://example.com');

        self::assertSame('https://example.com/favicon.ico', $favicon->getFaviconUrl());
    }

    /**
     * @test
     * @testWith ["https"]
     *           ["http"]
     */
    public function favicon_can_be_fetched_from_driver(string $protocol): void
    {
        Http::fake([
            'https://example.com' => Http::response('<link href="/icon/favicon.ico" rel="icon">'),
            'http://example.com' => Http::response('<link href="/icon/favicon.ico" rel="icon">'),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())->fetch($protocol.'://example.com');

        self::assertSame($protocol.'://example.com/icon/favicon.ico', $favicon->getFaviconUrl());
    }

    /** @test */
    public function favicon_can_be_fetched_from_the_cache_if_it_already_exists(): void
    {
        Cache::put(
            'favicon-fetcher.example.com',
            'url-goes-here',
            now()->addHour()
        );

        Http::fake([
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())->fetch('https://example.com');

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
            'https://example.com' => Http::response('<link href="/icon/favicon.ico" rel="icon">'),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())->useCache(false)->fetch('https://example.com');

        self::assertSame('https://example.com/icon/favicon.ico', $favicon->getFaviconUrl());
    }

    /** @test */
    public function null_is_returned_if_the_driver_cannot_find_the_favicon(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())->useCache(true)->fetch('https://example.com');

        self::assertNull($favicon);
    }

    /** @test */
    public function fallback_is_attempted_if_the_driver_cannot_find_the_favicon(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        FetcherManager::extend('custom-driver', new CustomDriver());

        $favicon = (new HttpDriver())
            ->withFallback('custom-driver')
            ->useCache(true)
            ->fetch('https://example.com');

        self::assertSame('favicon-from-default', $favicon->getFaviconUrl());
    }

    /** @test */
    public function exception_is_thrown_if_the_driver_cannot_find_the_favicon_and_the_throw_on_not_found_flag_is_true(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        $exception = null;

        try {
            (new HttpDriver())
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
            'https://example.com/*' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())
            ->useCache(true)
            ->fetchOr('https://example.com', 'fallback-to-this');

        self::assertSame('fallback-to-this', $favicon);
    }

    /** @test */
    public function default_value_can_be_returned_using_fetchOr_method_with_a_closure(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())
            ->fetchOr('https://example.com', function () {
                return 'fallback-to-this';
            });

        self::assertSame('fallback-to-this', $favicon);
    }

    /** @test */
    public function exception_can_be_thrown_after_attempting_a_fallback(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        FetcherManager::extend('custom-driver', new NullDriver());

        $exception = null;

        try {
            (new HttpDriver())
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
            (new HttpDriver())->fetch('example.com');
        } catch (\Exception $e) {
            $exception = $e;
        }

        self::assertInstanceOf(InvalidUrlException::class, $exception);
        self::assertSame('example.com is not a valid URL', $exception->getMessage());
    }

    public function faviconLinksInHtmlProvider(): array
    {
        return [
            $this->htmlOptionOne(),
            $this->htmlOptionTwo(),
            $this->htmlOptionThree(),
            $this->htmlOptionFour(),
            $this->htmlOptionFive(),
            $this->htmlOptionSix(),
            $this->htmlOptionSeven(),
            $this->htmlOptionEight(),
            $this->htmlOptionNine(),
            $this->htmlOptionTen(),
        ];
    }

    private function htmlOptionOne(): array
    {
        $responseHtml = <<<'HTML'
            <html lang="en">
                <link rel="icon" href="icon/is/here.ico" />
            </html>
        HTML;

        return [$responseHtml, 'https://example.com/icon/is/here.ico'];
    }

    private function htmlOptionTwo(): array
    {
        $responseHtml = <<<'HTML'
            <html lang="en">
                <link rel="icon" href="/icon/is/here.ico" />
            </html>
        HTML;

        return [$responseHtml, 'https://example.com/icon/is/here.ico'];
    }

    private function htmlOptionThree(): array
    {
        $responseHtml = <<<'HTML'
            <html lang="en">
                <link rel="shortcut icon" href="/icon/is/here.ico" />
            </html>
        HTML;

        return [$responseHtml, 'https://example.com/icon/is/here.ico'];
    }

    private function htmlOptionFour(): array
    {
        $responseHtml = <<<'HTML'
            <html lang="en">
                <link rel="icon" type="image/png" href="https://example.com/favicon/favicon-32x32.png"/><link rel="apple-touch-icon" sizes="57x57" href="https://example.com/favicon/apple-icon-57x57.png"/><link rel="apple-touch-icon" sizes="60x60" href="https://example.com/favicon/apple-icon-60x60.png"/><link rel="apple-touch-icon" sizes="72x72" href="https://example.com/favicon/apple-icon-72x72.png"/><link rel="apple-touch-icon" sizes="76x76" href="https://example.com/favicon/apple-icon-72x72.png"/><link rel="apple-touch-icon" sizes="114x114" href="https://example.com/favicon/apple-icon-76x76.png"/><link rel="apple-touch-icon" sizes="120x120" href="https://example.com/favicon/apple-icon-120x120.png"/><link rel="apple-touch-icon" sizes="144x144" href="https://example.com/favicon/apple-icon-144x144.png"/><link rel="apple-touch-icon" sizes="152x152" href="https://example.com/favicon/apple-icon-152x152.png"/><link rel="apple-touch-icon" sizes="180x180" href="https://example.com/favicon/apple-icon-180x180.png"/><link rel="icon" type="image/png" sizes="192x192" href="https://example.com/favicon/android-icon-192x192.png"/>
            </html>
        HTML;

        return [$responseHtml, 'https://example.com/favicon/favicon-32x32.png'];
    }

    private function htmlOptionFive(): array
    {
        $responseHtml = <<<'HTML'
            <html lang="en">
                <link href="/icon/is/here.ico" rel="shortcut icon" />
            </html>
        HTML;

        return [$responseHtml, 'https://example.com/icon/is/here.ico'];
    }

    private function htmlOptionSix(): array
    {
        $responseHtml = <<<'HTML'
            <head> <title>Title here</title> <meta name="description" content="Meta description here"> <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> <meta http-equiv="X-UA-Compatible" content="IE=edge"> <link rel="alternate" href="https://www.example.lv" hreflang="lv"> <link rel="alternate" href="https://www.example.lt/" hreflang="lt"> <link rel="alternate" href="https://www.example.ee/" hreflang="ee"> <link rel="alternate" href="https://www.example.ru/" hreflang="ru"> <link rel="alternate" href="https://www.example.com/en/" hreflang="en"> <link rel="alternate" href="https://www.example.com/default" hreflang="x-default"> <meta name="theme-color" content="#FFFFFF"> <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-icon-180x180.png"> <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico"> <link rel="stylesheet" href="/css/app.css?id=123"> <script src="/vendor/livewire/livewire.js?id=456" data-turbo-eval="false" data-turbolinks-eval="false" ></script><script data-turbo-eval="false" data-turbolinks-eval="false" >
        HTML;

        return [$responseHtml, 'https://example.com/images/favicon.ico'];
    }

    private function htmlOptionSeven(): array
    {
        $responseHtml = <<<'HTML'
            <head>
                <title>Title here</title>
                <meta name="description" content="Meta description here">
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <link rel="alternate" href="https://www.example.lv" hreflang="lv">
                <link rel="alternate" href="https://www.example.lt/" hreflang="lt">
                <link rel="alternate" href="https://www.example.ee/" hreflang="ee">
                <link rel="alternate" href="https://www.example.ru/" hreflang="ru">
                <link rel="alternate" href="https://www.example.com/en/" hreflang="en">
                <link rel="alternate" href="https://www.example.com/default" hreflang="x-default">
                <meta name="theme-color" content="#FFFFFF">
                <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-icon-180x180.png">
                <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico">
                <link rel="stylesheet" href="/css/app.css?id=123">
                <script src="/vendor/livewire/livewire.js?id=456" data-turbo-eval="false" data-turbolinks-eval="false" ></script>
                <script data-turbo-eval="false" data-turbolinks-eval="false" ></script>
            </head>
        HTML;

        return [$responseHtml, 'https://example.com/images/favicon.ico'];
    }

    private function htmlOptionEight(): array
    {
        $responseHtml = <<<'HTML'
            <head> <title>Title here</title> <meta name="description" content="Meta description here"> <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> <meta http-equiv="X-UA-Compatible" content="IE=edge"> <link rel="alternate" href="https://www.example.lv" hreflang="lv"> <link rel="alternate" href="https://www.example.lt/" hreflang="lt"> <link rel="alternate" href="https://www.example.ee/" hreflang="ee"> <link rel="alternate" href="https://www.example.ru/" hreflang="ru"> <link rel="alternate" href="https://www.example.com/en/" hreflang="en"> <link rel="alternate" href="https://www.example.com/default" hreflang="x-default"> <meta name="theme-color" content="#FFFFFF"> <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-icon-180x180.png"> <link rel="icon" type="image/x-icon" href="/images/favicon.ico"> <link rel="stylesheet" href="/css/app.css?id=123"> <script src="/vendor/livewire/livewire.js?id=456" data-turbo-eval="false" data-turbolinks-eval="false" ></script><script data-turbo-eval="false" data-turbolinks-eval="false" >
        HTML;

        return [$responseHtml, 'https://example.com/images/favicon.ico'];
    }

    private function htmlOptionNine(): array
    {
        $responseHtml = <<<'HTML'
            <head>
                <title>Title here</title>
                <meta name="description" content="Meta description here">
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <link rel="alternate" href="https://www.example.lv" hreflang="lv">
                <link rel="alternate" href="https://www.example.lt/" hreflang="lt">
                <link rel="alternate" href="https://www.example.ee/" hreflang="ee">
                <link rel="alternate" href="https://www.example.ru/" hreflang="ru">
                <link rel="alternate" href="https://www.example.com/en/" hreflang="en">
                <link rel="alternate" href="https://www.example.com/default" hreflang="x-default">
                <meta name="theme-color" content="#FFFFFF">
                <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-icon-180x180.png">
                <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
                <link rel="stylesheet" href="/css/app.css?id=123">
                <script src="/vendor/livewire/livewire.js?id=456" data-turbo-eval="false" data-turbolinks-eval="false" ></script>
                <script data-turbo-eval="false" data-turbolinks-eval="false" ></script>
            </head>
        HTML;

        return [$responseHtml, 'https://example.com/images/favicon.ico'];
    }
    
	private function htmlOptionTen(): array
	{
		$responseHtml = <<<'HTML'
            <head>
                <title>Test Title</title>
                <meta content='IE=edge' http-equiv='X-UA-Compatible'>
                <meta content='telephone=no' name='format-detection'>
                <meta content='width=device-width, initial-scale=1, maximum-scale=1' name='viewport'>
                <link href='https://www.example.com/favicon123.png' rel='apple-touch-icon'>
                <link href='https://www.example.com/favicon123.ico' rel='shortcut icon' type='image/x-icon'>
            </head>
        HTML;

		return [$responseHtml, 'https://www.example.com/favicon123.ico'];
	}
}
