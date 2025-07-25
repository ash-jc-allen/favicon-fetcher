<?php

declare(strict_types=1);

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\Concerns\MakesHttpRequests;

use AshAllenDesign\FaviconFetcher\Concerns\MakesHttpRequests;
use AshAllenDesign\FaviconFetcher\Exceptions\ConnectionException;
use AshAllenDesign\FaviconFetcher\Tests\Feature\TestCase;
use Illuminate\Http\Client\ConnectionException as ClientConnectionException;
use PHPUnit\Framework\Attributes\Test;

final class WithRequestExceptionHandlingTest extends TestCase
{
    use MakesHttpRequests;

    #[Test]
    public function exception_is_handled_and_rethrown(): void
    {
        $this->expectException(ConnectionException::class);

        $this->withRequestExceptionHandling(function () {
            throw new ClientConnectionException('Test exception');
        });
    }
}
