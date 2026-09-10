# F1 — Support API et lifecycle Store

## Contrats disponibles

- Collection, création, détail et modification : utiliser exclusivement les opérations exposées dans l’OpenAPI courant.
- Transitions métier : suspension, réactivation et demande de fermeture utilisent leurs endpoints dédiés.
- Annulation de fermeture : disponible en frontend uniquement si l’endpoint HTTP existe dans le contrat.

## Modèle utile à l’UX

Le contrat peut exposer `StoreId`, `OrganizationId`, `StoreCode`, `StoreName`, `StoreAddress`, `currency`, `timeZone`, `status` et `version`. Ne consommer que les champs effectivement publiés.

- `StoreCode` est éditable à la création et immuable ensuite.
- La devise est contrainte par l’organisation ; le serveur reste l’autorité.
- Le lifecycle Store et le workflow `StoreClosure` sont distincts.
- Statuts de fermeture connus : `REQUESTED`, `IN_PROGRESS`, `READY`, `COMPLETED`, `CANCELLED`.
- Les blockers sont calculés par le backend ; mapper leurs codes pour présentation et prévoir un fallback sûr pour tout code inconnu.
