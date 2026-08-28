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

# 7. Epic 6.1 — Purchasing foundation

Structure :

```text
src/Modules/Purchasing/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Créer ou vérifier le schéma PostgreSQL :

```text
purchasing
```

Tables prévues progressivement :

```text
supplier
purchase_order
purchase_order_line
goods_receipt
goods_receipt_line
goods_receipt_correction
goods_receipt_correction_line
purchase_return
purchase_return_line
```

Interdictions :

```text
Purchasing\Domain → Doctrine
Purchasing\Domain → Symfony
Purchasing\Domain → Inventory\Domain
Purchasing\Domain → Catalog\Domain
```

Commit :

```text
refactor(purchasing): add bounded context structure
```

---

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

# 9. Epic 6.3 — PurchasingPolicy

Créer une policy possédée par Purchasing.

Minimum :

```text
PurchasingPolicy
├── purchaseOrderRequiredForReceipt
└── overReceiptPolicy
```

Baseline :

```text
direct receipt allowed by default
```

Pour l’over receipt :

```text
default = forbidden
```

Exception uniquement avec :

```text
PURCHASING_OVER_RECEIPT
reason
authorizedBy
```

Commit :

```text
feat(purchasing): add purchasing policy
```

---

# 10. Epic 6.4 — PurchaseOrder

## Aggregate

```text
PurchaseOrder
├── PurchaseOrderId
├── OrganizationId
├── destinationStoreId
├── supplierId
├── number
├── status
├── currency
├── lines[]
├── expectedTotal
├── createdBy
├── createdAt
├── confirmedBy?
├── confirmedAt?
├── closedBy?
├── closedAt?
├── closedReason?
└── Version
```

## PurchaseOrderLine

```text
PurchaseOrderLine
├── PurchaseOrderLineId
├── ProductId
├── ProductPackagingId?
├── enteredOrderedQuantity
├── conversionFactorSnapshot
├── orderedBaseQuantity
├── unitCost
├── inventoryUnitCost
└── receivedQuantity
```

## Lifecycle

```text
DRAFT
→ CONFIRMED
→ PARTIALLY_RECEIVED
→ FULLY_RECEIVED
→ CLOSED
```

Avant toute réception :

```text
DRAFT → CANCELLED
CONFIRMED → CANCELLED
```

## Invariants

- une seule devise ;
- supplier figé après confirmation ;
- destination Store figé après confirmation ;
- lignes et coûts figés après confirmation ;
- produit au maximum une fois par commande ;
- `receivedQuantity` est un cumul transactionnel ;
- une commande ayant reçu ne peut plus être annulée.

Commits :

```text
feat(purchasing): add purchase order aggregate
feat(purchasing): persist purchase orders
feat(purchasing): add purchase order lifecycle
```

---

# 11. Epic 6.5 — Use cases PurchaseOrder

Créer :

```text
CreatePurchaseOrder
AddPurchaseOrderLine
UpdatePurchaseOrderLine
RemovePurchaseOrderLine
ConfirmPurchaseOrder
CancelPurchaseOrder
ClosePurchaseOrder
```

## Confirm

Préconditions :

- Store ACTIVE ;
- Supplier ACTIVE ;
- au moins une ligne ;
- produits achetables ;
- packaging `allowedForPurchase` si utilisé ;
- quantité > 0 ;
- coûts exacts ;
- currency cohérente.

## Close partial order

Une commande `PARTIALLY_RECEIVED` peut être fermée avant réception totale avec :

```text
reason
actor
audit
```

---

# 12. Epic 6.6 — GoodsReceipt

## Aggregate

```text
GoodsReceipt
├── GoodsReceiptId
├── OrganizationId
├── storeId
├── supplierId
├── purchaseOrderId?
├── number
├── status
├── lines[]
├── supplierDeliveryNote?
├── notes?
├── createdBy
├── createdAt
├── postedBy?
├── postedAt?
└── Version
```

## Line

```text
GoodsReceiptLine
├── GoodsReceiptLineId
├── ProductId
├── ProductPackagingId?
├── enteredReceivedQuantity
├── conversionFactorSnapshot
├── receivedBaseQuantity
├── actualUnitCost?
├── inventoryUnitCost
└── purchaseOrderLineId?
```

## Lifecycle

```text
DRAFT → POSTED
DRAFT → CANCELLED
```

`POSTED` est immutable.

## Linked receipt

Si `purchaseOrderId != null` :

- même organization ;
- même supplier ;
- même destination Store ;
- chaque produit appartient à la commande ;
- quantité rapprochée de la ligne correspondante.

Produit supplémentaire :

```text
→ réception directe séparée et justifiée
```

Commits :

```text
feat(purchasing): add goods receipt aggregate
feat(purchasing): persist goods receipts
```

---

# 13. Epic 6.7 — Direct GoodsReceipt

Si :

```text
purchaseOrderRequiredForReceipt = false
```

autoriser :

```text
CreateDirectGoodsReceipt
```

La réception directe doit tout de même contenir :

```text
supplierId
storeId
products
quantities
inventoryUnitCost
```

Elle ne simule pas un PurchaseOrder fictif.

Commit :

```text
feat(purchasing): add direct goods receipt
```

---

# 14. Epic 6.8 — PostGoodsReceipt

Command :

```text
PostGoodsReceipt
├── goodsReceiptId
└── commandId / idempotency context
```

`organizationId` et `actorId` viennent de `ActorContext`.

## Transaction nominale

```text
BEGIN

