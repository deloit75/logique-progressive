<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exercise\ExerciseLibrary;
use App\Exercise\ExerciseScorer;
use App\Exercise\ExerciseToken;
use App\Exercise\ProgressTracker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExerciseController extends AbstractController
{
    #[Route('/exercices', name: 'exercise_catalog', methods: ['GET'])]
    public function catalog(
        Request $request,
        ExerciseLibrary $library,
        ProgressTracker $progress,
    ): Response {
        $requested = $request->query->all()['niveau'] ?? 'tous';
        $level = is_string($requested) && in_array($requested, ['cm2', '6e'], true)
            ? $requested
            : 'tous';

        $all = $library->all();
        $exercises = $all;
        if ($level !== 'tous') {
            $target = $level === 'cm2' ? 'CM2' : '6e';
            $exercises = array_filter(
                $exercises,
                static fn (array $exercise): bool => str_contains((string) $exercise['level'], $target),
            );
        }

        $stored = $request->getSession()->get(ProgressTracker::SESSION_KEY, []);
        $state = is_array($stored) ? $stored : [];
        $suggestedSlug = $progress->recommend($state, $all);

        return $this->render('exercise/catalog.html.twig', [
            'exercises' => $exercises,
            'selectedLevel' => $level,
            'progress' => $progress->overview($state),
            'suggestedSlug' => $suggestedSlug,
            'suggested' => $suggestedSlug === null ? null : $all[$suggestedSlug],
        ]);
    }

    #[Route('/progression', name: 'exercise_progress', methods: ['GET'])]
    public function progress(
        Request $request,
        ExerciseLibrary $library,
        ProgressTracker $tracker,
    ): Response {
        $stored = $request->getSession()->get(ProgressTracker::SESSION_KEY, []);
        $state = is_array($stored) ? $stored : [];
        $all = $library->all();
        $suggestedSlug = $tracker->recommend($state, $all);

        return $this->render('exercise/progress.html.twig', [
            'progress' => $tracker->overview($state),
            'suggestedSlug' => $suggestedSlug,
            'suggested' => $suggestedSlug === null ? null : $all[$suggestedSlug],
        ]);
    }

    #[Route('/exercices/atelier/{slug}', name: 'exercise_play', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET', 'POST'])]
    public function play(
        string $slug,
        Request $request,
        ExerciseLibrary $library,
        ExerciseToken $tokens,
        ExerciseScorer $scorer,
        ProgressTracker $progress,
    ): Response {
        $metadata = $library->find($slug);
        if ($metadata === null) {
            throw $this->createNotFoundException('Exercice inconnu.');
        }

        $session = $request->getSession();
        $stored = $session->get(ProgressTracker::SESSION_KEY, []);
        $state = is_array($stored) ? $stored : [];

        $error = null;
        $results = null;
        $progressMessage = null;
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
            $tier = $progress->tier($state, $slug);
            $params = $library->generateParameters($slug, $tier);
            $token = $tokens->issue($slug, (int) $metadata['version'], $params);
        }

        $exercise = $library->build($slug, $params);

        if ($request->isMethod('POST') && $error === null) {
            $post = $request->request->all();
            $answers = is_array($post['answers'] ?? null) ? $post['answers'] : [];
            $results = $scorer->grade($exercise['steps'], $answers);

            $record = $progress->record($state, $slug, $results['stars'], $token);
            if ($record['recorded']) {
                $state = $record['state'];
                $session->set(ProgressTracker::SESSION_KEY, $state);
                $progressMessage = $record['tierChanged']
                    ? 'Ton palier évolue pour les prochains exercices de cette compétence !'
                    : 'Cette tentative est prise en compte dans ta progression.';
            } else {
                $progressMessage = 'Cette tentative a déjà été comptabilisée pour ta progression.';
            }
        }

        // Les solutions ne sont communiquees au navigateur qu'apres correction.
        $visibleSteps = array_map(static function (array $step): array {
            unset($step['expected'], $step['explanation']);
            return $step;
        }, $exercise['steps']);

        $all = $library->all();
        $nextSlug = $results === null ? null : $progress->recommend($state, $all, $slug);

        return $this->render('exercise/atelier.html.twig', [
            'slug' => $slug,
            'metadata' => $metadata,
            'story' => $exercise['story'],
            'table' => $exercise['table'],
            'steps' => $visibleSteps,
            'token' => $token,
            'results' => $results,
            'error' => $error,
            'tier' => (int) ($params['tier'] ?? 1),
            'progressMessage' => $progressMessage,
            'nextSlug' => $nextSlug,
            'next' => $nextSlug === null ? null : $all[$nextSlug],
        ]);
    }
}
