# 87. Definition of Done — Frontend Foundation

Le Foundation est `DONE` uniquement lorsque :

```text
[ ] workspace frontend exécutable
[ ] apps/admin séparée
[ ] apps/pos séparée
[ ] packages partagés contrôlés

[ ] Admin utilise Next.js + React + TypeScript
[ ] POS utilise React + Vite + TypeScript
[ ] Tauri POS exécutable
[ ] cible Windows MVP documentée

[ ] TypeScript strict
[ ] lint actif
[ ] formatting actif
[ ] typecheck racine
[ ] imports / boundaries contrôlés

[ ] design tokens partagés
[ ] primitives UI disponibles
[ ] Admin et POS peuvent diverger UX proprement
[ ] accessibility baseline définie

[ ] Money formatter
[ ] Quantity formatter
[ ] date/time formatter
[ ] Decimal API non converti aveuglément en float

[ ] typed API client
[ ] OpenAPI generation spike validé
[ ] generated code isolé
[ ] HTTP transport centralisé
[ ] correlation ID supporté
[ ] error contract mappé

[ ] authentication bootstrap
[ ] JWT lifecycle
[ ] refresh rotation compatible
[ ] concurrent refresh contrôlé
[ ] logout
[ ] expiration session
[ ] token secrets jamais loggés

[ ] OrganizationContext
[ ] StoreContext
[ ] organization switch sécurisé
[ ] store switch sécurisé
[ ] cache tenant-aware

[ ] EffectiveAccess depuis serveur
[ ] permission helpers
[ ] store scopes
[ ] aucun check brut de rôle comme règle métier
[ ] backend reste autorité d’autorisation

[ ] server state séparé du UI state
[ ] query keys tenant/store scoped
[ ] cache invalidation lors changement contexte

[ ] forms foundation
[ ] backend validation errors supportés
[ ] dirty/submission state standardisé

[ ] Admin table foundation
[ ] server pagination prête
[ ] filters/search conventions

[ ] Admin Shell
[ ] POS Shell
[ ] protected routing
[ ] permission-aware navigation

[ ] idempotency key support
[ ] unknown mutation outcome traité
[ ] retry même intention conserve la clé

[ ] unit tests
[ ] component tests
[ ] integration tests
[ ] auth E2E
[ ] organization/store E2E
[ ] permission/scope E2E

[ ] frontend observability foundation
[ ] aucune donnée sensible dans logs
[ ] frontend version identifiable

[ ] Admin build réussi
[ ] POS build réussi
[ ] Tauri check/build validé selon CI disponible
[ ] lint vert
[ ] format check vert
[ ] typecheck vert
[ ] tests verts
[ ] CI verte

[ ] aucune logique métier backend dupliquée
[ ] aucun SQLite métier offline prématuré
[ ] aucun Reporting fictif
[ ] aucune dépendance directe UI → Tauri partout
[ ] aucun secret frontend

[ ] ADR mis à jour pour tout choix structurant
```

---
