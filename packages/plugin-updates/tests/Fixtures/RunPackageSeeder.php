<?php

namespace QcenticEdge\PluginUpdates\Tests\Fixtures;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The one idempotent seeder a package declares at registration — except that
 * this one deliberately is not idempotent. It appends a row every time it runs.
 *
 * That is the point. A seed owed by several skipped releases must run once, and
 * the spec insists that be asserted by the resulting data rather than by
 * counting calls; an idempotent seeder would leave one row whether it ran once
 * or twice, and would hide exactly the bug this fixture exists to catch.
 */
class RunPackageSeeder extends Seeder
{
    public const TABLE = 'run_notes';

    public function run(): void
    {
        DB::table(self::TABLE)->insert(['body' => 'seeded']);
    }
}
