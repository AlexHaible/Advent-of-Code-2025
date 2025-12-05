<?php

namespace App\Livewire;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\Process\Process;

class RunCommand extends Component
{
    use WithFileUploads;

    /**
     * Map between UI command keys and the underlying artisan command
     * plus an optional default input file.
     *
     * [ key => [ artisanName, defaultFile|null ], ... ]
     */
    private const array COMMAND_MAP = [
        'day1-part1' => ['advent-of-code:day-one', 'day1.txt'],
        'day1-part2' => ['advent-of-code:day-one-part-two', 'day1.txt'],

        'day2-part1' => ['advent-of-code:day-two', 'day2.txt'],
        'day2-part2' => ['advent-of-code:day-two-part-two', 'day2.txt'],

        'day3-part1' => ['advent-of-code:day-three', 'day3.txt'],
        'day3-part2' => ['advent-of-code:day-three-part-two', 'day3.txt'],

        'day4-part1' => ['advent-of-code:day-four', 'day4.txt'],
        'day4-part2' => ['advent-of-code:day-four-part-two', 'day4.txt'],

        // Runs the full test suite; no input file argument.
        'tests'      => ['test', null],
    ];

    public string $commandKey;
    public string $label;
    public ?string $file = null;
    public string $filePlaceholder = '';

    public bool $running = false;
    public $uploadedFile;

    public function mount(string $commandKey, string $label, ?string $file = null): void
    {
        $this->commandKey = $commandKey;
        $this->label      = $label;
        $this->file       = $file;

        // Derive a sensible placeholder from the command map, if possible
        if (isset(self::COMMAND_MAP[$commandKey])) {
            [$artisanCommand, $defaultFile] = self::COMMAND_MAP[$commandKey];

            if ($defaultFile !== null) {
                $this->filePlaceholder = sprintf(
                    'Input file (optional, default: %s)',
                    $defaultFile
                );
            } else {
                $this->filePlaceholder = 'No input file required';
            }
        } else {
            $this->filePlaceholder = 'Input file (optional)';
        }
    }

    public function run(): void
    {
        if ($this->running) {
            return;
        }

        $this->running = true;

        try {
            [$artisanCommand, $arguments] = $this->resolveCommand($this->commandKey);

            // Special case: run the full test suite via the PHP CLI instead of Artisan::call('test'),
            // because Laravel's built-in test command spawns a subprocess using PHP_BINARY (php-fpm
            // under the web server), which causes the FPM usage help you're seeing.
            if ($this->commandKey === 'tests') {
                // Use a configurable PHP CLI binary; default is "php", but on Herd/Valet
                // you'll typically point this at your Herd PHP, e.g. via APP_PHP_CLI.
                $phpBinary = (string) config('app.php_cli', 'php');

                if ($phpBinary === '' || ! @is_executable($phpBinary)) {
                    $cmdString = 'php artisan test';

                    $this->dispatch('commandExecuted', [
                        'command' => $cmdString,
                        'output'  => sprintf(
                            "Configured PHP CLI binary '%s' is not executable.\n\n" .
                            "Set APP_PHP_CLI in your .env to your CLI PHP path (for example, the output of `which php`).",
                            $phpBinary === '' ? '(empty)' : $phpBinary
                        ),
                    ]);

                    return;
                }

                $process = new Process([$phpBinary, 'artisan', 'test'], base_path());
                $process->setTimeout(null);
                $process->run();

                $output = trim($process->getOutput() . $process->getErrorOutput());
                $cmdString = 'php artisan test';

                $this->dispatch('commandExecuted', [
                    'command' => $cmdString,
                    'output'  => $output === '' ? '(no output)' : $output,
                ]);

                return;
            }

            // Build a “nice” command string like the terminal for normal commands
            $cmdString = $this->buildCommandString($artisanCommand, $arguments);

            // Some commands (like `php artisan test` with Pest/PHPUnit) expect STDOUT to exist.
            // When running via the web server, STDOUT may be undefined, so define a fallback.
            if (! defined('STDOUT')) {
                define('STDOUT', fopen('php://output', 'w'));
            }

            // Run command
            Artisan::call($artisanCommand, $arguments);
            $output = trim(Artisan::output());

            // Emit to terminal component
            $this->dispatch('commandExecuted', [
                'command' => $cmdString,
                'output'  => $output === '' ? '(no output)' : $output,
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('commandExecuted', [
                'command' => 'ERROR',
                'output'  => $e->getMessage(),
            ]);
        } finally {
            $this->running = false;
        }
    }

    private function resolveCommand(string $key): array
    {
        if (! isset(self::COMMAND_MAP[$key])) {
            throw new \RuntimeException("Unknown command key: {$key}");
        }

        [$artisanCommand, $defaultFile] = self::COMMAND_MAP[$key];
        $arguments = [];

        if ($defaultFile !== null) {
            // file upload takes priority
            if ($this->uploadedFile) {
                $path = $this->uploadedFile->storePublicly(
                    'advent-of-code',
                    ['disk' => 'public']
                );

                // pass the full relative path on the public disk so InputFileLoader can find it
                $arguments['file'] = $path;
            } else {
                $arguments['file'] = $this->file ?: $defaultFile;
            }
        }

        return [$artisanCommand, $arguments];
    }

    private function buildCommandString(string $artisanCommand, array $arguments): string
    {
        $parts = ['php', 'artisan', $artisanCommand];

        foreach ($arguments as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // numeric keys are just values (rare here)
            if (is_int($key)) {
                $parts[] = (string) $value;
                continue;
            }

            if (Str::startsWith($key, '--')) {
                $flag = $key;
            } else {
                $flag = $key === 'file'
                    ? '' // we want "php artisan cmd file.txt", not "--file=file.txt"
                    : '--' . $key;
            }

            if ($flag === '') {
                $parts[] = (string) $value;
            } else {
                // Handle flags with values
                $parts[] = $flag . '=' . $value;
            }
        }

        return implode(' ', $parts);
    }

    public function render()
    {
        return view('livewire.run-command');
    }
}
