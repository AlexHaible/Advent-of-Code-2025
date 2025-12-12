<?php

namespace App\Support;

class PackagePlacementSolver
{
    private array $shapeVariants = [];
    private array $bitmaskCache = [];

    // Recursion state properties
    private array $currentPieces;
    private array $currentSuffixAreas;
    private array $currentPlacements;
    private int $currentPieceCount;

    public function __construct(array $shapeLines)
    {
        $this->parseShapes($shapeLines);
    }

    public function solve(string $regionLine): bool
    {
        if (!preg_match('/^(\d+)x(\d+):\s*(.*)$/', $regionLine, $m)) {
            return false;
        }

        $W = (int)$m[1];
        $H = (int)$m[2];
        $counts = preg_split('/\s+/', trim($m[3]));

        $pieces = [];
        foreach ($counts as $shapeId => $count) {
            $count = (int)$count;
            for ($i = 0; $i < $count; $i++) {
                $pieces[] = (int)$shapeId;
            }
        }

        // Sort largest first for better pruning
        usort($pieces, function($a, $b) {
            $sizeA = count($this->shapeVariants[$a][0]['cells']);
            $sizeB = count($this->shapeVariants[$b][0]['cells']);
            return $sizeB <=> $sizeA;
        });

        // Precompute suffix areas
        $suffixAreas = array_fill(0, count($pieces) + 1, 0);
        $sum = 0;
        for ($i = count($pieces) - 1; $i >= 0; $i--) {
            $sum += count($this->shapeVariants[$pieces[$i]][0]['cells']);
            $suffixAreas[$i] = $sum;
        }

        if ($sum > $W * $H) {
            return false;
        }

        // Optimization 1: Full Grid Bitmask (Area <= 64)
        if ($W * $H <= 64 && PHP_INT_SIZE >= 8) {
            return $this->solveRegionBitmask($W, $H, $pieces, $suffixAreas, $W * $H);
        }

        // Optimization 2: Row-based Bitmask (Width <= 63)
        // This fits each row into a single 64-bit integer.
        // Most inputs in day12.txt fall here (W ~ 30-50).
        if ($W < 64 && PHP_INT_SIZE >= 8) {
            return $this->solveRegionRowBitmask($W, $H, $pieces, $suffixAreas, $W * $H);
        }

        // Fallback: Array Solver (Slow, but handles massive W)
        $grid = array_fill(0, $W * $H, false);
        return $this->backtrack($grid, $W, $H, $pieces, 0, 0, $W * $H, $suffixAreas);
    }

    private function parseShapes(array $lines): void
    {
        $currentId = null;
        $currentBuffer = [];

        foreach ($lines as $line) {
            $trim = trim($line);
            if (preg_match('/^(\d+):$/', $trim, $m)) {
                if ($currentId !== null && !empty($currentBuffer)) {
                    $this->storeShape($currentId, $currentBuffer);
                }
                $currentId = (int)$m[1];
                $currentBuffer = [];
                continue;
            }

            if ($currentId !== null) {
                $row = [];
                for ($i = 0; $i < strlen($trim); $i++) {
                    $row[] = ($trim[$i] === '#');
                }
                $currentBuffer[] = $row;
            }
        }
        if ($currentId !== null && !empty($currentBuffer)) {
            $this->storeShape($currentId, $currentBuffer);
        }
    }

    private function storeShape(int $id, array $grid): void
    {
        $variants = [];
        $current = $grid;
        // 4 rotations + flips
        for ($i = 0; $i < 2; $i++) {
            for ($r = 0; $r < 4; $r++) {
                $variants[] = $current;
                $current = $this->rotate90($current);
            }
            $current = $this->flipH($grid);
        }

        $unique = [];
        foreach ($variants as $v) {
            $hash = $this->hashGrid($v);
            if (!isset($unique[$hash])) {
                $unique[$hash] = $v;
            }
        }

        $optimizedVariants = [];
        foreach ($unique as $vGrid) {
            $h = count($vGrid);
            $w = count($vGrid[0]);

            // 1. Cell based (for array solver)
            $cells = [];
            // 2. Row Bitmask based (for row solver)
            $rowMasks = array_fill(0, $h, 0);

            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    if ($vGrid[$y][$x]) {
                        $cells[] = [$y, $x];
                        $rowMasks[$y] |= (1 << $x);
                    }
                }
            }