validate GoodsReceipt
validate optional PurchaseOrder
validate Supplier / Store / Catalog snapshots

for each line:
    Inventory.receive(...)
    append StockMovement PURCHASE_RECEIPT

    InventoryCosting.valuePurchaseReceipt(...)
    update StockValuation
    append StockValuationMovement

if linked:
    update PurchaseOrderLine.receivedQuantity
    recalculate PurchaseOrder.status

GoodsReceipt.post()

append Audit if required
append Outbox

COMMIT
```

Avec le Lot 5 présent, `StockValuation` et `StockValuationMovement` font partie de la même transaction pour une réception valorisée.

Commit :

```text
feat(purchasing): add post goods receipt workflow
```

---

# 15. Epic 6.9 — Inventory integration

Contrat :

```text
InventoryGoodsReceiver
```

Entrée :

```text
organizationId
storeId
receiptId
supplierId?
items[]
    productId
    baseQuantity
receivedBy
receivedAt
```

Pour chaque ligne :

```text
Stock.receive(...)
```

Créer :

```text
StockMovement
 type = PURCHASE_RECEIPT
 source = GOODS_RECEIPT / receiptId
```

Idempotence :

```text
PURCHASE_RECEIPT + receiptId + productId
```

Commit :

```text
feat(inventory): receive supplier goods
```

---

# 16. Epic 6.10 — Costing integration

Une réception fournit :

```text
inventoryUnitCost
```

en base unit.

Calcul :

```text
incomingValue = baseQuantity × inventoryUnitCost
newTotalValue = oldTotalValue + incomingValue
newAverageUnitCost = newTotalValue / newQuantity
```

Créer `StockValuationMovement` lié au `StockMovement PURCHASE_RECEIPT`.

## actualUnitCost vs inventoryUnitCost

`actualUnitCost` représente l’information commerciale de réception.

`inventoryUnitCost` est le coût en base unit transmis au costing.

Si packaging :

```text
inventoryUnitCost = packagingCost / conversionFactor
```

avec calcul Decimal exact.

Aucune taxe/frais n’est soustrait implicitement.

Commit :

```text
feat(costing): value purchase receipt
```

---

# 17. Epic 6.11 — Partial receipts

Exemple :

```text
PurchaseOrder:
ordered = 100
received = 0
```

Receipt 1 :

```text
+40
→ received = 40
→ PARTIALLY_RECEIVED
```

Receipt 2 :

```text
+60
→ received = 100
→ FULLY_RECEIVED
```

`receivedQuantity` est persistée et protégée par version.

Commit :

```text
feat(purchasing): support partial goods receipts
```

---

# 18. Epic 6.12 — Over receipt

Invariant par défaut :

```text
cumulativeReceivedQuantity <= orderedQuantity
```

Exemple :

```text
ordered = 100
already received = 90
new receipt = 20
```

Sans autorisation :

```text
→ OVER_RECEIPT_NOT_ALLOWED
```

Avec :

```text
PURCHASING_OVER_RECEIPT
reason
authorizedBy
```

la quantité physique réellement reçue est enregistrée.

Ne jamais tronquer silencieusement la quantité à 10.

Commit :

```text
feat(purchasing): add over receipt authorization
```

---

# 19. Epic 6.13 — GoodsReceiptCorrection

Aggregate :

```text
GoodsReceiptCorrection
├── GoodsReceiptCorrectionId
├── OrganizationId
├── GoodsReceiptId
├── reason
├── status
├── lines[]
├── createdBy
├── createdAt
├── postedBy?
├── postedAt?
└── Version
```

Ligne :

```text
GoodsReceiptCorrectionLine
├── ProductId
├── originalReceivedQuantity
├── currentEffectiveQuantity
├── correctedReceivedQuantity
└── difference
```

Calcul :

```text
difference = correctedReceivedQuantity - currentEffectiveQuantity
```

Types :

```text
difference > 0
→ GOODS_RECEIPT_CORRECTION_IN
```

```text
difference < 0
→ GOODS_RECEIPT_CORRECTION_OUT
```

```text
difference = 0
→ aucun StockMovement
```

Permission :

```text
PURCHASING_RECEIPT_CORRECT
```

Raison obligatoire.

Une correction publiée est immuable.

Commit :

```text
feat(purchasing): add goods receipt correction
```

---

# 20. Costing des corrections

Les corrections sont prospectives : elles ajustent quantité et valeur sans rejouer rétroactivement toutes les sorties historiques.

## Correction IN

Une sous-déclaration corrigée positivement utilise une source de coût explicite et traçable.

## Correction OUT

Une correction négative ajuste quantité et valeur prospectivement et ne peut rendre Stock négatif.

Créer un `StockValuationMovement` lorsque le mouvement physique est valorisé.

Commit :

```text
feat(costing): value goods receipt corrections
```

---

# 21. Atomicité GoodsReceiptCorrection

```text
BEGIN

