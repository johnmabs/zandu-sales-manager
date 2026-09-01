# Zandu Sales Manager — Lot 5 : Inventory Costing & Returns

**Version :** 1.0
**Statut :** Backlog d’implémentation
**Langue :** Français — identifiants de code en anglais

---

# 1. Objectif du Lot 5

Le Lot 5 ajoute deux capacités indispensables après la première vente cash :

```text
Inventory Costing
+
Returns / Refunds essentiels
```

À la sortie du Lot 5, Zandu doit pouvoir :

- valoriser économiquement chaque position de stock suivie ;
- maintenir un coût moyen pondéré mobile par `Organization + Store + Product` ;
- conserver un journal de valorisation append-only ;
- associer chaque mouvement physique valorisé à au plus un mouvement de valorisation ;
- conserver un `SaleLineCostSnapshot` lors d’une vente ;
- sortir le stock vendu au coût moyen courant ;
- garantir que `quantityOnHand = 0` implique `totalValue = 0` ;
- distinguer strictement un retour physique d’un remboursement financier ;
- enregistrer un `ReturnSale` total ou partiel ;
- empêcher de retourner cumulativement plus que la quantité vendue ;
- décider explicitement, ligne par ligne, si un article retourné revient en stock ;
- créer un `StockMovement SALE_RETURN` uniquement lorsque `restock = true` ;
- restaurer lors d’un retour le **coût original de la vente**, et non le coût moyen courant ;
- enregistrer un remboursement cash lorsque le workflow l’exige ;
- créer un `CashMovement REFUND` idempotent sur une `CashSession OPEN` ;
- utiliser les snapshots de vente originaux pour les montants, taxes et remises retournés/remboursés ;
- empêcher les cumuls de retour/remboursement de dépasser les quantités et montants d’origine ;
- conserver l’atomicité entre état courant, ledgers, audits et outbox ;
- exposer les opérations par API ;
- garantir RLS, tenant isolation, scopes Store, idempotence et concurrence.

Le Lot 5 ne doit pas encore implémenter :

```text
Purchasing
GoodsReceipt
PurchaseReturn
StockTransfer
StockCount
Customer Credit
Payment providers
StockReservation
Reporting avancé
Offline
```

Le Lot 5 constitue la première partie du jalon :

```text
M3 — Gestion complète du stock
```

qui sera achevé avec les Lots 6 et 7.

---

# 2. Position dans la roadmap

