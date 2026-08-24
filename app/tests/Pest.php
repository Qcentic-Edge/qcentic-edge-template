<?php

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in(__DIR__.'/../../packages/filament-media-drive/tests');

require __DIR__.'/Support/authz.php';

$driveHelpers = __DIR__.'/../../packages/filament-media-drive/tests/helpers.php';

if (is_file($driveHelpers)) {
    require $driveHelpers;
}
