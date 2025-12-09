<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('computes the sample largest rectangle area for day nine part one', function () {
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
    Storage::disk('public')->put('day9_sample.txt', $contents);

    artisan('advent-of-code:day-nine', ['file' => 'day9_sample.txt'])
        ->expectsOutputToContain('50 (took')
        ->assertExitCode(0);
});