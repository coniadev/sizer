<?php

declare(strict_types=1);

use Conia\Sizer\Assets;
use Conia\Sizer\CachedImage;
use Conia\Sizer\Exception\RuntimeException;
use Conia\Sizer\Exception\ValueError;
use Conia\Sizer\Image;

beforeEach(function () {
    $root = __DIR__ . '/Fixtures';
    $assets = $root . '/assets';
    $cache = $root . '/cache/assets';
    $cachesub = $cache . '/sub';

    if (is_dir($cachesub)) {
        array_map('unlink', glob("{$cachesub}/*.*"));
        rmdir($cachesub);
    }

    $this->paths = ['assets' => $assets, 'cache' => $cache];
    $this->landscape = $assets . '/landscape.png';
    $this->portrait = $assets . '/sub/portrait.png';
    $this->square = $assets . '/square.png';
    $this->relativeSquare = 'square.png';
    $this->cached = $cache . '/cached.jpg';
});


test('Assets directory does not exist', function () {
    new Assets('/wrong/path', $this->paths['cache']);
})->throws(RuntimeException::class, 'does not exist');


test('Cache directory does not exist', function () {
    new Assets($this->paths['assets'], '/wrong/path');
})->throws(RuntimeException::class, 'does not exist');


test('Relative and absolute image path', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);

    $absolute = $assets->image($this->square);
    $relative = $assets->image($this->relativeSquare);

    expect($absolute)->toBeInstanceOf(Image::class);
    expect($relative)->toBeInstanceOf(Image::class);
    expect($relative->path())->toBe($this->square);
    expect($absolute->relative())->toBe($this->relativeSquare);
});


test('Cachebusting', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);

    $img = $assets->image($this->square);

    expect($img->relative(true))->toStartWith('square.png?v=');
});


test('Image does not exist', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);

    $assets->image('does-not-exist.jpg');
})->throws(RuntimeException::class, 'does not exist');


test('Resize to width', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);

    $assetImage = $assets->image($this->landscape);
    $cacheImage = $assetImage->resize(200, 0, false);
    $path = $cacheImage->path();
    $image = $cacheImage->get();

    expect($assetImage)->toBeInstanceOf(Image::class);
    expect(str_ends_with(
        $path,
        'assets/landscape-w200b.png'
    ))->toBe(true);
    expect(is_file($path))->toBe(true);
    expect(imagesx($image->get()))->toBe(200);

    $cacheImage->delete();

    expect(is_file($cacheImage->path()))->toBe(false);
});


test('Resize to height', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);

    $assetImage = $assets->image($this->landscape);
    $cacheImage = $assetImage->resize(0, 200, false);
    $path = $cacheImage->path();
    $image = $cacheImage->get();

    expect(str_ends_with(
        $path,
        'assets/landscape-h200b.png'
    ))->toBe(true);
    expect(is_file($path))->toBe(true);
    expect(imagesy($image->get()))->toBe(200);

    $cacheImage->delete();

    expect(is_file($cacheImage->path()))->toBe(false);
});


test('Resize portrait to bounding box', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);

    $assetImage = $assets->image($this->portrait);
    $cacheImage = $assetImage->resize(200, 200, false);
    $path = $cacheImage->path();
    $image = $cacheImage->get();

    expect(str_ends_with(
        $path,
        'assets/sub/portrait-200x200b.png'
    ))->toBe(true);
    expect(is_file($path))->toBe(true);
    expect(imagesx($image->get()))->toBe(150);
    expect(imagesy($image->get()))->toBe(200);

    $cacheImage->delete();

    expect(is_file($cacheImage->path()))->toBe(false);
});


test('Resize landscape to bounding box', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);

    $assetImage = $assets->image($this->landscape);
    $cacheImage = $assetImage->resize(200, 200, false);
    $path = $cacheImage->path();
    $image = $cacheImage->get();

    expect(str_ends_with(
        $path,
        'assets/landscape-200x200b.png'
    ))->toBe(true);
    expect(is_file($path))->toBe(true);
    expect(imagesx($image->get()))->toBe(200);
    expect(imagesy($image->get()))->toBe(150);

    $cacheImage->delete();

    expect(is_file($cacheImage->path()))->toBe(false);
});


test('Crop landscape into bounding box', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);

    $assetImage = $assets->image($this->landscape);
    $cacheImage = $assetImage->resize(200, 200, true);
    $path = $cacheImage->path();
    $image = $cacheImage->get();

    expect(str_ends_with(
        $path,
        'assets/landscape-200x200c.png'
    ))->toBe(true);
    expect(is_file($path))->toBe(true);
    expect(imagesx($image->get()))->toBe(200);
    expect(imagesy($image->get()))->toBe(200);

    $cacheImage->delete();

    expect(is_file($cacheImage->path()))->toBe(false);
});


test('Crop portrait into bounding box', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);

    $assetImage = $assets->image($this->portrait);
    $cacheImage = $assetImage->resize(200, 200, true);
    $path = $cacheImage->path();
    $image = $cacheImage->get();

    expect(str_ends_with(
        $path,
        'assets/sub/portrait-200x200c.png'
    ))->toBe(true);
    expect(is_file($path))->toBe(true);
    expect(imagesx($image->get()))->toBe(200);
    expect(imagesy($image->get()))->toBe(200);

    $cacheImage->delete();

    expect(is_file($cacheImage->path()))->toBe(false);
});


test('Recreate cached file', function () {
    $tmpdir = sys_get_temp_dir() . '/chuck' . (string)mt_rand();
    mkdir($tmpdir);

    $assets = new Assets($this->paths['assets'], $tmpdir);
    $image = $assets->image($this->square);
    $cachedImage = $image->resize(200, 200, false);
    touch($cachedImage->path(), 0); // sets the date of the cached file to 1970-01-01

    expect(filemtime($cachedImage->path()))->toBe(0);

    $image = $assets->image($this->square);
    $cachedImage = $image->resize(200, 200, false);

    // Indicates that the file was recreated
    expect(filemtime($cachedImage->path()))->toBeGreaterThan(0);

    unlink($cachedImage->path());
    rmdir($tmpdir);
});


test('Resize one side 0 when cropping', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);
    $assetImage = $assets->image($this->landscape);
    $assetImage->resize(200, 0, true);
})->throws(ValueError::class, 'Image cropping error');


test('Resize height < 0 error', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);
    $assetImage = $assets->image($this->landscape);
    $assetImage->resize(200, -1);
})->throws(ValueError::class, 'not be smaller than 0');


test('Resize width < 0 error', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);
    $assetImage = $assets->image($this->landscape);
    $assetImage->resize(-1, 200);
})->throws(ValueError::class, 'not be smaller than 0');


test('Resize height too large error', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);
    $assetImage = $assets->image($this->landscape);
    $assetImage->resize(200, 10000);
})->throws(ValueError::class, 'not be larger than');


test('Resize width too large error', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);
    $assetImage = $assets->image($this->landscape);
    $assetImage->resize(10000, 200);
})->throws(ValueError::class, 'not be larger than');

test('Image path validaton', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);

    new Image($assets, $this->cached);
})->throws(RuntimeException::class, 'not inside the assets directory');

test('CachedImage path validaton', function () {
    $assets = new Assets($this->paths['assets'], $this->paths['cache']);

    new CachedImage($assets, $this->landscape);
})->throws(RuntimeException::class, 'not inside the cache directory');
