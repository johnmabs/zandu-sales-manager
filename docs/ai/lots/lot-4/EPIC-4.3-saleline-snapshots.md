# 10. Epic 4.3 — SaleLine & snapshots

## SaleLine

```text
SaleLine
├── SaleLineId
├── ProductId
├── ProductPackagingId
├── productCodeSnapshot?
├── productNameSnapshot?
├── packagingCodeSnapshot
├── packagingNameSnapshot?
├── unitIdSnapshot
├── enteredQuantity
├── conversionFactorSnapshot
├── baseQuantity
├── unitPrice
├── priceListId?
├── productPriceId?
├── discountSnapshot?
├── promotionSnapshot?
├── taxSnapshot?
├── subtotal
├── discountAmount
├── taxableAmount
├── taxAmount
├── total
└── sourceVersions
```

Règle :

```text
baseQuantity
=
enteredQuantity × conversionFactorSnapshot
```

## Contrat Catalog

Créer/réutiliser :

```text
SaleProductProvider
```

retournant un DTO applicatif, jamais l’aggregate Product.

## AddSaleLine

```text
AddSaleLine
├── saleId
├── productId
├── productPackagingId
└── quantity
```

Préconditions :

- Sale modifiable ;
- produit vendable ;
- packaging vendable ;
- quantité conforme à precision/minimum/increment ;
- prix résolvable.

Commits :

```text
feat(catalog): expose sellable product contract
feat(sales): add sale line snapshots
feat(sales): add sale line use case
feat(sales): add draft sale line editing
```

### DoD Epic 4.3

- snapshots historiques ;
- conversion exacte ;
- lignes immuables après completion.

---
