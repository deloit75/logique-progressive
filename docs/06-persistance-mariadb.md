# Persistance MariaDB — première étape

## Périmètre

Cette étape ne connecte pas encore les exercices aux profils : elle prépare
le schéma de stockage en vue des comptes élèves, responsables et administration.
Les exercices, sessions et scores provisoires existants continuent de fonctionner.
Aucun écran d'inscription, aucun compte ni aucune donnée personnelle ne sont créés.

Les identifiants, mots de passe et paramètres réseau restent dans
`apps/web/.env.local`, exclu du dépôt public.

## Tables de la migration 001

- `lp_account` : futur compte, rôle et empreinte de mot de passe.
- `lp_learner` : pseudonyme et niveau de référence scolaire.
- `lp_guardian_learner` : association explicite responsable/élève.
- `lp_attempt` : résultats vérifiés côté serveur, une fois par
  couple élève + empreinte de l'instance signée.
- `lp_competency_progress` : paliers et réussites par compétence.
- `lp_schema_migration` : versions de schéma appliquées.

La suppression automatique des tentatives n'est pas activée. La durée de
conservation et les droits d'accès devront être validés avant les premiers
comptes réels.

## Préparation de la base

Prérequis : PHP avec `pdo_mysql` et un serveur MariaDB accessible sur
un réseau approuvé (ou une connexion TLS vérifiée si le réseau ne l'est pas).
Créer une nouvelle base, par exemple `logique_progressive`, avec
`utf8mb4`. Utiliser un utilisateur MariaDB propre à cette application.
L'administrateur choisit le mot de passe dans le terminal, jamais dans GitHub.

Pour l'initialisation, le compte SQL doit avoir les droits de création des
tables et contraintes sur cette seule base, en plus de SELECT et INSERT.
Après migration, retirer les droits DDL au compte applicatif et ne conserver
que SELECT, INSERT, UPDATE et DELETE nécessaires au fonctionnement.

## Configuration privée

Ajouter les cinq variables figurant dans `apps/web/.env.database.example`
au fichier local `apps/web/.env.local` déjà utilisé par l'installation.
Ne pas effacer l'APP_SECRET ou les autres paramètres existants.

## Vérification puis application

Depuis le répertoire `apps/web` :

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console app:db:schema
```

Par défaut la commande est en lecture seule. Avant l'application,
sauvegarder la base si elle contient des données, puis s'assurer qu'elle
est bien dédiée et vide. Pour créer les tables :

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console app:db:schema --apply
```

Rejouer sans l'option pour vérifier la version enregistrée.
La migration est additive, refuse de modifier une base déjà remplie
et ne contient aucun DROP. MariaDB valide les DDL immédiatement :
en cas d'échec en cours d'application, inspecter les tables existantes ;
la commande ne tente pas de réparation destructive automatique.

## Etapes suivantes

1. Installer et configurer le composant officiel de sécurité Symfony,
   maintenir le manifeste Composer ET son fichier verrou synchronisés.
2. Ajouter la création contrôlée de comptes responsables/élèves,
   authentification, hachage de mots de passe et protection CSRF.
3. Isoler les données par élève dans les requêtes applicatives et
   vérifier les autorisations avec des tests.
4. Enregistrer les tentatives et progression en base de manière atomique
   et idempotente ; prévoir une stratégie explicite de migration des
   résultats de session.
5. MFA obligatoire avant toute exposition d'une interface d'administration.
