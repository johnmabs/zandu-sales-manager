# 36. Idempotence & concurrence

Un retry ne doit jamais :

```text
restocker deux fois
restaurer deux fois la valeur
rembourser deux fois
sortir deux fois l’argent
```

Concurrence retour :

```text
sold quantity = 5

Return A = 3
Return B = 3

→ cumulative <= 5
```

Commits :

```text
test(returns): verify return and refund idempotence
test(returns): verify concurrent return quantity safety
```

---
