<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('computes the correct total for the sample input for day three', function () {
    $contents = <<<TXT
987654321111111
811111111111119
234234234234278
818181911112111
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day_three_sample.txt', $contents);

    artisan('advent-of-code:day-three', ['file' => 'day_three_sample.txt'])
        ->expectsOutputToContain('357 (took')
        ->assertExitCode(0);
});