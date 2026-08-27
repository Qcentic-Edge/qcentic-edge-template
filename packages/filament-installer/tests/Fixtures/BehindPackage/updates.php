<?php

/**
 * A fixture package whose database is several releases behind its code.
 *
 * It registers under a Composer name that is really installed while this suite
 * runs — the update library's own — because a run refuses a package whose
 * deployed version Composer cannot resolve, and this fixture has to be one an
 * operator could actually click Update on. Every release listed here therefore
 * sits below that package's version.
 *
 * Three releases and two unapplied migrations: enough for the page to have to
 * collapse a multi-release gap into a single actionable row rather than one row
 * per pending version.
 */
return [
    '0.0.1' => ['seed' => false],
    '0.0.2' => ['seed' => false],
    '0.0.3' => ['seed' => false],
];
