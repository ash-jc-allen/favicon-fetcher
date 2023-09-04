<?php

declare(strict_types=1);

namespace AshAllenDesign\FaviconFetcher\Concerns;

use AshAllenDesign\FaviconFetcher\Exceptions\ConnectionException;
use Illuminate\Http\Client\ConnectionException as ClientConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

trait MakesHttpRequests
{
    protected function httpClient(): PendingRequest
    {
        return Http::timeout(config('favicon-fetcher.timeout'))
            ->connectTimeout(config('favicon-fetcher.connect_timeout'));
    }

    protected function withRequestExceptionHandling(\Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (ClientConnectionException $exception) {
            throw new ConnectionException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }
}
