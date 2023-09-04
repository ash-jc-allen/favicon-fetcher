<?php

declare(strict_types=1);

namespace AshAllenDesign\FaviconFetcher\Drivers;

use AshAllenDesign\FaviconFetcher\Collections\FaviconCollection;
use AshAllenDesign\FaviconFetcher\Concerns\HasDefaultFunctionality;
use AshAllenDesign\FaviconFetcher\Concerns\MakesHttpRequests;
use AshAllenDesign\FaviconFetcher\Concerns\ValidatesUrls;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconFetcherException;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Exceptions\FeatureNotSupportedException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\Exceptions\RequestTimeoutException;
use AshAllenDesign\FaviconFetcher\Favicon;
use Illuminate\Http\Client\Response;

class FaviconGrabberDriver implements Fetcher
{
    use ValidatesUrls;
    use HasDefaultFunctionality;
    use MakesHttpRequests;

    private const BASE_URL = 'https://favicongrabber.com/api/grab/';

    /**
     * Attempt to fetch the favicon for the given URL.
     *
     * @param string $url
     * @return Favicon|null
     *
     * @throws InvalidUrlException
     * @throws FaviconNotFoundException
     * @throws RequestTimeoutException
     * @throws FaviconFetcherException
     */
    public function fetch(string $url): ?Favicon
    {
        if (! $this->urlIsValid($url)) {
            throw new InvalidUrlException($url.' is not a valid URL');
        }

        if ($this->useCache && $favicon = $this->attemptToFetchFromCache($url)) {
            return $favicon;
        }

        $urlWithoutProtocol = str_replace(['https://', 'http://'], '', $url);

        $apiUrl = self::BASE_URL.$urlWithoutProtocol;

        $response = $this->withRequestExceptionHandling(fn (): Response =>
            $this->httpClient()->get($apiUrl)
        );

        if (! $response->successful() || count($response->json('icons')) === 0) {
            return $this->notFound($url);
        }

        $faviconUrl = $response->json('icons')[0]['src'];

        return new Favicon(url: $url, faviconUrl: $faviconUrl, fromDriver: $this);
    }

    public function fetchAll(string $url): FaviconCollection
    {
        throw new FeatureNotSupportedException('The FaviconGrabber driver does not support fetching all favicons.');
    }
}