load posted GoodsReceipt
validate correction against effective quantity

for each non-zero difference:
    update Stock
    append correction StockMovement
    update StockValuation
    append StockValuationMovement

update linked PurchaseOrder receivedQuantity/status
post correction
append Audit
append Outbox

COMMIT
```

Toute erreur :

```text
ROLLBACK TOTAL
```

---

# 22. Epic 6.14 — PurchaseReturn

```text
PurchaseReturn
├── PurchaseReturnId
├── OrganizationId
├── sourceStoreId
├── supplierId
├── goodsReceiptId?
├── purchaseOrderId?
├── status
├── reason
├── lines[]
├── createdBy
├── createdAt
├── shippedBy?
├── shippedAt?
└── Version
```

Cycle :

```text
DRAFT → SHIPPED
DRAFT → CANCELLED
```

## PurchaseReturnLine

```text
PurchaseReturnLine
├── ProductId
├── quantity
├── goodsReceiptLineId?
└── reference snapshots
```

## Invariants

- source Store même tenant ;
- Supplier cohérent ;
- retour lié à une seule `GoodsReceipt` pour le MVP ;
- quantité <= stock disponible ;
- quantité <= reliquat retournable de la réception ;
- reason obligatoire ;
- `SHIPPED` immutable.

Commit :

```text
feat(purchasing): add purchase return
```

---

# 23. ShipPurchaseReturn

Transaction :

```text
BEGIN

validate PurchaseReturn

for each line:
    Inventory.shipPurchaseReturn(...)
    append StockMovement PURCHASE_RETURN

    InventoryCosting.valuePurchaseReturn(...)
    update StockValuation
    append StockValuationMovement

PurchaseReturn.ship()
append Outbox

COMMIT
```

Le mouvement :

```text
PURCHASE_RETURN
 direction = OUT
