<?php

namespace AshAllenDesign\FaviconFetcher\Drivers;

use AshAllenDesign\FaviconFetcher\Concerns\HasDefaultFunctionality;
use AshAllenDesign\FaviconFetcher\Concerns\ValidatesUrls;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\Favicon;
use Illuminate\Support\Facades\Http;

class HttpDriver implements Fetcher
{
    use ValidatesUrls;
    use HasDefaultFunctionality;

    /**
     * Attempt to fetch the favicon for the given URL.
     *
     * @param string $url
     * @return Favicon|null
     * @throws InvalidUrlException
     * @throws FaviconNotFoundException
     */
    public function fetch(string $url): ?Favicon
    {
        if (! $this->urlIsValid($url)) {
            throw new InvalidUrlException($url.' is not a valid URL');
        }

        if ($this->useCache && $favicon = $this->attemptToFetchFromCache($url)) {
            return $favicon;
        }

        $faviconUrl = $this->attemptToResolveFromUrl(
            $url, $this->attemptToResolveFromHeadTags($url) ?? $this->guessDefaultUrl($url)
        );

        return $faviconUrl ?? $this->notFound($url);
    }

    /**
     * Attempt to resolve a favicon from the given URL. If the response
     * is successful, we can assume that a valid favicon was returned.
     * Otherwise, we can assume that a favicon wasn't found.
     *
     * @param string $url
     * @param string $faviconUrl
     * @return Favicon|null
     */
    private function attemptToResolveFromUrl(string $url, string $faviconUrl): ?Favicon
    {
        $response = Http::get($faviconUrl);

        return $response->successful() ? new Favicon($url, $faviconUrl, $this) : null;
    }

    /**
     * Parse the HTML returned from the URL and attempt to find a favicon
     * specified using the "icon" or "shortcut icon" link tag. If one
     * is found, return the absolute URL of the link's "href".
     * Otherwise, return null.
     *
     * @param string $url
     * @return string|null
     */
    private function attemptToResolveFromHeadTags(string $url): ?string
    {
        $response = Http::get($url);

        if (! $response->successful()) {
            return null;
        }

        $linkTag = $this->findLinkElement($response->body());

        return $linkTag
            ? $this->convertToAbsoluteUrl($url, $this->parseLinkFromElement($linkTag))
            : null;
    }

    /**
     * Attempt to find an "icon" or "shortcut icon" link in the HTML.
     *
     * @param string $html
     * @return string|null
     */
    private function findLinkElement(string $html): ?string
    {
        $pattern = '/<link.*rel="(icon|shortcut icon)"[^>]*>/i';

        preg_match($pattern, $html, $linkElement);

        return isset($linkElement[0])
            ? strstr($linkElement[0], '>', true)
            : null;
    }

    /**
     * Find and return the text inside the "href" attribute from the link tag.
     *
     * @param string $linkElement
     * @return string
     */
    private function parseLinkFromElement(string $linkElement): string
    {
        $stringUntilHref = strstr($linkElement, 'href="');

        return explode('"', $stringUntilHref)[1];
    }

    /**
     * Convert the favicon URL to be absolute rather than relative.
     *
     * @param string $baseUrl
     * @param string $faviconUrl
     * @return string
     */
    private function convertToAbsoluteUrl(string $baseUrl, string $faviconUrl): string
    {
        if (! filter_var($faviconUrl, FILTER_VALIDATE_URL)) {
            $faviconUrl = $baseUrl.'/'.ltrim($faviconUrl, '/');
        }

        return $faviconUrl;
    }

    /**
     * Build and return the default path where we can guess the favicon
     * file might be stored.
     *
     * @param string $url
     * @return string
     */
    private function guessDefaultUrl(string $url): string
    {
        return rtrim($url, '/').'/favicon.ico';
    }
}
