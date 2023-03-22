<?php

namespace AshAllenDesign\FaviconFetcher\Collections;

use AshAllenDesign\FaviconFetcher\Favicon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * @extends Collection<int, Favicon>
 */
class FaviconCollection extends Collection
{
    // TODO Add tests.
    public function cache(CarbonInterface $ttl, bool $force = false): void
    {
        $this->each(function (Favicon $favicon) use ($ttl, $force) {
            $favicon->cache($ttl, $force);
        });
    }

    public function largest(): ?Favicon
    {
        // TODO To implement
        // TODO Add tests
    }
}
