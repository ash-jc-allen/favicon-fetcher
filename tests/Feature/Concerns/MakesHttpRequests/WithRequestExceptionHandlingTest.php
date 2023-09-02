<?php

declare(strict_types=1);

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\Concerns\MakesHttpRequests;

use AshAllenDesign\FaviconFetcher\Concerns\MakesHttpRequests;
use AshAllenDesign\FaviconFetcher\Exceptions\RequestTimeoutException;
use AshAllenDesign\FaviconFetcher\Tests\Feature\TestCase;
use Illuminate\Http\Client\ConnectionException;

final class WithRequestExceptionHandlingTest extends TestCase
{
    use MakesHttpRequests;

    /** @test */
    public function exception_is_handled_and_rethrown(): void
    {
        $this->expectException(RequestTimeoutException::class);

        $this->withRequestExceptionHandling(function () {
            throw new ConnectionException('Test exception');
        });
    }
}
