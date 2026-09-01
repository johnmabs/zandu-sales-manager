# 17. Decimal transport

Le client doit traiter les décimaux API sous forme sûre.

Recommandation :

```text
API decimal
→ string
```

et non :

```text
API decimal
→ JavaScript number
```

pour les valeurs nécessitant exactitude.

Exemple :

```ts
type DecimalString = string;
```

Types plus forts possibles :

```ts
type MoneyAmount = DecimalString;
type QuantityAmount = DecimalString;
```

sans prétendre reproduire entièrement `brick/math` côté frontend.

---
