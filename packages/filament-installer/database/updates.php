<?php

/**
 * What each release of this package owes, for qcentic-edge/plugin-updates.
 *
 * Seeds only. Schema work is never listed here — the library reads that from
 * Laravel's own migration ledger, diffed against this package's
 * `database/migrations` path, so writing the migration file *is* the
 * declaration and the two can never disagree.
 *
 * The installer is a package like the nine it renders, and it registers itself
 * on the same terms: it appears in its own list, and shipping a release adds
 * one row here and answers one question — does it need a seed? History is not
 * backfilled, because a site that has never recorded a stored version treats
 * every row here as pending, and a historical `seed: true` would make every
 * deployed site owe a seed on day one.
 *
 * The seeders in `config('installer.seeders')` are not this: those belong to
 * the host application and run once, during first install.
 */
return [
    '0.4.0' => ['seed' => false],
];
