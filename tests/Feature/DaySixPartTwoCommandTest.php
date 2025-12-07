<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('solves the sample worksheet for day six part two', function () {
    $contents = <<<TXT
123 328  51 64 
 45 64  387 23 
  6 98  215 314
*   +   *   +  
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day6_example_part2.txt', $contents);

    artisan('advent-of-code:day-six-part-two', ['file' => 'day6_example_part2.txt'])
        ->expectsOutputToContain('3263827 (took')
        ->assertExitCode(0);
});