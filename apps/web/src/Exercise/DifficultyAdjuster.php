<?php

declare(strict_types=1);

namespace App\Exercise;

/**
 * Trois paliers de complexite numerique. Les regles pedagogiques
 * restent identiques, sauf pour la suite dont la methode varie aussi.
 */
final class DifficultyAdjuster
{
    /** @param array<string, int|string> $parameters
     *  @return array<string, int|string>
     */
    public static function apply(string $slug, array $parameters, int $tier): array
    {
        $tier = max(1, min(3, $tier));
        $step = $tier - 1;

        if ($step > 0) {
            switch ($slug) {
                case 'suite-numerique':
                    $parameters['first'] += ($parameters['mode'] === 3 ? 5 : 3) * $step;
                    $parameters['gap'] += $step;
                    break;
                case 'billets-musee':
                    $parameters['price'] += 3 * $step;
                    $parameters['count'] += $step;
                    $parameters['discount'] += $step;
                    $parameters['change'] += 4 * $step;
                    break;
                case 'bibliotheque':
                    foreach (['lundi', 'mardi', 'mercredi'] as $day) {
                        $parameters[$day] += 18 * $step;
                    }
                    break;
                case 'jardin-rectangulaire':
                    $parameters['length'] += 6 * $step;
                    $parameters['width'] += 4 * $step;
                    $parameters['laps'] += $step;
                    break;
                case 'horaires-bus':
                    $parameters['legone'] += 10 * $step;
                    $parameters['legtwo'] += 10 * $step;
                    break;
                case 'gateaux-partages':
                    $parameters['cakes'] += 2 * $step;
                    $parameters['children'] += $step;
                    break;
                case 'robot-grille':
                    $parameters['right'] += 2 * $step;
                    $parameters['up'] += $step;
                    break;
                // La deduction animale conserve sa difficulte : nouvelle
                // variante logique a ajouter lorsque le modele sera enrichi.
            }
        }

        $parameters['tier'] = $tier;

        return $parameters;
    }
}
