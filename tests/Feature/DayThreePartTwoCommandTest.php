<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('computes the sample total joltage for day 3 part 2', function () {
    $contents = <<<TXT
987654321111111
811111111111119
234234234234278
818181911112111
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day_three_part_two_sample.txt', $contents);

    artisan('advent-of-code:day-three-part-two', ['file' => 'day_three_part_two_sample.txt'])
        ->expectsOutputToContain('3121910778619 (took')
        ->assertExitCode(0);
});