<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('counts fresh ingredient IDs for the sample input for day five part two', function () {
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
    Storage::disk('public')->put('day5_part2_sample.txt', $contents);

    artisan('advent-of-code:day-five-part-two', ['file' => 'day5_part2_sample.txt'])
        ->expectsOutputToContain('14 (took')
        ->assertExitCode(0);
});