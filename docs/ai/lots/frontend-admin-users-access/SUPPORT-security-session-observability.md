# F2 — Support sécurité, session et observabilité

- `ORGANIZATION_OWNER` a toujours le scope organisation ; seul un owner actif peut l’attribuer/retirer et le backend garantit qu’un owner actif subsiste.
- Toute mutation membership/role/scope/assignment incrémente `authorizationVersion` et peut invalider immédiatement access/refresh tokens.
- Mutation de ses propres accès : recharger l’accès effectif ou accepter l’invalidation serveur, vider l’état authentifié et naviguer vers un écran sûr.
- Refus cross-tenant : conserver le `404 NOT_FOUND` sans révélation.
- Tracer seulement feature, opération, route, catégorie de résultat, `correlationId` et version client.
- Ne jamais tracer/persister inutilement password, credentials, JWT, cookies, authorization headers ou token/secret d’invitation. Un secret retourné une seule fois peut être copié volontairement, avec avertissement explicite.
