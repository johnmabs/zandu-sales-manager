# 22. Tenant & scope tests

Tester :

```text
CASHIER Store A
→ Sale Store A OK
```

```text
CASHIER Store A
→ Sale Store B denied
```

```text
Tenant B → Sale Tenant A
→ NOT_FOUND
```

```text
Tenant B → CashSession Tenant A
→ NOT_FOUND
```

Aucune fuite d’existence cross-tenant.

Commit :

```text
test(access): verify cash sales permissions and scopes
```

---
