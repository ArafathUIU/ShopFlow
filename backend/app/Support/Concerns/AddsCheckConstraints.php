<?php

namespace App\Support\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Adds CHECK constraints to a table.
 *
 * The Laravel 13 schema builder does not expose a `check()` method, so
 * constraints are added via raw SQL. SQLite cannot ALTER TABLE to add
 * constraints, so this is a no-op for non-PostgreSQL connections (the test
 * suite runs on in-memory SQLite; production runs on PostgreSQL).
 */
trait AddsCheckConstraints
{
    protected function addCheck(string $table, string $name, string $expression): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            sprintf('ALTER TABLE %s ADD CONSTRAINT %s CHECK (%s);', $table, $name, $expression)
        );
    }
}
