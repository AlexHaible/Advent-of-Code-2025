<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('calculates the example password for part one', function () {
    Storage::fake('public');

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

    Storage::disk('public')->put('example1.txt', $contents);

    artisan('advent-of-code:day-one', ['file' => 'example1.txt'])
        ->expectsOutput('3')
        ->assertExitCode(0);
});

it('skips invalid lines', function () {
    Storage::fake('public');

    $contents = <<<TXT
R10
BADLINE
L10
Rfoo
L5
TXT;

    Storage::disk('public')->put('invalid-lines.txt', $contents);

    artisan('advent-of-code:day-one', ['file' => 'invalid-lines.txt'])
        ->expectsOutputToContain('Skipping invalid line: BADLINE')
        ->expectsOutputToContain('Skipping invalid line: Rfoo')
        ->assertExitCode(0);
});

it('fails when file is missing', function () {
    Storage::fake('public');

    artisan('advent-of-code:day-one', ['file' => 'missing.txt'])
        ->expectsOutputToContain('File not found in public storage:')
        ->assertExitCode(1);
});
