# Zandu Sales Manager — Lot 7 : StockTransfer & StockCount

**Version :** 1.0  
**Statut :** Backlog d’implémentation  
**Langue :** Français — identifiants de code en anglais

---
# 1. Objectif du Lot 7

Le Lot 7 termine le jalon :

```text
M3 — Gestion complète du stock
```

Il ajoute les deux derniers workflows opérationnels indispensables au premier MVP commercial recommandé :

```text
StockTransfer
+
StockCount
```

À la sortie du Lot 7, Zandu doit pouvoir :

- transférer du stock entre deux stores d’une même organization ;
- créer un transfert en `DRAFT` ;
- expédier atomiquement toutes ses lignes ;
- produire des `TRANSFER_OUT` au store source ;
- conserver les quantités demandées, expédiées et reçues ;
- représenter explicitement le stock en transit sans créer de troisième stock physique ;
- réceptionner une seule fois le transfert au store destination ;
- produire des `TRANSFER_IN` uniquement pour les quantités effectivement reçues ;
- conserver les écarts de réception sans créer artificiellement du stock ;
- transporter la valeur économique du store source vers le store destination ;
- conserver la valeur d’un écart comme perte potentielle en transit ;
- empêcher tout double mouvement via idempotence ;
- réaliser un inventaire physique complet ou partiel ;
- capturer un snapshot `expectedQuantity` à l’ouverture ;
- verrouiller uniquement les produits inclus dans le comptage ;
- permettre plusieurs opérateurs sur des lignes différentes ;
- enregistrer `countedQuantity`, y compris zéro ;
- distinguer `null` de zéro ;
- finaliser un inventaire via `FINALIZING` ;
- réconcilier les lignes par lots transactionnels et idempotents ;
- produire `STOCK_COUNT_CORRECTION_IN` / `OUT` lorsque nécessaire ;
- ne produire aucun mouvement pour une variance nulle ;
- reprendre après crash uniquement les lignes encore `PENDING` ;
- valoriser les corrections de comptage ;
- bloquer les opérations incompatibles pendant `OPEN` et `FINALIZING` ;
- fournir les blockers définitifs à `StoreClosure` ;
- appliquer permissions, scopes, audit, RLS, idempotence et concurrence ;
- exposer les APIs correspondantes ;
- clôturer formellement M3.

Le Lot 7 ne doit pas introduire :

```text
Customers
Customer Credit
Payment providers
StockReservation
Reporting avancé
Offline
LotTracking
StockLot
Advanced warehouse management
```

Après ce Lot, le backend du MVP commercial de base est suffisamment structuré pour commencer le frontend Admin et POS.

---
# 2. Position dans la roadmap

```text
Lot 5
Inventory Costing & Returns
        ↓
Lot 6
Purchasing & Goods Receipts
        ↓
Lot 7
StockTransfer & StockCount
        ↓
M3
Gestion complète du stock
        ↓
Frontend
Admin + POS
```

---
# 3. Responsabilité Inventory

`StockTransfer` et `StockCount` appartiennent à `Inventory`.

Inventory possède déjà :

```text
Stock
StockMovement
```

Le Lot 7 complète cette responsabilité avec :

```text
StockTransfer
StockTransferLine

StockCount
StockCountLine
OpenStockCountScope
```

Les concepts externes circulent par identifiants et Application Contracts.

Inventory ne dépend pas de :

```text
Organization\Store aggregate
Catalog\Product aggregate
Sales\Sale aggregate
Purchasing\GoodsReceipt aggregate
```

---
# 4. Principes critiques du Lot 7

## 4.1 Transfert inter-store uniquement dans un tenant

Invariant :

```text
sourceStoreId != destinationStoreId
```

et :

```text
sourceStore.organizationId
=
destinationStore.organizationId
=
StockTransfer.organizationId
```

Un transfert cross-tenant est impossible.

---

## 4.2 Workflow MVP du transfert

La baseline figée utilise :

```text
DRAFT
→ SHIPPED
→ RECEIVED
```

avec :

```text
DRAFT → CANCELLED
```

avant expédition.

Le premier cadrage fonctionnel évoquait un workflow plus riche (`APPROVED`, `IN_TRANSIT`), mais la baseline DDD a volontairement figé un MVP plus simple.

Le Lot 7 doit suivre la baseline.

---

## 4.3 Une seule expédition et une seule réception finale

Le MVP ne supporte pas :

