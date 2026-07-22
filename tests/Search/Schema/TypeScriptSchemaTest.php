<?php

use GomdimApps\LaravelMCPPilot\Search\Schema\TypeScriptSchema;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;

beforeEach(function () {
    $this->schema = new TypeScriptSchema(new AnchorExtractor);
    $this->source = file_get_contents(__DIR__.'/../../Fixtures/resources/js/types/thing.ts');
});

it('supports .ts files only', function () {
    $ts = new Symfony\Component\Finder\SplFileInfo(__DIR__.'/../../Fixtures/resources/js/types/thing.ts', '', 'thing.ts');
    $vue = new Symfony\Component\Finder\SplFileInfo(__DIR__.'/../../Fixtures/resources/js/components/ThingForm.vue', '', 'ThingForm.vue');

    expect($this->schema->supports($ts))->toBeTrue()
        ->and($this->schema->supports($vue))->toBeFalse();
});

it('extracts fields from the first interface only, per documented limitation', function () {
    $extracted = $this->schema->extract($this->source);

    expect($extracted['interface_fields'])->toBe(['id', 'title', 'amount']);
});
