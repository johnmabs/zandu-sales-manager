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
