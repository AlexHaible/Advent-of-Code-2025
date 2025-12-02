<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('sums invalid IDs for the sample input for day two part two', function () {
    $contents = <<<TXT
11-22,95-115,998-1012,1188511880-1188511890,222220-222224,
1698522-1698528,446443-446449,38593856-38593862,565653-565659,
824824821-824824827,2121212118-2121212124
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day_two_part_two_sample.txt', $contents);

    artisan('advent-of-code:day-two-part-two', ['file' => 'day_two_part_two_sample.txt'])
        ->expectsOutputToContain('4174379265 (took')
        ->assertExitCode(0);
});
