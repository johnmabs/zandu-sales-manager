# 11. Epic 6.5 — Use cases PurchaseOrder

Créer :

```text
CreatePurchaseOrder
AddPurchaseOrderLine
UpdatePurchaseOrderLine
RemovePurchaseOrderLine
ConfirmPurchaseOrder
CancelPurchaseOrder
ClosePurchaseOrder
```

## Confirm

Préconditions :

- Store ACTIVE ;
- Supplier ACTIVE ;
- au moins une ligne ;
- produits achetables ;
- packaging `allowedForPurchase` si utilisé ;
- quantité > 0 ;
- coûts exacts ;
- currency cohérente.

## Close partial order

Une commande `PARTIALLY_RECEIVED` peut être fermée avant réception totale avec :

```text
reason
actor
audit
```

---
