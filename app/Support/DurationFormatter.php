<?php

namespace App\Support;

final class DurationFormatter
{
    /**
     * Format a duration given as a start timestamp from hrtime(true).
     */
    public static function formatFromStart(int $startNs): string
    {
        $durationNs = hrtime(true) - $startNs;

        return self::format($durationNs);
    }

    /**
     * Format a duration in nanoseconds into a human-readable value + unit.
     *
     * Uses:
     * - ns for < 1,000 ns
     * - µs for < 1,000,000 ns
     * - ms for < 1,000,000,000 ns
     * - s  otherwise
     */
    public static function format(int $durationNs): string
    {
        if ($durationNs < 1000) {
            $time = $durationNs;
            $unit = 'ns';
        } elseif ($durationNs < 1_000_000) {
            $time = $durationNs / 1000;
            $unit = 'µs';
        } elseif ($durationNs < 1_000_000_000) {
            $time = $durationNs / 1_000_000;
            $unit = 'ms';
        } else {
            $time = $durationNs / 1_000_000_000;
            $unit = 's';
        }

        return sprintf('%.3f %s', $time, $unit);
    }
}
