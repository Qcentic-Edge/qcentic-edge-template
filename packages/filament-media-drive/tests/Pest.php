<?php

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in(__DIR__);

if (! function_exists('seedUser')) {
    require dirname(__DIR__, 2).'/app/tests/Support/authz.php';
}

require __DIR__.'/helpers.php';
