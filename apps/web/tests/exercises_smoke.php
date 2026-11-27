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

echo "OK : $cases exercices generes, verifies et corriges.\n";
