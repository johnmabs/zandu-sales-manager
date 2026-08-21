# ADR-0017 — PostgreSQL Row Level Security pour l'isolation tenant

**Status:** ACCEPTED  
**Date:** 2026-08-22

## Context

La baseline DDD définit `OrganizationId` comme frontière stricte de tenant. Les
handlers utilisent l'`ActorContext` et les repositories recherchent les données
par `organizationId + aggregateId`. Cette protection applicative reste
nécessaire, mais une requête ORM, DBAL ou SQL oubliant le prédicat tenant
pourrait contourner cette convention.

Le Lot 1 introduit les premières tables réellement tenant-owned, à commencer
par `Store`. L'isolation doit donc également être garantie au niveau de
PostgreSQL.

## Decision

PostgreSQL Row Level Security est obligatoire comme défense en profondeur sur
toute table tenant-owned.

Chaque table concernée :

- porte une colonne `organization_id UUID NOT NULL`, sauf la table racine des
  organizations où l'identifiant `id` représente directement le tenant ;
- active `ENABLE ROW LEVEL SECURITY` et `FORCE ROW LEVEL SECURITY` ;
- définit des policies couvrant à la fois `USING` et `WITH CHECK` ;
- compare son tenant au contexte transactionnel `app.organization_id` ;
- refuse toute ligne lorsque ce contexte est absent ou invalide.

Forme de référence :

```sql
ALTER TABLE store.stores ENABLE ROW LEVEL SECURITY;
ALTER TABLE store.stores FORCE ROW LEVEL SECURITY;

CREATE POLICY store_tenant_isolation ON store.stores
USING (
    organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid
)
WITH CHECK (
    organization_id = NULLIF(current_setting('app.organization_id', true), '')::uuid
);
```

Pour `organization.organizations`, la policy compare `id` au contexte tenant.
La création d'une nouvelle organization utilise son identifiant généré côté
serveur comme contexte de la transaction de provisioning. Aucun identifiant de
tenant fourni librement par un payload externe ne peut initialiser ce contexte.

## Transaction et propagation

Le contexte est installé après résolution de l'`ActorContext`, dans la même
transaction que les requêtes métier :

```sql
SELECT set_config('app.organization_id', :organization_id, true);
```

Le troisième paramètre `true` rend la valeur locale à la transaction et évite
sa fuite lors de la réutilisation d'une connexion. Toute opération tenant-owned
doit donc s'exécuter dans une transaction explicite. Une connexion sans contexte
est fail-closed.

Les repositories conservent malgré tout leurs prédicats
`organizationId + aggregateId`. Le RLS ne remplace ni l'autorisation métier, ni
les guards opérationnels, ni la réponse publique `NOT_FOUND`.

## Rôles PostgreSQL

Le rôle d'exécution de l'application :

- n'est ni superuser, ni propriétaire dispensé des policies ;
- ne possède pas `BYPASSRLS` ;
- ne peut pas modifier les policies ou le contexte d'une autre transaction.

Les migrations et opérations de maintenance utilisent une identité distincte.
Tout bypass administratif est explicite, limité et absent du trafic applicatif.

## Portée

Le RLS s'applique notamment à :

- stores et processus de fermeture ;
- invitations, memberships, rôles custom et assignments ;
- audits, outbox et futures données métier tenant-owned.

Les tables réellement globales, comme un catalogue de rôles système, peuvent
être exclues uniquement si cette portée globale est documentée et testée.

## Validation

Les tests PostgreSQL doivent prouver avec le rôle applicatif que :

- deux tenants voient uniquement leurs propres lignes ;
- une lecture sans prédicat tenant reste isolée par la policy ;
- `INSERT` et `UPDATE` cross-tenant échouent via `WITH CHECK` ;
- un contexte absent ne retourne aucune donnée tenant-owned ;
- le contexte ne fuit pas après commit ou rollback ;
- le repository traduit l'absence cross-tenant selon le contrat public
  `NOT_FOUND`.

## Consequences

L'isolation devient une responsabilité conjointe de l'application et de la
base. Les transactions Doctrine doivent systématiquement installer le contexte
tenant avant tout accès tenant-owned. Les tests et migrations sont plus
complexes, mais une omission de filtre applicatif ne suffit plus à exposer les
données d'une autre organization.
