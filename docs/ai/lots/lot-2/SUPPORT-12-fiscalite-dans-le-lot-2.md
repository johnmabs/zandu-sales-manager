# 12. Fiscalité dans le Lot 2

La baseline indique que :

```text
Product
→ TaxCategoryId?
```

et qu’un futur `SaleLine` conservera un `TaxSnapshot`.

Cependant, la réglementation fiscale du pilote doit être validée avant la vente finale.

Le Lot 2 ne doit donc pas inventer silencieusement des règles fiscales nationales.

Deux cas sont possibles au démarrage de l’implémentation :

### Cas A — règles fiscales déjà décidées dans les ADR / documentation à jour

Implémenter la fondation minimale nécessaire :

```text
TaxCategory
TaxRule
TaxResolver
```

sans moteur promotionnel.

### Cas B — règles encore ouvertes

Conserver :

```text
taxCategoryId?
```

dans Product uniquement si le contrat de référence le prévoit, mais reporter la résolution fiscale définitive jusqu’à décision explicite avant le Lot 4.

Dans tous les cas :

- ne pas coder un taux fiscal en dur dans Product ;
- ne pas stocker durablement seulement un pourcentage dans Product ;
- toute décision locale nouvelle doit être documentée ;
- `Sale` devra plus tard conserver un snapshot fiscal déterministe.

---
