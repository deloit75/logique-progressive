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
                'time' => $this->normalizeTime($answer) === (string) $step['expected'],
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

    /** Accepte 9:05, 09:05 et 09h05 pour une meme heure. */
    private function normalizeTime(string $answer): ?string
    {
        if (!preg_match('/^([01]?[0-9]|2[0-3])[:hH]([0-5][0-9])$/D', $answer, $matches)) {
            return null;
        }

        return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
    }

    private function normalize(string $text): string
    {
        return strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));
    }
}
