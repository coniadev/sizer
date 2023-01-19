<?php

declare(strict_types=1);

use Conia\Sizer\Util\Path;

test('Path realpath', function () {
    expect(
        Path::realpath('/perserverance/./of/././the/../time')
    )->toBe('/perserverance/of/time');
    expect(
        Path::realpath('spiritual/../../../healing')
    )->toBe('healing');
    expect(
        Path::realpath('\\\\///perserverance//\\.\\/of/.///./the//../\\\\time\\\\', separator: '/')
    )->toBe('/perserverance/of/time/');
});


test('Path is inside root dir', function () {
    $root = __DIR__ . '/Fixtures';

    expect(Path::inside($root, $root . '/../leprosy'))->toBe(false);
    expect(Path::inside($root, $root . '/symbolic'))->toBe(true);
    expect(Path::inside($root, $root . '/./././symbolic'))->toBe(true);
    expect(Path::inside($root, $root . '/./.././symbolic'))->toBe(false);
    expect(Path::inside($root, '/etc/apache'))->toBe(false);
});
