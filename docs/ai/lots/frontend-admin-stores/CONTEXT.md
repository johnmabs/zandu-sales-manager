# Frontend Lot F1 — Admin Stores — Context

## Goal

Livrer dans Zandu Admin le premier vertical slice métier réellement utilisable : administration des magasins, depuis le login et le contexte d’organisation jusqu’à la création, l’édition, les transitions opérationnelles et la demande de fermeture.

## Surface / capabilities

- Surface : `apps/admin` (Next.js, React, TypeScript).
- Feature : `stores`.
- Écrans : liste, détail, création et édition.
- Transitions : suspension, réactivation, demande de fermeture ; annulation seulement si exposée par l’API.
- Workflow présenté : `StoreClosure` et blockers fournis par le backend.

## Dependencies

- Frontend Foundation Gate : bootstrap Admin, session, authorization, routing, forms, server state, mutation/idempotency, erreurs, observabilité, tests et CI.
- `OrganizationContext` et `StoreContext` du Foundation.
- Client typé généré/dérivé du contrat OpenAPI réel.
- API backend Stores et transitions explicites du module Organization.
- Catalogue de permissions et scopes effectifs (`STORE_READ`, `STORE_CREATE`, `STORE_UPDATE` selon le contrat réel).

## Relevant ADRs

- ADR-0005 — le contrat REST/OpenAPI gouverne les payloads et opérations disponibles.
- ADR-0006 — session JWT et rejets d’authentification restent gérés par les primitives Foundation.
- ADR-0009 — Zandu Admin est le frontend Web.
- ADR-0013 — observabilité via les primitives et métadonnées sûres du Foundation.
- ADR-0017 — préserver l’isolation tenant ; aucune révélation cross-tenant.
- ADR-0018 — le contexte d’organisation active est résolu explicitement.
- ADR-0024 — respecter l’organisation du workspace frontend.

## Lot-wide invariants

- Symfony est l’unique source de vérité métier ; ne pas recréer l’aggregate ou les invariants Store côté client.
- Les DTO, champs, permissions, blocker codes et endpoints proviennent du contrat API réel.
- `PermissionGate` améliore l’UX mais n’est jamais une frontière de sécurité ; traiter tout `403` serveur.
- `StoreCode` et l’identité sont immuables après création.
- Une demande de fermeture est un workflow `StoreClosure`, pas `store.status = CLOSED`.
- Utiliser les transitions métier dédiées ; ne pas simuler suspension/réactivation par un PATCH de statut.
- Après mutation sensible, invalider les données concernées et resynchroniser le `StoreContext` actif.
- Préserver le `correlationId`, empêcher les doubles soumissions et ne pas supposer l’échec après timeout.
- Le Lot reste desktop-first, accessible et sans interfaces métier des modules hors périmètre.

## Source of truth

`docs/specs/planning/zandu-frontend-lot-f1-admin-stores.md`

Ouvrir la source complète uniquement si les fichiers compacts ne répondent pas à une question requise.
