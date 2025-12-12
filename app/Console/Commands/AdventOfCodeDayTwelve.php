<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use App\Support\PackagePlacementSolver;
use Illuminate\Console\Command;

class AdventOfCodeDayTwelve extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'advent-of-code:day-twelve {file : File name or path to the input file}';

    protected $description = 'Day 12 - Christmas Tree Farm (Packing Puzzle)';

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

        $shapeLines = [];
        $regionLines = [];
        $parsingShapes = true;

        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '') continue;

            if (preg_match('/^\d+x\d+:/', $trim)) {
                $parsingShapes = false;
            }

            if ($parsingShapes) {
                $shapeLines[] = $line;
            } else {
                $regionLines[] = $trim;
            }
        }

        $solver = new PackagePlacementSolver($shapeLines);

        $solvableCount = 0;
        foreach ($regionLines as $rLine) {
            if ($solver->solve($rLine)) {
                $solvableCount++;
            }
        }

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $solvableCount,
            $duration
        ));

        return Command::SUCCESS;
    }
}
