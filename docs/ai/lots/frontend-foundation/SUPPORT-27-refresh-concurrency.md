# 27. Refresh concurrency

Plusieurs requêtes recevant simultanément une expiration ne doivent pas déclencher :

```text
10 refresh requests
```

Le client doit avoir une stratégie :

```text
single-flight refresh
```

puis reprendre les requêtes compatibles.

Si refresh échoue :

```text
clear session
→ login
```

---