```

Le `PurchaseReturn` ne décrémente pas :

```text
PurchaseOrderLine.receivedQuantity
```

Commit :

```text
feat(purchasing): ship purchase return
```

---

# 24. Costing PurchaseReturn

La baseline fixe :

```text
PurchaseReturn
→ sortie au coût moyen courant
```

Donc :

```text
returnValue = baseQuantity × currentAverageUnitCost
```

La sortie finale respecte :

```text
quantity = 0
→ totalValue = 0
```

Commit :

```text
feat(costing): value purchase return
```

---

# 25. Idempotence Purchasing

Les opérations suivantes sont idempotentes :

```text
PostGoodsReceipt
PostGoodsReceiptCorrection
ShipPurchaseReturn
```

Un retry réseau ne double jamais :

```text
stock
valuation
receivedQuantity
purchase return
outbox effect
```

Un même `commandId` avec contenu différent produit :

```text
IdempotencyConflict
```

Contrainte logique :

```text
tenant + store + product + movementType + source
```

---

# 26. Concurrence

## Partial receipts concurrentes

```text
ordered = 100
received = 70
A = +20
B = +20
```

Sans over-receipt autorisé :

```text
final cumulative <= 100
```

`PurchaseOrder.version` protège le cumul.

## Stock

Les entrées simultanées utilisent la stratégie Inventory du Lot 3.

## Corrections

`GoodsReceiptCorrection` vérifie la quantité effective courante avant application.

## PurchaseReturn

Le retour fournisseur revalide le stock disponible et le reliquat retournable dans la transaction.

---

# 27. Catalog contract pour Purchasing

Créer/réutiliser :

```text
PurchasableProductProvider
```

Retour :

```text
PurchasableProductSnapshot
├── ProductId
├── type
├── status
├── inventoryTracked
├── baseUnitId
├── packaging
│   ├── ProductPackagingId
│   ├── conversionFactor
│   ├── allowedForPurchase
│   └── precision rules
└── version
```

Un service ne produit pas une réception Inventory physique.

Commit :

```text
feat(catalog): expose purchasable product contract
```

---

# 28. Permissions

Ajouter :

```text
SUPPLIER_CREATE
SUPPLIER_READ
SUPPLIER_UPDATE
SUPPLIER_ARCHIVE

PURCHASE_ORDER_CREATE
PURCHASE_ORDER_READ
PURCHASE_ORDER_UPDATE_DRAFT
PURCHASE_ORDER_CONFIRM
PURCHASE_ORDER_CANCEL
PURCHASE_ORDER_CLOSE

GOODS_RECEIPT_CREATE
GOODS_RECEIPT_READ
GOODS_RECEIPT_POST
GOODS_RECEIPT_CANCEL

PURCHASING_OVER_RECEIPT
PURCHASING_RECEIPT_CORRECT

PURCHASE_RETURN_CREATE
PURCHASE_RETURN_READ
PURCHASE_RETURN_SHIP
PURCHASE_RETURN_CANCEL
```

Commit :

```text
feat(access): add purchasing permissions
```

---

# 29. Rôles système

Exemple :

```text
ORGANIZATION_OWNER
→ toutes permissions

STORE_MANAGER
→ PO / receipt / return du scope Store

CASHIER
→ pas de Purchasing par défaut

ACCOUNTANT
→ lecture Purchasing
```

Les scopes Store sont obligatoires.

---

# 30. Guards opérationnels

Démarrer une nouvelle réception fournisseur est interdit sur un Store `SUSPENDED` ou `CLOSED` selon les guards existants.

Lecture et remédiation restent autorisées selon policy.

---

# 31. StoreClosure integration

Purchasing implémente le blocker attendu par `StoreClosure`.

Blockers possibles :

```text
OPEN_PURCHASE_ORDER
DRAFT_GOODS_RECEIPT
OPEN_PURCHASE_RETURN
OPEN_GOODS_RECEIPT_CORRECTION
```

Ne bloquer que les documents réellement ouverts selon la policy de fermeture.

Commit :

```text
feat(purchasing): provide store closure blockers
```

---

# 32. Audit

Auditer au minimum :

```text
OVER_RECEIPT_AUTHORIZED
GOODS_RECEIPT_CORRECTED
PARTIAL_PURCHASE_ORDER_CLOSED
```

Les documents Purchasing et `StockMovement` restent distincts du security audit.

---

# 33. Events

```text
SupplierCreated

PurchaseOrderCreated
PurchaseOrderConfirmed
PurchaseOrderPartiallyReceived
PurchaseOrderFullyReceived
PurchaseOrderClosed
PurchaseOrderCancelled

GoodsReceiptCreated
GoodsReceiptPosted
GoodsReceiptCancelled
GoodsReceiptCorrected

