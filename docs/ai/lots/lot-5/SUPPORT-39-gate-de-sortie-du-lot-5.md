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
