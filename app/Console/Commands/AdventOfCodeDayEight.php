<?php

namespace App\Console\Commands;

use App\Support\InputFileLoader;
use App\Support\DurationFormatter;
use Illuminate\Console\Command;
use SplPriorityQueue;

class AdventOfCodeDayEight extends Command
{
    protected $signature = 'advent-of-code:day-eight {file : Input file in public storage or absolute path}';
    protected $description = 'Day 8 Part 1 - Tachyon Junction Circuits';

    public function handle()
    {
        $file = $this->argument('file');

        try {
            $lines = InputFileLoader::loadLines($file);
        } catch (\RuntimeException $e) {
            // Use a normal line so the message appears in the web terminal as well
            $this->line('Input error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $start = hrtime(true);

        try {
            // Parse points
            $points = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                [$x, $y, $z] = array_map('intval', explode(',', $line));
                $points[] = [$x, $y, $z];
            }

            $n = count($points);

            if ($n === 0) {
                $this->line('Input appears to have no valid junction box coordinates.');
                return Command::FAILURE;
            }

            // How many shortest pairs do we need?
            $pairLimit = ($n <= 20) ? 10 : 1000;

            // Maintain only the pairLimit shortest edges using a max-heap (priority = distance).
            $heap = new SplPriorityQueue();
            $heap->setExtractFlags(SplPriorityQueue::EXTR_BOTH);

            for ($i = 0; $i < $n; $i++) {
                [$x1, $y1, $z1] = $points[$i];
                for ($j = $i + 1; $j < $n; $j++) {
                    [$x2, $y2, $z2] = $points[$j];
                    $dx   = $x1 - $x2;
                    $dy   = $y1 - $y2;
                    $dz   = $z1 - $z2;
                    $dist = $dx * $dx + $dy * $dy + $dz * $dz;

                    if ($heap->count() < $pairLimit) {
                        $heap->insert([$dist, $i, $j], $dist);
                    } else {
                        $top = $heap->current(); // ['data' => [...], 'priority' => dist]
                        if ($top['priority'] > $dist) {
                            $heap->extract();
                            $heap->insert([$dist, $i, $j], $dist);
                        }
                    }
                }
            }

            // Extract selected edges from heap into a simple array and sort ascending by distance.
            $edges = [];
            while (!$heap->isEmpty()) {
                $item    = $heap->extract();
                $edges[] = $item['data']; // [dist, i, j]
            }

            usort($edges, fn ($a, $b) => $a[0] <=> $b[0]);

            // DSU setup
            $parent = range(0, $n - 1);
            $size   = array_fill(0, $n, 1);

            $find = function (int $x) use (&$parent, &$find): int {
                if ($parent[$x] !== $x) {
                    $parent[$x] = $find($parent[$x]);
                }
                return $parent[$x];
            };

            $union = function (int $a, int $b) use (&$parent, &$size, $find): bool {
                $ra = $find($a);
                $rb = $find($b);
                if ($ra === $rb) {
                    return false;
                }
                if ($size[$ra] < $size[$rb]) {
                    [$ra, $rb] = [$rb, $ra];
                }
                $parent[$rb] = $ra;
                $size[$ra]  += $size[$rb];
                return true;
            };

            // Apply all selected edges (each edge represents one "pair" attempt).
            foreach ($edges as $edge) {
                [, $i, $j] = $edge;
                $union($i, $j);
            }

            // Count component sizes
            $components = [];
            for ($i = 0; $i < $n; $i++) {
                $root = $find($i);
                if (! isset($components[$root])) {
                    $components[$root] = 0;
                }
                $components[$root]++;
            }

            // Sort component sizes descending and safely take up to the top 3.
            rsort($components);
            $sizes = array_values($components);
            $top3  = array_slice($sizes, 0, 3);

            // If there are fewer than 3 circuits (e.g. tiny test inputs), pad with 1s so the
            // missing factors don't change the product.
            while (count($top3) < 3) {
                $top3[] = 1;
            }

            $result   = array_product($top3);
            $duration = DurationFormatter::formatFromStart($start);

            $this->info(sprintf('%d (took %s)', $result, $duration));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Unexpected error in Day 8 command: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
