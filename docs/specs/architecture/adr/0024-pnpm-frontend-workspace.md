# ADR-0024 — pnpm pour le workspace frontend

**Status:** ACCEPTED
**Date:** 2026-09-03

## Context

Le Frontend Foundation doit accueillir deux applications distinctes, Zandu
Admin et Zandu POS, ainsi que des packages réellement transversaux. Les Epics
suivants doivent pouvoir partager leurs dépendances et leurs outils sans
multiplier les lockfiles ni introduire prématurément un orchestrateur de build.

## Decision

Le frontend utilise un workspace `pnpm` sous `frontend/` avec :

- un unique `pnpm-lock.yaml` versionné à la racine du workspace ;
- les applications dans `apps/*` ;
- les packages partagés dans `packages/*` ;
- le protocole `workspace:*` pour les dépendances internes ;
- des scripts racine capables d'exécuter les commandes des membres du
  workspace.

Turborepo, Nx ou un autre orchestrateur ne sont pas introduits dans le
Foundation. Leur ajout futur exigera un besoin mesuré et une décision
architecturale distincte.

## Consequences

Admin et POS conservent des cycles de développement séparés tout en partageant
un graphe de dépendances explicite et un lockfile reproductible. Les Epics F0.2
et F0.3 pourront ajouter respectivement Next.js et Vite/Tauri sans modifier le
modèle du workspace.
