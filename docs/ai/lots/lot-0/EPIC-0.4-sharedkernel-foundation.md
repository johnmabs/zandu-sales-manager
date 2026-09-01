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
