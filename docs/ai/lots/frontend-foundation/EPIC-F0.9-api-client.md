# 18. Epic F0.9 — API client

Créer :

```text
packages/api-client
```

Architecture :

```text
Feature UI
   ↓
feature service / hooks
   ↓
typed API client
   ↓
HTTP
   ↓
Symfony API
```

Interdit comme pratique normale :

```ts
fetch('/api/...') 
```

directement dans chaque composant.

---
