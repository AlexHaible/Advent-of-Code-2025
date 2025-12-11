<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use Illuminate\Console\Command;

class AdventOfCodeDayElevenPartTwo extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'advent-of-code:day-eleven-part-two {file : File name or path to the input file}';

    protected $description = 'Day 11 Part 2 - Count paths from "svr" to "out" visiting "dac" and "fft"';

    private array $graph = [];
    private array $memo = [];

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

        // 1. Build the Graph
        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') {
                continue;
            }

            if (!str_contains($line, ':')) {
                continue;
            }

            [$sourcePart, $destPart] = explode(':', $line, 2);
            $source = trim($sourcePart);
            $destinations = preg_split('/\s+/', trim($destPart), -1, PREG_SPLIT_NO_EMPTY);

            if (!isset($this->graph[$source])) {
                $this->graph[$source] = [];
            }

            foreach ($destinations as $dest) {
                $this->graph[$source][] = $dest;
            }
        }

        // 2. Validate required nodes exist in the graph (either as source or destination)
        // Note: 'out' might not be a key in $this->graph if it has no outgoing edges.
        // We just need to ensure start/intermediate nodes are logical.
        $required = ['svr', 'dac', 'fft'];
        foreach ($required as $node) {
            // Strictly speaking, if a node isn't in the graph keys, it might be a leaf.
            // If 'svr' is missing, we definitely have 0 paths.
            if (!array_key_exists($node, $this->graph) && !$this->nodeExistsInValues($node)) {
                $this->warn("Node '{$node}' not found in graph.");
                // We proceed, but result will likely be 0.
            }
        }

        // 3. Calculate paths for the two possible orderings
        // Scenario A: svr -> ... -> dac -> ... -> fft -> ... -> out
        // Scenario B: svr -> ... -> fft -> ... -> dac -> ... -> out

        // Scenario A
        $svrToDac = $this->countPathsBetween('svr', 'dac');
        $dacToFft = $this->countPathsBetween('dac', 'fft');
        $fftToOut = $this->countPathsBetween('fft', 'out');

        $pathsA = (int)($svrToDac * $dacToFft * $fftToOut);

        // Scenario B
        $svrToFft = $this->countPathsBetween('svr', 'fft');
        $fftToDac = $this->countPathsBetween('fft', 'dac');
        $dacToOut = $this->countPathsBetween('dac', 'out');

        $pathsB = (int)($svrToFft * $fftToDac * $dacToOut);

        $totalPaths = $pathsA + $pathsB;

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $totalPaths,
            $duration
        ));

        return Command::SUCCESS;
    }

    /**
     * Wrapper to clear memoization and start DFS.
     */
    private function countPathsBetween(string $start, string $end): int
    {
        $this->memo = [];
        return $this->dfs($start, $end);
    }

    /**
     * Depth-First Search with Memoization to count paths.
     */
    private function dfs(string $current, string $target): int
    {
        if ($current === $target) {
            return 1;
        }

        if (isset($this->memo[$current])) {
            return $this->memo[$current];
        }

        $count = 0;
        if (isset($this->graph[$current])) {
            foreach ($this->graph[$current] as $neighbor) {
                $count += $this->dfs($neighbor, $target);
            }
        }

        return $this->memo[$current] = $count;
    }

    private function nodeExistsInValues(string $target): bool
    {
        foreach ($this->graph as $neighbors) {
            if (in_array($target, $neighbors)) {
                return true;
            }
        }
        return false;
    }
}
