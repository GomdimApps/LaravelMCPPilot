<?php

use GomdimApps\LaravelMCPPilot\Search\Schema\VueComponentSchema;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;

beforeEach(function () {
    $this->schema = new VueComponentSchema(new AnchorExtractor);
    $this->source = file_get_contents(__DIR__.'/../../Fixtures/resources/js/components/ThingForm.vue');
});

it('supports .vue files only', function () {
    $vue = new Symfony\Component\Finder\SplFileInfo(__DIR__.'/../../Fixtures/resources/js/components/ThingForm.vue', '', 'ThingForm.vue');
    $ts = new Symfony\Component\Finder\SplFileInfo(__DIR__.'/../../Fixtures/resources/js/types/thing.ts', '', 'thing.ts');

    expect($this->schema->supports($vue))->toBeTrue()
        ->and($this->schema->supports($ts))->toBeFalse();
});

it('extracts props, emits, and useForm fields from <script setup> only', function () {
    $extracted = $this->schema->extract($this->source);

    expect($extracted['props'])->toBe(['title', 'amount'])
        ->and($extracted['emits'])->toBe(['saved'])
        ->and($extracted['form_fields'])->toBe(['title', 'amount']);
});
