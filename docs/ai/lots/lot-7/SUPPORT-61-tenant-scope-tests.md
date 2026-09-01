# 61. Tenant & scope tests

Tester :

```text
Tenant B reads transfer A
→ NOT_FOUND
```

```text
Tenant B receives transfer A
→ NOT_FOUND
```

```text
Tenant B reads StockCount A
→ NOT_FOUND
```

```text
Manager scoped Store A
creates transfer A → B without B scope
→ denied according to policy
```

```text
Counter scoped Store A
records count Store B
→ denied
```

RLS réelle avec rôle runtime.

---
