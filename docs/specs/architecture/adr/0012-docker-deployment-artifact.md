# ADR-0012 — Docker comme artifact de déploiement

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Decision

Le backend est construit sous forme d’une **image Docker immuable**.

Le même artifact peut exécuter plusieurs process roles :

```text
zandu-backend:<version>
├── API
├── Worker
├── Scheduled Job
└── Migration
```

Les migrations constituent une étape contrôlée du déploiement et ne sont jamais exécutées implicitement au démarrage normal de chaque instance.

## Rationale

Le packaging Docker découple l’application de la plateforme d’hébergement et garantit qu’API et workers exécutent le même artifact applicatif.

## Open questions

La plateforme de déploiement reste `PROPOSED` : Render, région candidate Frankfurt, sous réserve de validation opérationnelle et de latence.
