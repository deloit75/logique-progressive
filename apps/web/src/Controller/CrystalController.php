<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exercise\Crystal\CrystalEvaluator;
use App\Exercise\Crystal\CrystalGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CrystalController extends AbstractController
{
    #[Route('/exercices/cristaux', name: 'exercise_crystals', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CrystalGenerator $generator,
        CrystalEvaluator $evaluator,
    ): Response {
        $answers = [];
        $result = null;
        $error = null;
        $challenge = null;

        if ($request->isMethod('POST')) {
            $postedChallenge = $request->request->all('challenge');

            if ($generator->isValid($postedChallenge)) {
                $challenge = $postedChallenge;
                $answers = $request->request->all('answers');
                $result = $evaluator->evaluate($challenge, $answers);
            } else {
                $error = 'Cet exercice a expiré ou ses données ont changé. Voici un nouveau défi.';
            }
        }

        return $this->render('exercise/crystals.html.twig', [
            'challenge' => $challenge ?? $generator->generate(),
            'answers' => $answers,
            'result' => $result,
            'error' => $error,
        ]);
    }
}
