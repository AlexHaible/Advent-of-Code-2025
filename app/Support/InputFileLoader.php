<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class InputFileLoader
{
    /**
     * Load lines from either an absolute path or from public storage.
     *
     * If you pass "input.txt", it reads: storage/app/public/input.txt
     * If you pass an absolute path, it reads directly.
     *
     * @throws \RuntimeException
     */
    public static function loadLines(string $fileArg): array
    {
        // Absolute path on *nix or Windows
        $isAbsolute = str_starts_with($fileArg, DIRECTORY_SEPARATOR)
            || preg_match('#^[A-Za-z]:\\\\#', $fileArg) === 1;

        if ($isAbsolute) {
            if (! is_readable($fileArg)) {
                throw new \RuntimeException("Unable to read file: {$fileArg}");
            }

            $lines = file($fileArg, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            return $lines === false ? [] : $lines;
        }

        // Treat as public storage file
        $disk = Storage::disk('public');

        if (! $disk->exists($fileArg)) {
            throw new \RuntimeException("File not found in public storage: {$fileArg}");
        }

        $contents = $disk->get($fileArg);

        $lines = preg_split('/\r\n|\r|\n/', $contents, -1, PREG_SPLIT_NO_EMPTY);

        return $lines === false ? [] : $lines;
    }
}
