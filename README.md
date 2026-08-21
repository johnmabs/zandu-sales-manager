# Zandu Sales Manager

Zandu Sales Manager est une plateforme de gestion commerciale multi-tenant,
conçue pour administrer des organisations, leurs magasins, leurs membres et
leurs droits d'accès.

Le projet est actuellement en développement. Le **Lot 0 — Architecture
exécutable** est terminé et le **Lot 1 — Administration opérationnelle** est en
cours. L'état détaillé est disponible dans
[`IMPLEMENTATION_STATUS.md`](IMPLEMENTATION_STATUS.md).

## Stack technique

- PHP 8.5 et Symfony 7.4 LTS ;
- API Platform 4 ;
- PostgreSQL 18 et Doctrine ORM ;
- authentification JWT ;
- architecture en monolithe modulaire inspirée du DDD ;
- isolation multi-tenant par transactions tenant-scoped et PostgreSQL RLS ;
- PHPUnit, PHPStan, PHP-CS-Fixer et Deptrac ;
- Docker Compose pour l'environnement local.

## Prérequis

- Docker avec le plugin Docker Compose ;
- GNU Make ;
- Git.

PHP, Composer et PostgreSQL n'ont pas besoin d'être installés sur la machine
hôte.

## Installation

Cloner le dépôt puis, depuis sa racine :

```bash
cp .env.example .env
make install
make start
make database-migrate
```

`make install` construit l'image, installe les dépendances Composer et génère
la paire de clés JWT. Les données PostgreSQL sont conservées dans le volume
Docker `postgres_data`.

Vérifier que les services sont prêts :

```bash
make ps
docker compose exec backend php bin/console about
make database-status
```

## Accéder à l'API dans le navigateur

Le service backend de développement n'expose pas de serveur HTTP par défaut.
Après `make start`, lancer dans un terminal séparé :

```bash
docker compose run --rm -p 8080:8080 backend \
  php -S 0.0.0.0:8080 -t public
```

Les URLs utiles sont alors :

- API et documentation : <http://localhost:8080/api/docs> ;
- disponibilité applicative : <http://localhost:8080/health/live> ;
- disponibilité de PostgreSQL : <http://localhost:8080/health/ready> ;
- métriques : <http://localhost:8080/metrics>.

Le serveur s'arrête avec `Ctrl+C`.

## Premier parcours utilisateur

Créer un compte et sa première organisation :

```bash
curl --request POST http://localhost:8080/api/auth/register \
  --header 'Content-Type: application/json' \
  --data '{
    "email": "owner@example.com",
    "password": "a-strong-password-for-zandu",
    "organizationName": "Ma société",
    "countryCode": "CG",
    "defaultCurrency": "XAF",
    "defaultTimeZone": "Africa/Brazzaville",
    "defaultLocale": "fr_CG"
  }'
```

La création est atomique : elle provisionne le compte, l'organisation, le
membership actif et le rôle `ORGANIZATION_OWNER`.

Obtenir ensuite les jetons d'authentification :

```bash
curl --request POST http://localhost:8080/api/auth/login \
  --header 'Content-Type: application/json' \
  --data '{
    "email": "owner@example.com",
    "password": "a-strong-password-for-zandu"
  }'
```

Pour appeler un endpoint protégé :

```bash
curl http://localhost:8080/api/example \
  --header 'Authorization: Bearer <token>'
```

Une personne invitée qui ne possède pas encore de compte peut s'inscrire avec
le token reçu :

```bash
curl --request POST \
  http://localhost:8080/api/auth/invitations/<invitation-token>/register \
  --header 'Content-Type: application/json' \
  --data '{"password":"another-strong-password"}'
```

## Commandes courantes

| Commande | Rôle |
| --- | --- |
| `make start` | Démarrer les services en arrière-plan |
| `make stop` | Arrêter les services |
| `make restart` | Redémarrer les services |
| `make logs` | Suivre les logs Docker |
| `make shell` | Ouvrir un shell dans le backend |
| `make test` | Préparer la base de test et exécuter PHPUnit |
| `make lint` | Valider Composer et le conteneur Symfony |
| `make quality` | Exécuter PHP-CS-Fixer et PHPStan |
| `make architecture` | Vérifier les couches et frontières des modules |
| `make security` | Auditer les dépendances Composer |
| `make database-migrate` | Appliquer les migrations de développement |
| `make database-status` | Afficher l'état des migrations |

Pour exécuter l'équivalent des contrôles principaux de la CI :

```bash
make lint
make quality
make architecture
make test
make security
```

## Architecture du dépôt

```text
.
├── backend/
│   ├── config/              Configuration Symfony
│   ├── migrations/          Migrations Doctrine
│   ├── public/              Point d'entrée HTTP
│   ├── src/
│   │   ├── Modules/         Bounded contexts métier
│   │   ├── Platform/        Adaptateurs techniques transverses
│   │   └── SharedKernel/    Concepts partagés stables
│   └── tests/               Tests unitaires, API et PostgreSQL
├── docs/
│   ├── architecture/        Spécification DDD, ADR et fitness tests
│   ├── planning/            Backlogs d'implémentation
│   └── spikes/              Résultats des spikes techniques
├── scripts/                 Validations opérationnelles
├── compose.yaml             Environnement Docker local
├── Makefile                 Commandes de développement
└── IMPLEMENTATION_STATUS.md Suivi de l'avancement réel
```

Les dépendances doivent respecter les frontières de couches et de bounded
contexts contrôlées par les deux configurations Deptrac du backend.

## Documentation

- [Suivi d'implémentation](IMPLEMENTATION_STATUS.md) ;
- [Planning du Lot 1](docs/planning/zandu-lot-1-administration-operationnelle.md) ;
- [Spécification DDD](docs/architecture/ddd/zandu-sales-manager-ddd-v1.1.docx) ;
- [Index des décisions d'architecture](docs/architecture/adr/README.md) ;
- [Fitness tests d'architecture](docs/architecture/fitness-tests.md).

## Licence

Projet propriétaire. Aucun droit d'utilisation, de modification ou de
distribution n'est accordé en dehors des autorisations explicites du
propriétaire.
