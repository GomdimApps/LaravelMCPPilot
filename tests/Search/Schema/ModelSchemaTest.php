<?php

use App\Models\Thing;
use GomdimApps\LaravelMCPPilot\Search\Schema\ModelSchema;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\UseImportResolver;

beforeEach(function () {
    $this->schema = new ModelSchema(new AnchorExtractor, new UseImportResolver);
    $this->source = file_get_contents(__DIR__.'/../../Fixtures/app/Models/Thing.php');
});

it('supports subclasses of Eloquent Model only, via native reflection', function () {
    expect($this->schema->supports(Thing::class))->toBeTrue()
        ->and($this->schema->supports(\App\Http\Controllers\ThingController::class))->toBeFalse();
});

it('extracts $fillable and belongsTo/hasMany relationships, with a matching relations edge', function () {
    expect($this->schema->extract(Thing::class, $this->source))->toBe([
        'fillable' => ['title', 'email', 'amount', 'user_id'],
        'relationships' => [
            ['type' => 'belongsTo', 'method' => 'user', 'target' => 'App\\Models\\User'],
        ],
        'relations' => [
            ['type' => 'belongsTo', 'target' => 'App\\Models\\User'],
            ['type' => 'references_table', 'target' => 'things'],
        ],
    ]);
});
