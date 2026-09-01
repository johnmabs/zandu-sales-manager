# 76. Dates

Le backend stocke les timestamps UTC.

Le frontend affiche selon :

```text
Store.timeZone
```

pour les événements métier store-scoped.

Distinguer :

```text
technical timestamp
BusinessDate
```

Ne jamais calculer une `BusinessDate` définitive uniquement à partir du timezone navigateur.

---
