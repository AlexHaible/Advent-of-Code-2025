<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use App\Support\RedGreenRectangleFinder;
use Illuminate\Console\Command;

class AdventOfCodeDayNinePartTwo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * If you pass "input.txt", it reads: storage/app/public/input.txt
     * If you pass an absolute path, it reads directly.
     */
    protected $signature = 'advent-of-code:day-nine-part-two {file : File name or path to the input file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Day 9 Part 2 - Largest rectangle with opposite corners on red tiles, using only red/green tiles';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Loosen resource limits a bit; Day 9 Part 2 can be fairly heavy on memory/CPU.
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $fileArg = $this->argument('file');

        try {
            $lines = InputFileLoader::loadLines($fileArg);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        $start = hrtime(true);

        // Parse red tile coordinates as (x, y) integer pairs in loop order.
        $points = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = explode(',', $line);
            if (count($parts) !== 2) {
                $this->warn("Skipping invalid coordinate line: {$line}");
                continue;
            }

            $x = (int) trim($parts[0]);
            $y = (int) trim($parts[1]);

            $points[] = [$x, $y];
        }

        if (count($points) < 2) {
            $duration = DurationFormatter::formatFromStart($start);
            $this->info(sprintf('0 (took %s)', $duration));
            return Command::SUCCESS;
        }

        // Delegate the heavy lifting to a geometry-based helper that works directly with
        // the polygon defined by the red tiles, avoiding the need to allocate a huge grid.
        $solver   = new RedGreenRectangleFinder($points);
        $maxArea  = $solver->findMaxArea();
        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf('%d (took %s)', $maxArea, $duration));

        return Command::SUCCESS;
    }
}
