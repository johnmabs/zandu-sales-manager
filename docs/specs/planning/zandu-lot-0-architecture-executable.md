# Zandu Sales Manager — Lot 0 : Architecture exécutable

**Version :** 1.0  
**Statut :** Backlog d’implémentation  
**Langue :** Français — identifiants de code en anglais

---

# 1. Objectif du Lot 0

Le Lot 0 construit le socle technique exécutable de Zandu Sales Manager.

Il ne vise pas encore à implémenter l’ensemble des modules métier. Son rôle est de :

- matérialiser le modular monolith dans le code ;
- rendre les frontières DDD vérifiables automatiquement ;
- mettre en place PostgreSQL, Doctrine et les migrations ;
- construire les primitives techniques partagées nécessaires ;
- rendre l’API et l’authentication opérationnelles ;
- valider les choix sensibles par des spikes reproductibles ;
- mettre en place un socle minimal d’exploitation et d’observabilité ;
- produire les ADR ou mises à jour d’ADR découlant des résultats.

Le Lot 0 est terminé uniquement lorsque son gate de sortie est satisfait.

---

# 2. Règle de commits

Le développement du Lot 0 suit une logique de **commits atomiques**.

Un commit doit :

- représenter une seule intention cohérente ;
- laisser le repository dans un état valide ;
- ne pas mélanger refactoring, feature et configuration sans nécessité ;
- inclure les tests directement liés à la modification ;
- éviter les commits du type `misc`, `changes`, `fix stuff` ou `wip` dans l’historique final.

## 2.1 Convention de message

Format recommandé :

```text
<type>(<scope>): <description>
```

Types principaux :

```text
feat      nouvelle capacité
fix       correction
refactor  restructuration sans changement fonctionnel
test      ajout ou adaptation de tests
build     système de build / dépendances
ci        pipeline CI
chore     maintenance technique
docs      documentation
perf      optimisation
```

Exemples :

```text
build(backend): initialize Symfony application
chore(docker): add local PostgreSQL service
test(architecture): enforce Domain dependency rules
feat(identity): add UUID v7 generator abstraction
feat(api): add correlation id middleware
```

Les messages restent en anglais, comme les identifiants de code.

---

# 3. Vue d’ensemble

```text
Epic 0.1 — Repository foundation
       ↓
Epic 0.2 — Architecture fitness tests
       ↓
Epic 0.3 — Persistence foundation
       ↓
Epic 0.4 — SharedKernel foundation
       ↓
Epic 0.5 — API foundation
       ↓
Epic 0.6 — Authentication foundation
       ↓
Epic 0.7 — Architectural spikes
       ↓
Epic 0.8 — Operations & observability
       ↓
Lot 0 Gate
```

---

# 4. Epic 0.1 — Initialisation du repository backend

## Objectif

Obtenir un projet Symfony exécutable, reproductible et prêt à accueillir l’architecture modulaire.

## Étape 0.1.1 — Initialiser Symfony

Créer le projet backend PHP/Symfony sans introduire prématurément les modules métier.

### Résultat attendu

```text
backend/
├── bin/
├── config/
├── public/
├── src/
├── tests/
├── composer.json
└── symfony.lock
```

### Validation

- Symfony démarre ;
- la configuration de test fonctionne ;
- aucune logique métier n’est encore introduite.

### Commit proposé

```text
build(backend): initialize Symfony application
```

---

## Étape 0.1.2 — Définir la structure racine DDD

Créer :

```text
src/
├── Modules/
├── SharedKernel/
└── Platform/
```

Créer également un fichier de documentation ou des placeholders nécessaires pour conserver les dossiers vides.

### Commit proposé

```text
refactor(architecture): introduce modular monolith root structure
```

---

## Étape 0.1.3 — Ajouter les premières structures de module

Créer uniquement les modules requis pour les premiers vertical slices et spikes :

```text
src/Modules/
├── Sales/
├── Inventory/
└── CashManagement/
```

Chaque module :

