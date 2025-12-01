<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\InputFileLoader;

class AdventOfCodeDayOnePartTwo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * If you pass "input.txt", it reads: storage/app/public/input.txt
     * If you pass an absolute path, it reads directly.
     */
    protected $signature = 'advent-of-code:day-one-part-two {file : File name or path to the input file}';

    protected $description = 'Process the rotation list using method 0x434C49434B and output how many clicks land on 0. File defaults to public storage.';

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

        // Dial config
        $position = 50;
        $zeroCount = 0;
        $range = 100; // 0–99

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);

            if ($line === '') {
                continue;
            }

            $direction = $line[0];
            $amountStr = substr($line, 1);

            if (!in_array($direction, ['L', 'R'], true) || !ctype_digit($amountStr)) {
                $this->warn("Skipping invalid line: {$line}");
                continue;
            }

            $amount = (int) $amountStr;

            if ($amount === 0) {
                continue;
            }

            // Step per click: -1 for L, +1 for R
            $step = $direction === 'L' ? -1 : 1;

            // Simulate each click and count every time we land on 0
            for ($i = 0; $i < $amount; $i++) {
                $position += $step;

                // Wrap into 0–99
                $position = (($position % $range) + $range) % $range;

                if ($position === 0) {
                    $zeroCount++;
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
            $zeroCount,
            $time,
            $unit
        ));

        return Command::SUCCESS;
    }
}
