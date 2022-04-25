<?php

namespace AshAllenDesign\FaviconFetcher\Tests\Feature\Drivers;

use AshAllenDesign\FaviconFetcher\Tests\Feature\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class HttpDriverTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @test
     * @testWith ["https"]
     *           ["http"]
     */
    public function favicon_can_be_fetched_from_driver(): void
    {

    }

    /** @test */
    public function favicon_can_be_fetched_from_the_cache_if_it_already_exists(): void
    {

    }

    /** @test */
    public function favicon_is_not_fetched_from_the_cache_if_it_does_not_exist(): void
    {

    }

    /** @test */
    public function favicon_is_not_fetched_from_the_cache_if_it_exists_but_the_use_cache_flag_is_false(): void
    {

    }

    /** @test */
    public function null_if_the_driver_cannot_find_the_favicon(): void
    {

    }

    /** @test */
    public function fallback_is_attempted_if_the_driver_cannot_find_the_favicon(): void
    {

    }

    /** @test */
    public function exception_is_thrown_if_the_driver_cannot_find_the_favicon_and_the_throw_on_not_found_flag_is_true(): void
    {

    }

    /** @test */
    public function default_value_can_be_returned_using_fetchOr_method(): void
    {

    }

    /** @test */
    public function exception_can_be_thrown_after_attempting_a_fallback(): void
    {

    }

    /** @test */
    public function exception_is_thrown_if_the_url_is_invalid(): void
    {

    }
}
