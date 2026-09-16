# ADR-0026 — Schéma PostgreSQL gouverné par les migrations

**Status:** ACCEPTED
**Date:** 2026-09-16

## Context

Le backend combine des repositories Doctrine ORM et DBAL sur un même schéma
PostgreSQL. Les migrations définissent également des capacités que les
métadonnées ORM ne représentent pas fidèlement : Row Level Security, index
partiels, clés étrangères composites, contraintes `CHECK` et tableaux natifs
comme `UUID[]`.

Laisser `SchemaTool` considérer ces objets comme sa propriété produisait des
diffs destructifs : suppression des tables DBAL, des contraintes de tenant et
des index métier. L'introspection échouait en outre sur le nom interne
PostgreSQL `_uuid`.

## Decision

Les migrations Doctrine sont l'unique source de vérité du schéma PostgreSQL.
Les métadonnées ORM décrivent l'hydratation et la persistence, mais sont exclues
de la génération et des mises à jour de schéma par `SchemaTool`.

La configuration applique les règles suivantes :

- toutes les classes ORM sont déclarées dans `schema_ignore_classes` ;
- le filtre de schéma DBAL n'expose aux outils de migration que leur table de
  métadonnées ;
- le type PostgreSQL introspecté `_uuid` est mappé vers `string`, conformément à
  la représentation textuelle utilisée par les repositories DBAL ;
- la validation combine `doctrine:schema:validate` pour la cohérence interne des
  mappings et `doctrine:migrations:up-to-date` pour l'état du schéma ;
- `doctrine:schema:update --force` ne doit pas être utilisé sur ce projet.

Un test d'intégration vérifie que chaque classe ORM connue est explicitement
ignorée par `SchemaTool` et que les colonnes `UUID[]` restent introspectables.

## Consequences

Les commandes Doctrine ne proposent plus de supprimer les objets PostgreSQL
gérés par migrations. Une nouvelle classe ORM doit être ajoutée à la liste
d'exclusion, faute de quoi le test de propriété du schéma échoue. Toute
évolution structurelle passe par une migration relue et testée.