PurchaseReturnCreated
PurchaseReturnShipped
PurchaseReturnCancelled
```

Tous les integration events passent par l’outbox versionnée.

---

# 34. API Supplier

```text
GET  /api/suppliers
POST /api/suppliers
GET  /api/suppliers/{id}
PATCH /api/suppliers/{id}
POST /api/suppliers/{id}/activate
POST /api/suppliers/{id}/deactivate
POST /api/suppliers/{id}/archive
```

---

# 35. API PurchaseOrder

```text
GET  /api/purchase-orders
POST /api/stores/{storeId}/purchase-orders
GET  /api/purchase-orders/{id}
POST   /api/purchase-orders/{id}/lines
PATCH  /api/purchase-orders/{id}/lines/{lineId}
DELETE /api/purchase-orders/{id}/lines/{lineId}
POST /api/purchase-orders/{id}/confirm
POST /api/purchase-orders/{id}/cancel
POST /api/purchase-orders/{id}/close
```

---

# 36. API GoodsReceipt

```text
GET  /api/goods-receipts
POST /api/stores/{storeId}/goods-receipts
GET  /api/goods-receipts/{id}
POST   /api/goods-receipts/{id}/lines
PATCH  /api/goods-receipts/{id}/lines/{lineId}
DELETE /api/goods-receipts/{id}/lines/{lineId}
POST /api/goods-receipts/{id}/post
POST /api/goods-receipts/{id}/cancel
```

---

# 37. API GoodsReceiptCorrection

```text
POST /api/goods-receipts/{id}/corrections
GET  /api/goods-receipt-corrections/{id}
POST /api/goods-receipt-corrections/{id}/post
```

---

# 38. API PurchaseReturn

```text
POST /api/stores/{storeId}/purchase-returns
GET  /api/purchase-returns/{id}
POST /api/purchase-returns/{id}/lines
POST /api/purchase-returns/{id}/ship
POST /api/purchase-returns/{id}/cancel
```

---

# 39. Error contract

```text
SUPPLIER_NOT_ACTIVE
PURCHASE_ORDER_NOT_EDITABLE
PURCHASE_ORDER_EMPTY
PURCHASE_ORDER_HAS_RECEIPTS
PURCHASE_ORDER_CURRENCY_MISMATCH
GOODS_RECEIPT_NOT_EDITABLE
GOODS_RECEIPT_ALREADY_POSTED
GOODS_RECEIPT_PURCHASE_ORDER_REQUIRED
GOODS_RECEIPT_PRODUCT_NOT_ORDERED
GOODS_RECEIPT_STORE_MISMATCH
GOODS_RECEIPT_SUPPLIER_MISMATCH
OVER_RECEIPT_NOT_ALLOWED
GOODS_RECEIPT_CORRECTION_INVALID
GOODS_RECEIPT_CORRECTION_CONFLICT
GOODS_RECEIPT_CORRECTION_WOULD_CREATE_NEGATIVE_STOCK
PURCHASE_RETURN_EXCEEDS_RETURNABLE
PURCHASE_RETURN_INSUFFICIENT_STOCK
PURCHASE_RETURN_ALREADY_SHIPPED
IDEMPOTENCY_CONFLICT
```

---

# 40. Persistence

Tables :

```text
purchasing.supplier
purchasing.purchase_order
purchasing.purchase_order_line
purchasing.goods_receipt
purchasing.goods_receipt_line
purchasing.goods_receipt_correction
purchasing.goods_receipt_correction_line
purchasing.purchase_return
purchasing.purchase_return_line
```

Contraintes :

```text
unique organization + purchase_order.number
unique organization + goods_receipt.number
```

Un produit apparaît au maximum une fois par PO.

Versioning optimiste et RLS obligatoires.

---

# 41. Tests PurchaseOrder

Couvrir :

```text
create draft
add line
same product twice rejected
confirm
freeze confirmed order
cancel before receipt
reject cancel after receipt
partial received state
fully received state
close partial with reason
currency invariant
```

Commit :

```text
test(purchasing): cover purchase order lifecycle
```

---

# 42. Tests GoodsReceipt

Couvrir :

```text
draft
cancel draft
post direct receipt
post linked receipt
multiple partial receipts
reject unordered extra product
reject wrong supplier
reject wrong store
posted immutable
```

Commit :

```text
test(purchasing): cover goods receipt lifecycle
```

---

# 43. Test réception + Inventory + Costing

Exemple :

```text
Stock quantity = 10
Stock value = 40 000
average = 4 000

