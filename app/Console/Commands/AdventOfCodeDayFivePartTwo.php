<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use Illuminate\Console\Command;

class AdventOfCodeDayFivePartTwo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * If you pass "input.txt", it reads: storage/app/public/input.txt
     * If you pass an absolute path, it reads directly.
     */
    protected $signature = 'advent-of-code:day-five-part-two {file : File name or path to the input file}';

    protected $description = 'Day 5 Part 2: count how many ingredient IDs are considered fresh by the ranges.';

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

        // Parse fresh ranges from the top section until the first blank line.
        $ranges = [];

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);

            if ($line === '') {
                // Stop at the blank line; lower section is irrelevant for part 2.
                break;
            }

            if (!preg_match('/^(\d+)\s*-\s*(\d+)$/', $line, $m)) {
                $this->warn("Skipping invalid range line: {$line}");
                continue;
            }

            $startId = (int) $m[1];
            $endId   = (int) $m[2];

            if ($startId > $endId) {
                [$startId, $endId] = [$endId, $startId];
            }

            $ranges[] = [$startId, $endId];
        }

        if (empty($ranges)) {
            $this->warn('No valid ranges found in input.');
            return Command::FAILURE;
        }

        // Sort ranges by start and merge overlaps/adjacent ones
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

        // Count total distinct IDs covered by the merged ranges
        $freshCount = 0;
        foreach ($merged as [$startId, $endId]) {
            $freshCount += ($endId - $startId + 1);
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
