<?php

namespace AshAllenDesign\FaviconFetcher;

use AshAllenDesign\FaviconFetcher\Concerns\BuildsCacheKeys;
use AshAllenDesign\FaviconFetcher\Contracts\Fetcher;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidIconSizeException;
use AshAllenDesign\FaviconFetcher\Exceptions\InvalidIconTypeException;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Favicon
{
    use BuildsCacheKeys;

    public const TYPE_ICON = 'icon';

    public const TYPE_SHORTCUT_ICON = 'shortcut_icon';

    public const TYPE_APPLE_TOUCH_ICON = 'apple_touch_icon';

    public const TYPE_ICON_UNKNOWN = 'unknown';

    /**
     * The URL of the website that the favicon belongs to.
     *
     * @var string
     */
    protected string $url;

    /**
     * The URL of the favicon.
     *
     * @var string
     */
    protected string $faviconUrl;

    /**
     * The driver that was used to fetch the favicon. If the favicon was
     * retrieved from the cache, this will be null.
     *
     * @var Fetcher|null
     */
    protected ?Fetcher $driver = null;

    /**
     * Whether the favicon's URL was retrieved from the cache.
     *
     * @var bool
     */
    protected bool $retrievedFromCache = false;

    protected string $iconType = self::TYPE_ICON_UNKNOWN;

    protected ?int $size = null;

    public function __construct(
        string $url,
        string $faviconUrl,
        Fetcher $fromDriver = null,
        bool $retrievedFromCache = false
    ) {
        $this->url = $url;
        $this->faviconUrl = $faviconUrl;
        $this->driver = $fromDriver;
        $this->retrievedFromCache = $retrievedFromCache;
    }

    public function setIconSize(?int $size): static
    {
        if ($size !== null && $size < 0) {
            throw new InvalidIconSizeException('The size ['.$size.'] is not a valid favicon size.');
        }

        $this->size = $size;

        return $this;
    }

    public function setIconType(string $type): static
    {
        if (! $this->acceptableIconType($type)) {
            throw new InvalidIconTypeException('The type ['.$type.'] is not a valid favicon type.');
        }

        $this->iconType = $type;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getFaviconUrl(): string
    {
        return $this->faviconUrl;
    }

    public function retrievedFromCache(): bool
    {
        return $this->retrievedFromCache;
    }

    /**
     * Get the contents of the favicon file.
     *
     * @return string
     */
    public function content(): string
    {
        return Http::get($this->faviconUrl)->body();
    }

    /**
     * Cache the favicon URL. If the favicon is already cached, "force"
     * must be passed as "true" to re-cache the URL.
     *
     * @param  CarbonInterface  $ttl
     * @param  bool  $force
     * @return $this
     */
    public function cache(CarbonInterface $ttl, bool $force = false): self
    {
        if ($force || ! $this->retrievedFromCache) {
            Cache::put(
                $this->buildCacheKey($this->url),
                $this->toCache(),
                $ttl
            );
        }

        return $this;
    }

    /**
     * Store the favicon in storage using an automatically generate filename.
     *
     * @param  string  $directory
     * @param  string|null  $disk
     * @return string
     */
    public function store(string $directory, string $disk = null): string
    {
        return $this->storeAs($directory, Str::uuid()->toString(), $disk);
    }

    /**
     * Store the favicon in storage.
     *
     * @param  string  $directory
     * @param  string  $filename
     * @param  string|null  $disk
     * @return string
     */
    public function storeAs(string $directory, string $filename, string $disk = null): string
    {
        $path = $this->buildStoragePath($directory, $filename);

        Storage::disk($disk)->put($path, $this->content());

        return $path;
    }

    public function getIconType(): string
    {
        return $this->iconType;
    }

    public function getIconSize(): ?int
    {
        return $this->size;
    }

    protected function buildStoragePath(string $directory, string $filename): string
    {
        return Str::of($directory)
            ->append('/')
            ->append($filename)
            ->append('.')
            ->append($this->guessFileExtension());
    }

    protected function guessFileExtension(): string
    {
        $default = File::extension($this->faviconUrl);

        if (Str::of($this->faviconUrl)->endsWith(['png', 'ico', 'svg'])) {
            return $default;
        }

        return $this->guessFileExtensionFromMimeType() ?? $default;
    }

    protected function guessFileExtensionFromMimeType(): ?string
    {
        $faviconMimetype = Http::get($this->faviconUrl)->header('content-type');

        $mimeToExtensionMap = [
            'image/x-icon' => 'ico',
            'image/x-ico' => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'image/png' => 'png',
            'image/x-png' => 'png',
            'image/svg+xml' => 'svg',
        ];

        return $mimeToExtensionMap[$faviconMimetype] ?? null;
    }

    private function acceptableIconType(string $type): bool
    {
        return in_array(
            needle: $type,
            haystack: [
                self::TYPE_ICON,
                self::TYPE_SHORTCUT_ICON,
                self::TYPE_APPLE_TOUCH_ICON,
                self::TYPE_ICON_UNKNOWN,
            ],
            strict: true);
    }

    /**
     * Transform the favicon object into an array that can be cached.
     *
     * @return array
     */
    public function toCache(): array
    {
        return [
            'favicon_url' => $this->getFaviconUrl(),
            'icon_size' => $this->getIconSize(),
            'icon_type' => $this->getIconType(),
        ];
    }
}
