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
        $client = Http::timeout(config('favicon-fetcher.timeout'))
            ->connectTimeout(config('favicon-fetcher.connect_timeout'));

        if ($userAgent = config('favicon-fetcher.user_agent')) {
            $client->withUserAgent($userAgent);
        }

        return $client;
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
