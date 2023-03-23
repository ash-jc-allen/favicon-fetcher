<?php

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\Drivers;

use AshAllenDesign\FaviconFetcher\Collections\FaviconCollection;
use AshAllenDesign\FaviconFetcher\Drivers\HttpDriver;
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

class HttpDriverTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @test
     *
     * @dataProvider faviconLinksInHtmlProvider
     */
    public function favicon_can_be_fetched_using_link_element_in_html(
        string $html,
        string $expectedFaviconUrl,
        ?int $expectedSize,
        string $expectedType,
    ): void {
        Http::fake([
            'https://example.com' => Http::response($html),
            $expectedFaviconUrl => Http::response('favicon contents here'),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())->fetch('https://example.com');

        self::assertSame($expectedFaviconUrl, $favicon->getFaviconUrl());
        self::assertSame($expectedSize, $favicon->getIconSize());
        self::assertSame($expectedType, $favicon->getIconType());
    }

    /** @test */
    public function favicon_can_be_fetched_if_the_url_has_a_path_and_thelink_element_contains_a_relative_url(): void
    {
        Http::fake([
            'https://example.com/blog' => Http::response($this->htmlOptionOne()),
            'https://example.com/icon/is/here.ico' => Http::response('favicon contents here'),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())->fetch('https://example.com/blog');

        self::assertSame('https://example.com/icon/is/here.ico', $favicon->getFaviconUrl());
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

    /** @test */
    public function favicon_can_be_fetched_from_guessed_url_if_it_cannot_be_found_in_response_html_and_a_relative_url_is_passed(): void
    {
        $responseHtml = <<<'HTML'
            <html lang="en">
                <link rel="localization" href="branding/brand.ftl" />
            </html>
        HTML;

        Http::fake([
            'https://example.com/blog' => Http::response($responseHtml),
            'https://example.com/favicon.ico' => Http::response('favicon contents here'),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())->fetch('https://example.com/blog');

        self::assertSame('https://example.com/favicon.ico', $favicon->getFaviconUrl());
    }

    /**
     * @test
     *
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

        $favicon = (new HttpDriver())->fetch('https://example.com');

        self::assertSame('url-goes-here', $favicon->getFaviconUrl());
        self::assertNull($favicon->getIconSize());
        self::assertSame(Favicon::TYPE_ICON_UNKNOWN, $favicon->getIconType());
    }

    /** @test */
    public function favicon_can_be_fetched_from_the_cache_if_it_already_exists_in_the_old_string_format(): void
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
        self::assertNull($favicon->getIconSize());
        self::assertSame(Favicon::TYPE_ICON_UNKNOWN, $favicon->getIconType());
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

    /**
     * @test
     *
     * @dataProvider allFaviconLinksInHtmlProvider
     */
    public function all_icons_for_a_url_can_be_fetched(string $html, $expectedFaviconCollection): void
    {
        Http::fake([
            'https://example.com' => Http::response($html),
            '*' => Http::response('should not hit here'),
        ]);

        $favicons = (new HttpDriver())->fetchAll('https://example.com');

        self::assertCount($expectedFaviconCollection->count(), $favicons);

        foreach ($favicons as $index => $favicon) {
            self::assertSame($expectedFaviconCollection[$index]->getFaviconUrl(), $favicon->getFaviconUrl());
            self::assertSame($expectedFaviconCollection[$index]->getIconType(), $favicon->getIconType());
            self::assertSame($expectedFaviconCollection[$index]->getIconSize(), $favicon->getIconSize());
        }
    }

    /** @test */
    public function favicon_can_be_fetched_from_guessed_url_if_it_cannot_be_found_in_response_html_when_trying_to_get_all_icons(): void
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

        $favicons = (new HttpDriver())->fetchAll('https://example.com');

        self::assertCount(1, $favicons);
        self::assertSame($favicons->first()->getFaviconUrl(), 'https://example.com/favicon.ico');
    }

    /** @test */
    public function empty_favicon_collection_is_returned_if_the_url_cannot_be_reached(): void
    {
        Http::fake([
            'https://example.com' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        $favicons = (new HttpDriver())->fetchAll('https://example.com');

        self::assertCount(0, $favicons);
    }

    /** @test */
    public function empty_favicon_collection_is_returned_if_no_icons_can_be_found_for_a_url(): void
    {
        $responseHtml = <<<'HTML'
            <html lang="en">
                <link rel="localization" href="branding/brand.ftl" />
            </html>
        HTML;

        Http::fake([
            'https://example.com' => Http::response($responseHtml),
            'https://example.com/favicon.ico' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        $favicons = (new HttpDriver())->fetchAll('https://example.com');

        self::assertCount(0, $favicons);
    }

    /** @test */
    public function error_is_thrown_if_trying_to_find_all_the_favicons_for_an_invalid_url(): void
    {
        Http::fake([
            '*' => Http::response('should not hit here'),
        ]);

        $exception = null;

        try {
            (new HttpDriver())->fetchAll('example.com');
        } catch (\Exception $e) {
            $exception = $e;
        }

        self::assertInstanceOf(InvalidUrlException::class, $exception);
        self::assertSame('example.com is not a valid URL', $exception->getMessage());
    }

    /** @test */
    public function all_favicon_for_a_url_can_be_fetched_from_the_cache_if_it_already_exists(): void
    {
        Cache::put(
            'favicon-fetcher.example.com.collection',
            [
                [
                    'favicon_url' => 'url-goes-here',
                    'icon_size' => null,
                    'icon_type' => Favicon::TYPE_ICON_UNKNOWN,
                ],
                [
                    'favicon_url' => 'url-goes-here-1',
                    'icon_size' => 100,
                    'icon_type' => Favicon::TYPE_ICON,
                ],
                [
                    'favicon_url' => 'url-goes-here-1.com',
                    'icon_size' => 192,
                    'icon_type' => Favicon::TYPE_APPLE_TOUCH_ICON,
                ],
            ],
            now()->addHour(),
        );

        Http::fake([
            '*' => Http::response('should not hit here'),
        ]);

        $favicons = (new HttpDriver())->fetchAll('https://example.com');

        self::assertCount(3, $favicons);

        self::assertSame('url-goes-here', $favicons->first()->getFaviconUrl());
        self::assertSame('url-goes-here-1', $favicons->skip(1)->first()->getFaviconUrl());
        self::assertSame('url-goes-here-1.com', $favicons->skip(2)->first()->getFaviconUrl());

        self::assertNull($favicons->first()->getIconSize());
        self::assertSame(100, $favicons->skip(1)->first()->getIconSize());
        self::assertSame(192, $favicons->skip(2)->first()->getIconSize());

        self::assertSame(Favicon::TYPE_ICON_UNKNOWN, $favicons->first()->getIconType());
        self::assertSame(Favicon::TYPE_ICON, $favicons->skip(1)->first()->getIconType());
        self::assertSame(Favicon::TYPE_APPLE_TOUCH_ICON, $favicons->skip(2)->first()->getIconType());
    }

    /** @test */
    public function all_favicons_for_a_url_are_not_fetched_from_the_cache_if_it_exists_but_the_use_cache_flag_is_false(): void
    {
        Cache::put(
            'favicon-fetcher.example.com.collection',
            [
                [
                    'favicon_url' => 'url-goes-here',
                    'icon_size' => null,
                    'icon_type' => Favicon::TYPE_ICON_UNKNOWN,
                ],
                [
                    'favicon_url' => 'url-goes-here-1',
                    'icon_size' => 100,
                    'icon_type' => Favicon::TYPE_ICON,
                ],
                [
                    'favicon_url' => 'url-goes-here-1.com',
                    'icon_size' => 192,
                    'icon_type' => Favicon::TYPE_APPLE_TOUCH_ICON,
                ],
            ],
            now()->addHour(),
        );

        Http::fake([
            'https://example.com' => Http::response('<link href="/icon/favicon.ico" rel="icon">'),
            '*' => Http::response('should not hit here'),
        ]);

        $favicons = (new HttpDriver())->useCache(false)->fetchAll('https://example.com');

        self::assertCount(1, $favicons);

        self::assertSame('https://example.com/icon/favicon.ico', $favicons->first()->getFaviconUrl());
    }

    /** @test */
    public function favicons_can_be_returned_using_the_fetchAllOr_method(): void
    {
        Http::fake([
            'https://example.com' => Http::response($this->htmlOptionOne()),
            '*' => Http::response('should not hit here'),
        ]);

        $favicons = (new HttpDriver())->fetchAllOr('https://example.com', 'should not fallback to this');

        self::assertCount(1, $favicons);

        self::assertSame('https://example.com/icon/is/here.ico', $favicons->first()->getFaviconUrl());
        self::assertSame(Favicon::TYPE_ICON, $favicons->first()->getIconType());
        self::assertSame(null, $favicons->first()->getIconSize());
    }

    /** @test */
    public function default_value_can_be_returned_using_fetchAllOr_method(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())
            ->useCache(true)
            ->fetchAllOr('https://example.com', 'fallback-to-this');

        self::assertSame('fallback-to-this', $favicon);
    }

    /** @test */
    public function default_value_can_be_returned_using_fetchAllOr_method_with_a_closure(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response('not found', 404),
            '*' => Http::response('should not hit here'),
        ]);

        $favicon = (new HttpDriver())
            ->fetchAllOr('https://example.com', function () {
                return 'fallback-to-this';
            });

        self::assertSame('fallback-to-this', $favicon);
    }

    public function allFaviconLinksInHtmlProvider(): array
    {
        return [
            [
                $this->htmlOptionOne(),
                FaviconCollection::make([
                    (new Favicon('https://example.com', 'https://example.com/icon/is/here.ico'))->setIconType(Favicon::TYPE_ICON),
                ]),
            ],
            [
                $this->htmlOptionTwo(),
                FaviconCollection::make([
                    (new Favicon('https://example.com', 'https://example.com/icon/is/here.ico'))->setIconType(Favicon::TYPE_ICON),
                ]),
            ],
            [
                $this->htmlOptionThree(),
                FaviconCollection::make([
                    (new Favicon('https://example.com', 'https://example.com/icon/is/here.ico'))->setIconType(Favicon::TYPE_SHORTCUT_ICON),
                ]),
            ],
            [
                $this->htmlOptionFour(),
                FaviconCollection::make([
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
                ]),
            ],
            [
                $this->htmlOptionFive(),
                FaviconCollection::make([
                    (new Favicon('https://example.com', 'https://example.com/icon/is/here.ico'))->setIconType(Favicon::TYPE_SHORTCUT_ICON),
                ]),
            ],
            [
                $this->htmlOptionSix(),
                FaviconCollection::make([
                    (new Favicon('https://example.com', 'https://example.com/images/apple-icon-180x180.png'))->setIconSize(180)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
                    (new Favicon('https://example.com', 'https://example.com/images/favicon.ico'))->setIconType(Favicon::TYPE_SHORTCUT_ICON),
                ]),
            ],
            [
                $this->htmlOptionSeven(),
                FaviconCollection::make([
                    (new Favicon('https://example.com', 'https://example.com/images/apple-icon-180x180.png'))->setIconSize(180)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
                    (new Favicon('https://example.com', 'https://example.com/images/favicon.ico'))->setIconType(Favicon::TYPE_SHORTCUT_ICON),
                ]),
            ],
            [
                $this->htmlOptionEight(),
                FaviconCollection::make([
                    (new Favicon('https://example.com', 'https://example.com/images/apple-icon-180x180.png'))->setIconSize(180)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
                    (new Favicon('https://example.com', 'https://example.com/images/favicon.ico'))->setIconType(Favicon::TYPE_ICON),
                ]),
            ],
            [
                $this->htmlOptionNine(),
                FaviconCollection::make([
                    (new Favicon('https://example.com', 'https://example.com/images/apple-icon-180x180.png'))->setIconSize(180)->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
                    (new Favicon('https://example.com', 'https://example.com/images/favicon.ico'))->setIconType(Favicon::TYPE_ICON),
                ]),
            ],
            [
                $this->htmlOptionTen(),
                FaviconCollection::make([
                    (new Favicon('https://example.com', 'https://www.example.com/favicon123.png'))->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON),
                    (new Favicon('https://example.com', 'https://www.example.com/favicon123.ico'))->setIconType(Favicon::TYPE_SHORTCUT_ICON),
                ]),
            ],
            [
                $this->htmlOptionEleven(),
                FaviconCollection::make([
                    (new Favicon('https://example.com', 'https://example.com/apple-icon-57x57.png'))->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON)->setIconSize(57),
                    (new Favicon('https://example.com', 'https://example.com/apple-icon-60x60.png'))->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON)->setIconSize(60),
                    (new Favicon('https://example.com', 'https://example.com/apple-icon-72x72.png'))->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON)->setIconSize(72),
                    (new Favicon('https://example.com', 'https://example.com/apple-icon-76x76.png'))->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON)->setIconSize(76),
                    (new Favicon('https://example.com', 'https://example.com/apple-icon-114x114.png'))->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON)->setIconSize(114),
                    (new Favicon('https://example.com', 'https://example.com/apple-icon-120x120.png'))->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON)->setIconSize(120),
                    (new Favicon('https://example.com', 'https://example.com/apple-icon-144x144.png'))->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON)->setIconSize(144),
                    (new Favicon('https://example.com', 'https://example.com/apple-icon-152x152.png'))->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON)->setIconSize(152),
                    (new Favicon('https://example.com', 'https://example.com/apple-icon-200x200.png'))->setIconType(Favicon::TYPE_APPLE_TOUCH_ICON)->setIconSize(200),
                    (new Favicon('https://example.com', 'https://example.com/android-icon-192x192.png'))->setIconType(Favicon::TYPE_ICON)->setIconSize(192),
                    (new Favicon('https://example.com', 'https://example.com/favicon-32x32.png'))->setIconType(Favicon::TYPE_ICON)->setIconSize(32),
                    (new Favicon('https://example.com', 'https://example.com/favicon-96x96.png'))->setIconType(Favicon::TYPE_ICON)->setIconSize(96),
                ]),
            ],
        ];
    }

    public function faviconLinksInHtmlProvider(): array
    {
        return [
            [$this->htmlOptionOne(), 'https://example.com/icon/is/here.ico', null, Favicon::TYPE_ICON],
            [$this->htmlOptionTwo(), 'https://example.com/icon/is/here.ico', null, Favicon::TYPE_ICON],
            [$this->htmlOptionThree(), 'https://example.com/icon/is/here.ico', null, Favicon::TYPE_SHORTCUT_ICON],
            [$this->htmlOptionFour(), 'https://example.com/favicon/favicon-32x32.png', null, Favicon::TYPE_ICON],
            [$this->htmlOptionFive(), 'https://example.com/icon/is/here.ico', null, Favicon::TYPE_SHORTCUT_ICON],
            [$this->htmlOptionSix(), 'https://example.com/images/favicon.ico', null, Favicon::TYPE_SHORTCUT_ICON],
            [$this->htmlOptionSeven(), 'https://example.com/images/favicon.ico', null, Favicon::TYPE_SHORTCUT_ICON],
            [$this->htmlOptionEight(), 'https://example.com/images/favicon.ico', null, Favicon::TYPE_ICON],
            [$this->htmlOptionNine(), 'https://example.com/images/favicon.ico', null, Favicon::TYPE_ICON],
            [$this->htmlOptionTen(), 'https://www.example.com/favicon123.ico', null, Favicon::TYPE_SHORTCUT_ICON],
            [$this->htmlOptionEleven(), 'https://example.com/android-icon-192x192.png', 192, Favicon::TYPE_ICON],
        ];
    }

    private function htmlOptionOne(): string
    {
        return <<<'HTML'
            <html lang="en">
                <link rel="icon" href="icon/is/here.ico" />
            </html>
        HTML;
    }

    private function htmlOptionTwo(): string
    {
        return <<<'HTML'
            <html lang="en">
                <link rel="icon" href="/icon/is/here.ico" />
            </html>
        HTML;
    }

    private function htmlOptionThree(): string
    {
        return <<<'HTML'
            <html lang="en">
                <link rel="shortcut icon" href="/icon/is/here.ico" />
            </html>
        HTML;
    }

    private function htmlOptionFour(): string
    {
        return <<<'HTML'
            <html lang="en">
                <link rel="icon" type="image/png" href="https://example.com/favicon/favicon-32x32.png"/><link rel="apple-touch-icon" sizes="57x57" href="https://example.com/favicon/apple-icon-57x57.png"/><link rel="apple-touch-icon" sizes="60x60" href="https://example.com/favicon/apple-icon-60x60.png"/><link rel="apple-touch-icon" sizes="72x72" href="https://example.com/favicon/apple-icon-72x72.png"/><link rel="apple-touch-icon" sizes="76x76" href="https://example.com/favicon/apple-icon-72x72.png"/><link rel="apple-touch-icon" sizes="114x114" href="https://example.com/favicon/apple-icon-76x76.png"/><link rel="apple-touch-icon" sizes="120x120" href="https://example.com/favicon/apple-icon-120x120.png"/><link rel="apple-touch-icon" sizes="144x144" href="https://example.com/favicon/apple-icon-144x144.png"/><link rel="apple-touch-icon" sizes="152x152" href="https://example.com/favicon/apple-icon-152x152.png"/><link rel="apple-touch-icon" sizes="180x180" href="https://example.com/favicon/apple-icon-180x180.png"/><link rel="icon" type="image/png" sizes="192x192" href="https://example.com/favicon/android-icon-192x192.png"/>
            </html>
        HTML;
    }

    private function htmlOptionFive(): string
    {
        return <<<'HTML'
            <html lang="en">
                <link href="/icon/is/here.ico" rel="shortcut icon" />
            </html>
        HTML;
    }

    private function htmlOptionSix(): string
    {
        return <<<'HTML'
            <head> <title>Title here</title> <meta name="description" content="Meta description here"> <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> <meta http-equiv="X-UA-Compatible" content="IE=edge"> <link rel="alternate" href="https://www.example.lv" hreflang="lv"> <link rel="alternate" href="https://www.example.lt/" hreflang="lt"> <link rel="alternate" href="https://www.example.ee/" hreflang="ee"> <link rel="alternate" href="https://www.example.ru/" hreflang="ru"> <link rel="alternate" href="https://www.example.com/en/" hreflang="en"> <link rel="alternate" href="https://www.example.com/default" hreflang="x-default"> <meta name="theme-color" content="#FFFFFF"> <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-icon-180x180.png"> <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico"> <link rel="stylesheet" href="/css/app.css?id=123"> <script src="/vendor/livewire/livewire.js?id=456" data-turbo-eval="false" data-turbolinks-eval="false" ></script><script data-turbo-eval="false" data-turbolinks-eval="false" >
        HTML;
    }

    private function htmlOptionSeven(): string
    {
        return <<<'HTML'
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
    }

    private function htmlOptionEight(): string
    {
        return <<<'HTML'
            <head> <title>Title here</title> <meta name="description" content="Meta description here"> <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> <meta http-equiv="X-UA-Compatible" content="IE=edge"> <link rel="alternate" href="https://www.example.lv" hreflang="lv"> <link rel="alternate" href="https://www.example.lt/" hreflang="lt"> <link rel="alternate" href="https://www.example.ee/" hreflang="ee"> <link rel="alternate" href="https://www.example.ru/" hreflang="ru"> <link rel="alternate" href="https://www.example.com/en/" hreflang="en"> <link rel="alternate" href="https://www.example.com/default" hreflang="x-default"> <meta name="theme-color" content="#FFFFFF"> <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-icon-180x180.png"> <link rel="icon" type="image/x-icon" href="/images/favicon.ico"> <link rel="stylesheet" href="/css/app.css?id=123"> <script src="/vendor/livewire/livewire.js?id=456" data-turbo-eval="false" data-turbolinks-eval="false" ></script><script data-turbo-eval="false" data-turbolinks-eval="false" >
        HTML;
    }

    private function htmlOptionNine(): string
    {
        return <<<'HTML'
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
    }

    private function htmlOptionTen(): string
    {
        return <<<'HTML'
            <head>
                <title>Test Title</title>
                <meta content='IE=edge' http-equiv='X-UA-Compatible'>
                <meta content='telephone=no' name='format-detection'>
                <meta content='width=device-width, initial-scale=1, maximum-scale=1' name='viewport'>
                <link href='https://www.example.com/favicon123.png' rel='apple-touch-icon'>
                <link href='https://www.example.com/favicon123.ico' rel='shortcut icon' type='image/x-icon'>
            </head>
        HTML;
    }

    private function htmlOptionEleven(): string
    {
        return <<<'HTML'
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">
                <link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png">
                <link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png">
                <link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png">
                <link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">
                <link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">
                <link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png">
                <link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">
                <link rel="apple-touch-icon" sizes="200x200" href="/apple-icon-200x200.png">
                <link rel="icon" type="image/png" sizes="192x192"  href="/android-icon-192x192.png">
                <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
                <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
                <link rel="manifest" href="/manifest.json">
                <meta name="msapplication-TileColor" content="#ffffff">
                <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
                <meta name="theme-color" content="#ffffff">
                <title>Dummy title</title>
            </head>
        HTML;
    }
}
