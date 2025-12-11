<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('computes the minimal total button presses for the sample input for day ten part two', function () {
    // Expected Result: 10 + 12 + 11 = 33
    $contents = <<<TXT
[.##.] (3) (1,3) (2) (2,3) (0,2) (0,1) {3,5,4,7}
[...#.] (0,2,3,4) (2,3) (0,4) (0,1,2) (1,2,3,4) {7,5,12,7,2}
[.###.#] (0,1,2,3,4) (0,3,4) (0,1,2,4,5) (1,2) {10,11,11,5,10,5}
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day10_sample.txt', $contents);

    artisan('advent-of-code:day-ten-part-two', ['file' => 'day10_sample.txt'])
        ->expectsOutputToContain('33 (took')
        ->assertExitCode(0);
});
