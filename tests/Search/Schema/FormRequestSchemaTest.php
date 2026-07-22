<?php

use App\Http\Requests\StoreThingRequest;
use GomdimApps\LaravelMCPPilot\Search\Schema\FormRequestSchema;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;

beforeEach(function () {
    $this->schema = new FormRequestSchema(new AnchorExtractor);
    $this->source = file_get_contents(__DIR__.'/../../Fixtures/app/Http/Requests/StoreThingRequest.php');
});

it('supports FormRequest subclasses only', function () {
    expect($this->schema->supports(StoreThingRequest::class))->toBeTrue()
        ->and($this->schema->supports(\App\Models\Thing::class))->toBeFalse();
});

it('keeps rule values as raw source text, including mixed array/string/expression forms', function () {
    $extracted = $this->schema->extract(StoreThingRequest::class, $this->source);

    expect($extracted['rules'])->toBe([
        'title' => "['required', 'string', 'max:255']",
        'email' => "'required|email'",
        'amount' => "['nullable', 'numeric', 'min:0']",
        'user_id' => "'required|exists:users,id,deleted_at,'.null",
    ]);
});

it('extracts the authorize() expression, preferring a can(...) sub-match', function () {
    $extracted = $this->schema->extract(StoreThingRequest::class, $this->source);

    expect($extracted['authorize'])->toBe("can('create', Thing::class)");
});
