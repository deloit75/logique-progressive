# Vision et périmètre — version de travail

> Statut : proposition initiale à valider. Ce document décrit le produit cible ; il ne signifie pas que les fonctionnalités sont réalisées.

## Mission

Proposer une plateforme web libre, accessible du primaire au lycée, permettant de résoudre des problèmes de logique et de mathématiques en plusieurs étapes, avec corrections explicatives, progression adaptative par compétences et récompenses mesurées en minutes de temps d'écran.

Le produit doit être utilisable **sans ScreenGuard**. Son intégration future est optionnelle et ne doit pas introduire de dépendance de fonctionnement.

## Acteurs et responsabilités

- **Élève** : effectue les exercices, consulte les étapes, les évaluations, les corrections, sa progression et son solde de minutes.
- **Responsable légal** (cible, à préciser) : suit la progression, paramètre éventuellement les récompenses, consulte l'historique de l'enfant.
- **Administrateur** : crée ou invite les utilisateurs, définit leur niveau de départ, gère les contenus, consulte les cagnottes et déclenche la génération de nouveaux exercices.
- **Service externe** : consomme, à terme, les droits issus de la cagnotte via une interface contrôlée.

## Besoins fonctionnels de référence

1. Compte utilisateur avec identifiant, adresse électronique et mot de passe ; récupération sécurisée par courrier électronique.
2. Niveaux scolaires du CP à la terminale, avec démarrage défini pour chaque utilisateur et évolution automatique à partir de résultats répétés.
3. Parcours par compétences : un niveau global indicatif ne remplace pas un suivi distinct par domaine.
4. Exercices courts, ludiques, généralement réalisables en **10 minutes maximum**, pouvant comporter plusieurs étapes avec difficultés progressives.
5. Types de réponse : QCM, saisie numérique, texte libre, schéma/relations graphiques.
6. Évaluation sur **0 à 5 étoiles**, fondée sur les étapes et leur importance ; correction consultable en cas d'échec.
7. Récompenses en minutes attribuées selon le résultat, avec règles anti-duplication, limites et journal traçable.
8. Réutilisation périodique des problèmes, idéalement avec variation de paramètres et sans répétition stérile.
9. Administration avec MFA fort (WebAuthn/FIDO2 ou TOTP), édition des utilisateurs, consultation des soldes et génération des contenus.
10. Interfaces découplées et documentées pour adapter ou remplacer chaque moteur d'exercices et l'intégration ScreenGuard.

## Exigences non fonctionnelles

- **Sécurité** : authentification moderne, limitation de débit, sessions protégées, audit des opérations sensibles, moindre privilège.
- **Protection des mineurs** : minimisation des données, droits des responsables légaux, durées de conservation, suppression/export, conformité RGPD à instruire avant mise en production.
- **Accessibilité** : navigation au clavier, textes lisibles, alternatives aux gestes de dessin, contrastes et retours d'erreur compréhensibles.
- **Reproductibilité** : installation de développement documentée, migrations de base de données, tests automatiques.
- **Interopérabilité** : contrat de données stable, exports possibles, dépendances optionnelles isolées.
- **Résilience** : une panne du service d'email ou de ScreenGuard ne bloque pas la réalisation d'exercices.

## Hors périmètre de la première version

- Synchronisation effective de la cagnotte vers ScreenGuard.
- Production automatisée et non contrôlée d'exercices par IA.
- Analyse de dessins manuscrits arbitraires ou reconnaissance d'images libres.
- Classements publics des élèves, réseau social ou mécanismes de compétition compulsive.

## Décisions ouvertes

- Politique précise des responsables légaux et de l'adresse email pour les plus jeunes.
- Âge minimal d'usage et modalités de consentement selon la cible.
- Barème minutes/étoiles, plafond journalier et règle de péremption éventuelle.
- Référentiel détaillé de compétences par niveau scolaire français.
- Première sélection de moteurs externes, après validation technique et des licences.
