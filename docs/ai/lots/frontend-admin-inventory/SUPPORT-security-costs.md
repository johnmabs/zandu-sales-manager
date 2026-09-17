# F4 — Support sécurité, scopes et coûts

- Le tenant actif vient de la session ; le client ne le choisit pas librement.
- Inventory est store-scoped ; un transfert peut exiger accès source + destination.
- Les guards UI ne remplacent jamais l’autorisation serveur.
- `averageUnitCost`, `totalValue`, valeur de transfert et coût assigné nécessitent
  `INVENTORY_COST_VIEW`, `INVENTORY_VALUE_VIEW` ou la permission contractuelle
  correspondante.
- Ne pas révéler une valeur interdite dans un message, export ou état de chargement.
- Toute ressource hors tenant/store est rejetée ou filtrée défensivement.

Relevant ADRs : ADR-0006, ADR-0017, ADR-0018, ADR-0021.

