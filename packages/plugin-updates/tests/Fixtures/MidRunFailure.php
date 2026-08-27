<?php

namespace QcenticEdge\PluginUpdates\Tests\Fixtures;

use RuntimeException;

/**
 * The interruption a resume test needs: the third of the run fixture's four
 * migrations fails while this is armed, and succeeds once it is disarmed.
 *
 * A real failure here is a host killing the request halfway through a long
 * catch-up. There is no way to arrange that from a test, and the thing under
 * test is not what killed the run — it is that the two files already applied
 * survive it, that the stored version has not moved, and that a second attempt
 * carries on from the third file rather than starting over.
 */
final class MidRunFailure
{
    private static bool $armed = false;

    public static function arm(): void
    {
        self::$armed = true;
    }

    /** Called from a test's own setup, so that one test's interruption is never another's. */
    public static function disarm(): void
    {
        self::$armed = false;
    }

    public static function detonate(): void
    {
        if (self::$armed) {
            throw new RuntimeException('The third migration failed, as this test arranged.');
        }
    }
}
