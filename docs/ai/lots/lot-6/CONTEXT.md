# Zandu Sales Manager — Lot 6 : Purchasing & Goods Receipts

**Version :** 1.0  
**Statut :** Backlog d’implémentation  
**Langue :** Français — identifiants de code en anglais

---
# 1. Objectif du Lot 6

Le Lot 6 construit le bounded context `Purchasing` et branche les approvisionnements fournisseurs sur les fondations déjà disponibles :

```text
Catalog
+
Inventory
+
Inventory Costing
+
Organization / Store
```

À la sortie du Lot 6, une organisation doit pouvoir :

- gérer un fournisseur minimal ;
- créer un `PurchaseOrder` ;
- ajouter des lignes de commande ;
- confirmer une commande ;
- recevoir une commande en une ou plusieurs réceptions partielles ;
- effectuer une réception directe sans commande lorsque la policy l’autorise ;
- publier un `GoodsReceipt` atomiquement ;
- augmenter le stock via `StockMovement PURCHASE_RECEIPT` ;
- alimenter `StockValuation` avec l’`inventoryUnitCost` réellement retenu ;
- recalculer le coût moyen pondéré mobile ;
- maintenir `PurchaseOrderLine.receivedQuantity` ;
- passer automatiquement une commande en `PARTIALLY_RECEIVED` ou `FULLY_RECEIVED` ;
- fermer une commande partiellement reçue avec une raison ;
- contrôler les dépassements de quantité reçue ;
- autoriser explicitement un over-receipt avec permission, raison et audit ;
- corriger une réception publiée par `GoodsReceiptCorrection` sans modifier l’historique ;
- distinguer strictement correction documentaire et retour physique fournisseur ;
- effectuer un `PurchaseReturn` ;
- décrémenter Inventory lors du retour fournisseur ;
- valoriser le `PurchaseReturn` au coût moyen courant ;
- garantir idempotence et concurrence ;
- appliquer RLS, scopes Store et tenant isolation ;
- fournir les blockers Purchasing à `StoreClosure` ;
- exposer API, audit, events et OpenAPI ;
- prouver les transactions critiques sur PostgreSQL réel.

Le Lot 6 ne doit pas encore implémenter :

```text
StockTransfer
StockCount
Supplier debt
Supplier payment
Accounts payable
Advanced landed cost
LotTracking
StockLot
Reporting avancé
Offline
```

Le Lot 6 est la deuxième tranche du jalon :

```text
M3 — Gestion complète du stock
```

Le Lot 7 terminera ce jalon avec `StockTransfer` et `StockCount`.

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
```

---
# 3. Responsabilités

## 3.1 Purchasing

`Purchasing` possède :

```text
Supplier
PurchaseOrder
PurchaseOrderLine
GoodsReceipt
GoodsReceiptLine
GoodsReceiptCorrection
PurchaseReturn
PurchasingPolicy
```

`Purchasing` possède le cycle commercial fournisseur.

Il ne possède pas :

```text
Stock
StockMovement
StockValuation
Product
Store
```

## 3.2 Inventory

`Inventory` reste l’autorité sur :

```text
quantityOnHand
StockMovement
```

Purchasing appelle uniquement une Application API.

Interdit :

```text
Purchasing
→ Inventory\Infrastructure\StockRepository
```

## 3.3 Inventory Costing

`Inventory Costing` reste l’autorité économique.

Une réception fournit :

```text
inventoryUnitCost
```

qui alimente le coût moyen pondéré mobile.

Un `PurchaseReturn` sort au coût moyen courant.

## 3.4 Catalog

Purchasing référence :

```text
ProductId
ProductPackagingId
```

et utilise des snapshots/contrats applicatifs nécessaires à l’achat.

Il ne transporte pas `Catalog\Product` dans son domaine.

---
# 4. Principes critiques

## 4.1 PurchaseOrder ≠ GoodsReceipt

Une commande et une réception sont deux réalités différentes.

```text
PurchaseOrder
→ intention commerciale d’acheter
```

```text
GoodsReceipt
→ fait physique de réception
```

Une commande peut accepter :

```text
0..n GoodsReceipt
```

et plusieurs réceptions partielles.

## 4.2 Réception directe

Une réception sans PurchaseOrder est autorisée par défaut selon la baseline, sous contrôle de :

```text
PurchasingPolicy.purchaseOrderRequiredForReceipt
```

Donc :

```text
purchaseOrderRequiredForReceipt = false
→ direct receipt possible
```

```text
purchaseOrderRequiredForReceipt = true
→ PurchaseOrder obligatoire
```

## 4.3 Publication immuable

```text
GoodsReceipt.status = POSTED
```

implique :

```text
immutable
```

Une erreur ne produit jamais :

```text
edit posted receipt
```

Elle produit :

```text
GoodsReceiptCorrection
```

## 4.4 Retour fournisseur ≠ correction

```text
PurchaseReturn
```

signifie que la marchandise quitte réellement le store vers le fournisseur.

```text
GoodsReceiptCorrection
```

signifie que la réception publiée comportait une erreur documentaire ou quantitative.

Ces intentions ne doivent jamais être fusionnées.

## 4.5 Packaging et base quantity

Les commandes et réceptions peuvent être exprimées dans un packaging d’achat.

Mais Inventory travaille toujours en :

```text
baseQuantity
```

Chaque ligne conserve donc :

```text
enteredQuantity
ProductPackagingId
conversionFactorSnapshot
baseQuantity
```

## 4.6 Coût fournisseur

Le coût utilisé pour Inventory Costing n’est jamais déduit du prix de vente.

Pour une réception :

```text
inventoryUnitCost
```

est fourni explicitement en base unit.

Les taxes et frais ne sont pas retirés implicitement.

---
# 5. Règle de commits

Exemples :

```text
refactor(purchasing): add bounded context structure
feat(purchasing): add supplier aggregate
feat(purchasing): add purchase order aggregate
feat(purchasing): add goods receipt aggregate
feat(purchasing): add direct goods receipt
feat(inventory): receive supplier goods
feat(costing): value purchase receipt
feat(purchasing): post goods receipt
feat(purchasing): add over receipt authorization
feat(purchasing): add goods receipt correction
feat(purchasing): add purchase return
test(purchasing): verify goods receipt rollback
test(purchasing): verify concurrent partial receipts
```

---
# 6. Vue d’ensemble

```text
Epic 6.1 — Purchasing foundation
Epic 6.2 — Supplier
Epic 6.3 — PurchasingPolicy
Epic 6.4 — PurchaseOrder aggregate
Epic 6.5 — PurchaseOrder use cases
Epic 6.6 — GoodsReceipt
Epic 6.7 — Direct GoodsReceipt
Epic 6.8 — PostGoodsReceipt
Epic 6.9 — Inventory integration
Epic 6.10 — Costing integration
Epic 6.11 — Partial receipts
Epic 6.12 — Over receipt
Epic 6.13 — GoodsReceiptCorrection et costing associé
Epic 6.14 — PurchaseReturn et costing associé
Epic 6.15 — StoreClosure, authorization, audit et API
Epic 6.16 — PostgreSQL, RLS, rollback, idempotence et concurrence
Lot 6 Gate
```

---


## Dependencies

Dependencies are capability-based. Follow dependencies explicitly named by the current Epic or support file; do not infer dependencies from Lot numbering.


## Source of truth

`docs/specs/planning/zandu-lot-6-purchasing-goods-receipts.md`

Open this complete specification only when the targeted AI files do not answer a required business question.
