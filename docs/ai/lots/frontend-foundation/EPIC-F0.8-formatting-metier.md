# 16. Epic F0.8 — Formatting métier

Créer :

```text
packages/domain-formatting
```

Responsabilités :

```text
formatMoney
formatQuantity
formatBusinessDate
formatDateTime
formatPercentage
```

Important :

```text
Money
Quantity
```

ne doivent jamais passer par des calculs JS flottants non maîtrisés pour les règles métier.

Le frontend affiche et saisit.

Le backend reste autorité des calculs métier définitifs.

---
