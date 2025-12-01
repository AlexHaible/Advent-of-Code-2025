<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('processes the same input for both part one and part two', function () {
    Storage::fake('public');

    // Example data reused for both tests
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

    Storage::disk('public')->put('shared.txt', $contents);

    artisan('advent-of-code:day-one', ['file' => 'shared.txt'])
        ->expectsOutput('3')
        ->assertExitCode(0);

    artisan('advent-of-code:day-one-part-two', ['file' => 'shared.txt'])
        ->expectsOutput('6')
        ->assertExitCode(0);
});
