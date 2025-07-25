<?php

declare(strict_types=1);

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\Concerns\MakesHttpRequests;

use PHPUnit\Framework\Attributes\Test;
use AshAllenDesign\FaviconFetcher\Concerns\MakesHttpRequests;
use AshAllenDesign\FaviconFetcher\Tests\Feature\TestCase;

final class HttpClientTest extends TestCase
{
    use MakesHttpRequests;

    #[Test]
    public function http_client_is_returned_with_correct_options(): void
    {
        config([
            'favicon-fetcher.timeout' => 10,
            'favicon-fetcher.connect_timeout' => 5,
            'favicon-fetcher.verify_tls' => false,
        ]);

        $client = $this->httpClient();

        self::assertEquals(10, $client->getOptions()['timeout']);
        self::assertEquals(5, $client->getOptions()['connect_timeout']);
        self::assertFalse($client->getOptions()['verify']);
    }

    #[Test]
    public function http_client_is_returned_with_correct_verify_tls_option(): void
    {
        config([
            'favicon-fetcher.verify_tls' => true,
        ]);

        $client = $this->httpClient();

        // The "verify" option shouldn't be present because we've not set it,
        // so if it's not in the array, we can make the assumption that it's
        // set to true under the hood by Laravel.
        self::assertArrayNotHasKey('verify', $client->getOptions());
    }
}
