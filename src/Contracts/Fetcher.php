<?php

namespace AshAllenDesign\FaviconFetcher\Contracts;

use AshAllenDesign\FaviconFetcher\Collections\FaviconCollection;
use AshAllenDesign\FaviconFetcher\Exceptions\FaviconNotFoundException;
use AshAllenDesign\FaviconFetcher\Exceptions\FeatureNotSupportedException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidUrlException;
use AshAllenDesign\FaviconFetcher\Favicon;

interface Fetcher
{
    /**
     * Attempt to fetch the favicon for the given URL.
     *
     * @param  string  $url
     * @return Favicon|null
     */
    public function fetch(string $url): ?Favicon;

    /**
     * Attempt to fetch all favicons and icons for the given URL.
     *
     * @param  string  $url
     * @return FaviconCollection
     */
    public function fetchAll(string $url): FaviconCollection;

    /**
     * Attempt to fetch the favicon for the given URL. If a favicon cannot
     * be found, return the default as a fallback.
     *
     * @param  string  $url
     * @param  mixed  $default
     * @return mixed
     *
     * @throws FaviconNotFoundException
     * @throws InvalidUrlException
     */
    public function fetchOr(string $url, mixed $default): mixed;

    /**
     * Attempt to fetch all the favicons for the given URL. If the favicons cannot
     * be found, return the default as a fallback.
     *
     * @param  string  $url
     * @param  mixed  $default
     * @return mixed
     *
     * @throws FaviconNotFoundException
     * @throws InvalidUrlException
     * @throws FeatureNotSupportedException
     */
    public function fetchAllOr(string $url, mixed $default): mixed;
}