```text
Module/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Ne pas créer artificiellement tous les futurs bounded contexts si aucun code ne les utilise encore.

### Commit proposé

```text
refactor(modules): add initial bounded context skeletons
```

---

## Étape 0.1.4 — Configurer les namespaces

Convention :

```text
Zandu\Modules\Sales\...
Zandu\Modules\Inventory\...
Zandu\Modules\CashManagement\...
Zandu\SharedKernel\...
Zandu\Platform\...
```

Vérifier l’autoload PSR-4.

### Commit proposé

```text
build(autoload): configure Zandu namespaces
```

---

## Étape 0.1.5 — Ajouter Docker pour le backend

Créer l’image de développement/production selon une structure compatible avec l’ADR Docker.

La même image backend doit pouvoir exécuter à terme :

```text
API
Worker
Scheduled Job
Migration
```

### Commit proposé

```text
build(docker): add backend container image
```

---

## Étape 0.1.6 — Ajouter PostgreSQL local

Ajouter PostgreSQL à `docker-compose.yml` / `compose.yaml`.

Ne pas ajouter de base alternative SQLite côté serveur.

### Validation

- conteneur PostgreSQL healthy ;
- backend joignable ;
- configuration par variables d’environnement.

### Commit proposé

```text
chore(database): add local PostgreSQL service
```

---

## Étape 0.1.7 — Ajouter le bootstrap développeur

Prévoir une commande simple de bootstrap, par exemple via `Makefile`, `justfile` ou scripts Composer.

Exemples de capacités :

```text
install
start
stop
test
lint
database-create
database-migrate
```

Ne pas sur-construire l’outillage.

### Commit proposé

```text
chore(dev): add local development commands
```

---

## Definition of Done — Epic 0.1

- le repository démarre depuis une machine propre avec les prérequis documentés ;
- Symfony fonctionne ;
- PostgreSQL fonctionne ;
- la structure `Modules / SharedKernel / Platform` existe ;
- les premiers bounded contexts ont leur squelette ;
- l’autoload est valide ;
- un test minimal passe ;
- aucune dépendance métier cross-context n’a été introduite.

---

# 5. Epic 0.2 — Architecture fitness tests

## Objectif

Transformer les règles DDD en contraintes automatiquement vérifiables.

## Étape 0.2.1 — Ajouter l’outil de test d’architecture

Choisir et intégrer un outil adapté à PHP permettant de tester les dépendances de namespaces.

### Commit proposé

```text
build(architecture): add dependency rule tooling
```

---

## Étape 0.2.2 — Protéger le Domain

Règles minimales :

```text
Domain
→ SharedKernel
```

Interdictions :

```text
Domain
✗ Symfony
✗ Doctrine
✗ API Platform
✗ Infrastructure
✗ Presentation
```

### Commit proposé

```text
test(architecture): enforce Domain dependency rules
```

---

## Étape 0.2.3 — Protéger les bounded contexts

Interdire :

```text
Sales
→ Inventory\Domain
Sales
→ Inventory\Infrastructure
```

Autoriser uniquement les contrats publics prévus :

```text
Sales
→ Inventory\Application\Contract
```

### Commit proposé

```text
test(architecture): enforce bounded context boundaries
```

---

## Étape 0.2.4 — Protéger les couches

Formaliser les dépendances autorisées entre :

```text
Domain
Application
Infrastructure
Presentation
```

### Commit proposé

```text
test(architecture): enforce application layer boundaries
```

---

## Definition of Done — Epic 0.2

- une violation volontaire fait échouer les tests ;
- les dépendances cross-context non autorisées sont détectées ;
- les règles sont documentées ;
- les tests tournent dans la CI.

---

# 6. Epic 0.3 — Persistence foundation

## Objectif

Rendre PostgreSQL et Doctrine utilisables conformément aux décisions d’architecture.

## Étape 0.3.1 — Installer Doctrine ORM, DBAL et Migrations

### Commit proposé

```text
build(persistence): add Doctrine ORM DBAL and migrations
```

---

## Étape 0.3.2 — Configurer PostgreSQL

Configurer :

- connexion ;
- environnement test ;
- conventions de schéma ;
- types nécessaires.

### Commit proposé

```text
chore(persistence): configure PostgreSQL integration
```

---

## Étape 0.3.3 — Créer les schémas initiaux

Créer uniquement les schémas nécessaires aux premiers spikes :

```text
sales
inventory
cash_management
platform
```

### Commit proposé

```text
feat(database): add initial bounded context schemas
```

---

## Étape 0.3.4 — Ajouter `TransactionManager`

Contrat côté application / kernel technique selon l’organisation retenue :

```text
TransactionManager
```

Implémentation :

```text
DoctrineTransactionManager
```

La transaction physique peut coordonner plusieurs bounded contexts du modular monolith sans autoriser l’accès direct à leurs repositories internes.

### Commit proposé

```text
feat(persistence): add application transaction manager
```

---

## Étape 0.3.5 — Définir les conventions de repository

- un repository par aggregate root lorsque nécessaire ;
- pas de `GenericRepository` métier ;
- interfaces définies du côté qui en a besoin ;
- infrastructure Doctrine derrière les interfaces.

### Commit proposé

```text
docs(persistence): define repository conventions
```

---

## Étape 0.3.6 — Ajouter les tests PostgreSQL réels

Les tests d’intégration importants utilisent le même moteur que la production.

### Commit proposé

```text
test(persistence): add real PostgreSQL integration tests
```

---

## Definition of Done — Epic 0.3

- migrations exécutables ;
- PostgreSQL réel utilisé en intégration ;
- transaction explicite fonctionnelle ;
- rollback prouvé ;
- conventions repository documentées.

---

# 7. Epic 0.4 — SharedKernel foundation

## Objectif

Construire seulement les primitives réellement nécessaires aux premiers use cases.

---

## Étape 0.4.1 — UUID abstraction

Créer :

```text
Uuid
UuidFactory
IdGenerator
```

### Commit proposé

```text
feat(identity): add UUID abstraction
```

---

## Étape 0.4.2 — UUID v7 Symfony implementation

Dans `Platform` :

```text
SymfonyUuid
SymfonyUuidFactory
SymfonyUuidV7Generator
```

### Commit proposé

```text
feat(identity): implement UUID v7 with Symfony UID
```

---

## Étape 0.4.3 — Typed IDs

Introduire uniquement les IDs nécessaires au Lot 0 :

```text
OrganizationId
StoreId
ProductId
SaleId
StockId
CashSessionId
```

### Commit proposé

```text
feat(identity): add initial typed domain identifiers
```

---

## Étape 0.4.4 — Decimal abstraction

Créer :

```text
Decimal
DecimalFactory
RoundingMode
```

sans exposer `brick/math`.

### Commit proposé

```text
feat(decimal): add exact decimal abstraction
```

---

## Étape 0.4.5 — brick/math implementation

### Commit proposé

```text
feat(decimal): implement decimal operations with brick math
```

---

## Étape 0.4.6 — Money et Currency

Créer les invariants de base :

- montant exact ;
- currency explicite ;
- opérations incompatibles interdites ;
- arrondi explicite.

### Commit proposé

```text
feat(money): add Money and Currency value objects
```

---

## Étape 0.4.7 — Quantity

Créer `Quantity` sans figer prématurément la précision métier finale.

### Commit proposé

```text
feat(quantity): add exact Quantity value object
```

---

## Étape 0.4.8 — Primitives d’exécution

Ajouter selon besoin :

```text
Clock
ActorContext
CorrelationId
CausationId
IdempotencyKey
DomainError
Result
```

Éviter un commit massif si ces concepts sont indépendants. Préférer plusieurs commits atomiques si l’implémentation devient significative.

### Commits proposés

```text
feat(time): add Clock abstraction
feat(context): add ActorContext
feat(messaging): add correlation and causation identifiers
feat(idempotency): add IdempotencyKey
feat(error): add DomainError and Result primitives
```

---

## Definition of Done — Epic 0.4

- aucune dépendance Symfony dans les contrats du `SharedKernel` ;
- aucune dépendance Brick exposée ;
- UUID v7 testé ;
- `Decimal` exact testé ;
- `Money` et `Quantity` testés ;
- aucun `float` dans ces primitives.

---

# 8. Epic 0.5 — API foundation

## Objectif

Rendre le flux HTTP → Application exécutable sans exposer le modèle métier.

## Étape 0.5.1 — Installer API Platform

### Commit proposé

```text
build(api): add API Platform and OpenAPI
```

---

## Étape 0.5.2 — Définir la convention `StateProcessor`

Prouver :

```text
HTTP
→ StateProcessor
→ Application Command
→ Result
```

### Commit proposé

```text
feat(api): add application command processor pattern
```

---

## Étape 0.5.3 — Définir la convention `StateProvider`

Prouver :

```text
HTTP
→ StateProvider
→ Application Query
→ Read Model
```

### Commit proposé

```text
feat(api): add application query provider pattern
```

---

## Étape 0.5.4 — Normaliser les erreurs

Format minimal :

```json
{
  "code": "STABLE_ERROR_CODE",
  "message": "Human-readable message",
  "correlationId": "..."
}
```

Le client ne doit jamais prendre une décision métier à partir du texte de `message`.

### Commit proposé

```text
feat(api): add stable application error responses
```

---

## Étape 0.5.5 — Correlation ID HTTP

Créer ou propager un `CorrelationId` par requête.

### Commit proposé

```text
feat(api): add correlation id propagation
```

---

## Étape 0.5.6 — Idempotency-Key foundation

Mettre en place le socle HTTP nécessaire aux futures commands critiques.

### Commit proposé

```text
feat(api): add idempotency key request handling
```

---

## Definition of Done — Epic 0.5

- OpenAPI généré ;
- processor et provider testés ;
- erreurs stables ;
- correlation propagée ;
- aucune entity Doctrine directement utilisée comme ressource métier critique.

---

# 9. Epic 0.6 — Authentication foundation

## Objectif

Fournir une authentication MVP compatible avec le futur modèle d’authorization.

## Étape 0.6.1 — Symfony Security

### Commit proposé

```text
build(auth): configure Symfony Security
```

---

## Étape 0.6.2 — JWT access token

Installer et configurer Lexik JWT.

### Commit proposé

```text
feat(auth): add JWT access token authentication
```

---

## Étape 0.6.3 — Refresh token session

Créer une session de refresh stateful avec persistance serveur.

### Commit proposé

```text
feat(auth): add stateful refresh token sessions
```

---

## Étape 0.6.4 — Rotation des refresh tokens

Un refresh token utilisé est remplacé. La réutilisation d’un token obsolète doit être détectable.

### Commit proposé

```text
feat(auth): rotate refresh tokens on use
```

---

## Étape 0.6.5 — Révocation

Supporter logout/révocation de session.

### Commit proposé

```text
feat(auth): add refresh session revocation
```

---

## Étape 0.6.6 — ActorContext

Construire `ActorContext` depuis l’identité authentifiée ; ne jamais faire confiance à un `actorId` fourni arbitrairement dans le payload métier.

### Commit proposé

```text
feat(auth): resolve ActorContext from authenticated identity
```

---

## Definition of Done — Epic 0.6

- login valide/invalide testé ;
- access token fonctionnel ;
- refresh fonctionnel ;
- rotation prouvée ;
- réutilisation interdite selon la stratégie retenue ;
- révocation fonctionnelle ;
- `ActorContext` dérivé côté serveur.

---

# 10. Epic 0.7 — Spikes architecturaux

Les spikes ne sont pas des prétextes pour produire du code métier final. Ils doivent être reproductibles, testés et documentés.

---

## Spike A — `CompleteSale`

### Objectif

Prouver une transaction locale atomique impliquant :

```text
Sale
Stock
StockMovement
CashSession
CashMovement
OutboxMessage
```

### Étapes / commits proposés

```text
test(spike-a): add CompleteSale transaction fixture
test(spike-a): inject inventory failure before commit
test(spike-a): inject cash failure before commit
test(spike-a): inject outbox failure before commit
docs(spike-a): document CompleteSale transaction results
```

Si une modification architecturale est nécessaire :

```text
docs(adr): update transaction coordination decision
```

### Succès

```text
failure before commit
→ zero partial business effect
```

---

## Spike B — Outbox

### Objectif

Prouver :

```text
at-least-once
multi-worker safety
retry
crash recovery
consumer idempotence
dead letter
```

### Commits proposés

```text
feat(outbox): add transactional outbox prototype
test(outbox): verify multi-worker message claiming
test(outbox): verify redelivery after worker crash
test(outbox): verify idempotent consumer processing
docs(spike-b): document outbox validation results
```

---

## Spike C — Decimal / Money / Quantity

### Objectif

Tester :

```text
JSON string
→ Decimal
→ Value Object
→ Doctrine
→ PostgreSQL NUMERIC
→ Value Object
```

Corpus :

```text
unit
kg
g
liter
meter
carton
fractional packaging
```

Inclure :

```text
taxes
discounts
conversion factors
allocation residues
costing
refunds
```

### Commits proposés

```text
test(decimal): add persistence round-trip cases
test(quantity): add real product precision corpus
test(money): add tax and discount rounding cases
test(costing): add intermediate precision cases
docs(spike-c): record numeric precision conclusions
```

Puis, après décision :

```text
docs(adr): record Money and Quantity persistence precision
```

---

## Spike E — Stock concurrency

### Scénario minimal

```text
Stock = 5

