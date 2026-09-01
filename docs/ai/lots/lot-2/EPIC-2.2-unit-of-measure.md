# 7. Epic 2.2 — Unit of measure

## Objectif

Fournir les unités utilisées par les produits et conditionnements.

---

## Étape 2.2.1 — Ajouter `UnitOfMeasure`

Modèle cible :

```text
UnitOfMeasure
├── UnitOfMeasureId
├── OrganizationId?
├── code
├── name
├── dimension
├── precision
├── roundingMode
├── status
└── Version
```

Dimensions :

```text
COUNT
MASS
VOLUME
LENGTH
TIME
OTHER
```

Statuts :

```text
ACTIVE
INACTIVE
```

Le choix exact entre unités système globales et unités tenant-owned doit suivre la décision déjà présente dans la baseline/ADR.

Ne pas inventer un modèle hybride implicitement.

Si aucune décision définitive n’existe pour ce point, commencer avec le minimum nécessaire au MVP et documenter toute décision structurante.

### Invariants

- `code` non vide ;
- `name` non vide ;
- precision valide ;
- rounding mode valide ;
- aucune quantité n’est arrondie silencieusement ;
- une unité inactive ne peut pas être choisie pour un nouveau produit/packaging.

### Domain events

```text
UnitOfMeasureCreated
UnitOfMeasureUpdated
UnitOfMeasureActivated
UnitOfMeasureDeactivated
```

### Commit proposé

```text
feat(catalog): add unit of measure model
```

---

## Étape 2.2.2 — Persistence `UnitOfMeasure`

Ajouter :

- mapping Doctrine ;
- repository ;
- migration ;
- contraintes d’unicité pertinentes ;
- optimistic locking si nécessaire ;
- tests PostgreSQL réels.

### Commit proposé

```text
feat(catalog): persist units of measure
```

---

## Étape 2.2.3 — Use cases UnitOfMeasure

Ajouter uniquement les opérations nécessaires :

```text
CreateUnitOfMeasure
UpdateUnitOfMeasure
ActivateUnitOfMeasure
DeactivateUnitOfMeasure
```

Ne pas créer de PATCH métier générique capable de modifier `status`.

### Commits proposés

```text
feat(catalog): add unit of measure management
```

---

## Definition of Done — Epic 2.2

- modèle UnitOfMeasure disponible ;
- précision testée ;
- persistence PostgreSQL réelle ;
- transitions testées ;
- aucune utilisation de float ;
- audit/outbox intégrables ;
- tests architecture verts.

---
