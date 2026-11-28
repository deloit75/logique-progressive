<?php

declare(strict_types=1);

namespace App\Exercise;

use InvalidArgumentException;

/**
 * Nouveaux modeles autonomes du meme moteur d'etapes et de correction.
 * Aucune donnée utilisateur ni acces a la base de donnees.
 */
final class AdditionalExercises
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return [
            'jardin-rectangulaire' => [
                'title' => 'Le jardin des explorateurs',
                'level' => 'CM2',
                'subject' => 'Géométrie',
                'skill' => 'Calculer un périmètre et une distance parcourue',
                'duration' => 7,
                'version' => 1,
                'icon' => '🌳',
            ],
            'horaires-bus' => [
                'title' => 'Le bus des aventuriers',
                'level' => 'CM2–6e',
                'subject' => 'Durées et horaires',
                'skill' => 'Additionner des durées et calculer un horaire d’arrivée',
                'duration' => 8,
                'version' => 1,
                'icon' => '🚌',
            ],
            'gateaux-partages' => [
                'title' => 'Le goûter partagé',
                'level' => '6e',
                'subject' => 'Partages et fractions',
                'skill' => 'Compter des parts égales et reconnaître une fraction',
                'duration' => 7,
                'version' => 1,
                'icon' => '🍰',
            ],
            'robot-grille' => [
                'title' => 'Le robot explorateur',
                'level' => 'CM2–6e',
                'subject' => 'Repérage',
                'skill' => 'Se déplacer sur un quadrillage à partir de coordonnées',
                'duration' => 7,
                'version' => 1,
                'icon' => '🤖',
            ],
        ];
    }

    /** @return array<string, int> */
    public static function generate(string $slug): array
    {
        return match ($slug) {
            'jardin-rectangulaire' => [
                'length' => random_int(9, 18),
                'width' => random_int(4, 8),
                'laps' => random_int(2, 3),
            ],
            'horaires-bus' => [
                'hour' => random_int(8, 15),
                'minute' => random_int(0, 5) * 10,
                'legone' => random_int(4, 9) * 5,
                'pause' => random_int(2, 4) * 5,
                'legtwo' => random_int(3, 8) * 5,
            ],
            'gateaux-partages' => [
                'cakes' => random_int(3, 6),
                'children' => random_int(3, 6),
                'each' => random_int(2, 3),
            ],
            'robot-grille' => [
                'startx' => random_int(1, 3),
                'starty' => random_int(1, 4),
                'right' => random_int(3, 5),
                'up' => random_int(2, 4),
                'left' => random_int(1, 2),
                'down' => 1,
            ],
            default => throw new InvalidArgumentException('Modèle inconnu.'),
        };
    }

    /**
     * @param array<string, int|string> $parameters
     * @return array{story: string, table: null, steps: list<array<string, mixed>>}
     */
    public static function build(string $slug, array $parameters): array
    {
        return match ($slug) {
            'jardin-rectangulaire' => self::garden($parameters),
            'horaires-bus' => self::bus($parameters),
            'gateaux-partages' => self::cakes($parameters),
            'robot-grille' => self::robot($parameters),
            default => throw new InvalidArgumentException('Modèle inconnu.'),
        };
    }

    /** @param array<string, int|string> $p */
    private static function garden(array $p): array
    {
        $length = (int) $p['length'];
        $width = (int) $p['width'];
        $laps = (int) $p['laps'];
        $pair = $length + $width;
        $perimeter = 2 * $pair;
        $distance = $laps * $perimeter;

        return [
            'story' => "Un jardin rectangulaire mesure $length m de long et $width m de large. Une exploratrice veut poser une clôture tout autour, puis faire $laps tours complets en suivant le bord. Aide-la à calculer les bonnes longueurs.",
            'table' => null,
            'steps' => [
                self::number('pair', 'Quelle est la longueur d’un grand côté et d’un petit côté réunis (en m) ?', 1, $pair,
                    "$length + $width = $pair m."),
                self::number('perimeter', 'Quelle longueur de clôture faut-il pour entourer tout le jardin (en m) ?', 1, $perimeter,
                    "Un rectangle a deux longueurs et deux largeurs : 2 × ($length + $width) = $perimeter m."),
                self::number('distance', "Quelle distance parcourt-elle en $laps tours complets (en m) ?", 2, $distance,
                    "$laps × $perimeter = $distance m."),
                self::choice('method', 'Quelle formule calcule le périmètre du rectangle ?', 1, 'b', [
                    'a' => 'Longueur × largeur',
                    'b' => '2 × (longueur + largeur)',
                    'c' => 'Longueur + largeur',
                ], 'Le périmètre est la somme des longueurs des quatre côtés.'),
            ],
        ];
    }

    /** @param array<string, int|string> $p */
    private static function bus(array $p): array
    {
        $hour = (int) $p['hour'];
        $minute = (int) $p['minute'];
        $legOne = (int) $p['legone'];
        $pause = (int) $p['pause'];
        $legTwo = (int) $p['legtwo'];
        $startMinutes = $hour * 60 + $minute;
        $travel = $legOne + $legTwo;
        $total = $travel + $pause;
        $start = self::clock($startMinutes);
        $arrival = self::clock($startMinutes + $total);

        return [
            'story' => "Le bus part à $start. Il roule pendant $legOne minutes, s’arrête pendant $pause minutes, puis roule encore $legTwo minutes. Calcule les durées, puis l’heure exacte d’arrivée.",
            'table' => null,
            'steps' => [
                self::number('travel', 'Combien de minutes le bus roule-t-il, sans compter la pause ?', 1, $travel,
                    "$legOne + $legTwo = $travel minutes."),
                self::number('total', 'Combien de minutes dure le trajet, pause comprise ?', 1, $total,
                    "$travel + $pause = $total minutes."),
                self::time('arrival', 'À quelle heure arrive le bus ? (format HH:MM)', 2, $arrival,
                    "On ajoute $total minutes à $start : arrivée à $arrival."),
                self::choice('method', 'Pour trouver l’heure d’arrivée, que faut-il faire ?', 1, 'a', [
                    'a' => 'Ajouter les deux trajets et la pause à l’heure de départ',
                    'b' => 'Retirer la durée de la pause',
                    'c' => 'Ne garder que la durée du deuxième trajet',
                ], 'Le temps écoulé comprend les déplacements et l’arrêt.'),
            ],
        ];
    }

    /** @param array<string, int|string> $p */
    private static function cakes(array $p): array
    {
        $cakes = (int) $p['cakes'];
        $children = (int) $p['children'];
        $each = (int) $p['each'];
        $total = 8 * $cakes;
        $eaten = $children * $each;
        $left = $total - $eaten;

        return [
            'story' => "Pour un goûter, on partage $cakes gâteaux identiques en 8 parts égales chacun. $children enfants mangent $each parts chacun. On ne jette aucune part : combien reste-t-il de morceaux ?",
            'table' => null,
            'steps' => [
                self::number('total', 'Combien de parts a-t-on préparées en tout ?', 1, $total,
                    "$cakes × 8 = $total parts."),
                self::number('eaten', 'Combien de parts sont mangées au total ?', 1, $eaten,
                    "$children × $each = $eaten parts."),
                self::number('left', 'Combien de parts restent après le goûter ?', 2, $left,
                    "$total − $eaten = $left parts."),
                self::choice('fraction', 'Dans un gâteau partagé en 8 parts égales, quelle fraction représente 2 parts ?', 1, 'b', [
                    'a' => '1/2',
                    'b' => '1/4',
                    'c' => '1/8',
                ], 'Deux huitièmes représentent un quart : 2/8 = 1/4.'),
            ],
        ];
    }

    /** @param array<string, int|string> $p */
    private static function robot(array $p): array
    {
        $startX = (int) $p['startx'];
        $startY = (int) $p['starty'];
        $right = (int) $p['right'];
        $up = (int) $p['up'];
        $left = (int) $p['left'];
        $down = (int) $p['down'];
        $afterRight = $startX + $right;
        $afterUp = $startY + $up;
        $finalX = $afterRight - $left;
        $finalY = $afterUp - $down;

        return [
            'story' => "Sur un quadrillage, le robot démarre en ($startX ; $startY). Il avance de $right cases vers la droite, de $up vers le haut, de $left vers la gauche et enfin de $down vers le bas. Vers la droite, le premier nombre (x) augmente ; vers le haut, le second nombre (y) augmente.",
            'table' => null,
            'steps' => [
                self::number('afterright', 'Quel est le premier nombre x après le déplacement vers la droite ?', 1, $afterRight,
                    "$startX + $right = $afterRight."),
                self::number('afterup', 'Quel est le second nombre y après le déplacement vers le haut ?', 1, $afterUp,
                    "$startY + $up = $afterUp."),
                self::number('finalx', 'Quel est le premier nombre x de sa position finale ?', 2, $finalX,
                    "$startX + $right − $left = $finalX."),
                self::number('finaly', 'Quel est le second nombre y de sa position finale ?', 1, $finalY,
                    "$startY + $up − $down = $finalY."),
            ],
        ];
    }

    private static function clock(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /** @return array<string, mixed> */
    private static function number(string $id, string $question, int $weight, int $expected, string $explanation): array
    {
        return compact('id', 'question', 'weight', 'expected', 'explanation') + [
            'type' => 'number',
            'choices' => [],
        ];
    }

    /** @return array<string, mixed> */
    private static function text(string $id, string $question, int $weight, string $expected, string $explanation): array
    {
        return compact('id', 'question', 'weight', 'expected', 'explanation') + [
            'type' => 'text',
            'choices' => [],
        ];
    }

    /** @return array<string, mixed> */
    private static function time(string $id, string $question, int $weight, string $expected, string $explanation): array
    {
        return compact('id', 'question', 'weight', 'expected', 'explanation') + [
            'type' => 'time',
            'choices' => [],
        ];
    }

    /** @param array<string, string> $choices
     * @return array<string, mixed>
     */
    private static function choice(string $id, string $question, int $weight, string $expected, array $choices, string $explanation): array
    {
        return compact('id', 'question', 'weight', 'expected', 'choices', 'explanation') + [
            'type' => 'choice',
        ];
    }
}