```text
Lot 4
Sales / CompleteSale cash
        ↓
M2
Première vente cash
        ↓
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

# 3. Références architecturales

La baseline fixe les décisions suivantes :

```text
Inventory Costing
≠ Inventory
≠ Purchasing
≠ Pricing
```

Le produit ne possède pas de champ générique :

```text
costPrice
```

Le MVP utilise :

```text
MOVING_WEIGHTED_AVERAGE
```

par :

```text
Organization + Store + Product
```

`Stock` reste l’autorité physique.

`StockValuation` devient l’autorité économique.

Les deux doivent rester cohérents transactionnellement.

---

# 4. Principes critiques du Lot 5

## 4.1 Séparation physique / économique

```text
Inventory
├── Stock
└── StockMovement
```

répond à :

> Quelle quantité physique est comptabilisée et pourquoi a-t-elle changé ?

```text
Inventory Costing
├── StockValuation
└── StockValuationMovement
```

répond à :

> Quelle valeur économique est associée à cette quantité et pourquoi a-t-elle changé ?

Les deux modèles sont distincts mais coordonnés lorsqu’un mouvement doit être valorisé.

## 4.2 Pas de `Product.costPrice`

Interdit :

```text
Product
├── sellingPrice
└── costPrice
```

Le coût dépend du store et de l’historique de mouvements.

## 4.3 Moving Weighted Average

Pour une entrée valorisée :

```text
newTotalValue = previousTotalValue + incomingValue
newQuantity = previousQuantity + incomingQuantity
newAverageUnitCost = newTotalValue / newQuantity
```

Pour une sortie de vente :

```text
outgoingValue = saleQuantity × currentAverageUnitCost
```

## 4.4 Sortie finale

Invariant :

```text
quantityOnHand = 0
→ totalValue = 0 exactement
```

La dernière sortie absorbe les résidus de précision.

## 4.5 Retour au coût original

Lors d’un retour client remis en stock :

```text
restoredUnitCost = SaleLineCostSnapshot.unitCost
```

et non :

```text
currentAverageUnitCost
```

## 4.6 Retour physique ≠ remboursement

```text
ReturnSale
→ réalité physique/commerciale
```

```text
PaymentRefund / RefundSale
→ réalité financière
```

Un remboursement ne remet jamais automatiquement le produit en stock.

## 4.7 Corrections compensatoires

Interdit :

```text
UPDATE StockMovement
UPDATE StockValuationMovement
DELETE business ledger row
```

---

# 5. Décision de bootstrap du costing

Le Lot 5 arrive après les premiers mouvements Inventory et les premières ventes.

La politique est fixée par
[l’ADR-0021](../architecture/adr/0021-inventory-costing-activation-policy.md).
Une position historique positive exige un coût d’ouverture explicite ; une
position nulle peut être activée avec une valeur totale nulle.

Les options suivantes sont rejetées :

```text
B — position UNVALUED
C — reconstruction opportuniste
```

Après activation, chaque mouvement physique est valorisé dans sa transaction.
`INITIAL_STOCK` et `ADJUSTMENT_IN` exigent un coût explicite,
`ADJUSTMENT_OUT` et `SALE` utilisent le coût moyen courant, et `SALE_RETURN`
restaure le coût original de la vente. Ne jamais reconstruire un coût depuis le
prix de vente.

Commit documentaire :

```text
docs(adr): define inventory costing activation policy
```

---

# 6. Règle de commits

```text
docs(adr): define inventory costing activation policy
docs(adr): define cash refund ownership
refactor(costing): add inventory costing bounded context
feat(costing): add moving weighted average calculator
feat(costing): add stock valuation aggregate
feat(costing): add valuation movement ledger
feat(costing): add valuation bootstrap
feat(inventory): value initial stock and adjustments
feat(sales): add sale line cost snapshot
feat(costing): value sale stock consumption
feat(sales): add return sale aggregate
feat(inventory): restock returned sale items
feat(costing): restore original sale cost on return
feat(payments): add cash payment refund
test(returns): verify cumulative return limits
test(costing): verify zero quantity zero value invariant
test(returns): verify return transaction rollback
```

---

# 7. Vue d’ensemble

```text
Epic 5.1 — Inventory Costing foundation
Epic 5.2 — StockValuation aggregate
Epic 5.3 — StockValuationMovement ledger
Epic 5.4 — Moving weighted average
Epic 5.5 — Costing bootstrap
Epic 5.6 — Sale costing integration
Epic 5.7 — SaleLineCostSnapshot
Epic 5.8 — CompleteSale costing atomicity
Epic 5.9 — ReturnSale foundation
Epic 5.10 — Inventory restock integration
Epic 5.11 — Return costing
Epic 5.12 — Return amount calculation
Epic 5.13 — Essential cash refund
Transverse — Authorization, audit, events et API
Transverse — PostgreSQL, RLS, concurrence, rollback et idempotence
Lot 5 Gate
```

---

# 8. Epic 5.1 — Inventory Costing foundation

Structure recommandée :

```text
src/Modules/InventoryCosting/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Interdit :

```text
InventoryCosting\Domain → Inventory\Infrastructure
InventoryCosting\Domain → Sales\Domain
InventoryCosting\Domain → Purchasing\Domain
```

Commit :

```text
refactor(costing): add inventory costing bounded context
```

---

# 9. Epic 5.2 — StockValuation

```text
StockValuation
├── StockValuationId?
├── OrganizationId
├── StoreId
├── ProductId
├── StockId
├── quantityOnHand
├── totalValue
├── currency
├── status?
└── Version
```

Invariants :

```text
quantityOnHand >= 0
totalValue >= 0
quantityOnHand = 0 → totalValue = 0
currency = Store.currency
StockValuation unique par StockId
```

Lorsque quantité > 0 :

```text
averageUnitCost = totalValue / quantityOnHand
```

Commit :

```text
feat(costing): add stock valuation aggregate
```

---

# 10. Epic 5.3 — StockValuationMovement

