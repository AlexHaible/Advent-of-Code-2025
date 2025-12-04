<?php

namespace App\Console\Commands;

use App\Support\InputFileLoader;
use App\Support\DurationFormatter;
use Illuminate\Console\Command;

class AdventOfCodeDayFourPartTwo extends Command
{
    protected $signature = 'advent-of-code:day-four-part-two {file : File name or path to the input file}';

    protected $description = 'Day 4 Part 2: simulate removal of accessible paper rolls until none remain and output total removed.';

    public function handle(): int
    {
        $fileArg = $this->argument('file');

        try {
            $lines = InputFileLoader::loadLines($fileArg);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        // Normalise lines (trim trailing newlines only; keep dots/@)
        $grid = [];
        foreach ($lines as $line) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }
            $grid[] = $line;
        }

        $height = count($grid);
        if ($height === 0) {
            $this->warn('Input grid is empty.');
            return Command::FAILURE;
        }

        $width = strlen($grid[0]);
        // (Optionally, you can assert all rows have same width.)

        $start = hrtime(true);

        // Degree matrix: -1 for '.', >=0 for '@'
        $deg      = array_fill(0, $height, array_fill(0, $width, -1));
        $removed  = array_fill(0, $height, array_fill(0, $width, false));
        $inQueue  = array_fill(0, $height, array_fill(0, $width, false));
        $totalAt  = 0;

        $dirs = [
            [-1, -1], [0, -1], [1, -1],
            [-1,  0],          [1,  0],
            [-1,  1], [0,  1], [1,  1],
        ];

        // First pass: compute degrees and total @ count
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($grid[$y][$x] !== '@') {
                    continue;
                }

                $totalAt++;
                $count = 0;

                foreach ($dirs as [$dx, $dy]) {
                    $nx = $x + $dx;
                    $ny = $y + $dy;

                    if ($nx < 0 || $nx >= $width || $ny < 0 || $ny >= $height) {
                        continue;
                    }

                    if ($grid[$ny][$nx] === '@') {
                        $count++;
                    }
                }

                $deg[$y][$x] = $count;
            }
        }

        // Queue of cells to remove (BFS on degree < 4)
        $queue = new \SplQueue();

        // Initialize queue with all nodes that already have degree < 4
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($deg[$y][$x] >= 0 && $deg[$y][$x] < 4) {
                    $queue->enqueue([$x, $y]);
                    $inQueue[$y][$x] = true;
                }
            }
        }

        $removedCount = 0;

        while (!$queue->isEmpty()) {
            [$x, $y] = $queue->dequeue();

            if ($removed[$y][$x]) {
                continue;
            }

            // It might have increased to >=4 because of some bug, but in this algorithm
            // degrees only go down, so this check is mostly defensive.
            if ($deg[$y][$x] >= 4 || $deg[$y][$x] < 0) {
                continue;
            }

            // Remove this roll
            $removed[$y][$x] = true;
            $removedCount++;

            // Decrement degree of all neighbors
            foreach ($dirs as [$dx, $dy]) {
                $nx = $x + $dx;
                $ny = $y + $dy;

                if ($nx < 0 || $nx >= $width || $ny < 0 || $ny >= $height) {
                    continue;
                }

                if ($deg[$ny][$nx] < 0 || $removed[$ny][$nx]) {
                    continue; // not a roll or already removed
                }

                $deg[$ny][$nx]--;

                if ($deg[$ny][$nx] < 4 && !$inQueue[$ny][$nx]) {
                    $queue->enqueue([$nx, $ny]);
                    $inQueue[$ny][$nx] = true;
                }
            }
        }

        // Sanity: removedCount should be total @ minus size of 4-core
        // $coreSize = $totalAt - $removedCount;

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $removedCount,
            $duration
        ));

        return Command::SUCCESS;
    }
}
