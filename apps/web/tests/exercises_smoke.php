<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Exercise\ExerciseLibrary;
use App\Exercise\ExerciseScorer;
use App\Exercise\ExerciseToken;

$library = new ExerciseLibrary();
$scorer = new ExerciseScorer();
$tokens = new ExerciseToken('test-secret-only');

$cases = 0;
foreach ($library->all() as $slug => $metadata) {
    for ($i = 0; $i < 100; ++$i) {
        $params = $library->generateParameters($slug);
        $exercise = $library->build($slug, $params);
        $steps = $exercise['steps'];

        $token = $tokens->issue($slug, $metadata['version'], $params);
        if ($tokens->verify($token, $slug, $metadata['version']) !== $params
            || $tokens->verify($token, 'other', $metadata['version']) !== null
            || $tokens->verify($token . 'a', $slug, $metadata['version']) !== null) {
            throw new RuntimeException("Jeton invalide : $slug");
        }

        $expected = array_column($steps, 'expected', 'id');
        $perfect = $scorer->grade($steps, $expected);
        $missed = $scorer->grade($steps, []);

        if ($perfect['stars'] !== 5 || $missed['stars'] !== 0
            || array_sum(array_column($steps, 'weight')) !== 5
            || count($steps) !== 4) {
            throw new RuntimeException("Notation invalide : $slug");
        }
        ++$cases;
    }
}


// Verification de chaque methode de suite : pas seulement la variation des chiffres.
$rules = ['a', 'b', 'c', 'd'];
foreach ($rules as $mode => $rule) {
    $params = ['mode' => $mode, 'first' => $mode === 3 ? 85 : 12, 'gap' => 5, 'drop' => 2];
    $exercise = $library->build('suite-numerique', $params);
    if ($exercise['steps'][3]['expected'] !== $rule
        || $scorer->grade($exercise['steps'], array_column($exercise['steps'], 'expected', 'id'))['stars'] !== 5) {
        throw new RuntimeException("Methode de suite incorrecte : $mode");
    }
}

// La correction des horaires accepte la notation usuelle "9:05" ou "09h05".
$bus = $library->build('horaires-bus', [
    'hour' => 8, 'minute' => 10, 'legone' => 20, 'pause' => 10, 'legtwo' => 25,
]);
$busAnswers = array_column($bus['steps'], 'expected', 'id');
$busAnswers['arrival'] = str_replace(':', 'h', $busAnswers['arrival']);
if ($scorer->grade($bus['steps'], $busAnswers)['stars'] !== 5) {
    throw new RuntimeException('Normalisation des heures incorrecte.');
}

echo "OK : $cases exercices generes, verifies et corriges ; 4 methodes de suite validees.\n";