```text
StockValuationMovement
├── StockValuationMovementId
├── OrganizationId
├── StoreId
├── ProductId
├── StockId
├── StockMovementId
├── type
├── quantity
├── unitCost
├── value
├── previousTotalValue
├── resultingTotalValue
├── previousAverageCost
├── resultingAverageCost
├── source
├── occurredAt
├── correlationId
└── append-only
```

Invariant :

```text
StockMovementId
→ max 1 StockValuationMovement
```

Commit :

```text
feat(costing): add valuation movement ledger
```

---

# 11. Epic 5.4 — Moving weighted average

Créer :

```text
MovingWeightedAverageCalculator
```

Entrée :

```text
previousQuantity
previousTotalValue
movementQuantity
movementUnitCost
movementDirection
```

Sortie :

```text
resultingQuantity
resultingTotalValue
resultingAverageUnitCost
movementValue
```

Tester :

- entrées successives à coûts différents ;
- sorties partielles ;
- sortie finale ;
- quantités décimales ;
- coûts décimaux ;
- résidus ;
- absence de float.

Commit :

```text
feat(costing): add moving weighted average calculator
```

---

# 12. Epic 5.5 — Costing bootstrap

Conformément à l’ADR-0021 :

```text
InitializeStockValuation
├── storeId
├── productId
├── openingUnitCost
└── reason
```

Préconditions :

- Stock existe ;
- valuation non initialisée ;
- permission dédiée ;
- raison obligatoire ;
- même tenant/store/devise.

Résultat :

```text
quantity = Stock.quantityOnHand
totalValue = quantity × openingUnitCost
```

Permission :

```text
INVENTORY_COSTING_INITIALIZE
```

Commit :

```text
feat(costing): add valuation bootstrap
```

---

# 13. Epic 5.6 — Intégration vente / costing

Le workflow `CompleteSale` est enrichi :

```text
Sale
Payment
CashMovement
Stock
StockMovement SALE
StockValuation
StockValuationMovement
SaleLineCostSnapshot
Outbox
```

Pour chaque ligne suivie :

```text
valueOut = baseQuantity × currentAverageUnitCost
```

Contrat applicatif :

```text
InventoryCostingService.valueSaleConsumption(...)
```

Commit :

```text
feat(costing): value sale stock consumption
```

---

# 14. Epic 5.7 — SaleLineCostSnapshot

```text
SaleLineCostSnapshot
├── SaleLineId
├── StockId
├── StockMovementId
├── quantity
├── unitCost
├── totalCost
├── currency
├── valuationVersion
└── occurredAt
```

Règles :

- immutable après completion ;
- service → aucun coût Inventory fictif ;
- produit non suivi → pas de snapshot Costing ;
- quantité = baseQuantity vendue.

Commit :

```text
feat(sales): add sale line cost snapshot
```

---

# 15. Epic 5.8 — Atomicité CompleteSale avec costing

```text
BEGIN

validate Sale
create/confirm Payment
consume Stock
append StockMovement SALE

update StockValuation
append StockValuationMovement
capture SaleLineCostSnapshot

append CashMovement SALE_PAYMENT
complete Sale
append Outbox

COMMIT
```

Échec Costing :

```text
rollback Sale
rollback Payment
rollback Cash
rollback Stock
rollback StockMovement
rollback Valuation
rollback Outbox
```

Commit :

```text
test(costing): verify CompleteSale costing atomicity
```

---

# 16. Epic 5.9 — ReturnSale foundation

Aggregate proposé :

```text
ReturnSale
├── ReturnSaleId
├── OrganizationId
├── StoreId
├── SaleId
├── status
├── lines
├── reason?
├── createdBy
├── createdAt
├── completedBy?
├── completedAt?
├── BusinessDate
└── Version
```

Statuts :

```text
DRAFT
COMPLETED
CANCELLED
```

Ligne :

```text
ReturnSaleLine
├── ReturnSaleLineId
├── SaleLineId
├── ProductId
├── returnedQuantity
├── baseReturnedQuantity
├── restock
├── reason?
└── originalSnapshots
```

MVP :

```text
restock = true | false
```

Commits :

```text
feat(sales): add return sale aggregate
feat(sales): add return sale line model
```

---

# 17. Invariants ReturnSale

Un retour cible :

