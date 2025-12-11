<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use Illuminate\Console\Command;

class AdventOfCodeDayEleven extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'advent-of-code:day-eleven {file : File name or path to the input file}';

    protected $description = 'Day 11 - Count distinct paths from "you" to "out"';

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

            // Parse format: "source: dest1 dest2 ..."
            if (!str_contains($line, ':')) {
                continue;
            }

            [$sourcePart, $destPart] = explode(':', $line, 2);
            $source = trim($sourcePart);
            // Split by whitespace to get destinations
            $destinations = preg_split('/\s+/', trim($destPart), -1, PREG_SPLIT_NO_EMPTY);

            if (!isset($this->graph[$source])) {
                $this->graph[$source] = [];
            }

            // Add edges
            foreach ($destinations as $dest) {
                $this->graph[$source][] = $dest;
            }
        }

        // 2. Count paths using DFS with Memoization
        // We assume the graph is a DAG (Directed Acyclic Graph) as per problem constraints.
        $pathCount = $this->countPaths('you');

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $pathCount,
            $duration
        ));

        return Command::SUCCESS;
    }

    /**
     * Recursively counts paths from current node to 'out'.
     */
    private function countPaths(string $node): int
    {
        // Base case: Reached the target
        if ($node === 'out') {
            return 1;
        }

        // Check memoization cache
        if (isset($this->memo[$node])) {
            return $this->memo[$node];
        }

        $count = 0;

        // Iterate over neighbors
        if (isset($this->graph[$node])) {
            foreach ($this->graph[$node] as $neighbor) {
                $count += $this->countPaths($neighbor);
            }
        }

        // Store result in cache
        return $this->memo[$node] = $count;
    }
}
