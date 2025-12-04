<?php

namespace App\Console\Commands;

use App\Support\InputFileLoader;
use App\Support\DurationFormatter;
use Illuminate\Console\Command;

class AdventOfCodeDayFour extends Command
{
    /**
     * The name and signature of the console command.
     *
     * If you pass "input.txt", it reads: storage/app/public/input.txt
     * If you pass an absolute path, it reads directly.
     */
    protected $signature = 'advent-of-code:day-four {file : File name or path to the input file}';

    protected $description = 'Count how many rolls of paper (@) can be accessed by a forklift '
    . '(fewer than 4 adjacent rolls in the 8 surrounding cells).';

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

        // Build grid, ignoring completely empty lines
        $grid = [];
        foreach ($lines as $rawLine) {
            $line = rtrim($rawLine, "\r\n");
            if ($line === '') {
                continue;
            }
            $grid[] = $line;
        }

        if (empty($grid)) {
            $this->warn('No grid data found in input.');
            return Command::FAILURE;
        }

        $rows = \count($grid);
        // In normal AoC-style input, all rows are same length; still, be a bit defensive
        $rowLengths = array_map('strlen', $grid);
        $maxCols = max($rowLengths);

        $accessible = 0;

        // 8 directions around a cell
        $dirs = [
            [-1, -1], [-1, 0], [-1, 1],
            [ 0, -1],          [ 0, 1],
            [ 1, -1], [ 1, 0], [ 1, 1],
        ];

        for ($y = 0; $y < $rows; $y++) {
            $cols = $rowLengths[$y]; // actual length of this row

            for ($x = 0; $x < $cols; $x++) {
                if ($grid[$y][$x] !== '@') {
                    continue;
                }

                $adjacentRolls = 0;

                foreach ($dirs as [$dy, $dx]) {
                    $ny = $y + $dy;
                    $nx = $x + $dx;

                    if ($ny < 0 || $ny >= $rows) {
                        continue;
                    }

                    // Some rows could theoretically be shorter
                    if ($nx < 0 || $nx >= $rowLengths[$ny]) {
                        continue;
                    }

                    if ($grid[$ny][$nx] === '@') {
                        $adjacentRolls++;
                    }

                    // Early exit if we already hit the threshold
                    if ($adjacentRolls >= 4) {
                        break;
                    }
                }

                if ($adjacentRolls < 4) {
                    $accessible++;
                }
            }
        }

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $accessible,
            $duration
        ));

        return Command::SUCCESS;
    }
}