```text
Sale.status = COMPLETED
```

Pour chaque ligne :

```text
cumulativeReturnedQuantity
<= originalSoldQuantity
```

Le retour utilise les snapshots originaux :

```text
packaging
conversion
price
discount
tax
cost
```

Il ne résout pas silencieusement le catalogue courant.

Commit :

```text
feat(sales): enforce cumulative return limits
```

---

# 18. Epic 5.10 — Inventory restock

Si :

```text
restock = true
```

Inventory exécute :

```text
Stock.restockFromSaleReturn(...)
```

et crée :

```text
StockMovement
type = SALE_RETURN
source = RETURN / ReturnSaleId
```

Si :

```text
restock = false
```

aucun changement Stock.

Idempotence :

```text
SALE_RETURN + returnId + productId
```

Commits :

```text
feat(inventory): restock returned sale items
feat(inventory): make sale return restock idempotent
```

---

# 19. Epic 5.11 — Return costing

Pour un restock :

```text
restoredUnitCost
=
SaleLineCostSnapshot.unitCost
```

Exemple :

```text
original unit cost = 4 000
current average cost = 4 700
return quantity = 2
restored value = 8 000
```

Puis recalcul du coût moyen.

Créer un `StockValuationMovement` lié au `StockMovement SALE_RETURN`.

Si `restock=false` :

```text
aucun StockValuationMovement
```

Commit :

```text
feat(costing): restore original sale cost on return
```

---

# 20. Epic 5.12 — Montants de retour

Les montants sont déterminés à partir des snapshots originaux.

Ne pas recalculer avec :

```text
prix courant
taxe courante
promotion courante
```

Créer :

```text
ReturnAmountCalculator
```

Garanties :

```text
cumulativeReturnedAmount <= original amount
cumulativeReturnedTax <= original tax
full return = exact refundable total
```

Commit :

```text
feat(sales): calculate return amounts from original snapshots
```

---

# 21. Epic 5.13 — Refund cash essentiel

Le modèle final suit les ADR Payment existants.

Le Lot 5 n’a besoin que du chemin :

```text
CASH
```

Invariants :

- paiement original confirmé ;
- `ReturnSale` original terminé ;
- montant > 0 ;
- cumul remboursé <= montant confirmé ;
- cumul remboursé <= montant remboursable du retour ;
- même devise ;
- idempotence ;
- méthode originale privilégiée.

Cash effect :

```text
CashMovement
type = REFUND
direction = OUT
```

sur une `CashSession OPEN`.

Un refund ne modifie jamais automatiquement Inventory.

L’ownership et le workflow sont fixés par
[l’ADR-0022](../architecture/adr/0022-cash-refund-ownership-and-workflow.md).

Commits :

```text
feat(payments): add cash payment refund
feat(cash): record cash refund movement
```

---

# 22. Coordination Return + Refund

Le Lot 5 conserve deux commandes transactionnelles distinctes.

Finalisation du retour :

```text
BEGIN

validate original Sale
validate return quantities
create ReturnSale

if restock:
    Stock.restockFromSaleReturn
    StockMovement SALE_RETURN
    restore original cost
    StockValuationMovement

complete ReturnSale
Outbox

COMMIT
```

Remboursement ultérieur :

```text
BEGIN

validate completed ReturnSale through Sales contract
lock original Payment and cumulative refunds
create PaymentRefund
append CashMovement REFUND
append Audit and Outbox

COMMIT
```

Un échec financier ne réouvre pas le retour. Le client peut rejouer
explicitement le remboursement avec la même clé d’idempotence.

---

# 23. Cas sans restock

```text
restock = false
```

Résultat :

```text
ReturnSale completed
no StockMovement
no StockValuationMovement
```

Le remboursement éventuel reste indépendant.

Pour un `SERVICE` :

```text
restock = false
```

---

# 24. Permissions Lot 5

```text
INVENTORY_COSTING_READ
INVENTORY_COSTING_INITIALIZE

SALE_RETURN_CREATE
SALE_RETURN_READ
SALE_RETURN_COMPLETE
SALE_RETURN_CANCEL

PAYMENT_REFUND_CREATE
PAYMENT_REFUND_READ
```

Les overrides éventuels exigent permissions spécifiques et audit.

