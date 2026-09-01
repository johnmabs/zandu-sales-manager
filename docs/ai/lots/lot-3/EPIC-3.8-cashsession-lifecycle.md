# 14. Epic 3.8 — CashSession lifecycle

## Objectif

Représenter la responsabilité d’un caissier sur un register pendant une période déterminée.

---

## Étape 3.8.1 — Aggregate `CashSession`

Modèle :

```text
CashSession
├── CashSessionId
├── OrganizationId
├── StoreId
├── CashRegisterId
├── cashierId
├── Money openingBalance
├── openedAt
├── status
├── Money countedClosingBalance?
├── Money expectedClosingBalance?
├── Money discrepancy?
├── closedAt?
├── closedBy?
└── Version
```

Statuts minimaux :

```text
OPEN
CLOSED
```

N’ajouter d’état intermédiaire que si nécessaire.

### Invariants

```text
openingBalance >= 0
```

selon la politique MVP retenue.

```text
CashRegister
→ max 1 CashSession OPEN
```

```text
CLOSED
→ terminal
```

Après fermeture :

- pas de nouveau CashMovement ;
- pas de modification des montants métier ;
- seulement métadonnées d’audit explicitement autorisées.

### Domain events

```text
CashSessionOpened
CashSessionClosed
```

### Commit proposé

```text
feat(cash): add cash session aggregate
```

---

## Étape 3.8.2 — Persistence CashSession

Ajouter :

- mapping ;
- migration ;
- repository ;
- RLS ;
- index register/status ;
- version ;
- contrainte garantissant une seule session ouverte.

PostgreSQL doit renforcer l’invariant.

Une approche possible :

```text
partial unique index
WHERE status = 'OPEN'
```

sur :

```text
organization_id
cash_register_id
```

si compatible avec la stratégie de mapping.

### Commit proposé

```text
feat(cash): persist cash sessions
```

---

## Étape 3.8.3 — `OpenCashSession`

Command externe :

```text
OpenCashSession
├── cashRegisterId
└── openingBalance
```

Le serveur dérive :

```text
organizationId
actorId
```

et résout :

```text
storeId
cashierId
```

selon contexte.

### Préconditions

- Organization opérationnelle ;
- Store opérationnel ;
- register actif ;
- register appartient au Store ;
- actor autorisé sur Store ;
- actor autorisé à ouvrir une caisse ;
- aucune session OPEN ;
- currency cohérente avec Store.

### Transaction

```text
BEGIN

check register
check open session invariant

create CashSession

audit

outbox CashSessionOpened

COMMIT
```

### Commit proposé

```text
feat(cash): add open cash session use case
```

---

## Étape 3.8.4 — Calcul du montant attendu

Formule de base :

```text
expectedCash
=
openingBalance
+
sum(IN CashMovement)
-
sum(OUT CashMovement)
```

Seuls les mouvements validés participent.

Au Lot 3, les types effectifs sont principalement manuels.

Le futur Lot 4 ajoutera :

```text
SALE_PAYMENT
```

sans changer le principe.

Ne pas stocker un solde mutable secondaire sans nécessité.

La stratégie préférée doit éviter deux sources de vérité.

### Commit proposé

```text
feat(cash): calculate expected cash balance
```

---

## Étape 3.8.5 — `CloseCashSession`

Command :

```text
CloseCashSession
├── cashSessionId
└── countedClosingBalance
```

### Calcul

```text
expectedClosingBalance
=
openingBalance + movements
```

```text
discrepancy
=
countedClosingBalance
-
expectedClosingBalance
```

### Préconditions

- session OPEN ;
- actor autorisé ;
- même tenant ;
- montant compté exact ;
- aucun mouvement concurrent non pris en compte au moment du commit.

### Résultat

```text
status = CLOSED
```

puis session immuable.

### Domain event

```text
CashSessionClosed
```

### Commit proposé

```text
feat(cash): add close cash session use case
```

---

## Étape 3.8.6 — Gestion des écarts

Le Lot 3 doit au minimum calculer et conserver :

```text
discrepancy
```

Si la baseline/permission catalog prévoit un seuil nécessitant approbation, implémenter uniquement la politique déjà décidée.

Ne pas inventer un workflow d’approbation complet non spécifié.

L’écart reste visible et auditable.

Il n’est jamais corrigé silencieusement par création automatique d’un mouvement.

---

## Definition of Done — Epic 3.8

- CashSession opérationnelle ;
- une seule OPEN par register ;
- ouverture testée ;
- fermeture testée ;
- expected balance calculé ;
- discrepancy calculé ;
- CLOSED immuable ;
- tests concurrence ouverture/fermeture ;
- persistence PostgreSQL ;
- RLS.

---