```text
partial shipment #1
partial shipment #2

partial receipt #1
partial receipt #2
```

Chaque transfert possède :

```text
1 expédition atomique
1 réception finale atomique
```

Les réceptions partielles successives restent hors scope.

---

## 4.4 Quantités distinctes

Chaque ligne conserve :

```text
requestedQuantity
shippedQuantity
receivedQuantity
```

Invariants :

```text
requestedQuantity > 0
```

Avant expédition :

```text
shippedQuantity = null
```

Après expédition :

```text
0 <= shippedQuantity <= requestedQuantity
```

Avant réception :

```text
receivedQuantity = null
```

Après réception :

```text
0 <= receivedQuantity <= shippedQuantity
```

Une quantité zéro est une donnée valide après phase correspondante, mais ne produit jamais de mouvement nul.

---

## 4.5 Le statut n’explique pas l’écart

Le statut décrit le workflow.

Les quantités décrivent la réalité.

Exemple :

```text
requested = 10
shipped = 10
received = 8
status = RECEIVED
```

Le transfert est terminé.

L’écart :

```text
2
```

reste visible comme écart de transit.

Il ne faut pas garder artificiellement le transfert `SHIPPED` pour représenter l’écart.

---

## 4.6 Surplus à destination

Si le store destination constate :

```text
receivedQuantity > shippedQuantity
```

le surplus n’est pas accepté dans `ReceiveStockTransfer`.

Il passe par :

```text
AdjustStock
```

avec raison et permission.

---

## 4.7 StockCount bloque uniquement son périmètre

Un inventaire ne doit pas bloquer tout le store.

La baseline matérialise :

```text
OpenStockCountScope
├── OrganizationId
├── StoreId
├── ProductId
└── StockCountId
```

avec unicité :

```text
organization + store + product
```

Pendant :

```text
OPEN
FINALIZING
```

aucun mouvement ne peut affecter les produits verrouillés.

---

## 4.8 StockCountLine séparée

`StockCount` reste petit.

Chaque produit est représenté par un aggregate séparé :

```text
StockCountLine
```

Cela permet :

- plusieurs compteurs ;
- pas de version globale géante ;
- traitement par batch ;
- reprise ciblée après incident.

---

## 4.9 Zéro ≠ non compté

```text
countedQuantity = null
```

signifie :

> pas encore compté.

```text
countedQuantity = 0
```

signifie :

> compté physiquement à zéro.

Cette distinction doit exister Domain, persistence et API.

---
# 5. Règle de commits

Exemples :

```text
feat(inventory): add stock transfer aggregate
feat(inventory): ship stock transfer
feat(inventory): receive stock transfer
feat(costing): transfer stock value between stores
test(inventory): verify stock transfer idempotence

feat(inventory): add stock count aggregate
feat(inventory): add stock count line aggregate
feat(inventory): lock open stock count scope
feat(inventory): record stock counts
feat(inventory): reconcile stock count batch
feat(costing): value stock count corrections
test(inventory): verify stock count crash recovery
```

---
# 6. Vue d’ensemble

```text
Epic 7.1 — StockTransfer foundation
Epic 7.2 — StockTransfer lifecycle
Epic 7.3 — ShipStockTransfer
Epic 7.4 — Stock en transit
Epic 7.5 — ReceiveStockTransfer
Epic 7.6 — Transfer discrepancy
Epic 7.7 — Transfer costing

Epic 7.8 — StockCount aggregate
Epic 7.9 — StockCountLine
Epic 7.10 — CreateStockCount
Epic 7.11 — StartStockCount
Epic 7.12 — OpenStockCountScope
Epic 7.13 — RecordStockCount
Epic 7.14 — BeginStockCountFinalization
Epic 7.15 — ReconcileStockCountBatch
Epic 7.16 — Crash recovery
Epic 7.17 — CompleteStockCountFinalization
Epic 7.18 — StockCount costing

Transverse — Locks & cross-workflow guards
Transverse — StoreClosure integration
Transverse — Authorization & audit
Transverse — APIs
Transverse — PostgreSQL / RLS / recovery tests

Lot 7 Gate
M3 Gate
```

---


## Dependencies

Dependencies are capability-based. Follow dependencies explicitly named by the current Epic or support file; do not infer dependencies from Lot numbering.


## Source of truth

`docs/specs/planning/zandu-lot-7-stock-transfer-stock-count.md`

Open this complete specification only when the targeted AI files do not answer a required business question.