---

# 25. Audit & events

Audit sensible :

```text
VALUATION_BOOTSTRAP
RETURN_OVERRIDE
REFUND_OVERRIDE
```

Events possibles :

```text
StockValuationInitialized
StockValuationChanged
SaleReturned
SaleItemReturned
PaymentRefundCreated
PaymentRefundConfirmed
```

Les events passent via l’envelope versionnée et l’outbox.

---

# 26. API Costing

```text
GET /api/stores/{storeId}/inventory-valuations
GET /api/stores/{storeId}/inventory-valuations/{productId}
GET /api/stores/{storeId}/inventory-valuations/{productId}/movements
```

Bootstrap si retenu :

```text
POST /api/stores/{storeId}/inventory-valuations/{productId}/initialize
```

Pas de PATCH générique de `totalValue`.

---

# 27. API Returns

```text
POST /api/sales/{saleId}/returns
POST /api/returns/{returnId}/lines
POST /api/returns/{returnId}/complete
POST /api/returns/{returnId}/cancel
GET  /api/returns/{returnId}
GET  /api/sales/{saleId}/returns
```

Payload de ligne :

```json
{
  "saleLineId": "...",
  "quantity": "2",
  "restock": true,
  "reason": "Customer return"
}
```

---

# 28. API Cash Refund

```text
POST /api/payments/{paymentId}/refunds
```

Payload :

```json
{
  "returnSaleId": "...",
  "cashSessionId": "...",
  "amount": {
    "amount": "5000",
    "currency": "XAF"
  },
  "reason": "Returned goods"
}
```

Le header `Idempotency-Key` est obligatoire. Aucune route concurrente sous
`/api/returns` n’est exposée dans le MVP.

---

# 29. Error contract

```text
VALUATION_NOT_INITIALIZED
VALUATION_ALREADY_INITIALIZED
VALUATION_CURRENCY_MISMATCH

SALE_NOT_RETURNABLE
RETURN_QUANTITY_EXCEEDS_SOLD
RETURN_ALREADY_COMPLETED
RETURN_NOT_EDITABLE
RETURN_LINE_NOT_FOUND

SALE_LINE_COST_SNAPSHOT_NOT_FOUND
RETURN_COSTING_CONFLICT

PAYMENT_NOT_REFUNDABLE
REFUND_AMOUNT_EXCEEDS_PAYMENT
CASH_SESSION_NOT_OPEN
REFUND_CURRENCY_MISMATCH

IDEMPOTENCY_CONFLICT
```

---

# 30. Persistence & RLS

Tables possibles :

```text
inventory_costing.stock_valuation
inventory_costing.stock_valuation_movement
sales.return_sale
sales.return_sale_line
payments.payment_refund
```

Contraintes :

```text
UNIQUE StockValuation.StockId
UNIQUE StockValuationMovement.StockMovementId
```

Toutes les données tenant-owned utilisent RLS.

---

# 31. Tests Costing

Couvrir :

```text
initialize valuation
reject second initialization
weighted average after entry
sale output at average cost
partial sale
final sale zeroes total value
decimal quantities
decimal costs
currency mismatch
service has no costing
```

Commit :

```text
test(costing): cover moving weighted average invariants
```

---

# 32. Tests ReturnSale

```text
return completed sale
reject draft sale return
partial return
multiple partial returns
reject cumulative quantity > sold
restock true
restock false
service without restock
cancel draft return
reject mutation completed return
```

Commit :

```text
test(returns): cover return sale invariants
```

---

# 33. Test coût original restauré

```text
Sale:
quantity = 2
original unit cost = 4 000

Current stock:
quantity = 8
average cost = 5 000
total = 40 000

Return:
quantity = 1
restock = true
```

Après :

```text
stock quantity = 9
restored value = 4 000
total value = 44 000
new average = 44 000 / 9
```

et non 45 000.

Commit :

```text
test(costing): restore original cost on sale return
```

---

# 34. Tests Refund

```text
confirmed cash payment
partial refund
multiple partial refunds
full refund
reject cumulative refund > paid
wrong currency
closed cash session
wrong store
wrong tenant
retry same refund
```

Commit :

```text
test(payments): cover cash refund invariants
```

---

