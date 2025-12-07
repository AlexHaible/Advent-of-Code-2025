<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use Illuminate\Console\Command;

class AdventOfCodeDaySixPartTwo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * If you pass "input.txt", it reads: storage/app/public/input.txt
     * If you pass an absolute path, it reads directly.
     */
    protected $signature = 'advent-of-code:day-six-part-two {file : File name or path to the input file}';

    protected $description = 'Solve Day 6 Part 2: cephalopod math, right-to-left, column-based numbers.';

    public function handle(): int
    {
        $fileArg = $this->argument('file');

        try {
            $lines = InputFileLoader::loadLines($fileArg, true);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        if (count($lines) < 2) {
            $this->error('Input must have at least one row of digits and one operator row.');
            return Command::FAILURE;
        }

        $start = hrtime(true);

        // Normalize all lines to same width
        $width = 0;
        foreach ($lines as $line) {
            $width = max($width, strlen($line));
        }
        $lines = array_map(fn ($l) => str_pad($l, $width, ' '), $lines);

        $opRowIndex = count($lines) - 1;
        $opRow = $lines[$opRowIndex];
        $digitRows = array_slice($lines, 0, $opRowIndex);

        // Split into problems by columns of all spaces (same as part 1)
        $problems = $this->splitProblemsByBlankColumns($digitRows, $opRow);

        $grandTotal = 0;

        foreach ($problems as [$startCol, $endCol]) {
            $operator = $this->extractOperator($opRow, $startCol, $endCol);
            if ($operator === null) {
                continue;
            }

            $numbers = $this->extractNumbersPartTwo($digitRows, $startCol, $endCol);
            if (empty($numbers)) {
                continue;
            }

            if ($operator === '+') {
                $value = array_sum($numbers);
            } else {
                $value = 1;
                foreach ($numbers as $n) {
                    $value *= $n;
                }
            }

            $grandTotal += $value;
        }

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $grandTotal,
            $duration
        ));

        return Command::SUCCESS;
    }

    private function splitProblemsByBlankColumns(array $digitRows, string $opRow): array
    {
        $height = count($digitRows);
        $width  = strlen($opRow);

        $problems = [];
        $currentStart = null;

        for ($col = 0; $col < $width; $col++) {
            $allBlank = ($opRow[$col] ?? ' ') === ' ';
            if ($allBlank) {
                foreach ($digitRows as $row) {
                    if (($row[$col] ?? ' ') !== ' ') {
                        $allBlank = false;
                        break;
                    }
                }
            }

            if ($allBlank) {
                if ($currentStart !== null) {
                    $problems[] = [$currentStart, $col - 1];
                    $currentStart = null;
                }
            } else {
                if ($currentStart === null) {
                    $currentStart = $col;
                }
            }
        }

        if ($currentStart !== null) {
            $problems[] = [$currentStart, $width - 1];
        }

        return $problems;
    }

    private function extractOperator(string $opRow, int $startCol, int $endCol): ?string
    {
        $len = $endCol - $startCol + 1;
        $segment = substr($opRow, $startCol, $len);

        if (preg_match('/[+*]/', $segment, $m)) {
            return $m[0];
        }

        return null;
    }

    /**
     * Part 2: each vertical column within the problem is a number,
     * read from top digit to bottom digit; columns are read right-to-left.
     */
    private function extractNumbersPartTwo(array $digitRows, int $startCol, int $endCol): array
    {
        $numbers = [];

        for ($col = $endCol; $col >= $startCol; $col--) {
            $digits = '';

            foreach ($digitRows as $row) {
                $ch = $row[$col] ?? ' ';
                if (ctype_digit($ch)) {
                    $digits .= $ch;
                }
            }

            if ($digits !== '') {
                $numbers[] = (int) $digits;
            }
        }

        return $numbers;
    }
}
