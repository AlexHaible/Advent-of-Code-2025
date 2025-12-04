<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('counts removable rolls for the sample input for day four part two', function () {
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
    Storage::disk('public')->put('day_four_part_two_sample.txt', $contents);

    artisan('advent-of-code:day-four-part-two', ['file' => 'day_four_part_two_sample.txt'])
        ->expectsOutputToContain('43 (took')
        ->assertExitCode(0);
});