# Advent of Code 2025

[![Tests](https://github.com/AlexHaible/Advent-of-Code-2025/actions/workflows/run-tests.yml/badge.svg)](https://github.com/AlexHaible/Advent-of-Code-2025/actions/workflows/run-tests.yml)

To run the project, I can recommend using [Laravel Herd](https://herd.laravel.com/)

## Setup Instructions
1. Clone the project
2. Set it up in your preferred bit of software that can serve PHP
3. Put your input file into `/storage/app/public/`
4. Run the console commands, e.g. `php artisan advent-of-code:day-one input.txt` or `php artisan advent-of-code:day-one-part-two input.txt`

And your result should be something like this:

```
php artisan advent-of-code:day-one input.txt
1234 (took 604.583 µs)

php artisan advent-of-code:day-one-part-two input.txt
5678 (took 14.875 ms)
```

For ease of reference, the commands are located in [`app/Console/Commands/`](https://github.com/AlexHaible/Advent-of-Code-2025/tree/master/app/Console/Commands)

And tests are located in [`tests/Feature/`](https://github.com/AlexHaible/Advent-of-Code-2025/tree/master/tests/Feature)

## Running Tests
Simply run `php artisan test`
