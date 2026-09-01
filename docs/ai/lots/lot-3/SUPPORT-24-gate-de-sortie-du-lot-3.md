# 24. Gate de sortie du Lot 3

Le Lot 3 est `DONE` uniquement lorsque :

```text
[ ] Inventory module opérationnel
[ ] CashManagement module opérationnel
[ ] architecture boundaries protégées

[ ] Catalog → Inventory contract disponible
[ ] aucun import Catalog Domain dans Inventory

[ ] Stock aggregate opérationnel
[ ] unicité Organization + Store + Product
[ ] StockQuantity >= 0
[ ] MovementQuantity > 0
[ ] aucun float
[ ] persistence Stock PostgreSQL

[ ] StockMovement append-only
[ ] direction dérivée du type
[ ] source structurée
[ ] aucun update/delete métier

[ ] InitializeStock opérationnel
[ ] InitializeStock possible une seule fois
[ ] initialisation à zéro couverte explicitement
[ ] AdjustStock opérationnel
[ ] delta zéro interdit
[ ] reason obligatoire
[ ] résultat négatif interdit

[ ] Stock + StockMovement + Audit + Outbox atomiques
[ ] idempotence Stock prouvée
[ ] stratégie concurrence du Spike E appliquée
[ ] tests PostgreSQL concurrents verts

[ ] CashRegister aggregate opérationnel
[ ] lifecycle CashRegister testé
[ ] register tenant/store scoped
[ ] persistence CashRegister PostgreSQL

[ ] CashSession aggregate opérationnel
[ ] max une session OPEN par CashRegister
[ ] contrainte base renforçant unicité OPEN
[ ] OpenCashSession opérationnel
[ ] CloseCashSession opérationnel
[ ] expectedClosingBalance calculé
[ ] discrepancy calculé
[ ] CLOSED immuable

[ ] CashMovement append-only
[ ] CASH_IN opérationnel
[ ] CASH_OUT opérationnel
[ ] CASH_WITHDRAWAL opérationnel si retenu
[ ] amount > 0
[ ] movement interdit sur session CLOSED
[ ] CashMovement idempotence préparée
[ ] openingBalance non dupliqué en movement

[ ] Cash mutation + Audit + Outbox atomiques

[ ] Inventory StoreClosure blocker opérationnel
[ ] Stock > 0 bloque fermeture Store
[ ] Cash StoreClosure blocker opérationnel
[ ] CashSession OPEN bloque fermeture Store
[ ] CloseCashSession autorisé pendant suspension selon politique

[ ] permissions Inventory disponibles
[ ] permissions Cash disponibles
[ ] rôles système mis à jour
[ ] AuthorizationService utilisé
[ ] Store scopes appliqués
[ ] OrganizationOperationalGuard actif
[ ] StoreOperationalGuard actif

[ ] API Stock disponible
[ ] API StockMovement read-only disponible
[ ] API CashRegister disponible
[ ] API CashSession disponible
[ ] API CashMovement intentionnelle disponible
[ ] aucun PATCH/DELETE ledger
[ ] OpenAPI à jour
[ ] contrat d’erreurs stable

[ ] application contracts pour Lot 4 disponibles
[ ] InventoryStockConsumer contract-testé
[ ] CashMovementRecorder contract-testé
[ ] aucun Sales Domain introduit

[ ] RLS Inventory actif
[ ] RLS Cash actif
[ ] cross-tenant NOT_FOUND
[ ] deux connexions PostgreSQL prouvent isolation
[ ] optimistic/version/conditional strategy testée

[ ] domain unit tests verts
[ ] integration tests verts
[ ] concurrency tests verts
[ ] rollback tests verts
[ ] tenant isolation tests verts
[ ] RLS tests verts
[ ] API contract tests verts
[ ] architecture fitness tests verts
[ ] PHPStan vert
[ ] PHP-CS-Fixer vert
[ ] Composer audit vert
[ ] CI verte

[ ] démonstration consolidée Lot 3 réussie

[ ] aucun Sale
[ ] aucun SaleLine
[ ] aucun Payment
[ ] aucun CompleteSale

[ ] aucun StockTransfer
[ ] aucun StockCount
[ ] aucun StockReservation
[ ] aucun Inventory Costing
[ ] aucun Purchasing workflow

[ ] IMPLEMENTATION_STATUS.md mis à jour
[ ] ADR mis à jour si décision DÉCIDÉ modifiée
[ ] nouvelles décisions structurantes documentées
```

---
