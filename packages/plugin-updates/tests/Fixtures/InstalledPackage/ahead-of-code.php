<?php

/**
 * The manifest of a package whose author added the row for the next release
 * before bumping its `composer.json` — the ordering the one-row developer
 * checklist invites, and the one that must not read as a site owing work its
 * deployed code cannot do.
 *
 * The first release is the version this library's own composer.json carries,
 * which is the version the code is at while this suite runs. The second is
 * unreachably above it.
 */
return [
    libraryComposer()['version'] => ['seed' => false],
    '99.0.0' => ['seed' => true],
];
