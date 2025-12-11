<?php

namespace App\Console\Commands;

use App\Support\DurationFormatter;
use App\Support\InputFileLoader;
use Illuminate\Console\Command;

class AdventOfCodeDayTenPartTwo extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'advent-of-code:day-ten-part-two {file : File name or path to the input file}';

    protected $description = 'Day 10 Part 2 - Fewest button presses (Optimized Linear Algebra + Robust Search)';

    public function handle(): int
    {
        $fileArg = $this->argument('file');

        try {
            $lines = InputFileLoader::loadLines($fileArg);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        $start = hrtime(true);
        $totalPresses = 0;

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '') {
                continue;
            }

            // 1. Parse Targets inside { ... }
            if (!preg_match('/\{(.*?)\}/', $line, $mTargets)) {
                continue;
            }

            $targetStrs = explode(',', $mTargets[1]);
            $targets = [];
            foreach ($targetStrs as $s) {
                $val = trim($s);
                if ($val !== '') {
                    $targets[] = (int)$val;
                }
            }

            if (empty($targets)) {
                continue;
            }

            $numCounters = count($targets);

            // 2. Parse Buttons inside ( ... )
            if (!preg_match_all('/\(([^)]*)\)/', $line, $mButtons)) {
                continue;
            }

            // Build Matrix A (columns are buttons, rows are counters)
            $matrixColumns = [];
            $buttonsAffectedIndices = []; // Keep track for bounds calculation

            foreach ($mButtons[1] as $btnSpec) {
                $indicesStr = explode(',', $btnSpec);
                $colVector = array_fill(0, $numCounters, 0.0);
                $indices = [];

                $hasEffect = false;
                foreach ($indicesStr as $idxStr) {
                    $idxStr = trim($idxStr);
                    if ($idxStr === '' || !ctype_digit($idxStr)) {
                        continue;
                    }
                    $idx = (int)$idxStr;
                    if ($idx >= 0 && $idx < $numCounters) {
                        $colVector[$idx] = 1.0;
                        $indices[] = $idx;
                        $hasEffect = true;
                    }
                }

                if ($hasEffect) {
                    $matrixColumns[] = $colVector;
                    $buttonsAffectedIndices[] = $indices;
                }
            }

            if (empty($matrixColumns)) {
                continue;
            }

            // 3. Solve using Linear Algebra
            $minPresses = $this->solveLinearSystem($matrixColumns, $targets, $buttonsAffectedIndices);

            if ($minPresses !== null) {
                $totalPresses += $minPresses;
            }
        }

        $duration = DurationFormatter::formatFromStart($start);

        $this->info(sprintf(
            '%d (took %s)',
            $totalPresses,
            $duration
        ));

        return Command::SUCCESS;
    }

    private function solveLinearSystem(array $columns, array $targets, array $buttonsAffectedIndices): ?int
    {
        $numVars = count($columns);
        $numRows = count($targets);

        // 1. Calculate Hard Upper Bounds for each variable
        // Since effects are additive and non-negative, a button cannot be pressed
        // more times than the target value of any counter it affects.
        $varBounds = [];
        for ($i = 0; $i < $numVars; $i++) {
            $minTarget = PHP_INT_MAX;
            foreach ($buttonsAffectedIndices[$i] as $cIdx) {
                if ($targets[$cIdx] < $minTarget) {
                    $minTarget = $targets[$cIdx];
                }
            }
            $varBounds[$i] = $minTarget;
        }

        // 2. Build Matrix [A | b]
        $matrix = [];
        for ($r = 0; $r < $numRows; $r++) {
            $row = [];
            for ($c = 0; $c < $numVars; $c++) {
                $row[] = $columns[$c][$r];
            }
            $row[] = (float)$targets[$r];
            $matrix[] = $row;
        }

        // 3. Gaussian Elimination to RREF
        $pivotRow = 0;
        $pivotCols = array_fill(0, $numVars, -1);
        $epsilon = 1e-4; // Relaxed epsilon

        for ($c = 0; $c < $numVars && $pivotRow < $numRows; $c++) {
            $sel = -1;
            for ($r = $pivotRow; $r < $numRows; $r++) {
                if (abs($matrix[$r][$c]) > $epsilon) {
                    $sel = $r;
                    break;
                }
            }

            if ($sel === -1) continue;

            if ($sel !== $pivotRow) {
                $temp = $matrix[$pivotRow];
                $matrix[$pivotRow] = $matrix[$sel];
                $matrix[$sel] = $temp;
            }

            $pivotVal = $matrix[$pivotRow][$c];
            for ($j = $c; $j <= $numVars; $j++) {
                $matrix[$pivotRow][$j] /= $pivotVal;
            }

            $pivotCols[$c] = $pivotRow;

            for ($r = 0; $r < $numRows; $r++) {
                if ($r !== $pivotRow && abs($matrix[$r][$c]) > $epsilon) {
                    $factor = $matrix[$r][$c];
                    for ($j = $c; $j <= $numVars; $j++) {
                        $matrix[$r][$j] -= $factor * $matrix[$pivotRow][$j];
                    }
                }
            }

            $pivotRow++;
        }

        // Check consistency
        for ($r = $pivotRow; $r < $numRows; $r++) {
            if (abs($matrix[$r][$numVars]) > $epsilon) return null;
        }

        // 4. Identify Variables
        $freeVars = [];
        $dependentVars = [];

        for ($c = 0; $c < $numVars; $c++) {
            if ($pivotCols[$c] !== -1) {
                $dependentVars[$c] = $pivotCols[$c];
            } else {
                $freeVars[] = $c;
            }
        }

        // 5. Search Free Variables using strict bounds
        $bestSum = null;
        $this->searchFreeVars($freeVars, 0, [], $matrix, $dependentVars, $numVars, $bestSum, $varBounds);

        return $bestSum;
    }

    private function searchFreeVars(
        array $freeVars,
        int $idx,
        array $currentFreeValues,
        array $matrix,
        array $dependentVars,
        int $numVars,
        ?int &$bestSum,
        array $varBounds
    ): void {
        if ($idx >= count($freeVars)) {
            // All free vars assigned, calculate dependent vars
            $currentSum = 0;
            foreach ($currentFreeValues as $val) $currentSum += $val;

            foreach ($dependentVars as $depIdx => $rowIdx) {
                $val = $matrix[$rowIdx][$numVars];
                foreach ($freeVars as $fIdx => $fVarIdx) {
                    $val -= $matrix[$rowIdx][$fVarIdx] * $currentFreeValues[$fIdx];
                }

                // Safety checks with relaxed epsilon
                if ($val < -1e-4) return; // Must be non-negative
                if (abs($val - round($val)) > 1e-4) return; // Must be integer

                $intVal = (int)round($val);

                // Double check against global upper bound
                if ($intVal > $varBounds[$depIdx]) return;

                $currentSum += $intVal;
            }

            if ($bestSum === null || $currentSum < $bestSum) {
                $bestSum = $currentSum;
            }
            return;
        }

        $fVarIdx = $freeVars[$idx];

        // Simpler, Safer Search:
        // Instead of calculating complex intersection of constraints which might fail due to float precision,
        // we iterate the full valid range of the free variable (0 to its Global Hard Bound).
        // We rely on the recursion base case to validate the solution.
        // Since typical AoC inputs have few free variables, this "Brute Force" over free vars is fast enough.

        $limit = $varBounds[$fVarIdx];

        for ($v = 0; $v <= $limit; $v++) {
            $nextFree = $currentFreeValues;
            $nextFree[] = $v;
            $this->searchFreeVars($freeVars, $idx + 1, $nextFree, $matrix, $dependentVars, $numVars, $bestSum, $varBounds);
        }
    }
}
