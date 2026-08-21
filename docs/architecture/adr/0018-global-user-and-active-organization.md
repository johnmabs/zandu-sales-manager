# ADR-0018 — Identité globale et organisation active

**Status:** ACCEPTED  
**Date:** 2026-08-21

## Context

Une personne peut appartenir à plusieurs organisations. L'authentification doit
retrouver son compte avant qu'un tenant soit connu, tandis que chaque membership,
rôle et donnée métier reste strictement tenant-owned.

Le premier parcours d'onboarding a introduit `defaultOrganizationId` sur `User`
pour établir le contexte initial, sans formaliser la sélection d'une autre
organisation ni le parcours d'une invitation destinée à un compte existant.

## Decision

`User` est une identité globale et n'est pas une donnée tenant-owned. Son email
canonique est unique dans Zandu. Les memberships constituent la relation
tenant-owned entre cette identité et une organisation.

Un access token représente exactement une organisation active. Il contient
l'identité de l'utilisateur, l'organisation active et la version d'autorisation
du membership correspondant. Un client ne peut jamais choisir librement une
organisation en modifiant un payload ou un header.

La sélection d'une organisation active doit :

1. s'exécuter pour un utilisateur authentifié ;
2. charger sous RLS un membership `ACTIVE` pour l'organisation demandée ;
3. émettre une nouvelle paire de tokens portant cette organisation et la bonne
   `authorizationVersion` ;
4. éventuellement mettre à jour `defaultOrganizationId` uniquement après cette
   validation.

Une invitation destinée à un email possédant déjà un compte est acceptée par un
parcours authentifié. Le token prouve l'invitation, mais ne remplace jamais
l'authentification de ce compte. Une personne sans compte utilise le parcours
public d'inscription depuis l'invitation.

## Accès PostgreSQL aux identités globales

La table `identity_access.users` n'est pas soumise à la policy RLS tenant, car
elle doit être interrogée avant la résolution de l'organisation. Cette exception
ne donne pas au runtime métier un droit général d'énumération.

À terme, une identité PostgreSQL d'authentification dédiée doit disposer du
minimum requis pour rechercher un utilisateur par email. L'identité runtime
métier reste distincte de l'identité de migration conformément à l'ADR-0017.

## Consequences

- un utilisateur peut avoir plusieurs memberships sans dupliquer son compte ;
- `defaultOrganizationId` est une préférence de connexion, pas une preuve
  d'autorisation ;
- tout changement de tenant renouvelle les tokens ;
- les refresh sessions doivent être liées à l'organisation active ;
- l'acceptation d'invitation d'un compte existant et la sélection active devront
  être exposées avant de déclarer le multi-organisation complet ;
- l'accès SQL global aux hashes de mots de passe reste une dette de sécurité à
  supprimer avant la production.
