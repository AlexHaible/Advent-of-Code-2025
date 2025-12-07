<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('computes the grand total for the sample cephalopod math worksheet', function () {
    $contents = <<<TXT
123 328  51 64 
 45 64  387 23 
  6 98  215 314
*   +   *   +  
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day6_example.txt', $contents);

    artisan('advent-of-code:day-six', ['file' => 'day6_example.txt'])
        ->expectsOutputToContain('4277556 (took')
        ->assertExitCode(0);
});

it('handles misaligned digits within a problem group', function () {
    $contents = <<<TXT
 123  328   51    64
   45   64 387   23 
    6   98  215  314
  *    +    *    + 
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day6_misaligned.txt', $contents);

    artisan('advent-of-code:day-six', ['file' => 'day6_misaligned.txt'])
        ->expectsOutputToContain('4277556 (took')
        ->assertExitCode(0);
});