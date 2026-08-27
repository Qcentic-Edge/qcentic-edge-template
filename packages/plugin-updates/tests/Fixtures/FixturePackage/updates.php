<?php

/**
 * The manifest a plugin ships: one row per release, seeds only. Schema work is
 * never declared here — it is read from the migrator's own ledger.
 */
return [
    '0.1.0' => ['seed' => false],
    '0.2.0' => ['seed' => true],
    '0.3.0' => ['seed' => false],
];
