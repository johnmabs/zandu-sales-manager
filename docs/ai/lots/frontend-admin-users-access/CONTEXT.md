# Frontend Lot F2 — Admin Users & Access — Context

## Goal

Livrer dans Zandu Admin l’administration complète des accès d’une organisation : invitations, membres, lifecycle des memberships, catalogue des rôles, attributions et scopes organisation/magasins.

## Surface / capabilities

- Surface : `apps/admin` (Next.js, React, TypeScript).
- Feature coordonnée : `access` ; ne pas fragmenter artificiellement Members, Invitations et Roles.
- Écrans : liste/détail des membres, invitations, invitation d’un membre et catalogue des rôles.
- Actions : inviter/annuler, attribuer/retirer un rôle, suspendre/réactiver/révoquer un membership.
- Scopes : `ORGANIZATION` et `SELECTED_STORES`, avec expiration optionnelle des attributions.

## Dependencies

- Frontend Foundation validé : session, authorization, contexts, routing, forms, server state, erreurs, observabilité, tests et CI.
- Gate F1 Admin Stores validé : requête/liste des Stores, identifiants et libellés réutilisables pour `StoreScopeSelector`.
- API backend IdentityAccess et contrat OpenAPI réel pour memberships, invitations, rôles et role assignments.
- `OrganizationContext`, `StoreContext`, `EffectiveAccess`, `authorizationVersion` et invalidation de session du Foundation.

## Relevant ADRs

- ADR-0005 — REST + API Platform + OpenAPI gouvernent les opérations et payloads.
- ADR-0006 — lifecycle JWT, rotation et invalidation de session.
- ADR-0009 — Zandu Admin est le frontend Web.
- ADR-0013 — observabilité sûre via les primitives Foundation.
- ADR-0017 — isolation tenant et non-révélation cross-tenant.
- ADR-0018 — utilisateur global distinct du membership et organisation active explicite.
- ADR-0024 — organisation du workspace frontend pnpm.

## Lot-wide invariants

- `User`, `OrganizationMembership`, `Role`, `RoleAssignment` et `AccessScope` restent distincts.
- Symfony est l’unique autorité d’autorisation. Le frontend présente et collecte une intention ; il ne calcule jamais les permissions effectives ni l’invariant du dernier owner.
- Seul un membership `ACTIVE` accorde des permissions ; suspension réversible, révocation terminale et historique préservé.
- Les rôles système sont globaux et immuables ; ne pas proposer édition ou suppression sans endpoint explicite.
- `ORGANIZATION_OWNER` requiert un scope organisation et une UX sensible ; tout refus final vient du backend.
- Toute mutation d’accès peut invalider `authorizationVersion`, y compris celle de l’utilisateur courant ; revenir à un état d’authentification sûr.
- Un scope `ORGANIZATION` ne transporte pas de magasins ; `SELECTED_STORES` utilise seulement des Stores de l’organisation active.
- Après mutation, invalider liste/détail concernés et accès effectif courant si nécessaire.
- Préserver `correlationId` ; ne jamais journaliser credentials, JWT, refresh token ou secret d’invitation.

## Source of truth

`docs/specs/planning/zandu-frontend-lot-f2-admin-users-access.md`

Ouvrir la source complète uniquement si les fichiers compacts ne répondent pas à la question requise.