GoodsReceipt quantity = 10
inventoryUnitCost = 6 000
```

Après post :

```text
Stock = 20
StockMovement PURCHASE_RECEIPT = +10
StockValuation totalValue = 100 000
averageUnitCost = 5 000
StockValuationMovement lié au StockMovement
GoodsReceipt = POSTED
```

---

# 44. Tests over receipt

```text
ordered = 100
received = 90
receipt = 20
```

Sans permission : rejet.

Avec permission + reason + authorizedBy :

```text
physical received = 20
cumulative = 110
audit present
```

Commit :

```text
test(purchasing): verify over receipt authorization
```

---

# 45. Tests GoodsReceiptCorrection

```text
correction IN
correction OUT
zero difference
negative stock protection
current effective quantity conflict
PO received cumulative recalculation
posted correction immutable
```

Commit :

```text
test(purchasing): cover goods receipt corrections
```

---

# 46. Tests PurchaseReturn

```text
create draft
ship
cancel draft
insufficient stock
return > receipt returnable balance
linked one receipt only
PO receivedQuantity unchanged
StockMovement PURCHASE_RETURN
valuation at current average cost
```

Commit :

```text
test(purchasing): cover purchase returns
```

---

# 47. Failure matrix PostGoodsReceipt

Injecter erreur :

```text
after GoodsReceipt validation
after Stock update line 1
after StockMovement line 1
after StockValuation line 1
after StockValuationMovement line 1
after PurchaseOrder receivedQuantity
after PurchaseOrder status
after GoodsReceipt POSTED
after Audit
after Outbox
before COMMIT
```

Résultat :

```text
ROLLBACK TOTAL
```

Commit :

```text
test(purchasing): verify goods receipt transaction rollback
```

---

# 48. Failure matrix Correction / Return

Même discipline pour :

```text
PostGoodsReceiptCorrection
ShipPurchaseReturn
```

Tout échec pré-commit annule :

```text
Purchasing document
Stock
StockMovement
StockValuation
StockValuationMovement
Audit
Outbox
```

---

# 49. Idempotence tests

Tester :

```text
PostGoodsReceipt same command twice
PostGoodsReceipt network retry after commit
same commandId different payload
PostGoodsReceiptCorrection retry
ShipPurchaseReturn retry
```

Aucun doublon physique ou économique.

Commit :

```text
test(purchasing): verify purchasing command idempotence
```

---

# 50. Concurrency tests

PostgreSQL réel.

## Concurrent receipts

```text
ordered = 100
received = 70
A = +20
B = +20
```

Sans over receipt :

```text
final cumulative <= 100
```

## Receipt and sale

Une vente et une réception simultanées sur le même Stock doivent conserver :

```text
quantity correct
valuation coherent
no lost update
```

## Correction concurrente

Deux corrections sur la même effective quantity doivent détecter un conflit.

Commit :

```text
test(purchasing): verify concurrent purchasing operations
```

---

# 51. Tenant / RLS tests

```text
Tenant B reads Supplier A
→ NOT_FOUND

Tenant B reads PurchaseOrder A
→ NOT_FOUND

Tenant B posts GoodsReceipt A
→ NOT_FOUND

Store-scoped manager A posts receipt Store B
→ denied
```

RLS sur toutes les tables Purchasing.

---

# 52. Observabilité

Métriques possibles :

```text
goods_receipt_posted_count
goods_receipt_failure_count
purchase_over_receipt_count
purchase_receipt_correction_count
purchase_return_count
purchasing_concurrency_conflict_count
```

Logs structurés :

```text
correlationId
operation
result
duration
```

---

# 53. Démonstration consolidée Lot 6

```text
1. Owner crée Supplier "Congo Distribution".

2. Manager crée PurchaseOrder Store A :
   Product A = 100 unités
   unitCost = 4 000 XAF.

3. PO → CONFIRMED.

4. Première livraison : 40 unités.

5. GoodsReceipt #1 POSTED.

6. Stock augmente de 40.

7. StockMovement PURCHASE_RECEIPT créé.