# 35. Atomicité ReturnSale

Injecter des erreurs :

```text
after ReturnSale creation
after Stock restock
after StockMovement SALE_RETURN
after StockValuation update
after StockValuationMovement
after PaymentRefund
after CashMovement REFUND
after ReturnSale completion
after Audit
after Outbox
before COMMIT
```

Tout échec pré-commit :

```text
rollback total
```

Commit :

```text
test(returns): verify return transaction failure matrix
```

---

# 36. Idempotence & concurrence

Un retry ne doit jamais :

```text
restocker deux fois
restaurer deux fois la valeur
rembourser deux fois
sortir deux fois l’argent
```

Concurrence retour :

```text
sold quantity = 5

Return A = 3
Return B = 3

→ cumulative <= 5
```

Commits :

```text
test(returns): verify return and refund idempotence
test(returns): verify concurrent return quantity safety
```

---

# 37. Tenant isolation

```text
Tenant B reads valuation Tenant A
→ NOT_FOUND

Tenant B creates return on Sale A
→ NOT_FOUND

Tenant B refunds Payment A
→ NOT_FOUND

Store-scoped actor A returns Sale Store B
→ denied
```

---

# 38. Démonstration consolidée

## Costing

```text
Stock = 10
totalValue = 40 000 XAF
averageUnitCost = 4 000 XAF
```

Vente de 2 :

```text
Stock = 8
SaleLineCostSnapshot = 8 000
StockValuation = 32 000
```

## Return

Retour de 1 avec restock :

```text
Stock = 9
StockMovement SALE_RETURN = +1
original cost restored = 4 000
StockValuation = 36 000
```

Retour avec `restock=false` :

```text
no Inventory effect
no Costing effect
```

Refund cash :

```text
PaymentRefund created
CashMovement REFUND created
expected cash decreases
```

Protections :

```text
return > sold → reject
refund > paid → reject
retry → no duplicate
Tenant B → NOT_FOUND
injected failure → rollback total
```

---

# 39. Gate de sortie du Lot 5

Le Lot 5 est `DONE` uniquement lorsque :

```text
[ ] Inventory Costing matérialisé
[ ] boundaries protégées
[ ] aucun Product.costPrice

[ ] politique de bootstrap décidée par ADR
[ ] aucune valorisation historique inventée

[ ] StockValuation opérationnelle
[ ] unique par Stock
[ ] quantity cohérente avec Stock
[ ] totalValue exact
[ ] currency cohérente
[ ] quantity=0 implique totalValue=0

[ ] MOVING_WEIGHTED_AVERAGE opérationnel
[ ] calculs décimaux exacts
[ ] résidus de sortie finale absorbés
[ ] aucun float

[ ] StockValuationMovement append-only
[ ] max un movement par StockMovement

[ ] CompleteSale enrichi du costing
[ ] vente sort au coût moyen courant
[ ] SaleLineCostSnapshot conservé
[ ] service sans coût Inventory fictif
[ ] produit non suivi sans costing Inventory
[ ] transaction CompleteSale toujours atomique

[ ] ReturnSale opérationnel
[ ] retour partiel supporté
[ ] retours multiples supportés
[ ] cumul retourné <= quantité vendue
[ ] snapshots originaux utilisés
[ ] ReturnSale completed immutable

[ ] restock=true crée SALE_RETURN
[ ] restock=false ne modifie pas Stock
[ ] retour restocké restaure coût original
[ ] StockValuationMovement créé
[ ] effet return idempotent

[ ] remboursement distinct du retour
[ ] cash refund essentiel opérationnel
[ ] cumul remboursé <= paiement confirmé
[ ] CashMovement REFUND créé
[ ] refund ne restocke jamais automatiquement
[ ] refund idempotent

[ ] montants/taxes/remises basés sur snapshots originaux
[ ] retour complet restitue exactement le montant remboursable

[ ] permissions Lot 5 ajoutées
[ ] Store scopes appliqués
[ ] audit sensible opérationnel

[ ] API Costing disponible
[ ] API Returns disponible
[ ] API cash Refund disponible
[ ] OpenAPI à jour
[ ] contrat d’erreurs stable

[ ] persistence PostgreSQL réelle
[ ] contraintes d’unicité actives
[ ] RLS actif
[ ] cross-tenant NOT_FOUND

[ ] domain tests verts
[ ] costing tests verts
[ ] return tests verts
[ ] refund tests verts
[ ] rollback matrix verte
[ ] concurrency tests verts
[ ] idempotence tests verts
[ ] tenant isolation tests verts
[ ] RLS tests verts
[ ] architecture fitness tests verts
[ ] PHPStan vert
[ ] PHP-CS-Fixer vert
[ ] Composer audit vert
[ ] CI verte

[ ] démonstration consolidée réussie

[ ] aucun Purchasing
[ ] aucun GoodsReceipt
[ ] aucun PurchaseReturn
[ ] aucun StockTransfer
[ ] aucun StockCount
[ ] aucun Customer Credit
[ ] aucun provider Payment
[ ] aucun StockReservation
[ ] aucun Reporting avancé
[ ] aucun Offline

[ ] IMPLEMENTATION_STATUS.md mis à jour
[ ] ADR mis à jour si décision DÉCIDÉ modifiée
```

