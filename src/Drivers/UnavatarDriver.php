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
use AshAllenDesign\FaviconFetcher\Favicon;
use Illuminate\Http\Client\Response;

class UnavatarDriver implements Fetcher
{
    use ValidatesUrls;
    use HasDefaultFunctionality;
    use MakesHttpRequests;

    private const BASE_URL = 'https://unavatar.io/';

    /**
     * Attempt to fetch the favicon for the given URL.
     *
     * @param  string  $url
     * @return Favicon|null
     *
     * @throws InvalidUrlException
     * @throws FaviconNotFoundException
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

        $faviconUrl = self::BASE_URL.$urlWithoutProtocol.'?fallback=false';

        $response = $this->withRequestExceptionHandling(fn (): Response => $this->httpClient()->get($faviconUrl)
        );

        return $response->successful()
            ? new Favicon(url: $url, faviconUrl: $faviconUrl, fromDriver: $this)
            : $this->notFound($url);
    }

    public function fetchAll(string $url): FaviconCollection
    {
        throw new FeatureNotSupportedException('The Unavatar API does not support fetching all favicons.');
    }
}
