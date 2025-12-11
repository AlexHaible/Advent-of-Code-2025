<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use Illuminate\Console\Command;
use SplQueue;

class AdventOfCodeDayTen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * If you pass "input.txt", it reads: storage/app/public/input.txt
     * If you pass an absolute path, it reads directly.
     */
    protected $signature = 'advent-of-code:day-ten {file : File name or path to the input file}';

    protected $description = 'Day 10 Part 1 - Fewest button presses to configure all machines';

    public function handle(): int
    {
        $fileArg = $this->argument('file');

        try {
            $lines = InputFileLoader::loadLines($fileArg);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        $start      = hrtime(true);
        $totalPress = 0;

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') {
                continue;
            }

            // Extract indicator pattern inside [ ... ]
            if (!preg_match('/\[(.*?)\]/', $line, $mPattern)) {
                $this->warn("Skipping line without indicator pattern: {$line}");
                continue;
            }

            $pattern = $mPattern[1];
            $n       = strlen($pattern);

            if ($n === 0) {
                // No lights, nothing to do for this machine.
                continue;
            }

            // Build target bitmask from pattern (. = 0, # = 1).
            // Bit i corresponds to light i (0-based from left).
            $targetMask = 0;
            for ($i = 0; $i < $n; $i++) {
                if ($pattern[$i] === '#') {
                    $targetMask |= (1 << $i);
                }
            }

            // Extract all button schematics: (...) blocks.
            if (!preg_match_all('/\(([^)]*)\)/', $line, $mButtons)) {
                // No buttons => only solvable if target is all off
                if ($targetMask !== 0) {
                    $this->warn("Machine has target lights but no buttons: {$line}");
                    // Treat as unsolvable => skip or count as impossible.
                    continue;
                }

                // All off already.
                continue;
            }

            $buttonMasks = [];
            foreach ($mButtons[1] as $btnSpec) {
                $btnSpec = trim($btnSpec);
                if ($btnSpec === '') {
                    continue;
                }

                $indices = array_filter(
                    array_map('trim', explode(',', $btnSpec)),
                    static fn ($val) => $val !== ''
                );

                $mask = 0;
                foreach ($indices as $idxStr) {
                    // Just ignore anything non-numeric or out-of-range
                    if (!ctype_digit($idxStr)) {
                        continue;
                    }
                    $idx = (int) $idxStr;
                    if ($idx < 0 || $idx >= $n) {
                        continue;
                    }
                    $mask |= (1 << $idx);
                }

                // If a button has no valid indices, it never changes anything, so skip it.
                if ($mask !== 0) {
                    $buttonMasks[] = $mask;
                }
            }

            // If target is already all off, zero presses needed.
            if ($targetMask === 0) {
                continue;
            }

            // If there are no effective buttons but target is non-zero, it's unsolvable.
            if (count($buttonMasks) === 0) {
                $this->warn("Machine appears unsolvable (no effective buttons): {$line}");
                continue;
            }

            $presses = $this->solveMachineMinPresses($targetMask, $buttonMasks);

            if ($presses === null) {
                $this->warn("Machine appears unsolvable given buttons: {$line}");
                continue;
            }

            $totalPress += $presses;
        }

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $totalPress,
            $duration
        ));

        return Command::SUCCESS;
    }

    /**
     * Solve a single machine via BFS over light states (bitmasks).
     *
     * @param int   $targetMask   Desired indicator bitmask
     * @param array $buttonMasks  List of button toggle masks (ints)
     *
     * @return int|null  Minimum number of presses, or null if unreachable
     */
    private function solveMachineMinPresses(int $targetMask, array $buttonMasks): ?int
    {
        // BFS on state space of all possible indicator configurations.
        // State: integer bitmask, value: distance (press count).
        $queue   = new SplQueue();
        $visited = [];

        $startState        = 0; // all lights off
        $visited[$startState] = 0;
        $queue->enqueue($startState);

        while (!$queue->isEmpty()) {
            /** @var int $state */
            $state = $queue->dequeue();
            $dist  = $visited[$state];

            if ($state === $targetMask) {
                return $dist;
            }

            foreach ($buttonMasks as $mask) {
                $next = $state ^ $mask;

                if (!array_key_exists($next, $visited)) {
                    $visited[$next] = $dist + 1;
                    $queue->enqueue($next);
                }
            }
        }

        // Target not reachable so we
        return null;
    }
}