8. StockValuation est mise à jour avec inventoryUnitCost.

9. PurchaseOrder.receivedQuantity = 40.
   status = PARTIALLY_RECEIVED.

10. Deuxième réception : 60 unités.

11. PO receivedQuantity = 100.
    status = FULLY_RECEIVED.

12. Retry du receipt : aucun doublon.

13. Tentative receipt +10 : refus sans permission.

14. Over-receipt autorisé avec raison :
    quantité réelle conservée + audit.

15. Erreur documentaire :
    GoodsReceiptCorrection créée.

16. Correction OUT appliquée sans modifier le GoodsReceipt original.

17. Supplier reprend une partie physique :
    PurchaseReturn SHIPPED.

18. StockMovement PURCHASE_RETURN créé.

19. Costing sort la valeur au coût moyen courant.

20. Tenant B → NOT_FOUND.

21. Erreur injectée avant commit → aucun effet partiel.

22. StoreClosure voit les documents Purchasing ouverts comme blockers.
```

---

# 54. Gate de sortie Lot 6

Le Lot 6 est `DONE` uniquement lorsque :

```text
[ ] Purchasing bounded context matérialisé
[ ] boundaries protégées

[ ] Supplier opérationnel
[ ] supplier name minimal supporté
[ ] coordonnées facultatives
[ ] historique supplier conservé

[ ] PurchasingPolicy opérationnelle
[ ] purchaseOrderRequiredForReceipt disponible
[ ] réception directe contrôlée
[ ] over receipt interdit par défaut

[ ] PurchaseOrder opérationnel
[ ] DRAFT
[ ] CONFIRMED
[ ] PARTIALLY_RECEIVED
[ ] FULLY_RECEIVED
[ ] CLOSED
[ ] CANCELLED
[ ] lignes figées après confirmation
[ ] une devise par PO
[ ] produit unique par PO
[ ] receivedQuantity transactionnel
[ ] cancel après réception interdit
[ ] close partial avec reason

[ ] GoodsReceipt opérationnel
[ ] direct receipt disponible selon policy
[ ] linked receipt disponible
[ ] partial receipts supportées
[ ] POSTED immutable
[ ] CANCELLED sans impact stock
[ ] produit supplémentaire lié interdit

[ ] PostGoodsReceipt coordinated local transaction
[ ] Inventory Application API utilisée
[ ] aucun accès direct StockRepository
[ ] StockMovement PURCHASE_RECEIPT
[ ] source receipt explicite
[ ] idempotence receipt

[ ] Costing intégré
[ ] inventoryUnitCost explicite
[ ] coût en base unit
[ ] StockValuation mise à jour
[ ] StockValuationMovement créé
[ ] moving average recalculé
[ ] aucune déduction implicite taxe/frais

[ ] over receipt protection
[ ] permission PURCHASING_OVER_RECEIPT
[ ] reason obligatoire
[ ] authorizedBy conservé
[ ] audit créé
[ ] quantité physique réelle conservée

[ ] GoodsReceiptCorrection opérationnelle
[ ] correction compensatoire
[ ] original POSTED non modifié
[ ] correction IN
[ ] correction OUT
[ ] différence zéro sans movement
[ ] correction OUT ne rend pas stock négatif
[ ] PO cumulative reçu recalculé
[ ] correction idempotente
[ ] correction concurrency safe

[ ] PurchaseReturn opérationnel
[ ] distinct de GoodsReceiptCorrection
[ ] DRAFT
[ ] SHIPPED
[ ] CANCELLED
[ ] PURCHASE_RETURN StockMovement
[ ] stock disponible protégé
[ ] returnable balance protégé
[ ] receivedQuantity PO non décrémenté
[ ] coût moyen courant utilisé
[ ] PurchaseReturn idempotent

[ ] StoreClosure Purchasing blocker actif
[ ] store suspended bloque nouvelle réception

[ ] permissions Purchasing ajoutées
[ ] rôles système mis à jour
[ ] Store scopes appliqués
[ ] security audits sensibles présents

[ ] API Supplier
[ ] API PurchaseOrder
[ ] API GoodsReceipt
[ ] API GoodsReceiptCorrection
[ ] API PurchaseReturn
[ ] OpenAPI à jour
[ ] error contract stable

