# 10. Epic 3.4 — Stock initialization & adjustment

## Objectif

Introduire les deux premières opérations réelles d’Inventory.

---

## Étape 3.4.1 — `InitializeStock`

Command :

```text
InitializeStock
├── storeId
├── productId
└── quantity
```

`organizationId` et `actorId` proviennent d’`ActorContext`.

### Préconditions

- Organization opérationnelle ;
- Store opérationnel ;
- actor autorisé sur Store ;
- Product du même tenant ;
- Product `inventoryTracked = true` ;
- Product physique ;
- aucune position déjà initialisée.

### Transaction

```text
BEGIN

create/load Stock

Stock.initialize(quantity)

persist Stock

append StockMovement
type = INITIAL_STOCK
previousQuantity = 0
resultingQuantity = quantity

append audit

append outbox

COMMIT
```

### Invariant

```text
InitializeStock
→ une seule fois
```

Un second appel ne remplace jamais le stock existant.

Résultat :

```text
CONFLICT
```

ou code métier stable équivalent.

### Domain events

```text
StockInitialized
```

### Commit proposé

```text
feat(inventory): add initialize stock use case
```

---

## Étape 3.4.2 — Initialisation à zéro

Une quantité initiale :

```text
0
```

doit être explicitement décidée comme valide ou interdite selon la baseline.

Comme `StockQuantity` accepte zéro, la recommandation cohérente est :

```text
InitializeStock(0)
→ valide
```

car :

```text
stock suivi mais épuisé
```

est différent de :

```text
stock non suivi
```

Cette décision doit être couverte par test.

---

## Étape 3.4.3 — `AdjustStock`

Command :

```text
AdjustStock
├── storeId
├── productId
├── delta
└── reason
```

`delta` :

```text
> 0 → ADJUSTMENT_IN
< 0 → ADJUSTMENT_OUT
= 0 → interdit
```

### Préconditions

- stock initialisé ;
- permission `INVENTORY_ADJUST` ;
- reason obligatoire ;
- Store opérationnel ;
- Product éligible ;
- résultat non négatif.

### Exemple

```text
Stock = 10

AdjustStock(+3)
→ Stock = 13
→ ADJUSTMENT_IN quantity=3
```

```text
Stock = 10

AdjustStock(-4)
→ Stock = 6
→ ADJUSTMENT_OUT quantity=4
```

```text
Stock = 2

AdjustStock(-3)
→ InsufficientStock
→ aucun effet
```

### Domain event

```text
StockAdjusted
```

### Commit proposé

```text
feat(inventory): add stock adjustment use case
```

---

## Étape 3.4.4 — Contrat de lecture de disponibilité

Préparer pour Lot 4 :

```text
StockAvailabilityProvider
```

Entrée :

```text
OrganizationId
StoreId
ProductId
```

Sortie minimale :

```text
StockAvailability
├── productId
├── quantityOnHand
├── stockVersion
└── initialized
```

Aucune réservation dans le Lot 3.

### Commit proposé

```text
feat(inventory): expose stock availability contract
```

---

## Definition of Done — Epic 3.4

- initialisation unique ;
- initialisation exacte ;
- adjustment IN/OUT ;
- reason obligatoire ;
- stock négatif impossible ;
- mouvement créé pour chaque changement ;
- Stock + Movement + Audit + Outbox atomiques ;
- contrat de lecture futur disponible.

---
