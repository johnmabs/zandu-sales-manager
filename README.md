# Zandu Sales Manager

Zandu Sales Manager est une plateforme de gestion commerciale multi-tenant
conçue pour administrer des organisations, leurs magasins, leurs membres et
leurs droits d'accès, ainsi que le catalogue, la tarification, les stocks, les
caisses et les ventes.

Le projet est actuellement en développement. Les **Lots 0 à 5** sont terminés,
le **Gate M2 — première vente cash de bout en bout** est validé et le **Lot 5 —
Inventory Costing & Returns** clôt la première partie du jalon M3 consacré à la
gestion complète du stock. L'état détaillé, le backlog et les preuves de
validation sont disponibles dans
[`IMPLEMENTATION_STATUS.md`](IMPLEMENTATION_STATUS.md).

## État de l'implémentation

| Lot | Périmètre | État |
| --- | --- | --- |
| 0 | Architecture exécutable | Terminé |
| 1 | Administration opérationnelle | Terminé |
| 2 | Catalogue et tarification de base | Terminé |
| 3 | Fondations Inventory et Cash | Terminé |
| 4 | Sales et `CompleteSale` cash | Terminé — Gate M2 validé |
| 5 | Inventory Costing et Returns | Terminé — Gate CI validé |
| 6 | Purchasing et Goods Receipts | En cours — Supplier terminé |

Dernière validation consolidée le 28 août 2026 : **592 tests et 2 866
assertions**, PHPStan et PHP-CS-Fixer sans erreur, zéro violation dans les deux
configurations Deptrac et aucune vulnérabilité connue dans les dépendances
Composer verrouillées. L’image production, la restauration PostgreSQL et le
workflow GitHub Actions `Backend CI` sont également validés.

Le [planning du Lot 6](docs/planning/zandu-lot-6-purchasing-goods-receipts.md)
ouvre désormais le backlog `Purchasing & Goods Receipts`. Le Lot 7 reste annoncé
comme la tranche `StockTransfer & StockCount`, mais ne dispose pas encore d’un
document de planning autonome dans le repository.

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

- Swagger UI : <http://localhost:8080/api/docs> ;
- ReDoc : <http://localhost:8080/api/docs?ui=re_doc> ;
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

Renouveler l'access token avec le refresh token reçu au login :

```bash
curl --request POST http://localhost:8080/api/auth/refresh \
  --header 'Content-Type: application/json' \
  --data '{"refreshToken":"<refresh-token>"}'
```

Une refresh session est liée à l'organisation active et à la version
d'autorisation du membership au moment du login. Une suspension, une
révocation ou une modification des rôles rend donc cette session inutilisable :
l'utilisateur doit se reconnecter pour obtenir des jetons cohérents avec ses
droits courants.

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
opérationnelle, audit et outbox dans la transaction locale.

### État du parcours Sales

Le parcours M2 est exposé de bout en bout : création d'une vente `DRAFT`, ajout,
modification et retrait de lignes snapshotées, annulation, lecture, finalisation
cash idempotente et reçu historique. `CompleteSale` coordonne Sale, Payment
CASH, Inventory, Inventory Costing, CashMovement, audit et outbox dans une
transaction tenant-scoped ; il verrouille la vente, revalide Pricing, contrôle
le scope Store, calcule la date métier et retourne la monnaie à rendre. Chaque
sortie `SALE` diminue la valorisation au coût moyen courant et crée un ledger
lié au mouvement physique. Chaque ligne de produit suivi conserve en plus un
`SaleLineCostSnapshot` immutable avec quantité de base, coût unitaire, coût
total, devise et version de valorisation. Une valorisation absente ou
incohérente annule toute la finalisation. Une panne injectée au moment de la
capture du snapshot prouve également le rollback de Sale, Payment, Cash,
Inventory, Costing, audit, outbox et clé d'idempotence, puis la réussite d'un
retry avec la même clé.

