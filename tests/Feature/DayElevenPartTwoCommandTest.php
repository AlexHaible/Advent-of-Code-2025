<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('computes the number of paths visiting dac and fft for day eleven part two', function () {
    $contents = <<<TXT
svr: aaa bbb
aaa: fft
fft: ccc
bbb: tty
tty: ccc
ccc: ddd eee
ddd: hub
hub: fff
eee: dac
dac: fff
fff: ggg hhh
ggg: out
hhh: out
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day11_part2_sample.txt', $contents);

    artisan('advent-of-code:day-eleven-part-two', ['file' => 'day11_part2_sample.txt'])
        ->expectsOutputToContain('2 (took')
        ->assertExitCode(0);
});