Transaction A consumes 4
Transaction B consumes 3
```

Comparer :

```text
optimistic locking
conditional DBAL update
```

### Commits proposés

```text
test(stock): add concurrent stock consumption scenario
test(stock): benchmark optimistic locking strategy
test(stock): benchmark conditional update strategy
docs(spike-e): record stock concurrency decision
```

Si une stratégie est retenue :

```text
docs(adr): record stock concurrency persistence strategy
```

### Invariant absolu

```text
quantityOnHand >= 0
```

---

# 11. Epic 0.8 — Operations & observability

## Étape 0.8.1 — Structured logging

### Commit proposé

```text
feat(observability): add structured application logging
```

---

## Étape 0.8.2 — OpenTelemetry

Instrumenter progressivement HTTP, application workflows, persistence, outbox et workers.

### Commit proposé

```text
feat(observability): add OpenTelemetry instrumentation
```

---

## Étape 0.8.3 — Health endpoints

```text
/health/live
/health/ready
```

### Commit proposé

```text
feat(operations): add liveness and readiness endpoints
```

---

## Étape 0.8.4 — Graceful shutdown

API et workers doivent terminer proprement leurs traitements selon les contraintes définies.

### Commit proposé

```text
feat(operations): add graceful process shutdown
```

---

## Étape 0.8.5 — Métriques workers et outbox

Au minimum :

```text
outbox_pending_count
outbox_oldest_pending_age
outbox_publish_failures
worker_retry_count
dead_letter_count
```

Les valeurs outbox/worker sont calculées depuis l'état persistant PostgreSQL,
et non depuis la mémoire locale d'un processus.

### Commit proposé

```text
feat(observability): add worker and outbox metrics
```

---

## Spike G — Infrastructure

Tester :

```text
Docker image
staging deployment
PostgreSQL
controlled migration
worker
health checks
OpenTelemetry export
backup
restore
```

### Commits proposés

```text
ci(deploy): add staging deployment pipeline
chore(deploy): add controlled migration step
test(operations): verify deployment health checks
test(backup): verify PostgreSQL restore procedure
docs(spike-g): document infrastructure validation
```

Si Render est validé :

```text
docs(adr): record initial deployment platform
```

Si Grafana Cloud est validé :

```text
docs(adr): record initial observability backend
```

---

# 12. CI minimale du Lot 0

Le pipeline doit évoluer progressivement avec les Epics.

Ordre cible :

```text
composer validate
↓
coding standards
↓
static analysis
↓
architecture tests
↓
unit tests
↓
PostgreSQL integration tests
↓
selected concurrency tests
↓
build Docker image
↓
dependency/security checks
```

Commits atomiques possibles :

```text
ci(quality): add Composer validation
ci(quality): add coding standard checks
ci(quality): add static analysis
ci(test): add unit and integration test jobs
ci(architecture): run architecture fitness tests
ci(build): build backend Docker image
```

---

# 13. Gate de sortie du Lot 0

Le Lot 0 est `DONE` uniquement lorsque :

```text
[x] Symfony backend exécutable
[x] Modular monolith matérialisé
[x] Architecture fitness tests actifs
[x] PostgreSQL opérationnel
[x] Doctrine ORM / DBAL / Migrations opérationnels
[x] TransactionManager validé
[x] UUID v7 abstraction et implémentation validées
[x] Decimal abstraction validée
[x] Money et Quantity disponibles
[x] API Platform + OpenAPI opérationnels
[x] Error contract stable
[x] CorrelationId propagé
[x] Idempotency-Key foundation disponible
[x] JWT access token opérationnel
[x] Refresh token rotation opérationnelle
[x] ActorContext dérivé de l’identité authentifiée
[x] Spike A conclu
[x] Spike B conclu
[x] Spike C conclu
[x] Spike E conclu
[x] décisions numériques nécessaires documentées
[x] Docker production image validée
[x] OpenTelemetry opérationnel
[x] liveness/readiness opérationnels
[x] staging validé
[x] backup restore testé
[x] ADR concernés mis à jour
```

---

# 14. Principe de travail pour la suite

Pour chaque étape d’implémentation :

1. préciser l’objectif ;
2. limiter le changement au strict nécessaire ;
3. écrire ou adapter les tests ;
4. exécuter les validations locales ;
5. effectuer un commit atomique ;
6. seulement ensuite passer à l’étape suivante.

Le backlog peut évoluer à mesure que les spikes produisent de nouvelles informations, mais toute modification d’une décision marquée `DÉCIDÉ` doit être traitée comme une nouvelle décision architecturale et documentée par ADR.
