# 30. Store selector

Le sélecteur ne doit afficher que les stores accessibles.

Il doit gérer :

```text
0 stores
1 store
multiple stores
store suspended
store closed
scope changed while session active
```

Si accès révoqué :

```text
active store becomes invalid
→ context must be cleared/reselected
```

---
