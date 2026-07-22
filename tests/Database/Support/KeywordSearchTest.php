<?php

use GomdimApps\LaravelMCPPilot\Database\Support\KeywordSearch;

beforeEach(function () {
    $this->search = new KeywordSearch;
});

it('matches a keyword contained in the haystack, case-insensitively', function () {
    expect($this->search->matches('user', 'users'))->toBeTrue()
        ->and($this->search->matches('USER', 'users'))->toBeTrue()
        ->and($this->search->matches('user', 'MODEL_HAS_ROLES'))->toBeFalse();
});

it('returns false when the keyword is not contained in the haystack', function () {
    expect($this->search->matches('thing', 'users'))->toBeFalse();
});
