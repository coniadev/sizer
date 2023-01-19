<?php

declare(strict_types=1);

use Conia\Sizer\Exception\InvalidArgumentException;
use Conia\Sizer\Exception\RuntimeException;
use Conia\Sizer\Exception\ValueError;
use Conia\Sizer\Util\Image;

beforeEach(function () {
    $root = __DIR__ . '/Fixtures';
    $this->cache = $root . '/cache';
    $this->landscape = $root . '/assets/landscape.png';
    $this->portrait = $root . '/assets/sub/portrait.png';
    $this->square = $root . '/assets/square.png';
    $this->jpeg = $root . '/assets/image.jpg';
    $this->webp = $root . '/assets/image.webp';
    $this->gif = $root . '/static/pixel.gif';
    $this->nonexistent = $root . '/assets/doesnotexist.png';
    $this->wrongext = $root . '/assets/image.ext';
    $this->failing = $root . '/assets/failing.jpg';
});


test('Failing static initialization', function () {
    Image::getImageFromPath($this->nonexistent);
})->throws(InvalidArgumentException::class, 'does not exist');


test('Static create resized', function () {
    $tmpfile = $this->cache . '/temp.png';

    expect(is_file($tmpfile))->toBe(false);

    Image::createResizedImage($this->landscape, $tmpfile, 200);

    expect(is_file($tmpfile))->toBe(true);

    unlink($tmpfile);
});


test('Initialize PNG', function () {
    $tmpfile = $this->cache . '/temp.png';
    $image = new Image($this->landscape);
    expect($image->get())->toBeInstanceOf(GdImage::class);
    $image->write($tmpfile);
    expect(is_file($tmpfile))->toBe(true);
    unlink($tmpfile);
});


test('Initialize JPG', function () {
    $tmpfile = $this->cache . '/temp.jpg';
    $image = new Image($this->jpeg);
    expect($image->get())->toBeInstanceOf(GdImage::class);
    $image->write($tmpfile);
    expect(is_file($tmpfile))->toBe(true);
    unlink($tmpfile);
});


test('Initialize WEBP', function () {
    $tmpfile = $this->cache . '/temp.webp';
    $image = new Image($this->webp);
    expect($image->get())->toBeInstanceOf(GdImage::class);
    $image->write($tmpfile);
    expect(is_file($tmpfile))->toBe(true);
    unlink($tmpfile);
});


test('Initialize GIF', function () {
    $tmpfile = $this->cache . '/temp.gif';
    $image = new Image($this->gif);
    expect($image->get())->toBeInstanceOf(GdImage::class);
    $image->write($tmpfile);
    expect(is_file($tmpfile))->toBe(true);
    unlink($tmpfile);
});


test('Failing initialization', function () {
    $image = new Image($this->nonexistent);
    $image->get();
})->throws(RuntimeException::class, 'does not exist');


test('Failing write', function () {
    $image = new Image($this->landscape);
    $image->write($this->cache . '/temp.ext');
})->throws(InvalidArgumentException::class, 'extension not supported');


test('Failing file extension', function () {
    $image = new Image($this->wrongext);
    $image->get();
})->throws(InvalidArgumentException::class, 'is not a valid');


test('Failing image', function () {
    $image = new Image($this->failing);
    $image->get();
})->throws(InvalidArgumentException::class, 'is not a valid');


test('Missing width/height', function () {
    $image = new Image($this->landscape);
    $image->resize();
})->throws(ValueError::class, 'Height and/or width');


test('Resize width, place in bounding box', function () {
    $image = new Image($this->landscape);
    $gdImage = $image->resize(200);

    expect($gdImage)->toBeInstanceOf(GdImage::class);

    $w = imagesx($gdImage);
    $h = imagesy($gdImage);

    expect($w)->toBe(200);
    expect($h)->toBe(150);
});


test('Resize height, place in bounding box', function () {
    $image = new Image($this->landscape);
    $gdImage = $image->resize(height: 300);
    $w = imagesx($gdImage);
    $h = imagesy($gdImage);

    expect($w)->toBe(400);
    expect($h)->toBe(300);
});


test('Resize width/height, place in bounding box', function () {
    // Landscape mode
    $image = new Image($this->landscape);
    $gdImage = $image->resize(200, 200);
    $w = imagesx($gdImage);
    $h = imagesy($gdImage);

    expect($w)->toBe(200);
    expect($h)->toBe(150);

    // Portrait mode
    $image = new Image($this->portrait);
    $gdImage = $image->resize(200, 200);
    $w = imagesx($gdImage);
    $h = imagesy($gdImage);

    expect($w)->toBe(150);
    expect($h)->toBe(200);
});


test('Resize cropped', function () {
    $image = new Image($this->landscape);
    $gdImage = $image->resize(200, 200, true);
    $w = imagesx($gdImage);
    $h = imagesy($gdImage);

    expect($w)->toBe(200);
    expect($h)->toBe(200);

    $image = new Image($this->portrait);
    $gdImage = $image->resize(200, 200, true);
    $w = imagesx($gdImage);
    $h = imagesy($gdImage);

    expect($w)->toBe(200);
    expect($h)->toBe(200);

    $image = new Image($this->square);
    $gdImage = $image->resize(200, 200, true);
    $w = imagesx($gdImage);
    $h = imagesy($gdImage);

    expect($w)->toBe(200);
    expect($h)->toBe(200);
});


test('Already in bounding box', function () {
    $image = new Image($this->landscape);
    $gdImage = $image->resize(1000, 1000);
    $w = imagesx($gdImage);
    $h = imagesy($gdImage);

    expect($w)->toBe(800);
    expect($h)->toBe(600);
});
