# Règles pédagogiques et récompenses — proposition

> Les valeurs chiffrées ci-dessous sont **illustratives**, non définitives.

## Référentiel des niveaux

Le système doit couvrir **CP, CE1, CE2, CM1, CM2, 6e, 5e, 4e, 3e, seconde, première et terminale**.

Un utilisateur commence avec un niveau scolaire de référence défini par l'administrateur. Sa progression réelle est suivie **par compétence** (ex. logique, calcul, géométrie, proportionnalité, lecture de données). Une compétence peut se situer au-dessus ou au-dessous du niveau de référence.

## Forme d'un exercice

- Un modèle possède un identifiant stable, une version, un ensemble de compétences et une difficulté de départ.
- Chaque présentation constitue une **instance** unique avec paramètres et graine pseudo-aléatoire enregistrés.
- Un exercice contient une ou plusieurs **étapes**, des consignes, un type de réponse et un poids d'évaluation pour chaque étape.
- La correction révèle les démarches et les notions mobilisées, en distinguant erreurs de raisonnement et erreurs de saisie.
- Cible pédagogique : **10 minutes maximum** par exercice, sauf paramétrage explicite d'une autre durée.

## Types de réponses et évaluation

| Type | Principe initial de correction |
| --- | --- |
| QCM | Choix simple ou multiple, réponse attendue structurée |
| Numérique | Valeur, unités et tolérance définie par exercice |
| Texte court | Espaces et casse normalisés ; variantes explicites et tolérance de frappe contrôlée |
| Schéma interactif | Évaluation d'un objet structuré (nœuds, arêtes, positions/relations), pas d'une image brute |

La normalisation ne doit pas effacer une distinction mathématique importante. Les tolérances sont adaptées aux réponses attendues et testées par cas limites.

## Étoiles

Proposition : calculer un score pondéré `S = somme(points_obtenus_i) / somme(points_max_i)`. Convertir ensuite ce score en **0 à 5 étoiles**, selon des seuils versionnés et administrables. Une étape peut attribuer un crédit partiel.

L'interface doit expliquer *quelles étapes sont réussies*, et pas seulement afficher une moyenne. Les étoiles correspondent à une évaluation pédagogique, **pas** à un droit d'écran calculé par le navigateur.

## Récompenses en minutes

Barème illustratif pour un exercice éligible : 1★ = 1 min ; 2★ = 2 min ; 3★ = 3 min ; 4★ = 5 min ; 5★ = 7 min. Les 0★ n'accordent aucun crédit. Ce barème est un exemple de discussion, non une configuration active.

Règles à sécuriser avant une mise en service :

1. Le crédit est issu uniquement d'une tentative finalisée côté serveur.
2. Une tentative et une version de barème ne peuvent être créditées qu'une fois.
3. La répétition d'une même instance et la lecture de la correction ne permettent aucun nouveau crédit.
4. Les reprises d'un modèle avec nouvelles données peuvent compter pour la progression, mais le gain de minutes sera plafonné selon une politique anti-farming.
5. Chaque mouvement de cagnotte est historisé : identifiant, utilisateur, type, minutes, justification, horodatage, idempotency key.
6. Le solde est dérivé du journal ; ajustements et corrections ne suppriment pas les écritures anciennes.
7. Les plafonds journaliers et la validation éventuelle par un responsable légal seront configurables.

## Progression adaptative

Une progression ne doit pas découler de la réussite d'une seule question. Première hypothèse de travail :

- évaluer plusieurs exercices distincts sur une compétence, sur plusieurs jours ;
- pondérer les réussites par difficulté et par maîtrise des étapes ;
- proposer des rappels espacés après acquisition ;
- augmenter la difficulté de façon progressive, avec retours possibles ;
- conserver le référentiel de compétences et l'historique des changements de niveau.

Les seuils, nombres de séances et fenêtres temporelles restent à mesurer lors des essais.

## Réapparition des problèmes

Le planificateur réintroduit un **modèle** selon l'historique de l'utilisateur, mais privilégie une **nouvelle instance** avec paramètres variés. Le système conserve la graine afin de pouvoir reproduire exactement une correction ou auditer une réponse.

La génération doit vérifier les contraintes du problème : unicité éventuelle de la solution, cohérence des données, absence de division par zéro, absence d'ambiguïté dans l'énoncé et difficulté estimée.

## Expérience de l'élève

Présenter un objectif court, des étapes explicites, la possibilité de continuer après un échec et une correction lisible. Éviter les effets punitifs ou une incitation à un temps d'écran excessif : le barème doit rester borné et contrôlable par un adulte.
