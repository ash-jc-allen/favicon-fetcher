<?php

namespace AshAllenDesign\FaviconFetcher;

class FetchedFavicon
{
    private string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function cache()
    {

    }

    public function store()
    {

    }

    public function storeAs()
    {

    }
}
