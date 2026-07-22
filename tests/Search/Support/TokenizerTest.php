<?php

use GomdimApps\LaravelMCPPilot\Search\Support\Tokenizer;

beforeEach(function () {
    $this->tokenizer = new Tokenizer;
});

it('splits camelCase and singularizes terms', function () {
    expect($this->tokenizer->tokenize('IndexTickets'))->toBe(['index', 'ticket']);
});

it('splits snake_case and kebab-case', function () {
    expect($this->tokenizer->tokenize('user_id-list'))->toBe(['user', 'id', 'list']);
});

it('drops terms shorter than two characters and deduplicates', function () {
    expect($this->tokenizer->tokenize('a a bb bb'))->toBe(['bb']);
});

it('collects terms from every string value in an entry, flattened', function () {
    $entry = ['kind' => 'class', 'symbol' => 'App\\Models\\Thing', 'methods' => ['getDisplayTitleAttribute']];

    expect($this->tokenizer->termsFor($entry))->toContain('thing', 'display', 'title', 'attribute', 'class');
});

it('finds postings for terms starting with the query', function () {
    $terms = ['ticket' => [1, 2], 'ticketing' => [3], 'user' => [4]];

    expect($this->tokenizer->postingsFor($terms, 'ticket'))->toBe([1, 2, 3]);
});
