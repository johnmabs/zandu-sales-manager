# 35. Navigation authorization

La navigation Admin doit être calculée à partir des capacités.

Exemple :

```text
Catalog
```

visible si l’acteur possède une permission pertinente Catalog.

Mais une route appelée directement doit encore :

1. appliquer le frontend guard ;
2. appeler le backend ;
3. accepter que le backend puisse répondre DENIED.

---
