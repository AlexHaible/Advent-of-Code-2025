<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('counts accessible rolls for the sample input for day four part one', function () {
    $contents = <<<'TXT'
..@@.@@@@.
@@@.@.@.@@
@@@@@.@.@@
@.@@@@..@.
@@.@@@@.@@
.@@@@@@@.@
.@.@.@.@@@
@.@@@.@@@@
.@@@@@@@@.
@.@.@@@.@.
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day_four_part_one_sample.txt', $contents);

    artisan('advent-of-code:day-four', ['file' => 'day_four_part_one_sample.txt'])
        ->expectsOutputToContain('13 (took')
        ->assertExitCode(0);
});