# Architecture initiale — proposition

> Statut : conception, à valider avant implémentation.

## Décision de principe

Démarrer sur un **monolithe modulaire** PHP, exposant des interfaces de module explicites. Extraire un composant en service autonome seulement si son cycle de vie, sa charge ou son langage le justifie. « Modulaire » ne signifie pas « une base de données et un conteneur par fonctionnalité » dès le départ.

Socle envisagé : PHP avec Symfony (version LTS à fixer au démarrage), MariaDB, HTTP/JSON, tests PHP et navigateur, GitHub Actions pour la CI. Aucun serveur de production ni secret n'est requis à ce stade.

## Modules et responsabilités

| Module | Responsabilité | N'a pas le droit de faire |
| --- | --- | --- |
| Identity & Access | Comptes, rôles, sessions, MFA, réinitialisation de mot de passe | Accorder des minutes |
| Exercise Catalog | Métadonnées, versions, compétences, paramètres des modèles | Lire le solde de la cagnotte |
| Exercise Generation | Génération d'une instance reproductible à partir d'un modèle et d'une graine | Déterminer le niveau d'un élève |
| Attempt & Assessment | Tentatives, saisies, évaluations étape par étape et corrections | Créditer directement une table de solde |
| Learner Progress | Maîtrise des compétences, difficulté proposée, répétitions | Modifier l'évaluation passée |
| Rewards Ledger | Journal immuable des crédits/débits/annulations, calcul du solde | Modifier une tentative |
| Administration | Vues et commandes autorisées, audit | Contourner les interfaces métier |
| Notifications | Envoi d'emails à partir d'une file / outbox | Accéder aux mots de passe |
| External Integrations | Adaptateurs optionnels, notamment ScreenGuard | Modifier les règles de contrôle parental |

L'interface web appelle les cas d'usage du backend. Les modules communiquent par contrats applicatifs et événements métier, **jamais par modification directe des tables d'un autre module**. Une même base MariaDB peut contenir des tables appartenant chacune à un module ; l'appartenance est documentée.

## Flux métier central

1. Le module Progress sélectionne des compétences et une difficulté.
2. Catalog/Generation instancie un exercice avec version, paramètres et graine persistés.
3. Assessment enregistre les réponses, calcule le détail des étapes, les étoiles et propose la correction.
4. Une validation finale émet un événement `AttemptAssessed` doté d'un identifiant stable.
5. Progress met à jour la maîtrise ; Rewards applique son barème et crée **au plus une transaction crédit** par tentative et règle de récompense.
6. L'interface affiche la correction, la progression et le solde reconstitué.
7. Ultérieurement, un adaptateur publie les droits validés vers ScreenGuard, de manière idempotente et réessayable.

## Contrats proposés (conceptuels, non encore implémentés)

- `GenerateExercise(templateId, version, seed, parameters) -> ExerciseInstance`
- `EvaluateAttempt(instanceId, answers) -> StepResults + stars + feedback`
- `RecordAssessment(attemptId, resultVersion) -> AttemptAssessed`
- `ApplyReward(attemptId, policyVersion) -> LedgerTransaction | AlreadyApplied`
- `GetLearnerProgress(userId) -> CompetencyProgress[]`
- `GetBalance(userId) -> minutes`

Ces contrats seront détaillés, versionnés et testés indépendamment. Les identifiants de tentative et de transaction garantissent l'idempotence.

## Données principales (schéma conceptuel)

- `user`, `credential`, `mfa_credential`, `user_role`, `password_reset_token`
- `competency`, `grade_level`, `exercise_template`, `template_version`, `exercise_instance`
- `attempt`, `step_answer`, `step_assessment`
- `learner_competency_progress`, `practice_schedule`
- `reward_policy`, `reward_transaction`, `integration_delivery`, `audit_log`

Aucun attribut n'est définitivement arrêté avant la définition des cas d'usage et contraintes de rétention.

## Sécurité et séparation des droits

- Hachage de mots de passe avec l'algorithme recommandé par le framework (Argon2id si disponible).
- Réinitialisation par lien à usage unique, durée limitée, réponse anti-énumération.
- MFA **obligatoire pour l'administration** ; WebAuthn/FIDO2 souhaité, TOTP en secours contrôlé.
- Calcul des résultats et récompenses **côté serveur**, jamais à partir d'étoiles soumises par le navigateur.
- Contrôles d'autorisation par ressource : un élève ne voit pas le détail d'un autre compte.
- Transactions de cagnotte append-only, débits et régularisations explicites.
- Journaux d'audit séparés des données de réponses pédagogiques.
- Gestion des secrets hors dépôt et configuration distincte pour dev/test/prod.

## Stratégie d'intégration

Les moteurs externes éventuels (MathALÉA, Numbas, H5P) seront évalués derrière des adaptateurs. Ne pas les intégrer avant un prototype de compatibilité technique et une revue de licence. ScreenGuard conserve le contrôle des horaires, quotas et décisions d'accès ; la plateforme produit uniquement des droits traçables.

## Qualité et livraison

- Tests unitaires de correction, progression, crédits et anti-duplication.
- Tests d'intégration de persistance et du flux de finalisation d'une tentative.
- Tests fonctionnels des autorisations, de la récupération de compte et de la MFA.
- Analyse statique, formatage, contrôle des dépendances et migrations reproductibles.
- Déploiement déclenché séparément de la fusion d'une PR ; pas de secret dans GitHub.
