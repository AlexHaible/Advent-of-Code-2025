<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use Illuminate\Console\Command;

class AdventOfCodeDayFive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * If you pass "input.txt", it reads: storage/app/public/input.txt
     * If you pass an absolute path, it reads directly.
     */
    protected $signature = 'advent-of-code:day-five {file : File name or path to the input file}';

    protected $description = 'Count how many available ingredient IDs are fresh according to the database ranges.';

    public function handle(): int
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
        $ids    = [];

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') {
                continue;
            }

            // Range line: "a-b"
            if (str_contains($line, '-')) {
                [$startStr, $endStr] = array_pad(explode('-', $line, 2), 2, null);

                if ($startStr === null || $endStr === null || !ctype_digit($startStr) || !ctype_digit($endStr)) {
                    $this->warn("Skipping invalid range line: {$line}");
                    continue;
                }

                $startId = (int) $startStr;
                $endId   = (int) $endStr;

                if ($startId > $endId) {
                    [$startId, $endId] = [$endId, $startId];
                }

                $ranges[] = [$startId, $endId];
                continue;
            }

            // ID line
            if (ctype_digit($line)) {
                $ids[] = (int) $line;
            } else {
                $this->warn("Skipping invalid ID line: {$line}");
            }
        }

        if ($ranges === [] || $ids === []) {
            $duration = DurationFormatter::formatFromStart($start);
            $this->info(sprintf('0 (took %s)', $duration));
            return Command::SUCCESS;
        }

        // Sort and merge ranges to avoid O(N*M) checks
        usort($ranges, static fn (array $a, array $b) => $a[0] <=> $b[0]);

        $merged = [];
        foreach ($ranges as [$s, $e]) {
            if ($merged === []) {
                $merged[] = [$s, $e];
                continue;
            }

            $lastIndex           = array_key_last($merged);
            [$lastStart, $lastEnd] = $merged[$lastIndex];

            if ($s <= $lastEnd + 1) {
                // Overlapping or touching; merge
                $merged[$lastIndex][1] = max($lastEnd, $e);
            } else {
                $merged[] = [$s, $e];
            }
        }
        $ranges = $merged;

        // Sort IDs and walk both lists with a pointer
        sort($ids);
        $rangeIndex = 0;
        $rangeCount = count($ranges);
        $freshCount = 0;

        foreach ($ids as $id) {
            // Advance past ranges that end before this id
            while ($rangeIndex < $rangeCount && $id > $ranges[$rangeIndex][1]) {
                $rangeIndex++;
            }

            if ($rangeIndex >= $rangeCount) {
                // All remaining IDs will be larger; none can be fresh
                break;
            }

            [$startId, $endId] = $ranges[$rangeIndex];

            if ($id >= $startId && $id <= $endId) {
                $freshCount++;
            }
        }

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $freshCount,
            $duration
        ));

        return Command::SUCCESS;
    }
}