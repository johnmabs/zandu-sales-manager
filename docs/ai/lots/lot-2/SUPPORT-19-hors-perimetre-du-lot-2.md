# 19. Hors périmètre du Lot 2

Ne pas introduire prématurément :

```text
Stock
StockMovement
StockTransfer
StockCount
StockReservation

Inventory Costing
StockValuation
StockValuationMovement

CashRegister
CashSession
CashMovement

Sale
SaleLine
CompleteSale
ReturnSale
RefundSale

Payment
PaymentAttempt
Settlement
PaymentRefund

Customer
CustomerAccount
Receivable

Supplier
PurchaseOrder
GoodsReceipt
PurchaseReturn

Reporting projections

OfflineCommand
SyncState
DeviceGrant
LocalOperationLedger
```

Ne pas introduire non plus sans besoin réel :

```text
ProductVariant
complex promotion engine
custom role engine changes
store-specific pricing engine
provider payments
negative stock configuration
```

Des contrats publics minimaux peuvent être préparés lorsqu’ils sont nécessaires pour le Lot 3 ou le Lot 4, mais aucun bounded context futur ne doit être implémenté par anticipation.

---
