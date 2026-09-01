# 33. Test coût original restauré

```text
Sale:
quantity = 2
original unit cost = 4 000

Current stock:
quantity = 8
average cost = 5 000
total = 40 000

Return:
quantity = 1
restock = true
```

Après :

```text
stock quantity = 9
restored value = 4 000
total value = 44 000
new average = 44 000 / 9
```

et non 45 000.

Commit :

```text
test(costing): restore original cost on sale return
```

---
