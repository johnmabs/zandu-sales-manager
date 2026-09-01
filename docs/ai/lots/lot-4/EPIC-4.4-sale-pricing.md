# 11. Epic 4.4 — Sale pricing

## SalePricingCalculator

Service pur et déterministe.

Ordre :

```text
1. quantity × unit price
2. line discounts
3. sale discount allocations
4. taxable amount
5. tax
6. gross line and sale totals
```

Le Lot 4 n’implémente que les mécanismes effectivement disponibles depuis le Lot 2.

## Price resolution

Utiliser :

```text
ProductPriceResolver
```

Snapshot :

```text
priceListId
productPriceId
unitPrice
currency
sourceVersion
```

## Protection contre prix obsolète

Avant CompleteSale :

```text
pricing snapshot accepté
≠ pricing applicable
→ SALE_PRICING_CHANGED
```

Pas de mise à jour silencieuse.

## Price override

Si activé :

```text
SALE_PRICE_OVERRIDE
+
reason
+
SecurityAuditEntry
```

Commits :

```text
feat(sales): add deterministic sale pricing calculator
feat(sales): resolve product price for sale lines
feat(sales): prevent silent sale repricing
```

### DoD Epic 4.4

- pricing déterministe ;
- Money exact ;
- aucune devise implicite ;
- snapshots conservés.

---
