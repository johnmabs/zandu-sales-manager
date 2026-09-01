# 26. Session bootstrap

Au démarrage :

```text
App
 ↓
resolve session
 ↓
fetch current actor / effective access context
 ↓
build frontend access state
 ↓
render protected shell
```

Ne pas afficher brièvement des écrans protégés avant résolution de session.

---
