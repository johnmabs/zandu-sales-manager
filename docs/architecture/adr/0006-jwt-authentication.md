# ADR-0006 — Authentication JWT interne pour le MVP

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Decision

Le MVP utilise :

- `Symfony Security` ;
- `LexikJWTAuthenticationBundle` ;
- des access tokens de courte durée ;
- des refresh tokens stateful, persistés, révocables et rotés à chaque utilisation.

L’autorisation métier reste entièrement dans Zandu via notamment `OrganizationMembership`, `RoleAssignment`, `PermissionCode`, `AccessScope` et `authorizationVersion`.

## Rationale

Cette solution exploite la maîtrise Symfony de l’équipe et évite d’introduire immédiatement un Identity Provider externe.

## Consequences

Zandu assume les responsabilités opérationnelles liées aux credentials, sessions, refresh tokens, révocation et récupération de compte.

## Constraints

Le `Domain` ne dépend jamais de Lexik. L’infrastructure d’authentication doit rester remplaçable par un fournisseur OIDC.

## Future

Un IdP externe pourra être adopté si des besoins de SSO, fédération, passkeys ou IAM enterprise le justifient.
