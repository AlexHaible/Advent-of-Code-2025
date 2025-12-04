<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\InputFileLoader;
use App\Support\DurationFormatter;

class AdventOfCodeDayThree extends Command
{
    /**
     * The name and signature of the console command.
     *
     * If you pass "input.txt", it reads: storage/app/public/input.txt
     * If you pass an absolute path, it reads directly.
     */
    protected $signature = 'advent-of-code:day-three {file : File name or path to the input file}';

    protected $description = 'Compute total maximum joltage from each battery bank (two batteries, order preserved).';

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

            // You said digits 1-9, but let's just be tolerant of 0 as well.
            if (!ctype_digit($line)) {
                $this->warn("Skipping non-numeric bank: {$line}");
                continue;
            }

            // Bank joltage: best 2-digit number using digits in order
            $bankMax = $this->maxBankJoltage($line);
            $total += $bankMax;
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
     * Given a string of digits, find the maximum 2-digit number AB
     * such that A is at position i, B is at position j, and i < j.
     *
     * Runs in O(length) time using a single right-to-left pass.
     */
    private function maxBankJoltage(string $digits): int
    {
        $len = strlen($digits);
        if ($len < 2) {
            return 0;
        }

        $best     = 0;
        $rightMax = -1; // best digit to the right of current index

        // Walk from right to left. For each position i, we already know
        // the maximum digit to its right (rightMax), so candidate = d[i]*10 + rightMax.
        for ($i = $len - 1; $i >= 0; $i--) {
            $d = ord($digits[$i]) - 48; // '0' = 48

            if ($rightMax >= 0) {
                $candidate = $d * 10 + $rightMax;
                if ($candidate > $best) {
                    $best = $candidate;
                    // Micro-early-exit: if we ever hit 99, that's the theoretical max.
                    if ($best === 99) {
                        // No need to check further for this bank.
                        break;
                    }
                }
            }

            if ($d > $rightMax) {
                $rightMax = $d;
            }
        }

        return $best;
    }
}