[ ] PostgreSQL persistence réelle
[ ] RLS Purchasing actif
[ ] cross-tenant NOT_FOUND

[ ] domain tests verts
[ ] integration tests verts
[ ] costing integration tests verts
[ ] rollback matrix verte
[ ] idempotence tests verts
[ ] concurrency tests verts
[ ] tenant isolation tests verts
[ ] RLS tests verts
[ ] architecture fitness tests verts
[ ] PHPStan vert
[ ] PHP-CS-Fixer vert
[ ] Deptrac vert
[ ] Composer audit vert
[ ] CI verte

[ ] démonstration consolidée Lot 6 réussie

[ ] aucun StockTransfer
[ ] aucun StockCount
[ ] aucun SupplierPayment
[ ] aucun SupplierDebt
[ ] aucun advanced landed cost
[ ] aucun LotTracking
[ ] aucun Reporting avancé
[ ] aucun Offline

[ ] IMPLEMENTATION_STATUS.md mis à jour
[ ] ADR créé/mis à jour pour toute décision structurante
```

---

# 55. Hors périmètre

```text
Accounts Payable
SupplierDebt
SupplierPayment

LandedCost allocation avancée
Freight allocation
Customs allocation

LotTracking
StockLot
Batch expiry enforcement

StockTransfer
StockCount

Customer
Customer Credit

Payment providers

Reporting avancé
Offline
```

`lotNumber` et `expirationDate` peuvent éventuellement rester documentaires, mais le Lot 6 ne doit pas introduire un faux modèle de stock par lot.

---

# 56. Transition vers Lot 7

Après le Lot 6, Zandu sait expliquer les principaux mouvements :

```text
INITIAL_STOCK
PURCHASE_RECEIPT
SALE
SALE_RETURN
PURCHASE_RETURN
ADJUSTMENT_IN
ADJUSTMENT_OUT
GOODS_RECEIPT_CORRECTION_IN
GOODS_RECEIPT_CORRECTION_OUT
```

Le Lot 7 terminera M3 avec :

```text
StockTransfer
+
StockCount
```

et ajoutera :

```text
TRANSFER_OUT
TRANSFER_IN
STOCK_COUNT_CORRECTION_IN
STOCK_COUNT_CORRECTION_OUT
```

---

# 57. Premier point d’entrée d’implémentation

Ordre recommandé :

```text
1. Purchasing foundation
2. Supplier
3. PurchasingPolicy
4. PurchaseOrder
5. GoodsReceipt
6. Catalog purchasing contract
7. Inventory receive contract
8. Costing receipt integration
9. PostGoodsReceipt
10. partial receipts
11. over receipt
12. GoodsReceiptCorrection
13. PurchaseReturn
14. StoreClosure blockers
15. API
16. rollback/idempotence/concurrency
17. Gate
```

Premiers commits :

```text
refactor(purchasing): add bounded context structure
feat(purchasing): add supplier aggregate
feat(purchasing): add purchasing policy
feat(purchasing): add purchase order aggregate
feat(purchasing): add goods receipt aggregate
feat(catalog): expose purchasable product contract
feat(inventory): receive supplier goods
feat(costing): value purchase receipt
feat(purchasing): add post goods receipt workflow
test(purchasing): verify goods receipt transaction rollback
```

---

# 58. Principe de travail

Pour chaque étape :

1. vérifier baseline et ADR ;
2. vérifier les APIs réellement livrées par Lots 3 et 5 ;
3. conserver PurchaseOrder et GoodsReceipt séparés ;
4. conserver PurchaseReturn et Correction séparés ;
5. utiliser les Application Contracts cross-context ;
6. travailler en base quantity pour Inventory ;
7. conserver les snapshots packaging/coût nécessaires ;
8. ne jamais inventer de coût fournisseur ;
9. rendre PostGoodsReceipt idempotent ;
10. protéger receivedQuantity contre la concurrence ;
11. intégrer Stock et Costing dans la transaction critique ;
12. injecter des erreurs aux frontières ;
13. tester RLS et tenant isolation ;
14. exécuter architecture tests / PHPStan / PHP-CS-Fixer / Deptrac ;
15. faire des commits atomiques ;
16. mettre à jour `IMPLEMENTATION_STATUS.md`.