La fondation domaine des retours est également disponible. `ReturnSaleLine`
conserve les snapshots commerciaux et de coût de la ligne vendue, calcule la
quantité retournée en unité de base et distingue explicitement `restock=true`
de `restock=false`. L’agrégat `ReturnSale` protège son ownership tenant, son
cycle `DRAFT`, `COMPLETED`, `CANCELLED`, sa date métier et son immutabilité.
La persistence PostgreSQL/RLS conserve les liens vers les snapshots originaux.
La complétion verrouille la vente source, exige son statut `COMPLETED` et
empêche les retours cumulés de dépasser chaque quantité vendue. Le workflow
Returns complet est exposé par API Platform : création depuis une vente, ajout
de lignes, complétion, annulation, lecture et liste par vente. Ces opérations
appliquent les permissions `SALE_RETURN_*`, le scope Store, l’audit et l’outbox,
et sont publiées dans Swagger UI/ReDoc. Inventory prépare un restock idempotent
`SALE_RETURN` pour les lignes `restock=true`. Son activation dans la complétion
restaure simultanément le coût snapshoté d’origine et crée le ledger Costing
associé, sans désynchroniser Stock et StockValuation.
`ReturnAmountCalculator` alloue aussi remise, base taxable, taxe, sous-total et
total depuis les seuls snapshots de vente. Son calcul cumulatif absorbe le
résidu d’arrondi sur le dernier retour : des retours partiels successifs ne
dépassent jamais les montants originaux et un retour complet restitue exactement
le total remboursable. Chaque allocation complétée est figée dans une table RLS
append-only et borne le remboursement cash de l’Epic 5.13.
L’Epic 5.13 fournit désormais `PaymentRefund` et la route canonique
`POST /api/payments/{paymentId}/refunds`. Le paiement et le retour imposent deux
plafonds cumulatifs distincts sous verrou ; chaque succès ajoute un mouvement
de caisse `REFUND` sortant sur une session ouverte, plus audit et outbox dans la
même transaction. Le rejeu idempotent ne duplique aucun effet et le workflow
ne touche jamais Inventory ou Costing. Une matrice de fautes PostgreSQL prouve
également qu’une erreur d’audit, d’outbox ou juste avant commit annule ensemble
le remboursement, le mouvement Cash et tous leurs effets transverses. La même
preuve couvre `CompleteReturnSale` : une faute après restock/valorisation ou
pendant les effets transverses restaure le Stock, le Costing et le retour sans
aucun ledger partiel. Les rejeux de complétion et de remboursement sont
idempotents et ne dupliquent aucun de ces effets. PostgreSQL RLS masque les
ventes et paiements inter-tenant, tandis que les permissions atomiques refusent
les opérations hors du scope Store avant toute mutation.

