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

    protected $description = 'Day 10 Part 2 - Fewest button presses (Linear Algebra Solver)';

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
            // and keep track of button indices
            $matrixColumns = [];

            foreach ($mButtons[1] as $btnSpec) {
                $indicesStr = explode(',', $btnSpec);
                $colVector = array_fill(0, $numCounters, 0.0);

                $hasEffect = false;
                foreach ($indicesStr as $idxStr) {
                    $idxStr = trim($idxStr);
                    if ($idxStr === '' || !ctype_digit($idxStr)) {
                        continue;
                    }
                    $idx = (int)$idxStr;
                    if ($idx >= 0 && $idx < $numCounters) {
                        $colVector[$idx] = 1.0;
                        $hasEffect = true;
                    }
                }

                // Only add buttons that do something
                if ($hasEffect) {
                    $matrixColumns[] = $colVector;
                }
            }

            // If no buttons but targets exist, impossible (unless targets are 0, handled by logic)
            if (empty($matrixColumns)) {
                if (array_sum($targets) > 0) {
                    // Impossible
                } else {
                    // 0 presses needed
                }
                continue;
            }

            // 3. Solve using Linear Algebra
            $minPresses = $this->solveLinearSystem($matrixColumns, $targets);

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

    /**
     * Solves the integer linear programming problem:
     * Minimize sum(x) subject to A*x = targets, x >= 0, x is integer.
     *
     * @param array $columns Array of column vectors (each is array of floats)
     * @param array $targets Target vector (array of ints)
     * @return int|null
     */
    private function solveLinearSystem(array $columns, array $targets): ?int
    {
        $numVars = count($columns);
        $numRows = count($targets);

        // Build Augmented Matrix: [A | b]
        // We transpose to work with rows [x1_coeff, x2_coeff, ... | target]
        $matrix = [];
        for ($r = 0; $r < $numRows; $r++) {
            $row = [];
            for ($c = 0; $c < $numVars; $c++) {
                $row[] = $columns[$c][$r];
            }
            $row[] = (float)$targets[$r]; // The augmented column
            $matrix[] = $row;
        }

        // Gaussian Elimination to get Row Echelon Form
        $pivotRow = 0;
        $pivotCols = array_fill(0, $numVars, -1); // Maps Variable Index -> Row Index

        for ($c = 0; $c < $numVars && $pivotRow < $numRows; $c++) {
            // Find pivot
            $sel = -1;
            for ($r = $pivotRow; $r < $numRows; $r++) {
                if (abs($matrix[$r][$c]) > 1e-9) {
                    $sel = $r;
                    break;
                }
            }

            if ($sel === -1) {
                // No pivot in this column, it's a free variable
                continue;
            }

            // Swap rows
            if ($sel !== $pivotRow) {
                $temp = $matrix[$pivotRow];
                $matrix[$pivotRow] = $matrix[$sel];
                $matrix[$sel] = $temp;
            }

            // Normalize pivot row
            $pivotVal = $matrix[$pivotRow][$c];
            for ($j = $c; $j <= $numVars; $j++) {
                $matrix[$pivotRow][$j] /= $pivotVal;
            }

            $pivotCols[$c] = $pivotRow;

            // Eliminate other rows
            for ($r = 0; $r < $numRows; $r++) {
                if ($r !== $pivotRow && abs($matrix[$r][$c]) > 1e-9) {
                    $factor = $matrix[$r][$c];
                    for ($j = $c; $j <= $numVars; $j++) {
                        $matrix[$r][$j] -= $factor * $matrix[$pivotRow][$j];
                    }
                }
            }

            $pivotRow++;
        }

        // Check for consistency (0 = NonZero)
        for ($r = $pivotRow; $r < $numRows; $r++) {
            if (abs($matrix[$r][$numVars]) > 1e-9) {
                return null; // System inconsistent
            }
        }

        // Identify Free Variables and Dependent Variables
        $freeVars = [];
        $dependentVars = []; // Maps var index => row index

        for ($c = 0; $c < $numVars; $c++) {
            if ($pivotCols[$c] !== -1) {
                $dependentVars[$c] = $pivotCols[$c];
            } else {
                $freeVars[] = $c;
            }
        }

        // We need to minimize sum(x).
        // Since numVars is usually small in this problem (e.g. < 10), and freeVars even smaller,
        // we can search the space of free variables.

        // HOWEVER, "Day 10" implies Part 2 might have huge targets.
        // If targets are huge, we cannot iterate 0..Target.
        // But usually, the system is fully determined or close to it.
        // If the system is fully determined (no free vars), we just check the solution.

        $bestSum = null;

        // Recursively search free variables.
        // Since we want to MINIMIZE sum, we should try small values for free variables?
        // But free variables might have negative coefficients in the equations for dependent vars,
        // meaning increasing a free var might INCREASE a dependent var required to stay non-negative.

        // Let's implement a recursive solver for free variables.
        // To prevent infinite search, we need bounds.
        // Dependent_i = Constant_i - Sum( Coeff_ij * Free_j )
        // Dependent_i >= 0 => Sum( Coeff_ij * Free_j ) <= Constant_i

        // This is a small Integer Programming problem.
        // Given AoC constraints, the number of free variables is likely 0, 1, or 2.

        $this->searchFreeVars($freeVars, 0, [], $matrix, $dependentVars, $numVars, $bestSum);

        return $bestSum;
    }

    private function searchFreeVars(
        array $freeVars,
        int $idx,
        array $currentFreeValues,
        array $matrix,
        array $dependentVars,
        int $numVars,
        ?int &$bestSum
    ): void {
        if ($idx >= count($freeVars)) {
            // All free vars assigned. Calculate dependent vars.
            $currentSum = 0;

            // Sum of free vars
            foreach ($currentFreeValues as $val) {
                $currentSum += $val;
            }

            // Calculate dependent vars
            foreach ($dependentVars as $depIdx => $rowIdx) {
                // x_dep = b - sum(coeff * x_free)
                // Note: The matrix is in RREF. The coeff of x_dep is 1.
                // The equation is: x_dep + sum(matrix[row][free] * x_free) = matrix[row][augmented]

                $val = $matrix[$rowIdx][$numVars]; // The constant

                foreach ($freeVars as $fIdx => $fVarIdx) {
                    $coeff = $matrix[$rowIdx][$fVarIdx];
                    $val -= $coeff * $currentFreeValues[$fIdx];
                }

                // Check integer and non-negative
                // Use epsilon for float logic
                if ($val < -1e-9) return; // Negative
                if (abs($val - round($val)) > 1e-9) return; // Not integer

                $currentSum += (int)round($val);
            }

            if ($bestSum === null || $currentSum < $bestSum) {
                $bestSum = $currentSum;
            }
            return;
        }

        // We need to pick a value for freeVars[$idx].
        // What is a reasonable range?
        // This is the tricky part. If targets are huge, we can't iterate blindly.
        // BUT, usually in these button puzzles, free variables are rare or small.
        // If there are free variables, usually the solution space is small or infinite.
        // If we want MIN press, and all coeffs are positive, 0 is best.
        // If some coeffs are negative, we might need to increase free var to satisfy others.

        // Heuristic: Iterate 0 to a reasonable limit.
        // If the system is underconstrained with huge targets, this is hard.
        // But typically, free variables are merely "alternative paths" of small length.
        // Let's try 0 to 100. If the user's input requires a free variable to be 100000,
        // this will fail, but that's extremely unlikely for a "min presses" puzzle
        // unless the button is a "shortcut" for a massive number of other presses.

        // Optimizing the range:
        // Look at constraints imposed by dependent variables.
        // For each dependent row: x_dep = B - (C * x_free + ...).
        // If C > 0, x_free is bounded by B/C.
        // If C < 0, x_free is lower-bounded.

        $fVarIdx = $freeVars[$idx];
        $minVal = 0;
        $maxVal = PHP_INT_MAX; // Practically infinite, or limited by optimization

        // Calculate tighter bounds based on currently assigned free vars + this one
        foreach ($dependentVars as $depIdx => $rowIdx) {
            $coeff = $matrix[$rowIdx][$fVarIdx];
            $b = $matrix[$rowIdx][$numVars];

            // Adjust b for already assigned free vars
            for ($k = 0; $k < $idx; $k++) {
                $prevF = $freeVars[$k];
                $prevCoeff = $matrix[$rowIdx][$prevF];
                $b -= $prevCoeff * $currentFreeValues[$k];
            }

            // Eq: x_dep = b - coeff * x_current
            // x_dep >= 0 => b - coeff * x_current >= 0

            if (abs($coeff) > 1e-9) {
                if ($coeff > 0) {
                    // b >= coeff * x => x <= b / coeff
                    $localMax = floor(($b + 1e-9) / $coeff);
                    if ($localMax < $maxVal) $maxVal = (int)$localMax;
                } else {
                    // coeff is negative.
                    // b - coeff * x >= 0 => b >= coeff * x (coeff is neg, so this flips)
                    // b / coeff <= x  (divide by neg flips inequality)
                    $localMin = ceil(($b - 1e-9) / $coeff);
                    if ($localMin > $minVal) $minVal = (int)$localMin;
                }
            }
        }

        // If range is invalid, backtrack
        if ($minVal > $maxVal) return;

        // Iterate
        // If range is massive, this might hang. But linear dependencies usually bound tight.
        // If maxVal is still PHP_INT_MAX, cap it?
        // In "fewest presses", unbounded variables usually mean infinite solutions or 0 is best.
        if ($maxVal === PHP_INT_MAX) $maxVal = $minVal + 1000;

        for ($v = $minVal; $v <= $maxVal; $v++) {
            // Pruning: if we already have a partial sum exceeding best, stop?
            // Hard to do because dependent vars might decrease total sum (if coeffs > 1).
            // But x_dep = b - coeff*x. Total = x + b - coeff*x = b + (1-coeff)x.
            // If (1-coeff) is positive, we want small x. If negative, large x.
            // We'll just iterate.

            $nextFree = $currentFreeValues;
            $nextFree[] = $v;
            $this->searchFreeVars($freeVars, $idx + 1, $nextFree, $matrix, $dependentVars, $numVars, $bestSum);
        }
    }
}