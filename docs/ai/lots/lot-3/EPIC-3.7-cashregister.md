# 13. Epic 3.7 — CashRegister

## Objectif

Créer la caisse durable d’un store.

---

## Étape 3.7.1 — Aggregate `CashRegister`

Modèle :

```text
CashRegister
├── CashRegisterId
├── OrganizationId
├── StoreId
├── code
├── name
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

ou statuts équivalents déjà décidés.

### Invariants

- appartient exactement à un Store ;
- tenant immutable ;
- store immutable ;
- code unique dans le store ou tenant selon décision ;
- register inactif n’accepte pas de nouvelle session ;
- register avec session ouverte ne peut pas être archivé ;
- historique conservé.

### Domain events

```text
CashRegisterCreated
CashRegisterUpdated
CashRegisterActivated
CashRegisterDeactivated
CashRegisterArchived
```

### Commit proposé

```text
feat(cash): add cash register aggregate
```

---

## Étape 3.7.2 — Persistence CashRegister

Ajouter :

- mapping ;
- migration ;
- repository ;
- contraintes uniques ;
- RLS ;
- indexes ;
- tests PostgreSQL.

Repository tenant/store-safe.

### Commit proposé

```text
feat(cash): persist cash registers
```

---

## Étape 3.7.3 — Use cases CashRegister

Créer :

```text
CreateCashRegister
UpdateCashRegister
ActivateCashRegister
DeactivateCashRegister
ArchiveCashRegister
```

Pas de suppression métier.

### Commit proposé

```text
feat(cash): add cash register management
```

---

## Definition of Done — Epic 3.7

- CashRegister opérationnel ;
- lifecycle testé ;
- tenant/store safe ;
- persistence réelle ;
- RLS ;
- aucune suppression historique.

---
