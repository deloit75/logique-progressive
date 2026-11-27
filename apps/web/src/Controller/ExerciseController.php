<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exercise\ExerciseLibrary;
use App\Exercise\ExerciseScorer;
use App\Exercise\ExerciseToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExerciseController extends AbstractController
{
    #[Route('/exercices', name: 'exercise_catalog', methods: ['GET'])]
    public function catalog(ExerciseLibrary $library): Response
    {
        return $this->render('exercise/catalog.html.twig', [
            'exercises' => $library->all(),
        ]);
    }

    #[Route('/exercices/atelier/{slug}', name: 'exercise_play', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET', 'POST'])]
    public function play(
        string $slug,
        Request $request,
        ExerciseLibrary $library,
        ExerciseToken $tokens,
        ExerciseScorer $scorer,
    ): Response {
        $metadata = $library->find($slug);
        if ($metadata === null) {
            throw $this->createNotFoundException('Exercice inconnu.');
        }

        $error = null;
        $results = null;
        $token = '';
        $params = null;

        if ($request->isMethod('POST')) {
            $post = $request->request->all();
            $candidate = $post['token'] ?? null;
            $token = is_string($candidate) ? $candidate : '';
            $params = $tokens->verify($token, $slug, (int) $metadata['version']);

            if ($params === null) {
                $error = 'Cet exercice a expiré ou a été modifié. Un nouveau défi est proposé.';
            }
        }

        if ($params === null) {
            $params = $library->generateParameters($slug);
            $token = $tokens->issue($slug, (int) $metadata['version'], $params);
        }

        $exercise = $library->build($slug, $params);

        if ($request->isMethod('POST') && $error === null) {
            $post = $request->request->all();
            $answers = is_array($post['answers'] ?? null) ? $post['answers'] : [];
            $results = $scorer->grade($exercise['steps'], $answers);
        }

        // Les solutions ne sont communiquees au navigateur qu'apres correction.
        $visibleSteps = array_map(static function (array $step): array {
            unset($step['expected'], $step['explanation']);
            return $step;
        }, $exercise['steps']);

        return $this->render('exercise/atelier.html.twig', [
            'slug' => $slug,
            'metadata' => $metadata,
            'story' => $exercise['story'],
            'table' => $exercise['table'],
            'steps' => $visibleSteps,
            'token' => $token,
            'results' => $results,
            'error' => $error,
        ]);
    }
}
