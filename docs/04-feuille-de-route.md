# Feuille de route et critères d'acceptation

> Proposition de découpage, sans calendrier imposé.

## Phase 0 — Cadrage (présente PR)

- [ ] Valider le périmètre, les acteurs et le référentiel scolaire.
- [ ] Valider le principe du monolithe modulaire et les limites entre modules.
- [ ] Valider les garde-fous de récompense et la protection des mineurs.
- [ ] Choisir le framework et la chaîne locale (PHP, Composer, MariaDB, éventuellement Docker).
- [ ] Documenter les premières décisions d'architecture (ADR).

**Sortie attendue :** documentation relue et fusionnée, aucun service en production.

## Phase 1 — Tranche verticale de démonstration

Construire un parcours de bout en bout avec **un seul compte de démonstration** et trois exercices :

1. QCM de logique ;
2. problème numérique à plusieurs étapes et correction partielle ;
3. schéma interactif de type graphe/relations.

Vérifier la conservation de la graine, les corrections serveur et l'affichage des étoiles. Aucune cagnotte réelle, aucun lien ScreenGuard.

**Acceptation :** exercices reproductibles, tests des évaluateurs, erreurs pédagogiques explicitées.

## Phase 2 — MVP multi-utilisateur sécurisé

- Gestion des comptes, récupération par email et rôles.
- Administration protégée par MFA.
- Suivi de compétences et niveau initial.
- Journal de cagnotte, barème configurable et protections anti-duplication.
- Catalogue d'exercices versionné et répétition contrôlée.
- Sauvegarde/restauration et tests automatiques.

**Acceptation :** aucun utilisateur ne peut modifier le résultat ni les minutes depuis les outils du navigateur ; tests d'isolation des comptes et des transactions.

## Phase 3 — Adaptation pédagogique

- Référentiel complet CP → terminale par compétences.
- Progression automatique mesurée sur plusieurs séances.
- Génération paramétrique contrôlée et tâches de révision espacée.
- Évaluation du recours à MathALÉA, Numbas ou H5P, sous réserve de compatibilité et de licences.

**Acceptation :** les décisions de progression sont explicables, auditables et réversibles.

## Phase 4 — Intégration ScreenGuard (optionnelle)

- Contrat API dédié, authentification service-à-service.
- Idempotence, file de synchronisation et reprise après indisponibilité.
- Séparation stricte entre « minutes gagnées » et « droit d'utilisation maintenant ».
- Tests de bout en bout sur un environnement de préproduction.

**Acceptation :** aucune défaillance de ScreenGuard ne fait perdre des minutes ni ne contourne ses règles horaires.

## Règles de travail Git

- Une branche par changement, pull request avant fusion sur `main`.
- Tests et revue requis pour les modifications de logique de correction, sécurité et cagnotte.
- Scripts reproductibles et versionnés ; pas de modifications manuelles non documentées en production.
- Secrets, sauvegardes, dumps contenant des données personnelles et fichiers locaux exclus du dépôt.