La politique fiscale du pilote est explicitement `NO_TAX` selon
[l'ADR-0020](docs/architecture/adr/0020-pilot-sales-tax-policy.md). Un scénario
HTTP PostgreSQL vérifie le parcours complet et son rejeu sans effet dupliqué.
Le détail du Gate M2 et des preuves est maintenu dans
[le planning du Lot 4](docs/planning/zandu-lot-4-sales-complete-sale-cash.md).

### Initialiser et ajuster un stock valorisé

Toute nouvelle position physique est valorisée dans la même transaction. Le
stock initial et les ajustements positifs exigent un coût unitaire explicite :

```bash
curl --request POST \
  http://localhost:8080/api/stores/<store-id>/stocks/<product-id>/initialize \
  --header 'Authorization: Bearer <token>' \
  --header 'Content-Type: application/json' \
  --data '{"quantity":"10","unitCost":"4000"}'

curl --request POST \
  http://localhost:8080/api/stores/<store-id>/stocks/<product-id>/adjust \
  --header 'Authorization: Bearer <token>' \
  --header 'Content-Type: application/json' \
  --data '{"delta":"5","unitCost":"6000","reason":"Restock"}'
```

Un ajustement négatif omet `unitCost` et sort au coût moyen courant :

```json
{"delta":"-2","reason":"Shrinkage"}
```

Le mouvement physique, la valorisation courante et son ledger sont atomiques :
une règle Costing invalide annule également la variation de stock.

### Reprendre la valorisation d'un stock historique

Pour une position créée avant l'activation de Costing, le bootstrap reprend
sous verrou la quantité physique actuelle et crée
atomiquement la valorisation économique, son mouvement `OPENING`, l'audit et
l'événement outbox. Un coût explicite et une justification sont obligatoires :

```bash
curl --request POST \
  http://localhost:8080/api/stores/<store-id>/inventory-valuations/<product-id>/initialize \
  --header 'Authorization: Bearer <token>' \
  --header 'Content-Type: application/json' \
  --data '{
    "openingUnitCost": "4000",
    "reason": "Controlled opening inventory"
  }'
```

La permission dédiée est `INVENTORY_COSTING_INITIALIZE`. Une position déjà
valorisée est refusée avec `VALUATION_ALREADY_INITIALIZED`.

### Contrat d'erreurs

Les opérations d'administration documentent et renvoient un format JSON stable :

```json
{
  "code": "NOT_FOUND",
  "message": "The requested resource was not found.",
  "correlationId": "0198e463-147c-72d5-b75a-a936797ff9c8"
}
```

| HTTP | Code stable | Signification |
| --- | --- | --- |
| `400` | `VALIDATION_ERROR` | Payload ou paramètres invalides |
| `401` | `UNAUTHENTICATED` | Authentification absente ou invalide |
| `403` | `FORBIDDEN` | Permission insuffisante |
| `404` | `NOT_FOUND` | Ressource inexistante ou appartenant à un autre tenant |
| `409` | `CONFLICT` | Conflit avec l'état courant |
| `422` | `DOMAIN_RULE_VIOLATION` | Invariant métier violé |

Les messages internes des exceptions ne sont pas exposés. Une ressource d'un
autre tenant produit volontairement `404 NOT_FOUND` afin de ne pas révéler son
existence. En développement, le contrat interactif est disponible dans
[Swagger UI](http://localhost:8080/api/docs) et dans
[ReDoc](http://localhost:8080/api/docs?ui=re_doc).

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

Les opérations d'invitation suivantes sont exposées :

| Méthode | Endpoint | Intention |
| --- | --- | --- |
| `POST` | `/api/member-invitations` | Inviter une personne avec ses rôles prévus |
| `POST` | `/api/member-invitations/{id}/cancel` | Annuler une invitation en attente |
| `POST` | `/api/invitations/{token}/accept` | Accepter l'invitation avec un compte authentifié |

Le token brut n'est retourné qu'à la création afin d'être transmis à l'invité.
Son hash n'apparaît jamais dans les ressources HTTP ou le contrat OpenAPI. Une
personne sans compte utilise le parcours public
`POST /api/auth/invitations/{token}/register` ; un compte existant doit être
authentifié pour accepter l'invitation.

Exemple d'invitation d'un caissier limité à un magasin :

```bash
curl --request POST http://localhost:8080/api/member-invitations \
  --header 'Authorization: Bearer <access-token>' \
  --header 'Content-Type: application/json' \
  --header 'X-Correlation-ID: <uuid-v7>' \
  --data '{
    "email": "cashier@example.com",
    "roleAssignments": [
      {
        "roleCode": "CASHIER",
        "storeIds": ["<store-id>"]
      }
    ]
  }'
```

La réponse contient les informations non sensibles de l'invitation et un champ
`token`. Il doit être transmis à la personne invitée et ne peut pas être relu
ultérieurement depuis l'API.

Si cette personne possède déjà un compte, elle se connecte avec son email puis
accepte l'invitation avec son propre access token :

```bash
curl --request POST \
  http://localhost:8080/api/invitations/<invitation-token>/accept \
  --header 'Authorization: Bearer <invitee-access-token>' \
  --header 'X-Correlation-ID: <uuid-v7>'
```

Si elle ne possède pas encore de compte, elle utilise plutôt le parcours
public présenté dans la section « Premier parcours utilisateur ». L'email du
compte authentifié ou créé doit toujours correspondre à celui de l'invitation.

Les opérations Membership suivantes sont exposées :

| Méthode | Endpoint | Intention |
| --- | --- | --- |
| `GET` | `/api/members` | Lister les memberships du tenant actif |
| `GET` | `/api/members/{id}` | Consulter un membership du tenant |
| `POST` | `/api/members/{id}/suspend` | Suspendre temporairement un membre |
| `POST` | `/api/members/{id}/reactivate` | Réactiver un membre suspendu |
| `POST` | `/api/members/{id}/revoke` | Révoquer définitivement un membre |

Les lectures exigent `MEMBER_READ`. Les transitions appliquent les permissions
dédiées, protègent le dernier owner actif et incrémentent
`authorizationVersion`, ce qui invalide les anciens tokens du membre modifié.

Les opérations de rôles suivantes sont exposées :

| Méthode | Endpoint | Intention |
| --- | --- | --- |
| `GET` | `/api/roles` | Lister le catalogue des rôles disponibles |
| `POST` | `/api/members/{id}/role-assignments` | Attribuer un rôle et son scope |
| `DELETE` | `/api/members/{id}/role-assignments/{assignmentId}` | Retirer un rôle |

L'attribution accepte un `scopeType` égal à `ORGANIZATION` ou
`SELECTED_STORES`, une liste `storeIds` pour le second cas et une expiration
optionnelle. Dans le modèle actuel, un membership ne peut avoir qu'une
attribution par rôle : `assignmentId` correspond donc au `roleId` affiché dans
la ressource Membership.

L'attribution ou le retrait incrémente `authorizationVersion`. Les opérations
sur le rôle `ORGANIZATION_OWNER` appliquent en plus les protections dédiées aux
owners, notamment l'interdiction de retirer le dernier owner actif.

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
- [Planning du Lot 0](docs/planning/zandu-lot-0-architecture-executable.md) ;
- [Planning du Lot 1](docs/planning/zandu-lot-1-administration-operationnelle.md) ;
- [Planning du Lot 2](docs/planning/zandu-lot-2-catalog-basic-pricing.md) ;
- [Planning du Lot 3](docs/planning/zandu-lot-3-inventory-cash-foundations.md) ;
- [Planning du Lot 4](docs/planning/zandu-lot-4-sales-complete-sale-cash.md) ;
- [Planning du Lot 5](docs/planning/zandu-lot-5-inventory-costing-returns.md) ;
- [Spécification DDD](docs/architecture/ddd/zandu-sales-manager-ddd-v1.1.docx) ;
- [Index des décisions d'architecture](docs/architecture/adr/README.md) ;
- [Fitness tests d'architecture](docs/architecture/fitness-tests.md).

## Licence

Projet propriétaire. Aucun droit d'utilisation, de modification ou de
distribution n'est accordé en dehors des autorisations explicites du
propriétaire.
