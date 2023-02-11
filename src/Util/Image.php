<?php

declare(strict_types=1);

namespace Conia\Sizer\Util;

use Conia\Sizer\Exception\InvalidArgumentException;
use Conia\Sizer\Exception\RuntimeException;
use GdImage;
use Throwable;

class Image
{
    protected string $path;

    public function __construct(string $path)
    {
        $realPath = realpath($path);

        if ($realPath === false) {
            throw new RuntimeException('Image does not exist: ' . $path);
        }

        $this->path = $realPath;
    }

    public static function getImageFromPath(string $path): GdImage
    {
        if (!is_file($path)) {
            throw new InvalidArgumentException('Image does not exist: ' . $path);
        }

        try {
            switch (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                case 'jfif':
                case 'jpeg':
                case 'jpg':
                    return imagecreatefromjpeg($path);
                case 'png':
                    return imagecreatefrompng($path);
                case 'gif':
                    return imagecreatefromgif($path);
                case 'webp':
                    return imagecreatefromwebp($path);
                default:
                    throw new InvalidArgumentException(
                        'File "' . $path . '" is not a valid jpg, webp, png or gif image.'
                    );
            }
        } catch (Throwable) {
            throw new InvalidArgumentException(
                'File "' . $path . '" is not a valid jpg, webp, png or gif image.'
            );
        }
    }

    public static function resizeImage(
        GdImage $image,
        int $width = 0,
        int $height = 0,
        bool $crop = false,
    ): GdImage {
        $size = new ImageSize(
            origWidth: imagesx($image),
            origHeight: imagesy($image),
            newWidth: $width,
            newHeight: $height,
        );

        if ($size->alreadyInBoundingBox()) {
            return $image;
        }

        return self::resizeToBox($image, $size->newSize($crop));
    }

    public static function writeImageToPath(GdImage $image, string $path): void
    {
        switch (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            case 'jfif':
            case 'jpeg':
            case 'jpg':
                imagejpeg($image, $path);

                break;
            case 'png':
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagepng($image, $path);

                break;
            case 'gif':
                imagegif($image, $path);

                break;
            case 'webp':
                imagewebp($image, $path);

                break;
            default:
                throw new InvalidArgumentException('Image with given extension not supported: ' . $path);
        }
    }

    public static function resizeToBox(GdImage $image, ImageSize $size): GdImage
    {
        $thumb = imagescale(
            $image,
            $size->newWidth,
            $size->newHeight,
        );

        // This is here to satisfy psalm.
        // We have not yet found a way to provoke this error.
        if ($thumb === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Error processing image: cannot resize');
            // @codeCoverageIgnoreEnd
        }

        return $thumb;
    }

    public function get(): GdImage
    {
        return self::getImageFromPath($this->path);
    }

    public function resize(
        int $width = 0,
        int $height = 0,
        bool $crop = false,
    ): GdImage {
        return self::resizeImage(
            $this->get(),
            $width,
            $height,
            $crop,
        );
    }

    public function thumb(
        string $dest,
        int $width = 0,
        int $height = 0,
        bool $crop = false,
    ): void {
        self::writeImageToPath($this->resize($width, $height, $crop), $dest);
    }
}
