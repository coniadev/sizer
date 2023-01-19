<?php

declare(strict_types=1);

namespace Conia\Sizer;

use Conia\Sizer\Exception\RuntimeException;
use Conia\Sizer\Util\Path;

class CachedImage extends AbstractImage
{
    public function url(bool $bust = true, ?string $host = null): string
    {
        return $this->getUrl($this->assets->staticRouteCache, $bust, $host);
    }

    protected function validatePath(string $path): void
    {
        if (!Path::inside($this->assets->cache, $path)) {
            throw new RuntimeException('Image is not inside the cache directory: ' . $path);
        }
    }

    protected function getRelativePath(): string
    {
        return trim(substr($this->path, strlen($this->assets->cache)), DIRECTORY_SEPARATOR);
    }
}
