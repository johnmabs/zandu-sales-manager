# 7. Epic 3.1 — Inventory foundation

## Objectif

Matérialiser le bounded context `Inventory` selon les frontières prévues depuis le Lot 0.

---

## Étape 3.1.1 — Vérifier / compléter la structure Inventory

Structure :

```text
src/Modules/Inventory/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Le squelette peut déjà exister depuis le Lot 0.

Ne pas recréer inutilement ce qui existe.

### Validation

- aucune dépendance `Inventory\Domain → Catalog\Domain` ;
- aucune dépendance `Inventory\Domain → Organization\Domain` ;
- aucun Doctrine/Symfony dans Domain ;
- architecture tests verts.

### Commit proposé

Si structure déjà présente :

```text
refactor(inventory): prepare inventory bounded context
```

Sinon :

```text
refactor(inventory): add bounded context structure
```

---

## Étape 3.1.2 — Vérifier le schéma PostgreSQL `inventory`

Le schéma existe potentiellement depuis le Lot 0.

Créer uniquement les tables nécessaires :

```text
inventory.stock
inventory.stock_movement
```

Ne pas créer encore :

```text
stock_transfer
stock_count
stock_count_line
stock_reservation
stock_valuation
```

Ces concepts appartiennent à des lots ultérieurs.

### Commit proposé

```text
feat(database): add inventory stock tables
```

---

## Étape 3.1.3 — Contrat Catalog pour Inventory

Inventory doit pouvoir vérifier au minimum :

```text
Product exists
Product belongs to Organization
Product is PHYSICAL
Product inventoryTracked = true
Product base unit / precision usable
```

Créer ou réutiliser un contrat public du Lot 2 :

```text
InventoryProductProvider
```

ou équivalent.

Sortie possible :

```text
InventoryProductDescriptor
├── productId
├── inventoryTracked
├── productType
├── baseUnitId
└── quantityPrecision
```

Ne pas transporter l’aggregate Product.

### Commit proposé

```text
feat(catalog): expose inventory product contract
```

---

## Definition of Done — Epic 3.1

- Inventory prêt pour implémentation métier ;
- schéma logique disponible ;
- contrat Catalog explicite ;
- aucune dépendance Domain externe ;
- architecture tests verts.

---
