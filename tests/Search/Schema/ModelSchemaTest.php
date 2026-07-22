<?php

use App\Models\Thing;
use GomdimApps\LaravelMCPPilot\Search\Schema\ModelSchema;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;

beforeEach(function () {
    $this->schema = new ModelSchema(new AnchorExtractor);
    $this->source = file_get_contents(__DIR__.'/../../Fixtures/app/Models/Thing.php');
});

it('supports classes under a Models namespace only', function () {
    expect($this->schema->supports(Thing::class))->toBeTrue()
        ->and($this->schema->supports(\App\Http\Controllers\ThingController::class))->toBeFalse();
});

it('extracts $fillable as a plain list of quoted field names', function () {
    expect($this->schema->extract(Thing::class, $this->source))
        ->toBe(['fillable' => ['title', 'email', 'amount', 'user_id']]);
});
