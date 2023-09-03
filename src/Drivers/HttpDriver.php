<?php

declare(strict_types=1);

namespace AshAllenDesign\FaviconFetcher\Drivers;

use AshAllenDesign\FaviconFetcher\Collections\FaviconCollection;
use AshAllenDesign\FaviconFetcher\Concerns\HasDefaultFunctionality;
use AshAllenDesign\FaviconFetcher\Concerns\ValidatesUrls;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconFetcherException;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidIconSizeException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidIconTypeException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\Favicon;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class HttpDriver implements Fetcher
{
    use ValidatesUrls;
    use HasDefaultFunctionality;

    /**
     * Attempt to fetch the favicon for the given URL.
     *
     * @param  string  $url
     * @return Favicon|null
     *
     * @throws FaviconNotFoundException
     * @throws InvalidIconSizeException
     * @throws InvalidIconTypeException
     * @throws InvalidUrlException
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

        $favicon = $this->attemptToResolveFromHeadTags($url)
            ?? new Favicon(url: $url, faviconUrl: $this->guessDefaultUrl($url), fromDriver: $this);

        $faviconCanBeReached = $this->faviconUrlCanBeReached($favicon->getFaviconUrl());

        return $faviconCanBeReached
            ? $favicon
            : $this->notFound($url);
    }

    public function fetchAll(string $url): FaviconCollection
    {
        if (! $this->urlIsValid($url)) {
            throw new InvalidUrlException($url.' is not a valid URL');
        }

        if ($this->useCache && $favicons = $this->attemptToFetchCollectionFromCache($url)) {
            return $favicons;
        }

        $favicons = $this->attemptToResolveAllFromHeadTags($url);

        // If the URL couldn't be reached, throw and exception and return
        // an empty FaviconCollection.
        if ($favicons === null) {
            if ($this->throwOnNotFound) {
                throw new FaviconNotFoundException('A favicon cannot be found for '.$url);
            }

            return new FaviconCollection();
        }

        if ($favicons->isEmpty()) {
            $favicons->push(new Favicon(url: $url, faviconUrl: $this->guessDefaultUrl($url), fromDriver: $this));
        }

        // Return a FaviconCollection of favicons that can be reached.
        return $favicons->filter(
            fn (Favicon $favicon): bool => $this->faviconUrlCanBeReached($favicon->getFaviconUrl())
        );
    }

    /**
     * Attempt to resolve a favicon from the given URL. If the response
     * is successful, we can assume that a valid favicon was returned.
     * Otherwise, we can assume that a favicon wasn't found.
     *
     * @param  string  $faviconUrl
     * @return bool
     */
    private function faviconUrlCanBeReached(string $faviconUrl): bool
    {
        return Http::get($faviconUrl)->successful();
    }

    /**
     * Parse the HTML returned from the URL and attempt to find a favicon
     * specified using the "icon" or "shortcut icon" link tag. If one
     * is found, return the absolute URL of the link's "href".
     * Otherwise, return null.
     *
     * @param  string  $url
     * @return Favicon|null
     *
     * @throws InvalidIconSizeException
     * @throws InvalidIconTypeException
     */
    private function attemptToResolveFromHeadTags(string $url): ?Favicon
    {
        $response = Http::get($url);

        if (! $response->successful()) {
            return null;
        }

        $linkTag = (new Crawler($response->body()))
            ->filter('
                head link[rel="icon"],
                head link[rel="shortcut icon"]
            ')
            ->first();

        if (! $linkTag->count()) {
            return null;
        }

        $favicon = new Favicon(
            url: $url,
            faviconUrl: $this->convertToAbsoluteUrl($url, $linkTag->attr('href')),
            fromDriver: $this,
        );

        if ($iconSize = $linkTag->attr('sizes')) {
            $favicon->setIconSize((int) $iconSize);
        }

        if ($iconType = $this->guessTypeFromElement($linkTag)) {
            $favicon->setIconType($iconType);
        }

        return $favicon;
    }

    private function attemptToResolveAllFromHeadTags(string $url): ?FaviconCollection
    {
        $response = Http::get($url);

        if (! $response->successful()) {
            return null;
        }

        $linkTags = (new Crawler($response->body()))
            ->filter('
                head link[rel="icon"],
                head link[rel="shortcut icon"],
                head link[rel="apple-touch-icon"]
            ');

        if (! $linkTags->count()) {
            return null;
        }

        $favicons = $linkTags->each(function (Crawler $linkTag) use ($url): Favicon {
            $favicon = new Favicon(
                $url,
                $this->convertToAbsoluteUrl($url, $linkTag->attr('href')),
                $this,
            );

            if ($iconSize = $linkTag->attr('sizes')) {
                $favicon->setIconSize((int) $iconSize);
            }

            if ($iconType = $this->guessTypeFromElement($linkTag)) {
                $favicon->setIconType($iconType);
            }

            return $favicon;
        });

        return new FaviconCollection($favicons);
    }

    private function guessTypeFromElement(Crawler $linkElement): string
    {
        return match ($linkElement->attr('rel')) {
            'icon' => Favicon::TYPE_ICON,
            'shortcut icon' => Favicon::TYPE_SHORTCUT_ICON,
            'apple-touch-icon' => Favicon::TYPE_APPLE_TOUCH_ICON,
            default => Favicon::TYPE_ICON_UNKNOWN,
        };
    }

    /**
     * Convert the favicon URL to be absolute rather than relative.
     *
     * @param  string  $baseUrl
     * @param  string  $faviconUrl
     * @return string
     */
    private function convertToAbsoluteUrl(string $baseUrl, string $faviconUrl): string
    {
        // If the favicon URL is relative, we need to convert it to be absolute.
        // We also strip the path (if there is one) from the base URL.
        if (! filter_var($faviconUrl, FILTER_VALIDATE_URL)) {
            $faviconUrl = $this->stripPathFromUrl($baseUrl).'/'.ltrim($faviconUrl, '/');
        }

        return $faviconUrl;
    }

    /**
     * Build and return the default path where we can guess the favicon
     * file might be stored.
     *
     * @param  string  $url
     * @return string
     */
    private function guessDefaultUrl(string $url): string
    {
        return rtrim($this->stripPathFromUrl($url)).'/favicon.ico';
    }

    /**
     * Strip the path and any query parameters from the given URL so that
     * we only return the scheme, host and port (if there is one).
     *
     * @param  string  $url
     * @return string
     */
    private function stripPathFromUrl(string $url): string
    {
        $parsedUrl = parse_url($url);

        $url = $parsedUrl['scheme'].'://'.$parsedUrl['host'];

        if (array_key_exists('port', $parsedUrl)) {
            $url .= ':'.$parsedUrl['port'];
        }

        return $url;
    }
}
