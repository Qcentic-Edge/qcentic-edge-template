<?php

namespace App\Support;

final class ReverbScaling
{
    /**
     * Reverb fan-out is Redis-only. Empty URLs keep the in-memory server.
     */
    public static function enabled(?string $redisUrl, ?string $reverbRedis = null): bool
    {
        return ($redisUrl !== null && $redisUrl !== '')
            || ($reverbRedis !== null && $reverbRedis !== '');
    }
}
