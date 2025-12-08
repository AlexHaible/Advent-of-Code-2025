<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('computes the sample product of three largest circuits for day eight', function () {
    $contents = <<<TXT
162,817,812
57,618,57
906,360,560
592,479,940
352,342,300
466,668,158
542,29,236
431,825,988
739,650,466
52,470,668
216,146,977
819,987,18
117,168,530
805,96,715
346,949,466
970,615,88
941,993,340
862,61,35
984,92,344
425,690,689
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day8_sample.txt', $contents);

    artisan('advent-of-code:day-eight', ['file' => 'day8_sample.txt'])
        ->expectsOutputToContain('40 (took')
        ->assertExitCode(0);
});