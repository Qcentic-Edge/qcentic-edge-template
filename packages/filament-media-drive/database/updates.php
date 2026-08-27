<?php

/**
 * What each release of filament-media-drive owes a site's database.
 *
 * Seeds only. Whether schema work is owed is read from Laravel's own migration
 * ledger, and this package owns no migrations at all — it browses and picks
 * media that Spatie's media library owns, and creates no tables of its own.
 *
 * The current release is recorded so the package appears in the operator's list
 * at the version its code is on. It is deliberately not backfilled with history
 * and deliberately not marked `seed: true`: a site that has never recorded a
 * stored version treats every row here as pending, so a seed flag written for a
 * release that never seeded anything would make every deployed site owe a seed
 * on day one.
 *
 * Shipping a release adds one row and answers one question: does it seed?
 */

return [
    '0.1.0' => ['seed' => false],
];
