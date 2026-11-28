<?php

declare(strict_types=1);

namespace App\Exercise;

/**
 * Un meme theme peut demander des raisonnements vraiment differents.
 * Les quatre regles sont choisies aleatoirement, et pas seulement les nombres.
 */
final class SequenceVariants
{
    /** @return array<string, int> */
    public static function generate(): array
    {
        $mode = random_int(0, 3);

        return [
            'mode' => $mode,
            'first' => match ($mode) {
                0 => random_int(3, 18),
                1 => random_int(2, 7),
                2 => random_int(8, 24),
                3 => random_int(65, 90),
            },
            'gap' => match ($mode) {
                1 => 2,
                2 => random_int(4, 9),
                default => random_int(3, 8),
            },
            'drop' => $mode === 2 ? random_int(1, 3) : 0,
        ];
    }

    /**
     * @param array<string, int|string> $p
     * @return array{story: string, table: null, steps: list<array<string, mixed>>}
     */
    public static function build(array $p): array
    {
        $mode = (int) $p['mode'];
        $gap = (int) $p['gap'];
        $drop = (int) $p['drop'];
        $numbers = [(int) $p['first']];

        for ($i = 1; $i <= 5; ++$i) {
            $previous = $numbers[$i - 1];
            $numbers[] = match ($mode) {
                0 => $previous + $gap,
                1 => $previous * 2,
                2 => $i % 2 === 1 ? $previous + $gap : $previous - $drop,
                3 => $previous - $gap,
            };
        }

        $shown = $mode === 2 ? 4 : 3;
        $sequence = implode(', ', array_slice($numbers, 0, $shown)) . ', ...';
        $rule = match ($mode) {
            0 => 'a',
            1 => 'b',
            2 => 'c',
            3 => 'd',
        };
        $explanation = match ($mode) {
            0 => "On ajoute $gap à chaque fois : les écarts sont constants.",
            1 => 'Chaque nombre est le double du précédent.',
            2 => "On alterne +$gap puis −$drop, et l’on recommence.",
            3 => "On retranche $gap à chaque fois : les écarts sont constants.",
        };
        $choices = [
            'a' => 'Ajouter toujours la même quantité',
            'b' => 'Multiplier chaque nombre par deux',
            'c' => 'Alterner une addition et une soustraction',
            'd' => 'Retrancher toujours la même quantité',
        ];

        if ($mode === 2) {
            $steps = [
                self::number('operation1', 'Combien ajoute-t-on pour passer du 1er au 2e nombre ?', 1, $gap,
                    "{$numbers[1]} − {$numbers[0]} = $gap."),
                self::number('operation2', 'Combien retranche-t-on pour passer du 2e au 3e nombre ?', 1, $drop,
                    "{$numbers[1]} − {$numbers[2]} = $drop."),
                self::number('sixth', 'Quel est le sixième nombre ?', 2, $numbers[5],
                    "Après {$numbers[3]}, on retranche $drop pour trouver {$numbers[4]}, puis on ajoute $gap pour obtenir {$numbers[5]}."),
            ];
        } else {
            $question = match ($mode) {
                0 => 'De combien augmente-t-on à chaque étape ?',
                1 => 'Par combien multiplie-t-on à chaque étape ?',
                3 => 'De combien diminue-t-on à chaque étape ?',
            };
            $amount = $mode === 1 ? 2 : $gap;
            $steps = [
                self::number('operation1', $question, 1, $amount,
                    $explanation),
                self::number('fourth', 'Quel est le quatrième nombre ?', 1, $numbers[3],
                    "Après {$numbers[2]}, la règle donne {$numbers[3]}. $explanation"),
                self::number('sixth', 'Quel est le sixième nombre ?', 2, $numbers[5],
                    "On continue la même règle : {$numbers[3]}, {$numbers[4]}, {$numbers[5]}."),
            ];
        }

        $steps[] = [
            'id' => 'rule',
            'question' => 'Quelle règle correspond à cette suite ?',
            'weight' => 1,
            'expected' => $rule,
            'explanation' => $explanation,
            'type' => 'choice',
            'choices' => $choices,
        ];

        return [
            'story' => "Une machine affiche la suite : $sequence Retrouve la règle cachée, puis déduis les nombres suivants. Attention : la règle peut changer d’un défi à l’autre !",
            'table' => null,
            'steps' => $steps,
        ];
    }

    /** @return array<string, mixed> */
    private static function number(string $id, string $question, int $weight, int $expected, string $explanation): array
    {
        return compact('id', 'question', 'weight', 'expected', 'explanation') + [
            'type' => 'number',
            'choices' => [],
        ];
    }
}
