<?php

namespace App\Support;

class TachyonManifold
{
    /** @var string[][] */
    public array $grid;
    public int $height;
    public int $width;
    public int $startRow;
    public int $startCol;

    public static function fromLines(array $lines): self
    {
        $grid = [];
        $startRow = $startCol = -1;

        foreach ($lines as $r => $line) {
            $row = str_split(rtrim($line, "\r\n"));
            foreach ($row as $c => $ch) {
                if ($ch === 'S') {
                    $startRow = $r;
                    $startCol = $c;
                }
            }
            $grid[] = $row;
        }

        if ($startRow < 0) {
            throw new \RuntimeException('No S found in manifold.');
        }

        $self = new self();
        $self->grid = $grid;
        $self->height = count($grid);
        $self->width = count($grid[0] ?? []);
        $self->startRow = $startRow;
        $self->startCol = $startCol;

        return $self;
    }

    public function countSplits(): int
    {
        $splits = 0;
        $visited = [];

        // queue of active beams (r, c)
        $queue = [[$this->startRow + 1, $this->startCol]];

        while ($queue) {
            [$r, $c] = array_pop($queue);

            // bounds
            if ($r < 0 || $r >= $this->height || $c < 0 || $c >= $this->width) {
                continue;
            }

            $key = $r . ',' . $c;
            if (isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;

            $cell = $this->grid[$r][$c];

            if ($cell === '^') {
                $splits++;

                // spawn beams left and right
                // their next move is downward
                $queue[] = [$r, $c - 1];
                $queue[] = [$r, $c + 1];
                continue;
            }

            // anything else (including '.')
            // continue down
            $queue[] = [$r + 1, $c];
        }

        return $splits;
    }

    public function countTimelines(): int
    {
        $memo = [];
        return $this->countFrom($this->startRow + 1, $this->startCol, $memo);
    }

    private function countFrom(int $r, int $c, array &$memo): int
    {
        // Exited the manifold: one completed timeline.
        if ($r < 0 || $r >= $this->height || $c < 0 || $c >= $this->width) {
            return 1;
        }

        $key = $r . ',' . $c;
        if (isset($memo[$key])) {
            return $memo[$key];
        }

        $cell = $this->grid[$r][$c] ?? '.';

        if ($cell === '^') {
            // Split into left and right at the same row.
            $result = $this->countFrom($r, $c - 1, $memo)
                    + $this->countFrom($r, $c + 1, $memo);
        } else {
            // Anything else: continue downward.
            $result = $this->countFrom($r + 1, $c, $memo);
        }

        return $memo[$key] = $result;
    }
}