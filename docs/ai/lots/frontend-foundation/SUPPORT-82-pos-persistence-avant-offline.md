# 82. POS persistence avant offline

Pour le premier POS online :

- ne pas utiliser SQLite comme cache métier complet prématurément ;
- isoler seulement les choix de persistance nécessaires ;
- préparer l’adapter futur.

Le vrai modèle :

```text
OfflineCommand
LocalOperationLedger
SyncState
```

arrive avec le lot Offline.

---
