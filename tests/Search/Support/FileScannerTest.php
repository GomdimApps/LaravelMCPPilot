<?php

use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;

it('returns an empty collection for a path that does not exist', function () {
    $scanner = new FileScanner([]);

    expect($scanner->filesUnder(__DIR__.'/../../Fixtures/no-such-directory'))->toBeEmpty();
});

it('excludes files under configured relative-path prefixes', function () {
    // relativePath() is a suffix of the *root path as given* to filesUnder(); pass a realpath()'d
    // root (as the fixture tree isn't under Testbench's base_path()) so both sides compare cleanly.
    $root = realpath(__DIR__.'/../../Fixtures/app');
    $scanner = new FileScanner([$root.'/Http']);

    $paths = $scanner->filesUnder($root)->map(fn ($file) => $scanner->relativePath($file))->all();

    expect($paths)->toContain($root.'/Models/Thing.php')
        ->and($paths)->not->toContain($root.'/Http/Controllers/ThingController.php');
});

it('returns the absolute path unchanged when the file is not under base_path()', function () {
    $scanner = new FileScanner([]);

    $file = $scanner->filesUnder(__DIR__.'/../../Fixtures/app/Models')->first();

    expect($scanner->relativePath($file))->toBe($file->getPathname());
});
