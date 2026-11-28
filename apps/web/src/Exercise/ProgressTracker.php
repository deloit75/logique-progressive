<?php

declare(strict_types=1);

namespace App\Exercise;

/**
 * Progression anonyme et provisoire, stockee dans une session Symfony.
 * Ce suivi n'est ni un niveau scolaire certifie ni un droit a des recompenses.
 */
final class ProgressTracker
{
    public const SESSION_KEY = 'lp.progress.v1';

    private const GROUPS = [
        'suite-numerique' => 'raisonnement',
        'enquete-animaux' => 'raisonnement',
        'billets-musee' => 'calcul',
        'horaires-bus' => 'calcul',
        'gateaux-partages' => 'calcul',
        'bibliotheque' => 'donnees',
        'jardin-rectangulaire' => 'espace',
        'robot-grille' => 'espace',
    ];

    private const LABELS = [
        'raisonnement' => 'Logique et déduction',
        'calcul' => 'Calcul et problèmes',
        'donnees' => 'Lecture de données',
        'espace' => 'Géométrie et repérage',
    ];

    public function tier(array $state, string $slug): int
    {
        $group = self::GROUPS[$slug] ?? null;
        if ($group === null) {
            return 1;
        }

        return max(1, min(3, (int) ($state['groups'][$group]['tier'] ?? 1)));
    }

    /**
     * Une instance signee ne peut contribuer qu'une fois aux statistiques
     * de cette session, meme si le formulaire est renvoye.
     *
     * @return array{state: array<string, mixed>, recorded: bool, tierChanged: bool}
     */
    public function record(array $state, string $slug, int $stars, string $signedToken): array
    {
        $group = self::GROUPS[$slug] ?? null;
        if ($group === null || $stars < 0 || $stars > 5) {
            return ['state' => $state, 'recorded' => false, 'tierChanged' => false];
        }

        $id = hash('sha256', $signedToken);
        $seen = $state['seen'] ?? [];
        if (!is_array($seen) || isset($seen[$id]) || count($seen) >= 500) {
            return ['state' => $state, 'recorded' => false, 'tierChanged' => false];
        }

        $seen[$id] = true;
        $state['seen'] = $seen;
        $state['count'] = (int) ($state['count'] ?? 0) + 1;
        $state['byModel'][$slug] = (int) ($state['byModel'][$slug] ?? 0) + 1;

        $groupState = $state['groups'][$group] ?? [];
        $groupState['count'] = (int) ($groupState['count'] ?? 0) + 1;
        $groupState['best'] = max($stars, (int) ($groupState['best'] ?? 0));
        $groupState['tier'] = max(1, min(3, (int) ($groupState['tier'] ?? 1)));
        $recent = $groupState['recent'] ?? [];
        if (!is_array($recent)) {
            $recent = [];
        }
        $recent[] = $stars;
        $recent = array_slice($recent, -3);

        $changed = false;
        if (count($recent) === 3 && min($recent) >= 4 && $groupState['tier'] < 3) {
            ++$groupState['tier'];
            $recent = [];
            $changed = true;
        } elseif (count($recent) === 3 && count(array_filter(
            $recent,
            static fn (int $value): bool => $value <= 2,
        )) >= 2 && $groupState['tier'] > 1) {
            --$groupState['tier'];
            $recent = [];
            $changed = true;
        }

        $groupState['recent'] = $recent;
        $state['groups'][$group] = $groupState;
        $state['last'] = ['slug' => $slug, 'stars' => $stars, 'group' => $group];

        return ['state' => $state, 'recorded' => true, 'tierChanged' => $changed];
    }

    /**
     * @return array{count: int, groups: list<array<string, int|string>>}
     */
    public function overview(array $state): array
    {
        $groups = [];
        foreach (self::LABELS as $code => $label) {
            $data = $state['groups'][$code] ?? [];
            $groups[] = [
                'code' => $code,
                'label' => $label,
                'count' => (int) ($data['count'] ?? 0),
                'tier' => max(1, min(3, (int) ($data['tier'] ?? 1))),
                'best' => (int) ($data['best'] ?? 0),
            ];
        }

        return ['count' => (int) ($state['count'] ?? 0), 'groups' => $groups];
    }

    /**
     * Propose d'abord un autre exercice dans la competence a renforcer,
     * sinon une activite encore peu pratiquee.
     *
     * @param array<string, array<string, mixed>> $catalog
     */
    public function recommend(array $state, array $catalog, ?string $currentSlug = null): ?string
    {
        $candidates = array_keys($catalog);
        if ($currentSlug !== null) {
            $candidates = array_values(array_filter(
                $candidates,
                static fn (string $slug): bool => $slug !== $currentSlug,
            ));
        }
        if ($candidates === []) {
            return null;
        }

        $last = $state['last'] ?? null;
        $weakGroup = is_array($last) && ($last['stars'] ?? 5) <= 2
            ? ($last['group'] ?? null) : null;

        if (is_string($weakGroup)) {
            $sameGroup = array_values(array_filter(
                $candidates,
                static fn (string $slug): bool => (self::GROUPS[$slug] ?? null) === $weakGroup,
            ));
            if ($sameGroup !== []) {
                $candidates = $sameGroup;
            }
        }

        usort($candidates, static function (string $a, string $b) use ($state): int {
            $aGroup = self::GROUPS[$a] ?? '';
            $bGroup = self::GROUPS[$b] ?? '';
            $aGroupCount = (int) ($state['groups'][$aGroup]['count'] ?? 0);
            $bGroupCount = (int) ($state['groups'][$bGroup]['count'] ?? 0);
            $aCount = (int) ($state['byModel'][$a] ?? 0);
            $bCount = (int) ($state['byModel'][$b] ?? 0);

            return [$aGroupCount, $aCount, $a] <=> [$bGroupCount, $bCount, $b];
        });

        return $candidates[0];
    }
}
