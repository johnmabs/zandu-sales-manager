# 17. Store suspension & transfer

La baseline autorise pendant suspension :

```text
ReceiveStockTransfer
```

pour un transfert déjà expédié, car il s’agit d’une terminaison/remédiation.

Elle interdit de démarrer :

```text
CreateStockTransfer
ShipStockTransfer
```

comme nouvelle opération si la policy l’interdit.

Donc :

```text
Store SUSPENDED destination
+
Transfer already SHIPPED
→ réception contrôlée autorisable
```

Ce comportement doit être testé explicitement.

---
