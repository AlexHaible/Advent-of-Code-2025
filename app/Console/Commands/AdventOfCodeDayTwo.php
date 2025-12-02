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

        // Stream all "repeated pattern" IDs up to the max endpoint
        // and sum only those that fall into at least one range
        $sum = 0;
        foreach ($this->generateInvalidIds($maxEnd) as $id) {
            foreach ($ranges as [$startId, $endId]) {
                if ($id >= $startId && $id <= $endId) {
                    $sum += $id;
                    break; // Don’t double-count if ranges ever overlap
                }
            }
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

        for ($totalLen = 2; $totalLen <= $maxLen; $totalLen += 2) {
            $halfLen = (int) ($totalLen / 2);

            $startPattern = (int) pow(10, $halfLen - 1);
            $endPattern   = (int) pow(10, $halfLen) - 1;

            for ($p = $startPattern; $p <= $endPattern; $p++) {
                $s = (string) $p . (string) $p;
                $n = (int) $s;

                if ($n > $maxEnd) {
                    break 2;
                }

                yield $n;
            }
        }
    }
}
