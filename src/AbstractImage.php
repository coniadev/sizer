<?php

declare(strict_types=1);

namespace Conia\Sizer;

use Conia\Sizer\Exception\RuntimeException;
use Conia\Sizer\Util\Image;
use Conia\Sizer\Util\Path;
use Throwable;

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
            $realPath = realpath($assets->assets . DIRECTORY_SEPARATOR . $path);
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

    public function relative(bool $bust = false): string
    {
        if ($bust) {
            return $this->relativePath . '?v=' . $this->getCacheBuster();
        }

        return $this->relativePath;
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
