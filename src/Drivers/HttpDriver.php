<?php

namespace AshAllenDesign\FaviconFetcher\Drivers;

use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\FetchedFavicon;
use Illuminate\Support\Facades\Http;

class HttpDriver implements Fetcher
{
    public function fetch(string $url): FetchedFavicon
    {
        $tags = get_meta_tags($url);

        // TODO Handle if the URL is invalid.
        // TODO Handle if the connection could not be made.

        // TODO Try and resolve from the <head> tags first.

        if ($this->tagsContainFaviconTag($tags)) {
            return $this->faviconMetaTag($tags);
        }

        if ($favicon = $this->attemptToResolveFromUrl($url)) {
            dd($favicon);
        }
    }

    private function tagsContainFaviconTag(array $tags): bool
    {
        return false;
    }

    private function faviconMetaTag(array $tags): bool
    {
        return true;
    }

    private function attemptToResolveFromUrl(string $url)
    {
        $faviconUrl = rtrim($url, '/').'/favicon.ico';

        $response = Http::get($faviconUrl);

        if ($response->successful()) {
            return new FetchedFavicon($faviconUrl);
        }

        // Return an error. It could not be resolved.
    }
}
