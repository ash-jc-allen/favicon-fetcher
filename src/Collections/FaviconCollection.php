<?php

declare(strict_types=1);

namespace AshAllenDesign\FaviconFetcher\Collections;

use AshAllenDesign\FaviconFetcher\Concerns\HasDefaultFunctionality;
use AshAllenDesign\FaviconFetcher\Favicon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * @extends Collection<int, Favicon>
 */
class FaviconCollection extends Collection
{
    use HasDefaultFunctionality;

    /**
     * Whether the favicons in this collection were all retrieved from the cache.
     */
    protected bool $retrievedFromCache = false;

    /**
     * @param  array<array<int,string>>  $items
     * @return static
     */
    public static function makeFromCache(array $items = []): static
    {
        $collection = new static($items);

        $collection->retrievedFromCache = true;

        return $collection;
    }

    /**
     * Cache the collection of favicons. We only cache the collection if it contains
     * items and if it was not retrieved from the cache. If the collection was
     * retrieved from the cache, then the "force" flag has to be set to
     * true in order to cache it.
     */
    public function cache(CarbonInterface $ttl, bool $force = false): self
    {
        $shouldCache = $this->isNotEmpty()
            && ($force || ! $this->retrievedFromCache);

        if ($shouldCache) {
            $cacheKey = $this->buildCacheKeyForCollection($this->first()->getUrl());

            $cacheData = $this->map(fn (Favicon $favicon): array => $favicon->toCache())->all();

            Cache::put($cacheKey, $cacheData, $ttl);
        }

        return $this;
    }

    /**
     * Get the favicon with the largest icon size. Any icons with an unknown size (null)
     * will be treated as having a size of 0.
     */
    public function largest(): ?Favicon
    {
        return $this->sortByDesc(
            fn (Favicon $favicon): ?int => $favicon->getIconSize()
        )->first();
    }
}
