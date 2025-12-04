<?php

namespace App\Console\Commands;

use App\Support\InputFileLoader;
use App\Support\DurationFormatter;
use Illuminate\Console\Command;

class AdventOfCodeDayThreePartTwo extends Command
{
    protected $signature = 'advent-of-code:day-three-part-two {file : File name or path to the input file}';

    protected $description = 'Day 3 Part 2: For each bank, pick exactly 12 batteries (digits) in order to maximize the joltage; sum across all banks.';

    private const TARGET_LENGTH = 12;

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
        $total = 0;

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') {
                continue;
            }

            $maxForBank = $this->maxKDigitJoltage($line, self::TARGET_LENGTH);
            $total += $maxForBank;
        }

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $total,
            $duration
        ));

        return Command::SUCCESS;
    }

    /**
     * Given a string of digits, pick exactly $k digits in order
     * to form the largest possible number (as int).
     */
    private function maxKDigitJoltage(string $digits, int $k): int
    {
        $digits = preg_replace('/\D/', '', $digits);
        $n = strlen($digits);

        if ($n === 0 || $k <= 0) {
            return 0;
        }

        if ($n <= $k) {
            // Nothing to remove; take the whole thing.
            return (int) $digits;
        }

        $toRemove = $n - $k;
        $stack = [];

        for ($i = 0; $i < $n; $i++) {
            $ch = $digits[$i];

            while ($toRemove > 0 && !empty($stack) && end($stack) < $ch) {
                array_pop($stack);
                $toRemove--;
            }

            $stack[] = $ch;
        }

        // If we still have digits to remove, chop from the end.
        if ($toRemove > 0) {
            $stack = array_slice($stack, 0, count($stack) - $toRemove);
        }

        // Ensure exact length k
        if (count($stack) > $k) {
            $stack = array_slice($stack, 0, $k);
        }

        $result = (int) implode('', $stack);

        return $result;
    }
}
