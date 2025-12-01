<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('calculates the example password for part two', function () {
    Storage::fake('public');

    // Expected = 6 (3 ends + 3 during rotation)
    $contents = <<<TXT
L68
L30
R48
L5
R60
L55
L1
L99
R14
L82
TXT;

    Storage::disk('public')->put('example2.txt', $contents);

    artisan('advent-of-code:day-one-part-two', ['file' => 'example2.txt'])
        ->expectsOutput('6')
        ->assertExitCode(0);
});

it('handles a single huge rotation correctly', function () {
    Storage::fake('public');

    // R1000 from position 50 hits 0 ten times
    Storage::disk('public')->put('huge.txt', "R1000\n");

    artisan('advent-of-code:day-one-part-two', ['file' => 'huge.txt'])
        ->expectsOutput('10')
        ->assertExitCode(0);
});

it('fails when file is missing', function () {
    Storage::fake('public');

    artisan('advent-of-code:day-one-part-two', ['file' => 'missing.txt'])
        ->expectsOutputToContain('File not found in public storage:')
        ->assertExitCode(1);
});
