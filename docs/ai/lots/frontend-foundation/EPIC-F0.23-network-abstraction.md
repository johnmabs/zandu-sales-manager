# 56. Epic F0.23 — Network abstraction

Le transport HTTP commun doit centraliser :

```text
base URL
auth header/cookies according to ADR
correlation headers
idempotency key
content type
timeout policy
refresh handling
error decoding
```

Pas de configuration HTTP dispersée.

---
