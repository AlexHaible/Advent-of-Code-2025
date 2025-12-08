<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use Illuminate\Console\Command;
use SplPriorityQueue;

class AdventOfCodeDayEightPartTwo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * If you pass "input.txt", it reads: storage/app/public/input.txt
     * If you pass an absolute path, it reads directly.
     */
    protected $signature = 'advent-of-code:day-eight-part-two {file : File name or path to the input file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Day 8 Part 2 - Find the last junction pair that connects all circuits';

    /**
     * Execute the console command.
     */
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

        // Parse junction box coordinates.
        $points = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = explode(',', $line);
            if (count($parts) !== 3) {
                $this->warn("Skipping invalid line: {$line}");
                continue;
            }

            [$x, $y, $z] = array_map('intval', $parts);
            $points[] = [$x, $y, $z];
        }

        $n = count($points);

        if ($n < 2) {
            $this->info(sprintf('0 (took %s)', DurationFormatter::formatFromStart($start)));
            return Command::SUCCESS;
        }

        // Build a minimum spanning tree using Prim's algorithm.
        // The graph is complete: every pair of junction boxes can be connected.
        $INF      = PHP_INT_MAX;
        $inTree   = array_fill(0, $n, false);
        $bestDist = array_fill(0, $n, $INF);
        $parent   = array_fill(0, $n, -1);

        // Track the heaviest edge in the MST as we build it; this corresponds to
        // the "last" connection that actually merges the final two circuits.
        $maxEdgeDist = -1;
        $maxI        = null;
        $maxJ        = null;

        // Start from junction 0.
        $bestDist[0] = 0;

        for ($iter = 0; $iter < $n; $iter++) {
            // Pick the vertex not yet in the tree with the smallest bestDist.
            $v       = -1;
            $minDist = $INF;

            for ($i = 0; $i < $n; $i++) {
                if (! $inTree[$i] && $bestDist[$i] < $minDist) {
                    $minDist = $bestDist[$i];
                    $v       = $i;
                }
            }

            if ($v === -1) {
                // Graph should be complete; this would indicate a logic error.
                $this->error('Failed to build a spanning tree for the junction boxes.');
                return Command::FAILURE;
            }

            $inTree[$v] = true;

            // For every non-root vertex, we know its MST parent; compute this edge's
            // distance and track the heaviest one seen so far.
            if ($parent[$v] !== -1) {
                $pv = $parent[$v];

                [$vx, $vy, $vz] = $points[$v];
                [$ux, $uy, $uz] = $points[$pv];

                $dx   = $vx - $ux;
                $dy   = $vy - $uy;
                $dz   = $vz - $uz;
                $dist = $dx * $dx + $dy * $dy + $dz * $dz;

                if ($dist > $maxEdgeDist) {
                    $maxEdgeDist = $dist;
                    $maxI        = $pv;
                    $maxJ        = $v;
                }
            }

            // Update best distances for vertices not yet in the tree.
            [$vx, $vy, $vz] = $points[$v];
            for ($u = 0; $u < $n; $u++) {
                if ($inTree[$u]) {
                    continue;
                }

                [$ux, $uy, $uz] = $points[$u];
                $dx   = $vx - $ux;
                $dy   = $vy - $uy;
                $dz   = $vz - $uz;
                $dist = $dx * $dx + $dy * $dy + $dz * $dz;

                if ($dist < $bestDist[$u]) {
                    $bestDist[$u] = $dist;
                    $parent[$u]   = $v;
                }
            }
        }

        if ($maxI === null || $maxJ === null) {
            $this->error('Failed to identify the final junction box connection.');
            return Command::FAILURE;
        }

        // Multiply the X coordinates of the heaviest MST edge, which corresponds to the
        // first connection that makes the whole system a single circuit.
        $x1 = $points[$maxI][0];
        $x2 = $points[$maxJ][0];
        $result = $x1 * $x2;

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf('%d (took %s)', $result, $duration));

        return Command::SUCCESS;
    }
}
