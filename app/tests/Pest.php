<?php

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

require __DIR__.'/Support/authz.php';
