# Frontend Lot F2 — Admin Users & Access — AI routing index

> Router compact : lire uniquement le contexte, l’Epic courant et les supports requis.

## Source specification

`docs/specs/planning/zandu-frontend-lot-f2-admin-users-access.md`

## Read first

- `CONTEXT.md`

## Epics

- `EPIC-F2.1-access-feature-foundation.md` — frontière feature et réutilisation Foundation.
- `EPIC-F2.2-access-navigation.md` — navigation Accès, Membres, Invitations et Rôles.
- `EPIC-F2.3-member-list.md` — collection tenant-scoped, recherche/filtres conditionnels.
- `EPIC-F2.4-member-details.md` — identité, statut, rôles, scopes et actions.
- `EPIC-F2.5-role-catalog.md` — catalogue système read-only.
- `EPIC-F2.6-permission-visualization.md` — libellés de présentation et fallback sûr.
- `EPIC-F2.7-invite-member.md` — workflow et formulaire d’invitation.
- `EPIC-F2.8-invitation-role-selection.md` — intentions de rôles et scopes.
- `EPIC-F2.9-store-scope-selector.md` — sélection des Stores de F1.
- `EPIC-F2.10-invitation-success.md` — succès et secret affichable une fois.
- `EPIC-F2.11-invitation-lifecycle.md` — liste conditionnée à l’API et transitions disponibles.
- `EPIC-F2.12-cancel-invitation.md` — confirmation et annulation.
- `EPIC-F2.13-assign-role.md` — attribution d’un rôle.
- `EPIC-F2.14-scope-rules-ux.md` — payload non ambigu et validation UX.
- `EPIC-F2.15-expiring-assignment.md` — expiration et conventions de dates.
- `EPIC-F2.16-remove-role-assignment.md` — retrait contextualisé.
- `EPIC-F2.17-owner-protection.md` — traitement sensible Owner.
- `EPIC-F2.18-suspend-membership.md` — suspension réversible.
- `EPIC-F2.19-reactivate-membership.md` — réactivation.
- `EPIC-F2.20-revoke-membership.md` — révocation terminale.
- `EPIC-F2.21-immediate-access-invalidation.md` — invalidation serveur immédiate.
- `EPIC-F2.22-current-user-self-impact.md` — mutation de ses propres accès.
- `EPIC-F2.23-permission-aware-ui.md` — visibilité UX et autorité serveur.
- `EPIC-F2.24-scope-aware-ui.md` — présentation fidèle des limites de scope.
- `EPIC-F2.25-role-assignment-summary.md` — résumé réutilisable feature-local.
- `EPIC-F2.26-status-access-badges.md` — badges accessibles.
- `EPIC-F2.27-error-handling.md` — contrat d’erreur et corrélation.
- `EPIC-F2.28-last-owner-error.md` — explication du refus métier.
- `EPIC-F2.29-stale-authorization-state.md` — invalidation des caches.
- `EPIC-F2.30-loading-empty-states.md` — états explicites par ressource.
- `EPIC-F2.31-sensitive-confirmations.md` — action, cible et conséquence.
- `EPIC-F2.32-accessibility.md` — clavier, focus, annonces et sémantique.
- `EPIC-F2.33-responsive-admin.md` — desktop/laptop/tablette et disclosure.
- `EPIC-F2.34-unit-component-tests.md` — primitives et disponibilité des actions.
- `EPIC-F2.35-integration-tests.md` — contrats et mutations de la feature.
- `EPIC-F2.36-session-invalidation-test.md` — preuve authorizationVersion/session.
- `EPIC-F2.37-e2e-administration-flow.md` — parcours owner et dernier owner.

## Support files

- `SUPPORT-domain-api-contract.md` — concepts, statuts, endpoints et rôles système.
- `SUPPORT-routing-and-screens.md` — routes et composants minimaux.
- `SUPPORT-security-session-observability.md` — owner, invalidation, secrets et traces.
- `SUPPORT-gate-and-delivery.md` — ordre, commits et Gate F2.
- `SUPPORT-out-of-scope-and-next.md` — limites, custom roles et F3.

## Dependencies

Utiliser seulement les capacités déclarées dans `CONTEXT.md` ou l’Epic courant. L’ordre F0 → F1 → F2 est une roadmap, pas une autorisation à lire tous les Lots précédents.

## Relevant ADRs

ADR-0005, ADR-0006, ADR-0009, ADR-0013, ADR-0017, ADR-0018 et ADR-0024.
