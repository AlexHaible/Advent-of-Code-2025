<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use Illuminate\Console\Command;
use App\Support\InputFileLoader;

class AdventOfCodeDayTwoPartTwo extends Command
{
    protected $signature = 'advent-of-code:day-two-part-two {file : File name or path to the input file}';

    protected $description = 'Process ID ranges and output the sum of invalid IDs '
    . '(numbers that are some digit sequence repeated at least twice).';

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

        // Stream all repeated-pattern IDs up to the max endpoint
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

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $sum,
            $duration
        ));

        return Command::SUCCESS;
    }

    /**
     * Generate all numbers <= $maxEnd that are of the form
     * X repeated at least twice (e.g. 11, 6464, 123123, 123123123, 1111111).
     */
    private function generateInvalidIds(int $maxEnd): \Generator
    {
        $maxLen = strlen((string) $maxEnd);
        $seen   = [];

        $pow10 = [1];
        for ($i = 1; $i <= $maxLen; $i++) {
            $pow10[$i] = $pow10[$i - 1] * 10;
        }

        // totalLen is the total digit length of the final number
        for ($totalLen = 2; $totalLen <= $maxLen; $totalLen++) {
            // patternLen is the length of the repeated block
            for ($patternLen = 1; $patternLen <= intdiv($totalLen, 2); $patternLen++) {
                if ($totalLen % $patternLen !== 0) {
                    continue;
                }

                $repeats = (int) ($totalLen / $patternLen);
                if ($repeats < 2) {
                    continue;
                }

                // No leading zeros in the pattern
                $startPattern = $pow10[$patternLen - 1];
                $endPattern   = $pow10[$patternLen] - 1;
                $factor       = $pow10[$patternLen];

                for ($p = $startPattern; $p <= $endPattern; $p++) {
                    // Build the repeated number numerically instead of via strings
                    $n = $p;
                    for ($r = 1; $r < $repeats; $r++) {
                        $n = $n * $factor + $p;
                    }

                    if ($n > $maxEnd) {
                        // For this pattern length and repeat count, any larger p
                        // will only increase $n, so we can stop this inner loop.
                        break;
                    }

                    if (isset($seen[$n])) {
                        continue;
                    }

                    $seen[$n] = true;
                    yield $n;
                }
            }
        }
    }
}
