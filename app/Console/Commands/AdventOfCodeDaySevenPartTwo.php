<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use App\Support\TachyonManifold;
use Illuminate\Console\Command;

class AdventOfCodeDaySevenPartTwo extends Command
{
    protected $signature = 'advent-of-code:day-seven-part-two {file : File name or path to the input file}';

    protected $description = 'Compute the number of quantum timelines for the tachyon manifold (Day 7 Part 2).';

    public function handle()
    {
        $fileArg = $this->argument('file');

        try {
            $lines    = InputFileLoader::loadLines($fileArg, true);
            $manifold = TachyonManifold::fromLines($lines);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        $start = hrtime(true);

        $timelines = $manifold->countTimelines();
        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $timelines,
            $duration
        ));

        return Command::SUCCESS;
    }
}
