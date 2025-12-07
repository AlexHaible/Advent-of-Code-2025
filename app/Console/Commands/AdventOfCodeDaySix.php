<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use Illuminate\Console\Command;

class AdventOfCodeDaySix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * If you pass "input.txt", it reads: storage/app/public/input.txt
     * If you pass an absolute path, it reads directly.
     */
    protected $signature = 'advent-of-code:day-six {file : File name or path to the input file}';

    protected $description = 'Solve the cephalopod math worksheet and output the grand total.';

    public function handle(): int
    {
        $fileArg = $this->argument('file');

        try {
            $lines = InputFileLoader::loadLines($fileArg);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        if (empty($lines)) {
            $this->warn('Input appears to be empty.');
            return Command::FAILURE;
        }

        $start = hrtime(true);

        // Normalize to a rectangular grid
        $height = count($lines);
        $width  = 0;

        foreach ($lines as $line) {
            $width = max($width, strlen($line));
        }

        $grid = [];
        foreach ($lines as $line) {
            $grid[] = str_pad($line, $width, ' ');
        }

        $bottomIndex = $height - 1;

        // Determine which columns belong to a problem vs separator columns
        $hasChar = array_fill(0, $width, false);

        for ($col = 0; $col < $width; $col++) {
            for ($row = 0; $row < $height; $row++) {
                if ($grid[$row][$col] !== ' ') {
                    $hasChar[$col] = true;
                    break;
                }
            }
        }

        // Group contiguous non-empty columns into problems
        $groups = [];
        $inGroup = false;
        $groupStart = 0;

        for ($col = 0; $col <= $width; $col++) {
            $colHasChar = $col < $width && $hasChar[$col];

            if ($colHasChar && ! $inGroup) {
                $inGroup = true;
                $groupStart = $col;
            } elseif (! $colHasChar && $inGroup) {
                $inGroup = false;
                $groups[] = [$groupStart, $col - 1];
            }
        }

        if (empty($groups)) {
            $this->warn('No problems detected in worksheet.');
            return Command::FAILURE;
        }

        $grandTotal = 0;

        foreach ($groups as [$startCol, $endCol]) {
            $segmentWidth = $endCol - $startCol + 1;

            // Identify operator (+ or *) on the bottom row within this group
            $bottomSeg = substr($grid[$bottomIndex], $startCol, $segmentWidth);

            $op = null;
            if (strpos($bottomSeg, '+') !== false) {
                $op = '+';
            } elseif (strpos($bottomSeg, '*') !== false) {
                $op = '*';
            } else {
                // No operator found; treat as malformed but continue with other groups
                $this->warn(sprintf(
                    'No operator found in columns %d-%d; skipping problem.',
                    $startCol,
                    $endCol
                ));
                continue;
            }

            // Collect numbers for this problem from all rows above the operator row
            $result = null;

            for ($row = 0; $row < $bottomIndex; $row++) {
                $slice = substr($grid[$row], $startCol, $segmentWidth);
                $numStr = trim($slice);

                if ($numStr === '') {
                    continue;
                }

                if (!ctype_digit($numStr)) {
                    $this->warn(sprintf(
                        'Non-numeric content "%s" in problem at columns %d-%d on row %d; skipping this entry.',
                        $numStr,
                        $startCol,
                        $endCol,
                        $row
                    ));
                    continue;
                }

                $value = (int) $numStr;

                if ($result === null) {
                    $result = $value;
                } else {
                    if ($op === '+') {
                        $result += $value;
                    } else { // '*'
                        $result *= $value;
                    }
                }
            }

            if ($result === null) {
                // No numbers found for this group; skip
                continue;
            }

            $grandTotal += $result;
        }

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $grandTotal,
            $duration
        ));

        return Command::SUCCESS;
    }
}
