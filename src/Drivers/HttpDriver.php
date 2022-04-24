<?php

namespace AshAllenDesign\FaviconFetcher\Drivers;

use AshAllenDesign\FaviconFetcher\Concerns\ValidatesUrls;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\FetchedFavicon;
use Illuminate\Support\Facades\Http;

// TODO Maybe add handling for redirects.
// TODO Add option to throw or return null if it doesn't exist.

class HttpDriver implements Fetcher
{
    use ValidatesUrls;

    /**
     * @param string $url
     * @return FetchedFavicon
     * @throws FaviconNotFoundException
     * @throws InvalidUrlException
     */
    public function fetch(string $url): FetchedFavicon
    {
        if (!$this->urlIsValid($url)) {
            throw new InvalidUrlException($url . ' is not a valid URL');
        }

        $faviconUrl = $this->attemptToResolveFromHeadTags($url);

        return $this->attemptToResolveFromUrl($faviconUrl ?? $this->guessDefaultUrl($url))
            ?? throw new FaviconNotFoundException('A favicon cannot be found for ' . $url);
    }

    private function attemptToResolveFromUrl(string $url): ?FetchedFavicon
    {
        $response = Http::get($url);

        return $response->successful()
            ? new FetchedFavicon($url)
            : null;
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
        $pattern = "/<link(.)*rel=\"(icon|shortcut icon)\"[^>]*>/i";

        preg_match($pattern, $html, $linkElement);

        return $linkElement[0] ?? null;
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
