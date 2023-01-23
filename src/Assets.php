<?php

declare(strict_types=1);

namespace Conia\Sizer;

use Conia\Sizer\Exception\RuntimeException;

/** @psalm-api */
class Assets
{
    public readonly string $assets;
    public readonly string $cache;

    public function __construct(string $assetsPath, string $cachePath)
    {
        $realAssetsPath = realpath($assetsPath);
        $realCachePath = realpath($cachePath);

        if ($realAssetsPath === false || !is_dir($realAssetsPath)) {
            throw new RuntimeException('Assets directory does not exist: ' . $assetsPath);
        }

        if ($realCachePath === false || !is_dir($realCachePath)) {
            throw new RuntimeException('Assets cache directory does not exist: ' . $cachePath);
        }

        $this->assets = $realAssetsPath;
        $this->cache = $realCachePath;
    }

    public function image(string $path): Image
    {
        return new Image($this, $path);
    }
}
