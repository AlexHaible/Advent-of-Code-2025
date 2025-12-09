<?php

namespace App\Support;

class RedGreenRectangleFinder
{
    /** @var array<int, array{0:int, 1:int}> */
    private array $points;

    /** @var int[] */
    private array $xs = [];

    /** @var int[] */
    private array $ys = [];

    /** @var array<int,int> */
    private array $xIndex = [];

    /** @var array<int,int> */
    private array $yIndex = [];

    /** @var int[][] inside[y][x] = 0|1 for compressed cells */
    private array $inside = [];

    /** @var int[][] prefix sums over inside grid */
    private array $prefix = [];

    public function __construct(array $points)
    {
        // points must be in loop order: [ [x1,y1], [x2,y2], ..., ]
        $this->points = $points;

        $this->buildCoordinateCompression();
        $this->buildInsideGrid();
        $this->buildPrefixSums();
    }

    public function findMaxArea(): int
    {
        $n = count($this->points);
        if ($n < 2) {
            return 0;
        }

        $maxArea = 0;

        for ($i = 0; $i < $n; $i++) {
            [$x1, $y1] = $this->points[$i];
            $x1i = $this->xIndex[$x1];
            $y1i = $this->yIndex[$y1];

            for ($j = $i + 1; $j < $n; $j++) {
                [$x2, $y2] = $this->points[$j];

                // Need opposite corners, not aligned on same row/column.
                if ($x1 === $x2 || $y1 === $y2) {
                    continue;
                }

                // Tile area (inclusive of both corners).
                $dx   = abs($x1 - $x2);
                $dy   = abs($y1 - $y2);
                $area = ($dx + 1) * ($dy + 1);

                if ($area <= $maxArea) {
                    continue; // can't beat current best
                }

                $x2i = $this->xIndex[$x2];
                $y2i = $this->yIndex[$y2];

                $left   = min($x1i, $x2i);
                $right  = max($x1i, $x2i);
                $top    = min($y1i, $y2i);
                $bottom = max($y1i, $y2i);

                // Compressed cell indices: [left, right-1] × [top, bottom-1]
                $widthCells  = $right  - $left;
                $heightCells = $bottom - $top;

                if ($widthCells <= 0 || $heightCells <= 0) {
                    continue;
                }

                $totalCells = $widthCells * $heightCells;

                // Count how many "inside" cells are in this rectangle.
                $sum = $this->rectSum($top, $left, $bottom, $right);

                if ($sum === $totalCells) {
                    $maxArea = $area;
                }
            }
        }

        return $maxArea;
    }

    private function buildCoordinateCompression(): void
    {
        $xs = [];
        $ys = [];

        foreach ($this->points as [$x, $y]) {
            $xs[] = $x;
            $ys[] = $y;
        }

        $xs = array_values(array_unique($xs));
        $ys = array_values(array_unique($ys));
        sort($xs);
        sort($ys);

        $this->xs = $xs;
        $this->ys = $ys;

        $this->xIndex = [];
        foreach ($xs as $i => $x) {
            $this->xIndex[$x] = $i;
        }

        $this->yIndex = [];
        foreach ($ys as $j => $y) {
            $this->yIndex[$y] = $j;
        }
    }

    private function buildInsideGrid(): void
    {
        $nx = count($this->xs);
        $ny = count($this->ys);

        $cellW = $nx - 1;
        $cellH = $ny - 1;

        if ($cellW <= 0 || $cellH <= 0) {
            $this->inside = [];
            return;
        }

        // Initialise inside grid to 0
        $inside = array_fill(0, $cellH, array_fill(0, $cellW, 0));

        // Collect vertical edges of the polygon
        $verticalEdges = [];
        $n = count($this->points);

        for ($i = 0; $i < $n; $i++) {
            [$x1, $y1] = $this->points[$i];
            [$x2, $y2] = $this->points[($i + 1) % $n];

            if ($x1 === $x2 && $y1 !== $y2) {
                if ($y1 < $y2) {
                    $verticalEdges[] = [
                        'x'  => $x1,
                        'y1' => $y1,
                        'y2' => $y2,
                    ];
                } else {
                    $verticalEdges[] = [
                        'x'  => $x1,
                        'y1' => $y2,
                        'y2' => $y1,
                    ];
                }
            }
        }

        // For each horizontal strip between Ys[j] and Ys[j+1], compute where we are inside.
        for ($row = 0; $row < $cellH; $row++) {
            $yLow  = $this->ys[$row];
            $yHigh = $this->ys[$row + 1];

            // Pick any point strictly between yLow and yHigh
            $yMid = ($yLow + $yHigh) * 0.5;

            $xsIntersections = [];

            foreach ($verticalEdges as $edge) {
                // Standard half-open intersection: include if y1 <= yMid < y2
                if ($edge['y1'] <= $yMid && $yMid < $edge['y2']) {
                    $xsIntersections[] = $edge['x'];
                }
            }

            if (!$xsIntersections) {
                continue; // no interior on this strip
            }

            sort($xsIntersections);
            $count = count($xsIntersections);

            // For each [left,right) interval, mark inside cells.
            $col = 0;
            for ($k = 0; $k + 1 < $count; $k += 2) {
                $leftX  = $xsIntersections[$k];
                $rightX = $xsIntersections[$k + 1];

                // Move to first cell whose right boundary > leftX
                while ($col < $cellW && $this->xs[$col + 1] <= $leftX) {
                    $col++;
                }

                $c = $col;
                while (
                    $c < $cellW &&
                    $this->xs[$c]   >= $leftX &&
                    $this->xs[$c+1] <= $rightX
                ) {
                    $inside[$row][$c] = 1;
                    $c++;
                }

                $col = $c;
            }
        }

        $this->inside = $inside;
    }

    private function buildPrefixSums(): void
    {
        $h = count($this->inside);
        if ($h === 0) {
            $this->prefix = [[0]];
            return;
        }
        $w = count($this->inside[0]);

        $ps = array_fill(0, $h + 1, array_fill(0, $w + 1, 0));

        for ($y = 0; $y < $h; $y++) {
            $rowSum = 0;
            for ($x = 0; $x < $w; $x++) {
                $rowSum += $this->inside[$y][$x];
                $ps[$y + 1][$x + 1] = $ps[$y][$x + 1] + $rowSum;
            }
        }

        $this->prefix = $ps;
    }

    /**
     * Sum of inside-cells over compressed rectangle:
     * rows [top, bottom-1], cols [left, right-1]
     */
    private function rectSum(int $top, int $left, int $bottom, int $right): int
    {
        // guard against degenerate ranges
        if ($bottom <= $top || $right <= $left) {
            return 0;
        }

        $ps = $this->prefix;

        return $ps[$bottom][$right]
            - $ps[$top][$right]
            - $ps[$bottom][$left]
            + $ps[$top][$left];
    }
}
