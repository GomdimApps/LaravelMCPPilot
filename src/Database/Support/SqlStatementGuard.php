<?php

namespace GomdimApps\LaravelMCPPilot\Database\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;

/** Pure text guards around a single SQL statement — no I/O, no connection, safe to reuse standalone. */
class SqlStatementGuard
{
    private const READ_KEYWORDS = ['SELECT', 'WITH'];

    private const WRITE_KEYWORDS = ['INSERT', 'UPDATE', 'DELETE'];

    public function __construct(private readonly int $maxRows) {}

    public function maxRows(): int
    {
        return $this->maxRows;
    }

    public function statementType(string $query): string
    {
        return strtoupper(Str::match('/^[a-zA-Z]+/', trim($query)));
    }

    public function isRead(string $type): bool
    {
        return in_array($type, self::READ_KEYWORDS, true);
    }

    public function isWrite(string $type): bool
    {
        return in_array($type, self::WRITE_KEYWORDS, true);
    }

    /**
     * Blocks obvious multi-statement payloads; a `;` inside a string literal is a rare
     * false positive we accept in exchange for a simple, dependency-free guard.
     */
    public function assertSingleStatement(string $query): void
    {
        if (str_contains(rtrim($query, "; \t\n\r"), ';')) {
            throw new InvalidArgumentException('Only a single SQL statement is allowed per call.');
        }
    }

    /**
     * Caps unbounded SELECTs at maxRows to avoid dumping huge result sets into the caller's
     * context; a `LIMIT` already present in the query is respected as-is.
     */
    public function capSelect(string $query): string
    {
        if (Str::contains(Str::lower($query), 'limit')) {
            return $query;
        }

        return rtrim($query, "; \t\n\r").' LIMIT '.$this->maxRows;
    }
}
