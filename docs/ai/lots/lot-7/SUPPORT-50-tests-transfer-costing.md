# 50. Tests Transfer Costing

Exemple :

```text
Source:
quantity = 10
value = 50 000
avg = 5 000

Ship = 4
```

Après ship :

```text
source quantity = 6
source value = 30 000
transferred value snapshot = 20 000
```

Receive 4 dans destination :

```text
destination receives
quantity +4
value +20 000
```

Receive seulement 3 :

```text
destination receives deterministic value for 3
remaining value stays transit discrepancy
```

Tester les résidus.

Commit :

```text
test(costing): verify stock transfer value transport
```

---
