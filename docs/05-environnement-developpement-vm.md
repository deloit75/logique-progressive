# Développement distant avec VS Code

> Guide générique, destiné à être publié dans un dépôt open source. **Ne pas y inscrire de paramètres d'une infrastructure réelle.**

## Objectif

Contribuer à l'application depuis VS Code, en exécutant le code et les outils de développement sur une machine Linux accessible en SSH. Le dépôt Git sert de référence commune pour le code et la documentation.

Ce document ne présuppose ni nom de domaine, ni fournisseur d'hébergement, ni organisation réseau, ni système de production particulier.

## Prérequis

- VS Code et l'extension officielle **Remote - SSH** sur le poste de travail.
- Une machine Linux accessible via SSH, avec un compte non administrateur autorisé.
- Git et, selon l'étape de développement, PHP, Composer et un accès à une base de données de test.
- Une clé SSH pour la machine distante. Utiliser une identité distincte pour GitHub si nécessaire.
- Un répertoire de travail appartenant à l'utilisateur de développement et **non publié automatiquement** par le serveur web.

## Sécurité

- Ne jamais versionner d'adresses IP internes, d'URL privées, de noms d'hôtes, de ports de tunnels, de topologies d'infrastructure ou de chemins propres à une installation.
- Ne pas committer de clés privées, jetons, secrets, fichiers d'environnement locaux, sauvegardes, dumps SQL ou données d'utilisateurs.
- Utiliser des exemples avec des paramètres fictifs ou des variables génériques.
- Séparer l'environnement de développement de la zone réellement servie par le serveur HTTP.
- Appliquer le principe du moindre privilège : pas de session de développement en root.
- Préférer un accès SSH privé ou via un relais authentifié ; ne pas ouvrir de ports publics uniquement pour développer.
- Conserver les accès d'administration de secours lors de tout changement de configuration.
- Vérifier les licences avant intégration de moteurs d'exercices tiers.

## Connexion SSH directe (exemple fictif)

Dans le fichier de configuration SSH **local** de l'utilisateur (non versionné) :

```sshconfig
Host dev-app
    HostName VM_HOST
    User DEV_USER
    IdentityFile ~/.ssh/DEV_KEY
    IdentitiesOnly yes
```

Tester d'abord en terminal :

```sh
ssh dev-app
```

Comparer l'empreinte de l'hôte à une valeur obtenue par un canal fiable avant d'accepter une première connexion.

## Connexion facultative par bastion SSH

Si l'environnement utilise un relais SSH autorisant le transfert TCP, **ProxyJump** permet de se connecter sans exposer la machine finale directement :

```sshconfig
Host gateway
    HostName BASTION_HOST
    User BASTION_USER
    IdentityFile ~/.ssh/BASTION_KEY

Host dev-app
    HostName VM_PRIVATE_HOST
    User DEV_USER
    IdentityFile ~/.ssh/DEV_KEY
    IdentitiesOnly yes
    ProxyJump gateway
```

`ProxyJump` nécessite que l'utilisateur s'authentifie sur le relais et que ce dernier puisse joindre la destination. L'existence d'une interface d'administration web ne garantit pas la disponibilité d'un tunnel SSH.

Ces identifiants sont **des espaces réservés fictifs**, à remplacer uniquement dans les fichiers de configuration locaux.

## Utilisation dans VS Code

1. Installer l'extension **Remote - SSH**.
2. Tester la connexion SSH dans un terminal du poste de travail.
3. Depuis la palette de commandes de VS Code, choisir `Remote-SSH: Connect to Host...` et sélectionner l'alias local.
4. Ouvrir le répertoire du projet sur la machine distante.
5. Installer les extensions nécessaires **dans la fenêtre distante**.
6. Utiliser le transfert de ports de VS Code pour consulter les services de développement non publics.

## Gestion du code

Les copies locales du dépôt, sur le poste de travail et sur le serveur distant, sont des clones Git **indépendants**. Elles échangent les commits via GitHub :

```sh
git status
git fetch origin
git branch --show-current
```

Créer une branche dédiée à chaque fonctionnalité, lancer les tests, puis ouvrir une pull request. Ne pas fusionner directement dans `main` sans revue.

## Déploiement

Le déploiement vers un serveur HTTP est une opération distincte du développement :

- code applicatif et secrets séparés ;
- configuration locale hors du dépôt ;
- migrations explicites et réversibles autant que possible ;
- vérifications et sauvegardes avant changement ;
- publication uniquement après les tests et la revue.

## Documentation officielle

- [VS Code : Remote SSH](https://code.visualstudio.com/docs/remote/ssh)
- [VS Code : Linux prerequisites](https://code.visualstudio.com/docs/remote/linux)
- [GitHub : authentication](https://docs.github.com/en/authentication)
