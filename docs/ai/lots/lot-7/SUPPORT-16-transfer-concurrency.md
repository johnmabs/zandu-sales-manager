# 16. Transfer concurrency

Tester :

```text
Stock source = 10

Transfer A ships 7
Sale consumes 5 concurrently
```

ou :

```text
Transfer A ships 7
Transfer B ships 6 concurrently
```

Résultat :

```text
Stock never negative
no lost update
only compatible operations succeed
```

Utiliser la stratégie de concurrence déjà décidée dans Inventory.

Commit :

```text
test(inventory): verify concurrent transfer shipment
```

---
