# ADR-0025 — Versionnement commun des agrégats mutables

**Status:** ACCEPTED
**Date:** 2026-09-16

## Context

Les bounded contexts utilisaient tous un entier `version`, mais chacun exposait
et incrémentait cet état directement. La validation d'une version positive
était inégale et aucune règle exécutable n'empêchait un nouvel agrégat de
réintroduire une convention différente.

La version est un invariant de domaine utilisé par les repositories pour
détecter les écritures concurrentes. Elle ne doit pas dépendre de Doctrine ni
être confondue avec la stratégie particulière des mises à jour conditionnelles
de stock décrite par l'ADR-0015.

## Decision

Tout modèle de domaine persistant une version implémente le contrat
`VersionedAggregate` du SharedKernel et utilise le trait
`TracksAggregateVersion`.

Le mécanisme commun impose les règles suivantes :

- la première version persistée vaut `1` ;
- une version reconstituée doit être strictement positive ;
- chaque transition métier modifiant l'état appelle `advanceVersion()` ;
- la lecture publique reste `version(): int` afin de préserver les contrats de
  persistence et d'API existants ;
- les incréments directs de `$version` sont interdits dans les modèles de
  domaine.

Un test d'architecture découvre les modèles de domaine portant une propriété
`version` et vérifie qu'ils utilisent le contrat et le mécanisme commun. Les
entités internes qui possèdent leur propre version persistée suivent la même
règle, même lorsqu'elles ne sont pas racines d'agrégat.

La stratégie de verrouillage reste choisie par le workflow : optimistic locking
pour les agrégats riches, mise à jour DBAL conditionnelle pour les chemins chauds
explicitement couverts par l'ADR-0015.

## Consequences

La sémantique de version devient uniforme dans tous les bounded contexts sans
modifier les schémas PostgreSQL ni les payloads API. Les repositories continuent
à comparer les mêmes entiers, mais les transitions futures ne peuvent plus
contourner silencieusement la validation et l'avancement communs.

Une mutation métier composée doit décider explicitement si elle représente une
ou plusieurs transitions de version ; cette décision reste dans l'agrégat et
non dans l'infrastructure.
