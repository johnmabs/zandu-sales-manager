# 37. Tenant isolation

```text
Tenant B reads valuation Tenant A
→ NOT_FOUND

Tenant B creates return on Sale A
→ NOT_FOUND

Tenant B refunds Payment A
→ NOT_FOUND

Store-scoped actor A returns Sale Store B
→ denied
```

---
