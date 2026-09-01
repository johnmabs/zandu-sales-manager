# 24. Gate de sortie du Lot 4

Le Lot 4 est `DONE` uniquement lorsque :

```text
[x] Sales bounded context opérationnel
[x] architecture boundaries protégées

[x] Sale aggregate opérationnel
[x] lifecycle Sale testé
[x] DRAFT disponible
[x] COMPLETED non éditable
[x] CANCELLED non finalisable
[x] aucune suppression de vente finalisée

[x] SaleLine opérationnelle
[x] packaging snapshot conservé
[x] conversionFactor snapshot conservé
[x] baseQuantity conservée
[x] prix snapshoté
[x] tax snapshot selon politique
[x] historique indépendant du catalogue courant

[x] SalePricingCalculator déterministe
[x] Money exact
[x] Quantity exacte
[x] aucun repricing silencieux
[x] ProductPriceNotFound géré
[x] SalePricingChanged géré

[x] fiscalité pilote explicitement décidée

[x] Payment CASH minimal opérationnel
[x] purpose SALE
[x] status CONFIRMED
[x] Payment distinct de CashMovement

[x] Inventory Application Contract utilisé
[x] aucun StockRepository importé dans Sales
[x] tracked product consomme Stock
[x] untracked product ne touche pas Stock
[x] Service ne touche pas Stock
[x] StockMovement SALE créé
[x] stock négatif impossible
[x] effet Inventory idempotent

[x] Cash Application Contract utilisé
[x] aucune mutation directe CashSession depuis Sales
[x] CashSession OPEN obligatoire
[x] CashMovement SALE_PAYMENT créé
[x] expected cash mis à jour
[x] effet Cash idempotent

[x] CompleteSale cash opérationnel
[x] coordinated local transaction
[x] Sale + Payment + Inventory + Cash + Outbox atomiques
[x] rollback Inventory failure
[x] rollback Cash failure
[x] rollback Outbox failure
[x] failure matrix verte

[x] CompleteSale idempotent
[x] Idempotency-Key supportée
[x] retry après commit sûr
[x] double completion concurrente sûre
[x] stock concurrency PostgreSQL réelle

[x] BusinessDate calculée avec Store.timeZone
[x] timestamps UTC séparés

[x] permissions Sales disponibles
[x] Store scopes appliqués
[x] OrganizationOperationalGuard actif
[x] StoreOperationalGuard actif

[x] API CreateSale
[x] API ReadSale
[x] API SaleLines
[x] API CancelSale
[x] API CompleteSale cash
[x] API Receipt
[x] OpenAPI à jour
[x] contrat d’erreurs stable

[x] RLS Sales actif
[x] RLS Payment actif
[x] cross-tenant NOT_FOUND

[x] domain tests verts
[x] persistence tests verts
[x] API contract tests verts
[x] integration tests verts
[x] rollback tests verts
[x] concurrency tests verts
[x] idempotence tests verts
[x] tenant isolation tests verts
[x] RLS tests verts
[x] architecture fitness tests verts
[x] PHPStan vert
[x] PHP-CS-Fixer vert
[x] Composer audit vert
[x] CI verte

[x] démonstration M2 réussie

[x] aucun ReturnSale
[x] aucun RefundSale
[x] aucun Customer Credit
[x] aucun payment provider
[x] aucun StockReservation
[x] aucun Inventory Costing
[x] aucun Purchasing
[x] aucun StockTransfer
[x] aucun StockCount
[x] aucun Reporting avancé
[x] aucun Offline

[x] IMPLEMENTATION_STATUS.md mis à jour
[x] ADR mis à jour si décision DÉCIDÉ modifiée
```

---
