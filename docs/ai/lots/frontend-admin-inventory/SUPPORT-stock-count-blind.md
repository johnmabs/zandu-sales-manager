# F4 — Support StockCount BLIND

- En mode BLIND, afficher uniquement les champs réellement fournis par le serveur.
- Ne jamais dériver `expectedQuantity` ou `variance` depuis Stock ou un cache.
- `countedQuantity = "0"` est une saisie valide ; `null` signifie non compté.
- La saisie transmet la version de ligne lorsque le contrat la requiert.
- Le serveur décide verrouillage, conflits de snapshot et finalisation/reprise.

Source : spécification F4, sections 11 et 36.

