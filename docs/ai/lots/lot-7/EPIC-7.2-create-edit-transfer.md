# 8. Epic 7.2 — Create / edit transfer

Commands :

```text
CreateStockTransfer
AddStockTransferLine
UpdateStockTransferLine
RemoveStockTransferLine
CancelStockTransfer
```

Autorisés uniquement en :

```text
DRAFT
```

Préconditions :

- Organization opérationnelle ;
- stores distincts ;
- même Organization ;
- stores existants ;
- source Store ACTIVE pour nouveau workflow ;
- destination Store compatible ;
- produit suivi en stock ;
- quantité demandée > 0.

Annulation :

```text
DRAFT → CANCELLED
```

raison selon policy.

Après expédition :

```text
aucune annulation simple
```

Commit :

```text
feat(inventory): add stock transfer draft lifecycle
```

---
