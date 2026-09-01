# 25. Hors périmètre du Lot 3

Ne pas introduire :

```text
Sale
SaleLine
SalePricingCalculator
CompleteSale
ReturnSale
RefundSale

Payment
PaymentAttempt
PaymentSettlement
PaymentRefund

Customer
CustomerAccount
CustomerReceivable

StockTransfer
StockCount
StockCountLine
StockReservation

StockValuation
StockValuationMovement
InventoryCosting

Supplier
PurchaseOrder
GoodsReceipt
PurchaseReturn

Reporting projections

OfflineCommand
DeviceGrant
SyncState
LocalCashSession
LocalStockPosition
```

Des enums ou contracts préparatoires peuvent contenir des références futures déjà décidées, mais aucun workflow futur ne doit être implémenté par anticipation.

---
