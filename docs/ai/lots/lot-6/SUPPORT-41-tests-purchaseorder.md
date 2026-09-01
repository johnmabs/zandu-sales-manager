# 41. Tests PurchaseOrder

Couvrir :

```text
create draft
add line
same product twice rejected
confirm
freeze confirmed order
cancel before receipt
reject cancel after receipt
partial received state
fully received state
close partial with reason
currency invariant
```

Commit :

```text
test(purchasing): cover purchase order lifecycle
```

---
