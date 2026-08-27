<?php

/**
 * A package with a history, so a test can place a database at any point in it.
 *
 * Seeds only — schema is never declared here. Which release shipped which
 * migration file is recorded in the test suite (see
 * `releaseFixture(HISTORY_FIXTURE)` in Pest.php) and deliberately nowhere in
 * the library: a map from migration file to version would be a third
 * hand-maintained copy of a fact the migrator already holds.
 *
 * Releases, and what each shipped:
 *
 *   0.1.0  create_history_widgets_table
 *   0.2.0  create_history_notes_table
 *   0.3.0  no schema, owes a seed
 *   0.4.0  nothing
 *   0.5.0  add_colour_to_history_widgets_table, create_history_tags_table
 */
return [
    '0.1.0' => ['seed' => false],
    '0.2.0' => ['seed' => false],
    '0.3.0' => ['seed' => true],
    '0.4.0' => ['seed' => false],
    '0.5.0' => ['seed' => false],
];
