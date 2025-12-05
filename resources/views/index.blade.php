@extends('layouts.app')

@section('content')
    <div class="h-full">
        {{-- Top section: header + day buttons, fixed to the top half of the viewport with its own scroll --}}
        <div class="h-1/2 overflow-y-auto pr-2 pb-24">
            <div class="max-w-4xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Day 1 --}}
                    <livewire:run-command
                        :command-key="'day1-part1'"
                        :label="'Run Day 1 Part 1'"
                        :file="'day1.txt'"
                        :day="1"
                    />

                    <livewire:run-command
                        :command-key="'day1-part2'"
                        :label="'Run Day 1 Part 2'"
                        :file="'day1.txt'"
                        :day="1"
                    />

                    {{-- Day 2 --}}
                    <livewire:run-command
                        :command-key="'day2-part1'"
                        :label="'Run Day 2 Part 1'"
                        :file="'day2.txt'"
                        :day="2"
                    />

                    <livewire:run-command
                        :command-key="'day2-part2'"
                        :label="'Run Day 2 Part 2'"
                        :file="'day2.txt'"
                        :day="2"
                    />

                    {{-- Day 3 --}}
                    <livewire:run-command
                        :command-key="'day3-part1'"
                        :label="'Run Day 3 Part 1'"
                        :file="'day3.txt'"
                        :day="3"
                    />

                    <livewire:run-command
                        :command-key="'day3-part2'"
                        :label="'Run Day 3 Part 2'"
                        :file="'day3.txt'"
                        :day="3"
                    />

                    {{-- Day 4 --}}
                    <livewire:run-command
                        :command-key="'day4-part1'"
                        :label="'Run Day 4 Part 1'"
                        :file="'day4.txt'"
                        :day="4"
                    />

                    <livewire:run-command
                        :command-key="'day4-part2'"
                        :label="'Run Day 4 Part 2'"
                        :file="'day4.txt'"
                        :day="4"
                    />

                    {{-- Day 5 --}}
                    <livewire:run-command
                        :command-key="'day5-part1'"
                        :label="'Run Day 5 Part 1'"
                        :file="'day5.txt'"
                        :day="5"
                    />

                    <livewire:run-command
                        :command-key="'day5-part2'"
                        :label="'Run Day 5 Part 2'"
                        :file="'day5.txt'"
                        :day="5"
                    />

                    {{-- Day 6 --}}
                    <livewire:run-command
                        :command-key="'day6-part1'"
                        :label="'Run Day 6 Part 1'"
                        :file="'day6.txt'"
                        :day="6"
                    />

                    <livewire:run-command
                        :command-key="'day6-part2'"
                        :label="'Run Day 6 Part 2'"
                        :file="'day6.txt'"
                        :day="6"
                    />

                    {{-- Day 7 --}}
                    <livewire:run-command
                        :command-key="'day7-part1'"
                        :label="'Run Day 7 Part 1'"
                        :file="'day7.txt'"
                        :day="7"
                    />

                    <livewire:run-command
                        :command-key="'day7-part2'"
                        :label="'Run Day 7 Part 2'"
                        :file="'day7.txt'"
                        :day="7"
                    />

                    {{-- Day 8 --}}
                    <livewire:run-command
                        :command-key="'day8-part1'"
                        :label="'Run Day 8 Part 1'"
                        :file="'day8.txt'"
                        :day="8"
                    />

                    <livewire:run-command
                        :command-key="'day8-part2'"
                        :label="'Run Day 8 Part 2'"
                        :file="'day8.txt'"
                        :day="8"
                    />

                    {{-- Day 9 --}}
                    <livewire:run-command
                        :command-key="'day9-part1'"
                        :label="'Run Day 9 Part 1'"
                        :file="'day9.txt'"
                        :day="9"
                    />

                    <livewire:run-command
                        :command-key="'day9-part2'"
                        :label="'Run Day 9 Part 2'"
                        :file="'day9.txt'"
                        :day="9"
                    />

                    {{-- Day 10 --}}
                    <livewire:run-command
                        :command-key="'day10-part1'"
                        :label="'Run Day 10 Part 1'"
                        :file="'day10.txt'"
                        :day="10"
                    />

                    <livewire:run-command
                        :command-key="'day10-part2'"
                        :label="'Run Day 10 Part 2'"
                        :file="'day10.txt'"
                        :day="10"
                    />

                    {{-- Day 11 --}}
                    <livewire:run-command
                        :command-key="'day11-part1'"
                        :label="'Run Day 11 Part 1'"
                        :file="'day11.txt'"
                        :day="11"
                    />

                    <livewire:run-command
                        :command-key="'day11-part2'"
                        :label="'Run Day 11 Part 2'"
                        :file="'day11.txt'"
                        :day="11"
                    />

                    {{-- Day 12 --}}
                    <livewire:run-command
                        :command-key="'day12-part1'"
                        :label="'Run Day 12 Part 1'"
                        :file="'day12.txt'"
                        :day="12"
                    />

                    <livewire:run-command
                        :command-key="'day12-part2'"
                        :label="'Run Day 12 Part 2'"
                        :file="'day12.txt'"
                        :day="12"
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