# 55. Mutation lifecycle

Une mutation critique doit distinguer :

```text
idle
submitting
unknown outcome
success
business failure
technical failure
```

Le cas :

```text
request timeout after server commit
```

est crucial.

Le frontend ne doit pas dire automatiquement :

```text
Échec
```

si le résultat réel est inconnu.

Il peut retry avec la même idempotency key.

---
