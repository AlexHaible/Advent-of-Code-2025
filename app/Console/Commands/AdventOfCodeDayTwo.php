<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\InputFileLoader;

class AdventOfCodeDayTwo extends Command
{
    protected $signature = 'advent-of-code:day-two {file : File name or path to the input file}';

    protected $description = 'Process ID ranges and output the sum of invalid IDs (numbers that are some digit sequence repeated twice).';

    public function handle()
    {
        $fileArg = $this->argument('file');

        try {
            $lines = InputFileLoader::loadLines($fileArg);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        $start = hrtime(true);

        $ranges = [];
        $maxEnd = 0;

        // Parse ranges from one or more lines of comma-separated "start-end"
        foreach ($lines as $rawLine) {
            $line = trim($rawLine);

            if ($line === '') {
                continue;
            }

            $parts = explode(',', $line);

            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }

                if (!preg_match('/^(\d+)-(\d+)$/', $part, $m)) {
                    $this->warn("Skipping invalid range: {$part}");
                    continue;
                }

                $startId = (int) $m[1];
                $endId   = (int) $m[2];

                if ($startId > $endId) {
                    [$startId, $endId] = [$endId, $startId];
                }

                $ranges[] = [$startId, $endId];

                if ($endId > $maxEnd) {
                    $maxEnd = $endId;
                }
            }
        }

        if (empty($ranges)) {
            $this->warn('No valid ranges found in input.');
            return Command::FAILURE;
        }

        // Sort and merge ranges to minimize membership checks
        usort($ranges, static function (array $a, array $b): int {
            return $a[0] <=> $b[0];
        });

        $merged = [];
        foreach ($ranges as [$startId, $endId]) {
            if (empty($merged)) {
                $merged[] = [$startId, $endId];
                continue;
            }

            $lastIndex = \count($merged) - 1;
            [$lastStart, $lastEnd] = $merged[$lastIndex];

            if ($startId <= $lastEnd + 1) {
                // Overlapping or adjacent; merge
                $merged[$lastIndex][1] = max($lastEnd, $endId);
            } else {
                $merged[] = [$startId, $endId];
            }
        }

        $ranges = $merged;

        $lastRangeEnd  = $ranges[\array_key_last($ranges)][1];
        $rangeIndex    = 0;
        $rangeCount    = \count($ranges);

        // Stream all "repeated pattern" IDs up to the max endpoint
        // and sum only those that fall into at least one range
        $sum = 0;
        foreach ($this->generateInvalidIds($maxEnd) as $id) {
            // If we've passed the last range, we can stop entirely
            if ($id > $lastRangeEnd) {
                break;
            }

            // Advance the current range index while this ID is beyond the end
            while ($rangeIndex < $rangeCount && $id > $ranges[$rangeIndex][1]) {
                $rangeIndex++;
            }

            if ($rangeIndex >= $rangeCount) {
                break;
            }

            [$startId, $endId] = $ranges[$rangeIndex];

            // If the ID is before the current range start, it isn't in any range yet
            if ($id < $startId) {
                continue;
            }

            // At this point, $startId <= $id <= $endId
            $sum += $id;
        }

        $durationNs = hrtime(true) - $start;

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

        $this->info(sprintf(
            '%d (took %.3f %s)',
            $sum,
            $time,
            $unit
        ));

        return Command::SUCCESS;
    }

    /**
     * Generate all numbers <= $maxEnd that are of the form
     * X repeated twice (e.g. 11, 6464, 123123).
     */
    private function generateInvalidIds(int $maxEnd): \Generator
    {
        $maxLen = strlen((string) $maxEnd);

        $pow10 = [1];
        for ($i = 1; $i <= $maxLen; $i++) {
            $pow10[$i] = $pow10[$i - 1] * 10;
        }

        for ($totalLen = 2; $totalLen <= $maxLen; $totalLen += 2) {
            $halfLen = (int) ($totalLen / 2);

            $startPattern = $pow10[$halfLen - 1];
            $endPattern   = $pow10[$halfLen] - 1;
            $factor       = $pow10[$halfLen];

            for ($p = $startPattern; $p <= $endPattern; $p++) {
                // Build the repeated number numerically: n = p * 10^halfLen + p
                $n = $p * $factor + $p;

                if ($n > $maxEnd) {
                    // For this half-length, larger patterns only increase n;
                    // and for larger total lengths, n will only grow further.
                    break 2;
                }

                yield $n;
            }
        }
    }
}