---

# 40. Hors périmètre

```text
Supplier
PurchaseOrder
GoodsReceipt
GoodsReceiptCorrection
PurchaseReturn
SupplierPayment

StockTransfer
StockCount
StockCountLine

StockReservation

Customer
CustomerAccount
CustomerReceivable

Payment provider
PaymentAttempt
provider callback
provider reversal

Reporting avancé
Offline
```

---

# 41. Transition vers le Lot 6

Après Lot 5, Zandu sait :

```text
vendre
valoriser la sortie vendue
conserver le coût historique
retourner partiellement ou totalement
remettre explicitement en stock
restaurer le coût original
rembourser en cash
```

Le Lot 6 introduira :

```text
Purchasing
├── Supplier
├── PurchaseOrder
├── GoodsReceipt
├── direct receipt selon policy
├── inventoryUnitCost
└── GoodsReceiptCorrection
```

et alimentera :

```text
Stock
+
StockMovement PURCHASE_RECEIPT
+
StockValuation
+
StockValuationMovement
```

---

# 42. Premier point d’entrée

Ordre recommandé :

```text
1. décisions ADR-0021 et ADR-0022
2. Inventory Costing foundation et boundaries
3. MovingWeightedAverageCalculator
4. StockValuation et StockValuationMovement
5. persistence, RLS et bootstrap valuation
6. valoriser INITIAL_STOCK et ADJUSTMENT_IN/OUT
7. enrichir CompleteSale et SaleLineCostSnapshot
8. ReturnSale et calcul des montants
9. Inventory SALE_RETURN
10. restaurer le coût original
11. PaymentRefund et CashMovement REFUND
12. permissions, audit, events et API
13. rollback, idempotence, concurrence et tenant isolation
14. Gate
```

Premiers commits :

```text
docs(adr): define inventory costing activation policy
docs(adr): define cash refund ownership
refactor(costing): add inventory costing bounded context
feat(costing): add moving weighted average calculator
feat(costing): add stock valuation aggregate
feat(costing): add valuation movement ledger
feat(costing): add valuation bootstrap
feat(inventory): value initial stock and adjustments
feat(sales): add sale line cost snapshot
feat(costing): value sale stock consumption
feat(sales): add return sale aggregate
feat(inventory): restock returned sale items
feat(costing): restore original sale cost on return
feat(payments): add cash payment refund
test(returns): verify return transaction failure matrix
```

---

# 43. Principe de travail

Pour chaque étape :

1. vérifier baseline et ADR ;
2. vérifier l’état réel du repository ;
3. ne pas inventer un coût historique ;
4. conserver séparation Stock / Valuation ;
5. conserver séparation Return / Refund ;
6. préserver tous les snapshots historiques ;
7. utiliser les Application Contracts entre contexts ;
8. garantir idempotence ;
9. tester les cumuls de quantités et montants ;
10. tester concurrence sur PostgreSQL réel ;
11. injecter les échecs des transactions critiques ;
12. vérifier RLS et tenant isolation ;
13. exécuter PHPStan, PHP-CS-Fixer, Deptrac et Composer audit ;
14. effectuer un commit atomique ;
15. mettre à jour `IMPLEMENTATION_STATUS.md`.
