<?php

declare(strict_types=1);

namespace App\Exercise;

/**
 * Catalogue pedagogique : un modele stable, plusieurs instances aleatoires.
 * Les reponses attendues restent cote serveur.
 */
final class ExerciseLibrary
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return [
            'suite-numerique' => [
                'title' => 'La suite mystérieuse',
                'level' => 'CM2',
                'subject' => 'Suites et régularités',
                'skill' => 'Identifier et prolonger une règle numérique',
                'duration' => 6,
                'version' => 2,
                'icon' => '🔢',
            ],
            'billets-musee' => [
                'title' => 'La sortie au musée',
                'level' => 'CM2–6e',
                'subject' => 'Problèmes à étapes',
                'skill' => 'Enchaîner des calculs et contrôler le résultat',
                'duration' => 8,
                'version' => 1,
                'icon' => '🎟️',
            ],
            'enquete-animaux' => [
                'title' => 'L’enquête des animaux',
                'level' => '6e',
                'subject' => 'Déduction logique',
                'skill' => 'Éliminer les hypothèses incompatibles',
                'duration' => 7,
                'version' => 1,
                'icon' => '🔎',
            ],
            'bibliotheque' => [
                'title' => 'Les chiffres de la bibliothèque',
                'level' => 'CM2–6e',
                'subject' => 'Lecture de données',
                'skill' => 'Lire, comparer et exploiter un tableau',
                'duration' => 8,
                'version' => 1,
                'icon' => '📚',
            ],
        ] + AdditionalExercises::all();
    }

    /** @return array<string, mixed>|null */
    public function find(string $slug): ?array
    {
        return $this->all()[$slug] ?? null;
    }

    /** @return array<string, int|string> */
    public function generateParameters(string $slug): array
    {
        return match ($slug) {
            'suite-numerique' => SequenceVariants::generate(),
            'billets-musee' => [
                'price' => random_int(5, 10),
                'count' => random_int(3, 7),
                'discount' => random_int(2, 6),
                'change' => random_int(3, 15),
            ],
            'enquete-animaux' => $this->generateAnimalParameters(),
            'bibliotheque' => $this->generateLibraryParameters(),
            default => AdditionalExercises::generate($slug),
        };
    }

    /**
     * @param array<string, mixed> $params
     * @return array{story: string, table: array<string, int>|null, steps: list<array<string, mixed>>}
     */
    public function build(string $slug, array $params): array
    {
        return match ($slug) {
            'suite-numerique' => SequenceVariants::build($params),
            'billets-musee' => $this->buildMuseum($params),
            'enquete-animaux' => $this->buildAnimals($params),
            'bibliotheque' => $this->buildLibrary($params),
            default => AdditionalExercises::build($slug, $params),
        };
    }

    /** @return array<string, string> */
    private function generateAnimalParameters(): array
    {
        $names = ['Lina', 'Malo', 'Sara', 'Nora', 'Sami', 'Tom'];
        shuffle($names);

        return ['rabbit' => $names[0], 'cat' => $names[1], 'dog' => $names[2]];
    }

    /** @return array<string, int> */
    private function generateLibraryParameters(): array
    {
        $numbers = [
            random_int(8, 15),
            random_int(19, 25),
            random_int(29, 38),
        ];
        shuffle($numbers);

        return ['lundi' => $numbers[0], 'mardi' => $numbers[1], 'mercredi' => $numbers[2]];
    }

    /** @param array<string, mixed> $p */
    private function buildMuseum(array $p): array
    {
        $price = (int) $p['price'];
        $count = (int) $p['count'];
        $discount = (int) $p['discount'];
        $change = (int) $p['change'];
        $subtotal = $price * $count;
        $total = $subtotal - $discount;
        $cash = $total + $change;

        return [
            'story' => "Une classe achète $count billets de musée à $price € chacun. Le groupe bénéficie d’une réduction totale de $discount €. La personne qui paie remet $cash €. Retrouve les calculs dans le bon ordre.",
            'table' => null,
            'steps' => [
                $this->number('subtotal', 'Quel est le prix des billets avant la réduction (en €) ?', 1, $subtotal,
                    "$count × $price = $subtotal €."),
                $this->number('total', 'Combien faut-il payer après la réduction (en €) ?', 1, $total,
                    "$subtotal − $discount = $total €."),
                $this->number('change', 'Quelle monnaie doit-on rendre (en €) ?', 2, $change,
                    "$cash − $total = $change €."),
                $this->choice('method', 'Quel ordre de calcul est correct ?', 1, 'b', [
                    'a' => 'Soustraire la réduction à chaque billet, puis multiplier',
                    'b' => 'Multiplier, soustraire la réduction totale, puis calculer la monnaie',
                    'c' => 'Calculer la monnaie avant le prix total',
                ], 'La réduction porte sur le groupe entier, pas sur chaque billet.'),
            ],
        ];
    }

    /** @param array<string, mixed> $p */
    private function buildAnimals(array $p): array
    {
        $rabbit = (string) $p['rabbit'];
        $cat = (string) $p['cat'];
        $dog = (string) $p['dog'];

        return [
            'story' => "$rabbit, $cat et $dog possèdent chacun un animal différent : un lapin, un chat et un chien. On sait que $rabbit a le lapin et que $cat n’a pas le chien. Chaque personne n’a qu’un seul animal. À toi de déduire qui possède quoi.",
            'table' => null,
            'steps' => [
                $this->text('rabbit', 'Qui possède le lapin ? (prénom)', 1, $rabbit,
                    "L’énoncé indique directement que $rabbit a le lapin."),
                $this->text('cat', 'Qui possède le chat ? (prénom)', 1, $cat,
                    "$cat n’a ni le lapin (déjà attribué), ni le chien : c’est donc le chat."),
                $this->text('dog', 'Qui possède le chien ? (prénom)', 2, $dog,
                    "Le lapin et le chat sont attribués. Il reste le chien pour $dog."),
                $this->choice('method', 'Quelle méthode permet de conclure ?', 1, 'c', [
                    'a' => 'Choisir au hasard une réponse plausible',
                    'b' => 'Additionner les noms des animaux',
                    'c' => 'Éliminer les possibilités incompatibles',
                ], 'On utilise les informations disponibles pour barrer les possibilités impossibles.'),
            ],
        ];
    }

    /** @param array<string, mixed> $p */
    private function buildLibrary(array $p): array
    {
        $monday = (int) $p['lundi'];
        $tuesday = (int) $p['mardi'];
        $wednesday = (int) $p['mercredi'];
        $data = ['lundi' => $monday, 'mardi' => $tuesday, 'mercredi' => $wednesday];
        $highest = array_search(max($data), $data, true);
        $sum = $monday + $tuesday + $wednesday;
        $gap = max($data) - min($data);
        $twoDays = $monday + $tuesday;

        return [
            'story' => 'À la bibliothèque, on compte les livres empruntés pendant trois jours. Lis le tableau, puis utilise ses données pour répondre.',
            'table' => $data,
            'steps' => [
                $this->number('two', 'Combien de livres ont été empruntés lundi et mardi ensemble ?', 1, $twoDays,
                    "$monday + $tuesday = $twoDays livres."),
                $this->choice('highest', 'Quel jour compte le plus d’emprunts ?', 1, (string) $highest, [
                    'lundi' => 'Lundi',
                    'mardi' => 'Mardi',
                    'mercredi' => 'Mercredi',
                ], 'On compare les trois nombres et on retient le plus grand.'),
                $this->number('sum', 'Combien de livres ont été empruntés au total ?', 2, $sum,
                    "$monday + $tuesday + $wednesday = $sum livres."),
                $this->number('gap', 'Quel est l’écart entre le jour le plus chargé et le moins chargé ?', 1, $gap,
                    max($data) . ' − ' . min($data) . " = $gap livres."),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function number(string $id, string $question, int $weight, int $expected, string $explanation): array
    {
        return compact('id', 'question', 'weight', 'expected', 'explanation') + [
            'type' => 'number', 'choices' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function text(string $id, string $question, int $weight, string $expected, string $explanation): array
    {
        return compact('id', 'question', 'weight', 'expected', 'explanation') + [
            'type' => 'text', 'choices' => [],
        ];
    }

    /**
     * @param array<string, string> $choices
     * @return array<string, mixed>
     */
    private function choice(string $id, string $question, int $weight, string $expected, array $choices, string $explanation): array
    {
        return compact('id', 'question', 'weight', 'expected', 'choices', 'explanation') + [
            'type' => 'choice',
        ];
    }
}
