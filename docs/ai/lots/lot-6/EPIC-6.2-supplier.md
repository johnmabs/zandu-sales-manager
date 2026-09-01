# 8. Epic 6.2 — Supplier

Le fournisseur appartient à l’organisation et son nom suffit pour démarrer.

Modèle minimal :

```text
Supplier
├── SupplierId
├── OrganizationId
├── name
├── phone?
├── email?
├── address?
├── notes?
├── status
├── createdAt
├── createdBy
└── Version
```

Statuts :

```text
ACTIVE
INACTIVE
ARCHIVED
```

Les coordonnées sont facultatives.

Un fournisseur archivé reste référencé dans les documents historiques.

Use cases :

```text
CreateSupplier
UpdateSupplier
ActivateSupplier
DeactivateSupplier
ArchiveSupplier
```

Pas de suppression historique.

Commits :

```text
feat(purchasing): add supplier aggregate
feat(purchasing): persist suppliers
feat(purchasing): add supplier management
```

---
