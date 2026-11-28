<?php

declare(strict_types=1);

namespace App\Exercise;

use InvalidArgumentException;

/**
 * Etayage progressif : au palier 1 toutes les questions sont posees,
 * au palier 2 la premiere etape est a trouver seul,
 * au palier 3 seules les deux conclusions sont demandees.
 *
 * Les etapes masquees restent disponibles pour la correction expliquee.
 */
final class ExerciseScaffolder
{
    /**
     * @param list<array<string, mixed>> $allSteps
     * @return list<array<string, mixed>>
     */
    public function select(array $allSteps, int $tier): array
    {
        if (count($allSteps) !== 4
            || array_sum(array_map(static fn (array $step): int => (int) $step['weight'], $allSteps)) !== 5) {
            throw new InvalidArgumentException('Exercice incompatible avec les paliers de difficulté.');
        }

        $tier = max(1, min(3, $tier));
        if ($tier === 1) {
            return $allSteps;
        }

        $steps = array_slice($allSteps, $tier === 2 ? -3 : -2);
        $weights = $tier === 2 ? [1, 3, 1] : [4, 1];

        foreach ($steps as $index => &$step) {
            $step['weight'] = $weights[$index];
        }
        unset($step);

        return $steps;
    }
}
