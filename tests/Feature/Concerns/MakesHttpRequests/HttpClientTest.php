<?php

declare(strict_types=1);

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\Concerns\MakesHttpRequests;

use AshAllenDesign\FaviconFetcher\Concerns\MakesHttpRequests;
use AshAllenDesign\FaviconFetcher\Tests\Feature\TestCase;

final class HttpClientTest extends TestCase
{
    use MakesHttpRequests;

    /** @test */
    public function http_client_is_returned_with_correct_options(): void
    {
        config([
            'favicon-fetcher.timeout' => 10,
            'favicon-fetcher.connect_timeout' => 5,
        ]);

        $client = $this->httpClient();

        self::assertEquals(10, $client->getOptions()['timeout']);
        self::assertEquals(5, $client->getOptions()['connect_timeout']);
    }
}
