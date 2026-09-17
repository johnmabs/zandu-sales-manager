# Zandu Sales Manager — Lot 5 : Inventory Costing & Returns

**Version :** 1.0
**Statut :** Terminé — Gate Lot 5 validé le 28 août 2026
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
[l’ADR-0021](../../../specs/architecture/adr/0021-inventory-costing-activation-policy.md).
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


## Dependencies

Dependencies are capability-based. Follow dependencies explicitly named by the current Epic or support file; do not infer dependencies from Lot numbering.


## Source of truth

`docs/specs/planning/zandu-lot-5-inventory-costing-returns.md`

Open this complete specification only when the targeted AI files do not answer a required business question.
