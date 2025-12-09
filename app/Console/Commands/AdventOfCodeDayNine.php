<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use Illuminate\Console\Command;

class AdventOfCodeDayNine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * If you pass "input.txt", it reads: storage/app/public/input.txt
     * If you pass an absolute path, it reads directly.
     */
    protected $signature = 'advent-of-code:day-nine {file : File name or path to the input file}';

    protected $description = 'Day 9 Part 1 - Largest rectangle area using two red tiles as opposite corners';

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

        // Parse red tile coordinates: "x,y"
        $points = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = explode(',', $line);
            if (count($parts) !== 2) {
                $this->warn("Skipping invalid line: {$line}");
                continue;
            }

            [$x, $y] = array_map('intval', $parts);
            $points[] = [$x, $y];
        }

        $n = count($points);
        $maxArea = 0;

        if ($n >= 2) {
            // Brute-force all pairs: area = |x1 - x2| * |y1 - y2|
            for ($i = 0; $i < $n; $i++) {
                [$x1, $y1] = $points[$i];

                for ($j = $i + 1; $j < $n; $j++) {
                    [$x2, $y2] = $points[$j];

                    $dx = abs($x1 - $x2);
                    $dy = abs($y1 - $y2);

                    // Rectangle area is inclusive of both corners, so add 1 to each side length.
                    // For example, corners (2,5) and (11,1) cover 10 x 5 = 50 tiles.
                    $area = ($dx + 1) * ($dy + 1);

                    if ($area > $maxArea) {
                        $maxArea = $area;
                    }
                }
            }
        }

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf('%d (took %s)', $maxArea, $duration));

        return Command::SUCCESS;
    }
}
