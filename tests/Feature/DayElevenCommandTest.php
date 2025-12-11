<?php

use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\artisan;

it('computes the number of paths from you to out for day eleven', function () {
    $contents = <<<TXT
aaa: you hhh
you: bbb ccc
bbb: ddd eee
ccc: ddd eee fff
ddd: ggg
eee: out
fff: out
ggg: out
hhh: ccc fff iii
iii: out
TXT;

    Storage::fake('public');
    Storage::disk('public')->put('day11_sample.txt', $contents);

    artisan('advent-of-code:day-eleven', ['file' => 'day11_sample.txt'])
        ->expectsOutputToContain('5 (took')
        ->assertExitCode(0);
});
