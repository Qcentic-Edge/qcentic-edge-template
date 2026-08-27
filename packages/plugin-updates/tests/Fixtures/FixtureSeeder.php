<?php

namespace QcenticEdge\PluginUpdates\Tests\Fixtures;

use Illuminate\Database\Seeder;

/**
 * The one idempotent seeder a plugin declares at registration, run whenever
 * any pending release owes a seed.
 */
class FixtureSeeder extends Seeder
{
    public function run(): void
    {
        //
    }
}
