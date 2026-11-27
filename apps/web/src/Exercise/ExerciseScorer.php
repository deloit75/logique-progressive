<?php

declare(strict_types=1);

namespace App\Exercise;

final class ExerciseScorer
{
    /**
     * @param list<array<string, mixed>> $steps
     * @param array<string, mixed> $submitted
     * @return array{stars: int, steps: array<string, array<string, mixed>>}
     */
    public function grade(array $steps, array $submitted): array
    {
        $stars = 0;
        $details = [];

        foreach ($steps as $step) {
            $raw = $submitted[$step['id']] ?? '';
            $answer = is_scalar($raw) ? trim((string) $raw) : '';
            $answer = substr($answer, 0, 60);

            $correct = match ($step['type']) {
                'number' => preg_match('/^[+]?[0-9]{1,9}$/D', $answer) === 1
                    && (int) $answer === $step['expected'],
                'text' => $this->normalize($answer) === $this->normalize((string) $step['expected']),
                'choice' => $answer === $step['expected'],
                default => false,
            };

            $points = $correct ? (int) $step['weight'] : 0;
            $stars += $points;
            $details[$step['id']] = [
                'correct' => $correct,
                'points' => $points,
                'max' => $step['weight'],
                'expected' => $step['type'] === 'choice'
                    ? ($step['choices'][$step['expected']] ?? '')
                    : (string) $step['expected'],
                'explanation' => $step['explanation'],
            ];
        }

        return ['stars' => $stars, 'steps' => $details];
    }

    private function normalize(string $text): string
    {
        return strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));
    }
}
