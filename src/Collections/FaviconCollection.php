<?php

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
     * Cache the collection of favicons.
     *
     * @param CarbonInterface $ttl
     * @return void
     */
    public function cache(CarbonInterface $ttl): void
    {
        $cacheKey = $this->buildCacheKeyForCollection($this->first()->getUrl());

        $cacheData = $this->map(fn (Favicon $favicon): array => $favicon->toCache())->all();

        Cache::put($cacheKey, $cacheData, $ttl);
    }

    /**
     * Get the favicon with the largest icon size. Any icons with an unknown size (null)
     * will be treated as having a size of 0.
     *
     * @return Favicon|null
     */
    public function largest(): ?Favicon
    {
        return $this->sortByDesc(
            fn (Favicon $favicon): ?int => $favicon->getIconSize()
        )->first();
    }
}
