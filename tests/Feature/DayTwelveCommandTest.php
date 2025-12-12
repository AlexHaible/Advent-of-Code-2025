<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('computes the solvable regions for day twelve', function () {
    $contents = <<<TXT
0:
###
##.
##.

1:
###
##.
.##

2:
.##
###
##.

3:
##.
###
##.

4:
###
#..
###

5:
###
.#.
###

4x4: 0 0 0 0 2 0
12x5: 1 0 1 0 2 2
12x5: 1 0 1 0 3 2
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day12_sample.txt', $contents);

    artisan('advent-of-code:day-twelve', ['file' => 'day12_sample.txt'])
        ->expectsOutputToContain('2 (took')
        ->assertExitCode(0);
});
