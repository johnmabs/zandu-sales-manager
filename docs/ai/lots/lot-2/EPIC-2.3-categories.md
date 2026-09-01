# 8. Epic 2.3 — Categories

## Objectif

Permettre l’organisation simple du catalogue.

---

## Étape 2.3.1 — Ajouter l’aggregate `Category`

Modèle :

```text
Category
├── CategoryId
├── OrganizationId
├── name
├── parentCategoryId?
├── status
├── createdAt
├── createdBy
├── updatedAt?
├── updatedBy?
└── Version
```

Statuts :

```text
ACTIVE
INACTIVE
ARCHIVED
```

### Invariants

- nom obligatoire ;
- tenant immutable ;
- une catégorie parent appartient au même tenant ;
- une catégorie ne peut pas être son propre parent ;
- aucun cycle hiérarchique ;
- `ARCHIVED` n’est plus sélectionnable pour un nouveau produit ;
- une catégorie archivée reste résolvable pour l’historique.

### Domain events

```text
CategoryCreated
CategoryUpdated
CategoryMoved
CategoryActivated
CategoryDeactivated
CategoryArchived
```

### Commit proposé

```text
feat(catalog): add category aggregate
```

---

## Étape 2.3.2 — Persistence Category

Ajouter :

- Doctrine mapping ;
- repository ;
- migration ;
- index tenant ;
- contraintes ;
- RLS si tenant-owned ;
- tests PostgreSQL.

Toutes les lectures utilisent au minimum :

```text
organizationId + categoryId
```

### Commit proposé

```text
feat(catalog): persist categories
```

---

## Étape 2.3.3 — Use cases Category

Ajouter :

```text
CreateCategory
UpdateCategory
MoveCategory
ActivateCategory
DeactivateCategory
ArchiveCategory
```

Le contrôle de cycle hiérarchique doit être robuste.

### Commit proposé

```text
feat(catalog): add category management
```

---

## Definition of Done — Epic 2.3

- aggregate `Category` opérationnel ;
- cycles interdits ;
- lifecycle testé ;
- tenant isolation vérifiée ;
- persistence PostgreSQL réelle ;
- RLS appliqué si nécessaire ;
- événements disponibles ;
- architecture tests verts.

---
