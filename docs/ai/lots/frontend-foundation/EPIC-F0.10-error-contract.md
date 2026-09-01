# 22. Epic F0.10 — Error contract

Le frontend doit distinguer au minimum :

```text
validation error
authentication error
authorization denied
not found
business rule conflict
idempotency conflict
network error
server error
```

Ne pas afficher directement :

```text
HTTP 409
```

à l’utilisateur.

Transformer :

```text
backend error code
→ UX message
```

Exemple :

```text
INSUFFICIENT_STOCK
→ Stock insuffisant pour finaliser la vente.
```

---
