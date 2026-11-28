<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Exercise\ExerciseLibrary;
use App\Exercise\ExerciseScorer;
use App\Exercise\ExerciseToken;
use App\Exercise\ProgressTracker;

$progress = new ProgressTracker();
$library = new ExerciseLibrary();
$scorer = new ExerciseScorer();
$tokens = new ExerciseToken('test-secret-only');

$state = [];
if ($progress->tier($state, 'suite-numerique') !== 1) {
    throw new RuntimeException('Palier initial incorrect.');
}
if ($progress->overview($state)['count'] !== 0) {
    throw new RuntimeException('Historique initial non vide.');
}

// Trois reussites distinctes -> progression de palier.
for ($i = 0; $i < 3; ++$i) {
    $outcome = $progress->record($state, 'suite-numerique', 5, "token-$i");
    if (!$outcome['recorded']) {
        throw new RuntimeException('Tentative unique refusee.');
    }
    $state = $outcome['state'];
}
if ($progress->tier($state, 'suite-numerique') !== 2 || $progress->overview($state)['count'] !== 3) {
    throw new RuntimeException('Progression du palier 1 vers 2 incorrecte.');
}

// Soumettre de nouveau le meme jeton ne modifie pas les statistiques.
$duplicate = $progress->record($state, 'suite-numerique', 0, 'token-1');
if ($duplicate['recorded'] || $duplicate['state'] !== $state) {
    throw new RuntimeException('Rejeu de tentative non bloque.');
}

// Nouvelle serie de trois reussites -> palier 3.
for ($i = 3; $i < 6; ++$i) {
    $state = $progress->record($state, 'suite-numerique', 4, "token-$i")['state'];
}
if ($progress->tier($state, 'suite-numerique') !== 3) {
    throw new RuntimeException('Progression du palier 2 vers 3 incorrecte.');
}

// Plusieurs echecs -> un seul palier en moins. La premiere erreur ne suffit pas.
$state = $progress->record($state, 'suite-numerique', 1, 'weak-a')['state'];
if ($progress->tier($state, 'suite-numerique') !== 3) {
    throw new RuntimeException('Palier reduit apres un seul echec.');
}
$state = $progress->record($state, 'suite-numerique', 1, 'weak-b')['state'];
$state = $progress->record($state, 'suite-numerique', 3, 'weak-c')['state'];
if ($progress->tier($state, 'suite-numerique') !== 2) {
    throw new RuntimeException('Adaptation apres difficultes incorrecte.');
}

// En cas de difficulte, proposer un autre exercice du meme domaine.
$next = $progress->recommend($state, $library->all(), 'suite-numerique');
if ($next !== 'enquete-animaux') {
    throw new RuntimeException('Recommandation de soutien incorrecte.');
}

$cases = 0;
foreach ($library->all() as $slug => $meta) {
    for ($tier = 1; $tier <= 3; ++$tier) {
        for ($i = 0; $i < 25; ++$i) {
            $parameters = $library->generateParameters($slug, $tier);
            if (($parameters['tier'] ?? null) !== $tier) {
                throw new RuntimeException("Palier absent : $slug / $tier");
            }

            $exercise = $library->build($slug, $parameters);
            $steps = $exercise['steps'];
            $answers = array_column($steps, 'expected', 'id');
            if ($scorer->grade($steps, $answers)['stars'] !== 5) {
                throw new RuntimeException("Correction invalide : $slug / $tier");
            }
            if ($scorer->grade($steps, [])['stars'] !== 0) {
                throw new RuntimeException("Correction incomplete invalide : $slug / $tier");
            }

            $token = $tokens->issue($slug, $meta['version'], $parameters);
            if ($tokens->verify($token, $slug, $meta['version']) !== $parameters) {
                throw new RuntimeException("Signature invalide : $slug / $tier");
            }
            ++$cases;
        }
    }
}

echo "OK : $cases exercices verifies aux trois paliers ; progression et anti-rejeu valides.\n";
