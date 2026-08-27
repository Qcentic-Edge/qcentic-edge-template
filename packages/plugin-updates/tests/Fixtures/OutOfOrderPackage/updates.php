<?php

/**
 * A manifest whose releases are listed in no particular order, and which spans
 * the version whose string ordering disagrees with its version ordering:
 * `0.10.0` is above `0.9.0`, and `'0.10.0' < '0.9.0'` as strings.
 *
 * A package author adds a row when they ship; nothing keeps the file sorted,
 * so nothing may depend on it being sorted.
 */
return [
    '0.10.0' => ['seed' => false],
    '0.2.0' => ['seed' => false],
    '0.9.0' => ['seed' => true],
    '0.1.0' => ['seed' => false],
];
