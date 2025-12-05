<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('counts fresh ingredient IDs for the sample input for day five part one', function () {
    $contents = <<<TXT
3-5
10-14
16-20
12-18

1
5
8
11
17
32
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day5_sample.txt', $contents);

    artisan('advent-of-code:day-five', ['file' => 'day5_sample.txt'])
        ->expectsOutputToContain('3 (took')
        ->assertExitCode(0);
});

it('handles unsorted ranges and IDs correctly for day five part one', function () {
    // Ranges overlap and are out of order; IDs are out of order too.
    // Fresh: 2 (in 1-3), 18 (in 10-20 & 15-25) -> 2 fresh IDs.
    $contents = <<<TXT
10-20
1-3
15-25

30
2
18
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day5_unsorted.txt', $contents);

    artisan('advent-of-code:day-five', ['file' => 'day5_unsorted.txt'])
        ->expectsOutputToContain('2 (took')
        ->assertExitCode(0);
});