            $optimizedVariants[] = [
                'h' => $h,
                'w' => $w,
                'cells' => $cells,
                'row_masks' => $rowMasks
            ];
        }

        $this->shapeVariants[$id] = $optimizedVariants;
    }

    private function flipH(array $grid): array
    {
        $new = [];
        foreach ($grid as $row) {
            $new[] = array_reverse($row);
        }
        return $new;
    }

    private function rotate90(array $grid): array
    {
        $h = count($grid);
        $w = count($grid[0]);
        $new = [];
        for ($x = 0; $x < $w; $x++) {
            $newRow = [];
            for ($y = $h - 1; $y >= 0; $y--) {
                $newRow[] = $grid[$y][$x];
            }
            $new[] = $newRow;
        }
        return $new;
    }

    private function hashGrid(array $grid): string
    {
        $s = '';
        foreach ($grid as $row) {
            foreach ($row as $cell) {
                $s .= $cell ? '#' : '.';
            }
            $s .= '|';
        }
        return $s;
    }

    // -------------------------------------------------------------------------
    // Row-Bitmask Solver (Width < 64)
    // -------------------------------------------------------------------------

    private function solveRegionRowBitmask(int $W, int $H, array $pieces, array $suffixAreas, int $totalArea): bool
    {
        // Grid is array of ints, one per row. Initially all 0.
        $gridRows = array_fill(0, $H, 0);
        return $this->backtrackRowBitmask($gridRows, $W, $H, $pieces, 0, 0, $totalArea, $suffixAreas);
    }

    private function backtrackRowBitmask(
        array $gridRows,
        int $W,
        int $H,
        array $pieces,
        int $idx,
        int $lastPlacedIndex,
        int $emptyCount,
        array $suffixAreas
    ): bool {
        if ($idx >= count($pieces)) return true;
        if ($emptyCount < $suffixAreas[$idx]) return false;

        $shapeId = $pieces[$idx];
        $startIndex = 0;

        // Symmetry breaking
        if ($idx > 0 && $shapeId === $pieces[$idx - 1]) {
            $startIndex = $lastPlacedIndex;
        }

        // Optimization: Find the first empty cell to guide placement?
        // Finding first empty cell in row-bitmask is reasonably fast.
        // We can skip rows that are full.

        $startY = intdiv($startIndex, $W);
        $fullRowMask = (1 << $W) - 1;

        // Fast-forward to the first row that isn't full
        for ($y = 0; $y < $H; $y++) {
            if ($gridRows[$y] !== $fullRowMask) {
                // Found a row with empty spots.
                // We should prioritize filling this row.
                // However, we must respect $startIndex.
                // If $y * W > $startIndex, we can jump ahead.
                if ($y * $W > $startIndex) {
                    $startIndex = $y * $W;
                }
                break;
            }
        }

        // We scan the grid by index (i = y * W + x)
        for ($i = $startIndex; $i < $W * $H; $i++) {
            $y = intdiv($i, $W);
            $x = $i % $W;

            // If this cell is already occupied, skip
            if (($gridRows[$y] >> $x) & 1) {
                continue;
            }

            foreach ($this->shapeVariants[$shapeId] as $variant) {
                if ($y + $variant['h'] > $H) continue;
                if ($x + $variant['w'] > $W) continue;

                // Check collision using row masks
                $collision = false;
                for ($r = 0; $r < $variant['h']; $r++) {
                    // Precompute shift?
                    // Note: variant['row_masks'][$r] is aligned to x=0.
                    // We check if shifting it to $x collides with grid.
                    // If ($variant_mask << $x) & $grid_row is non-zero, collision.
                    if ($gridRows[$y + $r] & ($variant['row_masks'][$r] << $x)) {
                        $collision = true;
                        break;
                    }
                }

                if (!$collision) {
                    // Place
                    $newRows = $gridRows;
                    for ($r = 0; $r < $variant['h']; $r++) {
                        $newRows[$y + $r] |= ($variant['row_masks'][$r] << $x);
                    }

                    $placedSize = count($variant['cells']);

                    if ($this->backtrackRowBitmask(
                        $newRows,
                        $W,
                        $H,
                        $pieces,
                        $idx + 1,
                        $i,
                        $emptyCount - $placedSize,
                        $suffixAreas
                    )) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // Full Bitmask Solver (Total Area <= 64)
    // -------------------------------------------------------------------------

    private function solveRegionBitmask(int $W, int $H, array $pieces, array $suffixAreas, int $totalArea): bool
    {
        $this->currentPieces = $pieces;
        $this->currentSuffixAreas = $suffixAreas;
        $this->currentPieceCount = count($pieces);
        $this->currentPlacements = [];

        foreach ($pieces as $pId) {
            if (!isset($this->currentPlacements[$pId])) {
                $this->currentPlacements[$pId] = $this->generateBitmaskPlacements($W, $H, $pId);
            }
        }

        return $this->backtrackBitmaskFast(0, 0, -1, $totalArea);
    }

    private function generateBitmaskPlacements(int $W, int $H, int $shapeId): array
    {
        $cacheKey = "{$shapeId}|{$W}|{$H}";
        if (isset($this->bitmaskCache[$cacheKey])) {
            return $this->bitmaskCache[$cacheKey];
        }

        $masks = [];
        foreach ($this->shapeVariants[$shapeId] as $variant) {
            for ($r = 0; $r <= $H - $variant['h']; $r++) {
                for ($c = 0; $c <= $W - $variant['w']; $c++) {
                    $mask = 0;
                    $size = 0;
                    $baseIdx = $r * $W + $c;

                    foreach ($variant['cells'] as $cell) {
                        $idx = $baseIdx + ($cell[0] * $W) + $cell[1];
                        $mask |= (1 << $idx);
                        $size++;
                    }
                    $masks[] = [$mask, $size];
                }
            }
        }

        $unique = [];
        foreach ($masks as $m) {
            $unique[$m[0]] = $m;
        }
        $final = array_values($unique);

        usort($final, function($a, $b) {
            $lsbA = $a[0] & -$a[0];
            $lsbB = $b[0] & -$b[0];
            return $lsbA <=> $lsbB;
        });

        $this->bitmaskCache[$cacheKey] = $final;
        return $final;
    }

    private function backtrackBitmaskFast(int $gridMask, int $idx, int $lastPlacementIndex, int $emptyCount): bool
    {
        if ($idx >= $this->currentPieceCount) return true;
        if ($emptyCount < $this->currentSuffixAreas[$idx]) return false;

        $inv = ~$gridMask;
        $firstHoleBit = $inv & -$inv;
        $isExactCover = ($emptyCount === $this->currentSuffixAreas[$idx]);

        $shapeId = $this->currentPieces[$idx];
        $startIndex = 0;
        if ($idx > 0 && $shapeId === $this->currentPieces[$idx - 1]) {
            $startIndex = $lastPlacementIndex + 1;
        }

        $possiblePlacements = $this->currentPlacements[$shapeId];
        $count = count($possiblePlacements);

        for ($i = $startIndex; $i < $count; $i++) {
            [$mask, $size] = $possiblePlacements[$i];

            if (($gridMask & $mask) === 0) {
                if ($isExactCover) {
                    if (($mask & $firstHoleBit) === 0) {
                        if (($mask & -$mask) > $firstHoleBit) {
                            break;
                        }
                        continue;
                    }
                }

                if ($this->backtrackBitmaskFast($gridMask | $mask, $idx + 1, $i, $emptyCount - $size)) {
                    return true;
                }
            }
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // Array Solver (Fallback)
    // -------------------------------------------------------------------------

    private function backtrack(array $grid, int $W, int $H, array $pieces, int $idx, int $lastPlacedIndex, int $emptyCount, array $suffixAreas): bool
    {
        if ($idx >= count($pieces)) return true;
        if ($emptyCount < $suffixAreas[$idx]) return false;

        $shapeId = $pieces[$idx];
        $startIndex = 0;
        if ($idx > 0 && $pieces[$idx] === $pieces[$idx - 1]) {
            $startIndex = $lastPlacedIndex;
        }

        for ($i = $startIndex; $i < $W * $H; $i++) {
            $y = intdiv($i, $W);
            $x = $i % $W;

            foreach ($this->shapeVariants[$shapeId] as $variant) {
                if ($y + $variant['h'] > $H) continue;
                if ($x + $variant['w'] > $W) continue;

                if ($this->canPlace($grid, $W, $variant['cells'], $y, $x)) {
                    $newGrid = $this->place($grid, $W, $variant['cells'], $y, $x, true);
                    $placedSize = count($variant['cells']);
                    if ($this->backtrack($newGrid, $W, $H, $pieces, $idx + 1, $i, $emptyCount - $placedSize, $suffixAreas)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function canPlace(array $grid, int $W, array $cells, int $offY, int $offX): bool
    {
        foreach ($cells as $c) {
            $idx = ($offY + $c[0]) * $W + ($offX + $c[1]);
            if ($grid[$idx]) return false;
        }
        return true;
    }

    private function place(array $grid, int $W, array $cells, int $offY, int $offX, bool $val): array
    {
        foreach ($cells as $c) {
            $grid[($offY + $c[0]) * $W + ($offX + $c[1])] = $val;
        }
        return $grid;
    }
}
