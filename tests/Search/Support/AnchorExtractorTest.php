<?php

use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;

beforeEach(function () {
    $this->anchor = new AnchorExtractor;
});

it('captures the balanced body immediately following the anchor', function () {
    $source = 'const x = foo([1, 2, 3]);';

    expect($this->anchor->balanced($source, 'foo\s*', '(', ')'))->toBe('[1, 2, 3]');
});

it('handles nested delimiters via recursive matching', function () {
    $source = "\$fillable = ['a', ['nested', 'array'], 'b'];";

    expect($this->anchor->balanced($source, '\$fillable\s*=\s*', '[', ']'))
        ->toBe("'a', ['nested', 'array'], 'b'");
});

it('returns null when the anchor is not found', function () {
    expect($this->anchor->balanced('no anchor here', 'missing\s*', '(', ')'))->toBeNull();
});

it('requires the gap to reach the opening delimiter, avoiding an unrelated earlier occurrence', function () {
    $source = "import { defineProps } from 'vue';\ndefineProps({ title: '' })";

    expect($this->anchor->balanced($source, 'defineProps\s*', '(', ')'))
        ->toBe("{ title: '' }");
});

it('extracts top-level key names from an object/array body', function () {
    $body = "title: string\n  amount: number\n  'user_id': number";

    expect($this->anchor->fieldNames($body))->toBe(['title', 'amount', 'user_id']);
});

it('extracts plain quoted string items from a list body', function () {
    $body = "'title', 'email', 'amount'";

    expect($this->anchor->quotedItems($body))->toBe(['title', 'email', 'amount']);
});
