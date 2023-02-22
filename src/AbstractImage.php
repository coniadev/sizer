<?php

declare(strict_types=1);

namespace Conia\Sizer;

use Conia\Sizer\Exception\RuntimeException;
use Conia\Sizer\Util\Image;
use Conia\Sizer\Util\Path;

/** @psalm-api */
abstract class AbstractImage
{
    protected string $path;
    protected Image $image;
    protected string $relativePath;

    public function __construct(protected readonly Assets $assets, string $path)
    {
        if (Path::isAbsolute($path)) {
            $realPath = realpath($path);
        } else {
            $realPath = realpath($assets->assets . '/' . $path);
        }

        if ($realPath === false) {
            throw new RuntimeException('Image does not exist: ' . $path);
        }

        $this->validatePath($realPath);
        $this->path = $realPath;
        $this->relativePath = $this->getRelativePath();
        $this->image = new Image($realPath);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function relative(): string
    {
        return $this->relativePath;
    }

    public function urlPath(bool $bust = false): string
    {
        $path = str_replace('\\', '/', $this->relativePath);

        if ($bust) {
            return $path . '?v=' . $this->getCacheBuster();
        }

        return $path;
    }

    public function delete(): bool
    {
        return unlink($this->path);
    }

    public function get(): Image
    {
        return $this->image;
    }

    protected function getCacheBuster(): string
    {
        return hash('xxh32', (string)filemtime($this->path));
    }

    abstract protected function getRelativePath(): string;

    abstract protected function validatePath(string $path): void;
}
