<?php

namespace AshAllenDesign\FaviconFetcher\Drivers;

use AshAllenDesign\FaviconFetcher\Concerns\HasDefaultFunctionality;
use AshAllenDesign\FaviconFetcher\Concerns\ValidatesUrls;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\FetchedFavicon;
use Illuminate\Support\Facades\Http;

class HttpDriver implements Fetcher
{
    use ValidatesUrls;
    use HasDefaultFunctionality;

    /**
     * @param string $url
     * @return FetchedFavicon
     * @throws InvalidUrlException
     */
    public function fetch(string $url): ?FetchedFavicon
    {
        if (!$this->urlIsValid($url)) {
            throw new InvalidUrlException($url . ' is not a valid URL');
        }

        if ($this->useCache && $favicon = $this->attemptToFetchFromCache($url)) {
            return $favicon;
        }

        $faviconUrl = $this->attemptToResolveFromUrl(
            $url, $this->attemptToResolveFromHeadTags($url) ?? $this->guessDefaultUrl($url)
        );

        return $faviconUrl ?? $this->notFound($url);
    }

    private function attemptToResolveFromUrl(string $url, string $faviconUrl): ?FetchedFavicon
    {
        $response = Http::get($faviconUrl);

        return $response->successful() ? new FetchedFavicon($url, $faviconUrl, $this) : null;
    }

    private function attemptToResolveFromHeadTags(string $url): ?string
    {
        $response = Http::get($url);

        if (!$response->successful()) {
            return null;
        }

        $linkTag = $this->findLinkElement($response->body());

        return $linkTag
            ? $this->convertToAbsoluteUrl($url, $this->parseLinkFromElement($linkTag))
            : null;
    }

    private function findLinkElement(string $html): ?string
    {
        $pattern = "/<link.*rel=\"(icon|shortcut icon)\"[^>]*>/i";

        preg_match($pattern, $html, $linkElement);

        return isset($linkElement[0])
            ? strstr($linkElement[0], '>', true)
            : null;
    }

    private function parseLinkFromElement(string $linkElement): string
    {
        $stringUntilHref = strstr($linkElement, 'href="');

        return explode('"', $stringUntilHref)[1];
    }

    private function convertToAbsoluteUrl(string $baseUrl, string $faviconUrl): string
    {
        if (str_starts_with($faviconUrl, '/')) {
            $faviconUrl = $baseUrl . $faviconUrl;
        }

        return $faviconUrl;
    }

    private function guessDefaultUrl(string $url): string
    {
        return rtrim($url, '/') . '/favicon.ico';
    }
}
