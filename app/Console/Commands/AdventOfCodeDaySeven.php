<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use App\Support\TachyonManifold;
use Illuminate\Console\Command;

class AdventOfCodeDaySeven extends Command
{
    protected $signature = 'advent-of-code:day-seven {file : Input file name or absolute path}';
    protected $description = 'Count tachyon beam splits in the manifold diagram.';

    public function handle(): int
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

        $splitCount = $manifold->countSplits();
        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $splitCount,
            $duration
        ));

        return Command::SUCCESS;
    }
}
