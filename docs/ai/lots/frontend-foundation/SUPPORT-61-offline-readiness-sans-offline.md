# 61. Offline readiness sans offline

Le Foundation ne construit pas le Lot Offline.

Mais il évite :

```text
React component
→ directly knows SQLite schema
```

ou :

```text
component
→ directly calls Tauri SQL plugin
```

Les sources de données futures doivent pouvoir être substituées derrière des services/adapters.

---
