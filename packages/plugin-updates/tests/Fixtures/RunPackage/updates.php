<?php

/**
 * A package a run test can actually run.
 *
 * Every release here sits below the version of the only package guaranteed to
 * be installed while this suite runs — the library itself — because a run
 * refuses a package whose deployed version Composer does not know, and the
 * fixture has to be a package Composer really knows. The newest release is
 * deliberately *not* the code version either, so that "the stored version
 * advances to the code version, not to an intermediate one" is a claim a test
 * can tell apart from "advances to the newest pending release".
 *
 * Seeds only. Which release shipped which migration file is recorded in the
 * test suite (see `runReleases()` in Pest.php) and deliberately nowhere in the
 * library.
 *
 * Releases, and what each shipped:
 *
 *   0.0.1  create_run_widgets_table
 *   0.0.2  create_run_notes_table, and owes a seed
 *   0.0.3  create_run_tags_table, and owes a seed
 *   0.0.4  add_colour_to_run_widgets_table
 *
 * Two of them owe a seed, which is the case that matters: a site catching up
 * across both must seed once, not twice.
 */
return [
    '0.0.1' => ['seed' => false],
    '0.0.2' => ['seed' => true],
    '0.0.3' => ['seed' => true],
    '0.0.4' => ['seed' => false],
];
