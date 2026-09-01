# 12. Epic 3.6 — Cash Management foundation

## Objectif

Préparer le bounded context `CashManagement`.

---

## Étape 3.6.1 — Vérifier / compléter le module

Structure :

```text
src/Modules/CashManagement/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Le squelette existe potentiellement depuis le Lot 0.

### Commit proposé

```text
refactor(cash): prepare cash management bounded context
```

---

## Étape 3.6.2 — Schéma PostgreSQL

Réutiliser :

```text
cash_management
```

Créer :

```text
cash_register
cash_session
cash_movement
```

Ne pas créer encore :

```text
payment
payment_attempt
settlement
customer_receivable
```

### Commit proposé

```text
feat(database): add cash management tables
```

---

## Definition of Done — Epic 3.6

- module prêt ;
- schéma prêt ;
- aucune logique Payment/Sales ;
- architecture tests verts.

---
