# F4 — Support sécurité, scopes et coûts

- Le tenant actif vient de la session ; le client ne le choisit pas librement.
- Inventory est store-scoped ; un transfert peut exiger accès source + destination.
- Les guards UI ne remplacent jamais l’autorisation serveur.
- Le contrat serveur actuel protège la lecture des valorisations par
  `INVENTORY_READ`. Il ne définit pas `INVENTORY_COST_VIEW` ni
  `INVENTORY_VALUE_VIEW` ; le frontend ne doit pas inventer ces permissions.
- L’initialisation et l’attribution manuelle restent protégées respectivement
  par `INVENTORY_COSTING_INITIALIZE` et `INVENTORY_COST_ASSIGN`.
- Ne pas révéler une valeur interdite dans un message, export ou état de chargement.
- Toute ressource hors tenant/store est rejetée ou filtrée défensivement.

Relevant ADRs : ADR-0006, ADR-0017, ADR-0018, ADR-0021.
