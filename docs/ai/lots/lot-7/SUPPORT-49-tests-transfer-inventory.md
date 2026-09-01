# 49. Tests Transfer Inventory

Cas :

```text
Source Stock = 20
Transfer requested = 10
Ship = 8
Receive = 7
```

Après ship :

```text
source = 12
TRANSFER_OUT = 8
status = SHIPPED
```

Après receive :

```text
destination += 7
TRANSFER_IN = 7
status = RECEIVED
discrepancy = 1
```

Aucun mouvement automatique pour l’unité manquante.

---
