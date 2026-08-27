<?php

namespace QcenticEdge\FilamentInstaller\Tests\Fixtures;

use Illuminate\Database\Seeder;

class ProbeSeeder extends Seeder
{
    public static bool $ran = false;

    public function run(): void
    {
        self::$ran = true;
    }
}
