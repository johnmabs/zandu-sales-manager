# 37. Scope multi-store

Un transfert touche :

```text
sourceStoreId
destinationStoreId
```

L’autorisation doit donc valider le scope nécessaire sur les deux stores selon l’opération.

Exemple :

```text
Create/Ship
→ accès source + destination requis
```

Pour :

```text
Receive
```

la policy peut exiger destination scope, tout en tenant compte du contexte de sécurité global.

Cette règle doit être explicitement testée, pas supposée.

---
