<?php

declare(strict_types=1);

namespace AshAllenDesign\FaviconFetcher\Drivers;

use AshAllenDesign\FaviconFetcher\Collections\FaviconCollection;
use AshAllenDesign\FaviconFetcher\Concerns\HasDefaultFunctionality;
use AshAllenDesign\FaviconFetcher\Concerns\ValidatesUrls;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidIconSizeException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidIconTypeException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\Favicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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

        $linkTag = $this->findLinkElement($response->body());

        if (! $linkTag) {
            return null;
        }

        $linkElement = $this->parseLinkFromElement($linkTag);

        $favicon = new Favicon(
            url: $url,
            faviconUrl: $this->convertToAbsoluteUrl($url, $linkElement),
            fromDriver: $this,
        );

        if ($iconSize = $this->guessSizeFromElement($linkTag)) {
            $favicon->setIconSize($iconSize);
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

        $linkTags = $this->findAllLinkElements($response->body());

        $favicons = $linkTags->map(function (string $linkTag) use ($url): Favicon {
            $favicon = new Favicon(
                $url,
                $this->convertToAbsoluteUrl($url, $this->parseLinkFromElement($linkTag)),
                $this,
            );

            if ($iconSize = $this->guessSizeFromElement($linkTag)) {
                $favicon->setIconSize($iconSize);
            }

            if ($iconType = $this->guessTypeFromElement($linkTag)) {
                $favicon->setIconType($iconType);
            }

            return $favicon;
        });

        return new FaviconCollection($favicons);
    }

    /**
     * @param  string  $html
     * @return Collection<int, string>
     */
    private function findAllLinkElements(string $html): Collection
    {
        $pattern = '/<link.*rel=["\'](icon|shortcut icon|apple-touch-icon)["\'][^>]*>/i';

        preg_match_all($pattern, $html, $linkElementLines);

        // If multiple link elements were found in a single line, we need to loop
        // through and split them out.
        /** @phpstan-ignore-next-line  */
        return collect($linkElementLines[0])
            ->map(function (string $htmlLine): array {
                return collect(explode('>', $htmlLine))
                    ->filter(
                        fn (string $link): bool => Str::is([
                            '*rel="shortcut icon"*',
                            '*rel="icon"*',
                            '*rel="apple-touch-icon"*',
                            "*rel='shortcut icon'*",
                            "*rel='icon'*",
                            "*rel='apple-touch-icon'*",
                        ], $link)
                    )
                    ->all();
            })
            ->flatten();
    }

    /**
     * Attempt to find an "icon" or "shortcut icon" link in the HTML.
     *
     * @param  string  $html
     * @return string|null
     */
    private function findLinkElement(string $html): ?string
    {
        $pattern = '/<link.*rel=["\'](icon|shortcut icon)["\'][^>]*>/i';

        preg_match($pattern, $html, $linkElement);

        if (! isset($linkElement[0])) {
            return null;
        }

        // If multiple link elements were found in the HTML, we need to loop
        // through and only grab the "shortcut icon" or "icon" link.
        return collect(explode('>', $linkElement[0]))
            ->filter(
                fn (string $link): bool => Str::is([
                    '*rel="shortcut icon"*',
                    '*rel="icon"*',
                    "*rel='shortcut icon'*",
                    "*rel='icon'*",
                ], $link)
            )
            ->first();
    }

    /**
     * Find and return the text inside the "href" attribute from the link tag.
     *
     * @param  string  $linkElement
     * @return string
     */
    private function parseLinkFromElement(string $linkElement): string
    {
        $stringUntilHref = strstr($linkElement, 'href="');

        if (! $stringUntilHref) {
            $stringUntilHref = strstr($linkElement, "href='");
        }

        // Replace the double or single quotes with a common delimiter
        // that can be used for exploding the string.
        $stringUntilHref = str_replace(['"', '\''], '|', $stringUntilHref);

        return explode('|', $stringUntilHref)[1];
    }

    private function guessSizeFromElement(string $linkElement): ?int
    {
        $stringUntilSizesAttr = strstr($linkElement, 'sizes="');

        if (! $stringUntilSizesAttr) {
            $stringUntilSizesAttr = strstr($linkElement, "sizes='");
        }

        // If we couldn't find a "sizes" attribute, then we can't guess the size.
        if (! $stringUntilSizesAttr) {
            return null;
        }

        // Replace the double or single quotes with a common delimiter
        // that can be used for exploding the string.
        $stringUntilSizesAttr = str_replace(
            search: ['"', '\''],
            replace: '|',
            subject: $stringUntilSizesAttr
        );

        // Find the size of the icon (e.g. - 192x192)
        $sizesIncludingX = explode('|', $stringUntilSizesAttr)[1];

        // The favicons should be squares, so the height and width should
        // be the same. So we can just return the first number.
        return (int) explode('x', $sizesIncludingX)[0];
    }

    private function guessTypeFromElement(string $linkElement): string
    {
        $stringUntilRelAttr = strstr($linkElement, 'rel="');

        if (! $stringUntilRelAttr) {
            $stringUntilRelAttr = strstr($linkElement, "rel='");
        }

        // Replace the double or single quotes with a common delimiter
        // that can be used for exploding the string.
        $stringUntilRelAttr = str_replace(
            search: ['"', '\''],
            replace: '|',
            subject: $stringUntilRelAttr
        );

        $type = explode('|', $stringUntilRelAttr)[1];

        return match ($type) {
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

    private function stripPathFromUrl(string $url): string
    {
        $parsedUrl = parse_url($url);

        return $parsedUrl['scheme'].'://'.$parsedUrl['host'];
    }
}
