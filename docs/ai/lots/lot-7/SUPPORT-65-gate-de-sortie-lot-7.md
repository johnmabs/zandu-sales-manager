# 65. Gate de sortie Lot 7

Le Lot 7 est `DONE` uniquement lorsque :

```text
[ ] StockTransfer aggregate opérationnel
[ ] DRAFT
[ ] SHIPPED
[ ] RECEIVED
[ ] CANCELLED
[ ] source != destination
[ ] stores même tenant
[ ] produit unique par transfert

[ ] requestedQuantity > 0
[ ] shippedQuantity bornée
[ ] receivedQuantity bornée
[ ] zéro conservé sans mouvement nul

[ ] ShipStockTransfer atomique
[ ] TRANSFER_OUT créé
[ ] source Stock décrémenté
[ ] source Valuation décrémentée
[ ] snapshot coût/valeur transportée
[ ] aucune expédition partielle transactionnelle

[ ] ReceiveStockTransfer atomique
[ ] TRANSFER_IN créé
[ ] destination Stock incrémenté
[ ] valeur transférée au destination
[ ] réception finale unique
[ ] aucune réception partielle successive

[ ] discrepancy conservée
[ ] surplus non créé automatiquement
[ ] transit value discrepancy conservée
[ ] transfer idempotent
[ ] transfer concurrency safe

[ ] réception transfert déjà expédié
    autorisable pendant suspension selon baseline

[ ] StockCount aggregate opérationnel
[ ] StockCountLine aggregate séparé
[ ] FULL
[ ] PARTIAL
[ ] BLIND
[ ] GUIDED si activé
[ ] DRAFT
[ ] OPEN
[ ] FINALIZING
[ ] COMPLETED
[ ] CANCELLED

[ ] snapshot expectedQuantity à OPEN
[ ] produit sans Stock => expected 0
[ ] aucun Stock vide créé inutilement
[ ] OpenStockCountScope matérialisé
[ ] unique lock org/store/product

[ ] tous mouvements concurrents bloqués
    pour produits OPEN/FINALIZING
[ ] mouvements hors scope autorisés

[ ] countedQuantity null = non compté
[ ] countedQuantity zero = compté à zéro
[ ] correction saisie possible en OPEN
[ ] line revision/version opérationnelle

[ ] finalization exige toutes lignes comptées
[ ] FINALIZING fige les saisies
[ ] annulation FINALIZING interdite

[ ] reconciliation par batch
[ ] PENDING / RECONCILED
[ ] snapshot conflict détecté
[ ] variance IN correcte
[ ] variance OUT correcte
[ ] variance zero sans mouvement
[ ] Stock.reconcile utilisé

[ ] StockCount costing intégré
[ ] correction OUT au coût moyen
[ ] correction IN au coût moyen si existant
[ ] manual cost protégé si aucun coût
[ ] INVENTORY_COST_ASSIGN
[ ] raison obligatoire
[ ] StockValuationMovement créé

[ ] crash recovery prouvée
[ ] lignes RECONCILED jamais rejouées
[ ] scopes libérés seulement à completion/cancel autorisé

[ ] StoreClosure transfer blocker actif
[ ] StoreClosure stock count blocker actif

[ ] permissions Transfer ajoutées
[ ] permissions StockCount ajoutées
[ ] Store scopes appliqués
[ ] coût protégé par permissions
[ ] audit sensible disponible

[ ] API StockTransfer
[ ] API StockCount
[ ] BLIND protégé backend
[ ] OpenAPI à jour
[ ] error contract stable

[ ] persistence PostgreSQL réelle
[ ] indexes / uniques
[ ] RLS actif
[ ] cross-tenant NOT_FOUND

[ ] domain tests verts
[ ] transfer integration tests verts
[ ] costing transfer tests verts
[ ] stock count tests verts
[ ] lock tests verts
[ ] reconciliation tests verts
[ ] crash recovery tests verts
[ ] rollback matrices vertes
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

[ ] démonstration StockTransfer réussie
[ ] démonstration StockCount réussie

[ ] M3 formellement validé

[ ] aucun Customers
[ ] aucun Credit
[ ] aucun provider Payment
[ ] aucun StockReservation
[ ] aucun Reporting avancé
[ ] aucun Offline
[ ] aucun StockLot/LotTracking

[ ] IMPLEMENTATION_STATUS.md mis à jour
[ ] ADR mis à jour si décision structurante modifiée
```

---
