<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('computes the sample largest rectangle for day nine part two', function () {
    $contents = <<<TXT
7,1
11,1
11,7
9,7
9,5
2,5
2,3
7,3
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day9_part2_sample.txt', $contents);

    artisan('advent-of-code:day-nine-part-two', ['file' => 'day9_part2_sample.txt'])
        ->expectsOutputToContain('24 (took')
        ->assertExitCode(0);
});
