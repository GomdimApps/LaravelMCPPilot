<?php

use GomdimApps\LaravelMCPPilot\Database\Support\SqlStatementGuard;

beforeEach(function () {
    $this->guard = new SqlStatementGuard(maxRows: 200);
});

it('detects the statement type from the first keyword, case-insensitively', function () {
    expect($this->guard->statementType('select * from users'))->toBe('SELECT')
        ->and($this->guard->statementType('  WITH cte AS (...) SELECT * FROM cte'))->toBe('WITH')
        ->and($this->guard->statementType('insert into users (id) values (1)'))->toBe('INSERT');
});

it('classifies read vs write statement types', function () {
    expect($this->guard->isRead('SELECT'))->toBeTrue()
        ->and($this->guard->isRead('WITH'))->toBeTrue()
        ->and($this->guard->isRead('INSERT'))->toBeFalse()
        ->and($this->guard->isWrite('INSERT'))->toBeTrue()
        ->and($this->guard->isWrite('UPDATE'))->toBeTrue()
        ->and($this->guard->isWrite('DELETE'))->toBeTrue()
        ->and($this->guard->isWrite('SELECT'))->toBeFalse();
});

it('allows a single statement, with or without a trailing semicolon/whitespace', function () {
    $this->guard->assertSingleStatement('SELECT * FROM users');
    $this->guard->assertSingleStatement('SELECT * FROM users;');
    $this->guard->assertSingleStatement("SELECT * FROM users;  \n");

    expect(true)->toBeTrue();
});

it('rejects a query containing more than one statement', function () {
    $this->guard->assertSingleStatement('SELECT * FROM users; DROP TABLE users');
})->throws(InvalidArgumentException::class, 'Only a single SQL statement is allowed per call.');

it('appends a LIMIT clause to an unbounded select', function () {
    expect($this->guard->capSelect('SELECT * FROM users'))->toBe('SELECT * FROM users LIMIT 200');
});

it('leaves a query with an existing LIMIT clause untouched, case-insensitively', function () {
    expect($this->guard->capSelect('SELECT * FROM users limit 10'))->toBe('SELECT * FROM users limit 10');
});

it('exposes the configured max row count', function () {
    expect($this->guard->maxRows())->toBe(200);
});
