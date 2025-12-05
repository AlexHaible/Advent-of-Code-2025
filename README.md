# Advent of Code 2025

[![Tests](https://github.com/AlexHaible/Advent-of-Code-2025/actions/workflows/run-tests.yml/badge.svg)](https://github.com/AlexHaible/Advent-of-Code-2025/actions/workflows/run-tests.yml)

To run the project, I can recommend using [Laravel Herd](https://herd.laravel.com/)

## Setup Instructions
```
# clone the repo
git clone AlexHaible/Advent-of-Code-2025

# cd into the directory
cd Advent-of-Code-2025

# install dependencies
composer install
npm install

# copy the .env file and set the APP_PHP_CLI variable
cp .env.example .env

# set the APP_PHP_CLI variable
if sed --version >/dev/null 2>&1; then
  sed -i 's|^APP_PHP_CLI=.*|APP_PHP_CLI="'"$(which php)"'"|g' .env
else
  sed -i '' 's|^APP_PHP_CLI=.*|APP_PHP_CLI="'"$(which php)"'"|g' .env
fi || echo 'APP_PHP_CLI="'"$(which php)"'"' >> .env

# generate the application key
php artisan key:generate

# run the migrations and seeder
php artisan migrate
```

1. Put your input file into `/storage/app/public/`
2. Run the console commands, e.g. `php artisan advent-of-code:day-one input.txt` or `php artisan advent-of-code:day-one-part-two input.txt`

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
