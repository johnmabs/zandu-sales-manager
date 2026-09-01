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
