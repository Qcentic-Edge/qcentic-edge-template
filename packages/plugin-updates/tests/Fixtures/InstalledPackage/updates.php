<?php

/**
 * A manifest for a package that really is installed under a Composer name, so
 * a test can put the stored version, the code version and the newest release
 * all at the same point.
 *
 * The only package guaranteed to be installed while this suite runs is the
 * library itself, so the single release this declares is read from the
 * library's own composer.json rather than hard-coded — bumping the library
 * must not quietly turn this fixture into a version gap.
 */
return [
    libraryComposer()['version'] => ['seed' => false],
];
