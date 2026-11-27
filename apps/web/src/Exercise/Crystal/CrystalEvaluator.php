<?php

declare(strict_types=1);

namespace App\Exercise\Crystal;

final class CrystalEvaluator
{
    /**
     * @param array<string, mixed> $challenge
     * @param array<string, mixed> $answers
     * @return array<string, mixed>
     */
    public function evaluate(array $challenge, array $answers): array
    {
        $beforeThird = intdiv((int) $challenge['end'], 2);
        $beforeSecond = $beforeThird + (int) $challenge['loss'];
        $start = $beforeSecond - (int) $challenge['gain'];

        $expected = [
            'third' => $beforeThird,
            'second' => $beforeSecond,
            'start' => $start,
            'order' => 'b',
        ];
        $weights = [
            'third' => 1,
            'second' => 1,
            'start' => 2,
            'order' => 1,
        ];

        $details = [];
        $stars = 0;

        foreach ($weights as $key => $weight) {
            $raw = $answers[$key] ?? '';
            $value = is_scalar($raw) ? trim((string) $raw) : '';
            $value = substr($value, 0, 40);
            $correct = $key === 'order'
                ? strtolower($value) === $expected[$key]
                : preg_match('/^[+]?[0-9]{1,9}$/D', $value) === 1
                    && (int) $value === $expected[$key];

            $points = $correct ? $weight : 0;
            $stars += $points;
            $details[$key] = [
                'correct' => $correct,
                'points' => $points,
                'max' => $weight,
            ];
        }

        return [
            'stars' => $stars,
            'details' => $details,
            'beforeThird' => $beforeThird,
            'beforeSecond' => $beforeSecond,
            'start' => $start,
        ];
    }
}
