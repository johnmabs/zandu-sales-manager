# Zandu Sales Manager

Zandu Sales Manager est une plateforme de gestion commerciale multi-tenant,
conçue pour administrer des organisations, leurs magasins, leurs membres et
leurs droits d'accès.

Le projet est actuellement en développement. Le **Lot 0 — Architecture
exécutable** est terminé et le **Lot 1 — Administration opérationnelle** est en
cours. Les Epics 1.1 à 1.7 sont terminés et l'Epic 1.8 — API
d'administration est en cours. L'état détaillé est disponible dans
[`IMPLEMENTATION_STATUS.md`](IMPLEMENTATION_STATUS.md).

## Stack technique

- PHP 8.5 et Symfony 7.4 LTS ;
- API Platform 4 ;
- PostgreSQL 18 et Doctrine ORM ;
- authentification JWT ;
- architecture en monolithe modulaire inspirée du DDD ;
- isolation multi-tenant par transactions tenant-scoped et PostgreSQL RLS ;
- autorisation par permissions atomiques et scopes organisation/magasins ;
- audit de sécurité append-only et outbox transactionnelle ;
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
curl http://localhost:8080/api/organizations/<organization-id> \
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

## API d'administration disponible

Les opérations Organization suivantes sont actuellement exposées :

| Méthode | Endpoint | Intention |
| --- | --- | --- |
| `GET` | `/api/organizations/{id}` | Consulter l'organisation active du tenant |
| `PATCH` | `/api/organizations/{id}` | Modifier son profil |
| `POST` | `/api/organizations/{id}/suspend` | Suspendre explicitement l'organisation |
| `POST` | `/api/organizations/{id}/reactivate` | Réactiver l'organisation |
| `POST` | `/api/organizations/{id}/closure-request` | Demander sa fermeture |

Pour l'instant, un owner ne peut créer qu'une seule organisation sur la
plateforme. Celle-ci est créée exclusivement pendant l'inscription via
`POST /api/auth/register`. Le modèle conserve les memberships par organisation
afin de permettre le multi-organisation plus tard, lorsque le changement
d'organisation active et le renouvellement des tokens seront disponibles.

Exemple de modification :

```bash
curl --request PATCH \
  http://localhost:8080/api/organizations/<organization-id> \
  --header 'Authorization: Bearer <token>' \
  --header 'Content-Type: application/merge-patch+json' \
  --header 'X-Correlation-ID: <uuid-v7>' \
  --data '{
    "name": "Ma société mise à jour",
    "countryCode": "CG",
    "defaultCurrency": "XAF",
    "defaultTimeZone": "Africa/Brazzaville",
    "defaultLocale": "fr_CG"
  }'
```

Les ressources HTTP sont des DTO situés dans la couche
`Modules/<Contexte>/Presentation/Api`. Les agrégats métier et les entités
Doctrine ne sont jamais exposés directement. Les lectures restent
tenant-scoped et les commandes sensibles appliquent autorisation, garde
opérationnel, audit et outbox dans la transaction locale.

Les opérations Store suivantes sont également exposées :

| Méthode | Endpoint | Intention |
| --- | --- | --- |
| `GET` | `/api/stores` | Lister les magasins du tenant actif |
| `POST` | `/api/stores` | Créer un magasin |
| `GET` | `/api/stores/{id}` | Consulter un magasin du tenant |
| `PATCH` | `/api/stores/{id}` | Modifier un magasin |
| `POST` | `/api/stores/{id}/suspend` | Suspendre un magasin |
| `POST` | `/api/stores/{id}/reactivate` | Réactiver un magasin |
| `POST` | `/api/stores/{id}/closure-request` | Demander la fermeture d'un magasin |

Toutes les lectures Store sont filtrées par l'organisation active et les
opérations appliquent les permissions et scopes de magasins du token.

Les APIs invitations, memberships et attributions de rôles constituent la
suite de l'Epic 1.8 et ne sont pas encore exposées.

## Commandes courantes

| Commande | Rôle |
| --- | --- |
| `make start` | Démarrer les services en arrière-plan |
| `make stop` | Arrêter les services |
| `make restart` | Redémarrer les services |
| `make logs` | Suivre les logs Docker |
| `make shell` | Ouvrir un shell dans le backend |
| `make test` | Préparer la base et exécuter PHPUnit avec `APP_ENV=test` |
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
│   │   │   └── */Presentation/Api  DTO et adaptateurs HTTP du contexte
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
contexts contrôlées par les deux configurations Deptrac du backend. Un fitness
test interdit également de placer une ressource API métier dans le dossier
global générique `backend/src/ApiResource`.

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
