# 11. Epic 3.5 — Stock concurrency & idempotence

## Objectif

Prouver que deux opérations concurrentes ne peuvent ni perdre une mise à jour ni produire un stock négatif.

---

## Étape 3.5.1 — Appliquer la stratégie issue du Spike E

Avant implémentation finale, vérifier l’ADR produit par le Spike E.

Options historiques étudiées :

```text
optimistic locking
conditional DBAL update
hybrid strategy
```

Ne pas choisir une nouvelle stratégie sans vérifier la décision réelle déjà enregistrée.

### Exemple critique

```text
Stock = 5

Transaction A
consume 4

Transaction B
consume 3
```

Résultat obligatoire :

```text
une seule consommation compatible réussit
```

et jamais :

```text
quantityOnHand = -2
```

### Commit proposé

Selon ADR :

```text
feat(inventory): enforce stock concurrency strategy
```

---

## Étape 3.5.2 — Tests PostgreSQL concurrents

Utiliser de vraies connexions/transactions parallèles.

Tester :

```text
adjust-out concurrent
```

et préparer le scénario de consommation future.

Cas :

```text
Stock = 5

A → -4
B → -3
```

Résultat :

```text
final stock >= 0
```

et somme des mouvements cohérente.

### Commit proposé

```text
test(inventory): verify concurrent stock updates
```

---

## Étape 3.5.3 — Idempotence

Toute opération externe susceptible d’être rejouée doit pouvoir porter :

```text
Idempotency-Key
```

ou une source unique.

Pour `StockMovement`, la logique future suit :

```text
movementType
+
sourceReference
+
productId
```

Le Lot 3 doit au minimum rendre :

```text
InitializeStock
AdjustStock
```

compatibles avec la stratégie globale d’idempotence lorsqu’elles sont exposées via un point d’entrée retryable.

Un retry ne doit jamais produire deux mouvements.

### Commit proposé

```text
feat(inventory): enforce stock operation idempotence
```

---

## Definition of Done — Epic 3.5

- stratégie ADR appliquée ;
- tests de concurrence réels ;
- aucun lost update ;
- aucun stock négatif ;
- idempotence prouvée ;
- ledger cohérent après concurrence.

---
