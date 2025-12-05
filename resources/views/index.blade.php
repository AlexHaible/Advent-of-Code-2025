@extends('layouts.app')

@section('content')
    <div class="h-full flex flex-col overflow-hidden">
        {{-- Top section: header + day buttons, constrained to the top half via padding for the fixed terminal --}}
        <div class="flex-1 overflow-y-auto pr-2">
            <div class="max-w-4xl mx-auto space-y-8 py-8">
                <h1 class="text-2xl font-bold mb-4">Advent of Code Runner</h1>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Day 1 --}}
                    <livewire:run-command
                        :command-key="'day1-part1'"
                        :label="'Run Day 1 Part 1'"
                        :file="'day1.txt'"
                    />

                    <livewire:run-command
                        :command-key="'day1-part2'"
                        :label="'Run Day 1 Part 2'"
                        :file="'day1.txt'"
                    />

                    {{-- Day 2 --}}
                    <livewire:run-command
                        :command-key="'day2-part1'"
                        :label="'Run Day 2 Part 1'"
                        :file="'day2.txt'"
                    />

                    <livewire:run-command
                        :command-key="'day2-part2'"
                        :label="'Run Day 2 Part 2'"
                        :file="'day2.txt'"
                    />

                    {{-- Day 3 --}}
                    <livewire:run-command
                        :command-key="'day3-part1'"
                        :label="'Run Day 3 Part 1'"
                        :file="'day3.txt'"
                    />

                    <livewire:run-command
                        :command-key="'day3-part2'"
                        :label="'Run Day 3 Part 2'"
                        :file="'day3.txt'"
                    />

                    {{-- Day 4 --}}
                    <livewire:run-command
                        :command-key="'day4-part1'"
                        :label="'Run Day 4 Part 1'"
                        :file="'day4.txt'"
                    />

                    <livewire:run-command
                        :command-key="'day4-part2'"
                        :label="'Run Day 4 Part 2'"
                        :file="'day4.txt'"
                    />
                </div>
            </div>
        </div>

        {{-- Bottom fixed terminal output, occupying 50% of the viewport height --}}
        <div class="fixed inset-x-0 bottom-0 h-1/2 bg-black text-green-100 border-t border-gray-700">
            <div class="w-full h-full">
                <livewire:terminal-output />
            </div>
        </div>
    </div>
@endsection