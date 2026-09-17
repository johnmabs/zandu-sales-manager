# 21. Epic 5.13 — Refund cash essentiel

Le modèle final suit les ADR Payment existants.

Le Lot 5 n’a besoin que du chemin :

```text
CASH
```

Invariants :

- paiement original confirmé ;
- `ReturnSale` original terminé ;
- montant > 0 ;
- cumul remboursé <= montant confirmé ;
- cumul remboursé <= montant remboursable du retour ;
- même devise ;
- idempotence ;
- méthode originale privilégiée.

Cash effect :

```text
CashMovement
type = REFUND
direction = OUT
```

sur une `CashSession OPEN`.

Un refund ne modifie jamais automatiquement Inventory.

L’ownership et le workflow sont fixés par
[l’ADR-0022](../../../specs/architecture/adr/0022-cash-refund-ownership-and-workflow.md).

Commits :

```text
feat(payments): add cash payment refund
feat(cash): record cash refund movement
```

---
