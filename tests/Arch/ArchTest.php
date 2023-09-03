<?php

use AshAllenDesign\FaviconFetcher\Exceptions\FaviconFetcherException;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

test('strict types')
    ->expect('AshAllenDesign\FaviconFetcher')
    ->toUseStrictTypes();

test('interfaces')
    ->expect('AshAllenDesign\FaviconFetcher\Contracts')
    ->toBeInterfaces();

test('traits')
    ->expect('AshAllenDesign\FaviconFetcher\Concerns')
    ->toBeTraits();

test('collections')
    ->expect('AshAllenDesign\FaviconFetcher\Collections')
    ->toBeClasses()
    ->toExtend(Collection::class);

test('drivers')
    ->expect('AshAllenDesign\FaviconFetcher\Drivers')
    ->toBeClasses()
    ->toOnlyImplement(Fetcher::class)
    ->toHaveSuffix('Driver');

test('facades')
    ->expect('AshAllenDesign\FaviconFetcher\Facades')
    ->toBeClasses()
    ->toExtend(Facade::class);

test('exceptions')
    ->expect('AshAllenDesign\FaviconFetcher\Exceptions')
    ->toBeClasses()
    ->toExtend(Exception::class)
    ->toHaveSuffix('Exception');

test('base exception extends php exception')
    ->expect(FaviconFetcherException::class)
    ->toExtend(Exception::class);

test('exceptions extend base exception')
    ->expect('AshAllenDesign\FaviconFetcher\Exceptions')
    ->toBeClasses()
    ->ignoring('FaviconFetcherException')
    ->toExtend(FaviconFetcherException::class);

test('globals')
    ->expect(['dd', 'dump', 'ray', 'sleep'])
    ->not
    ->toBeUsed();
