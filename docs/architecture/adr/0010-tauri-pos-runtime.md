# ADR-0010 — Tauri comme runtime POS

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Decision

Le POS utilise :

- `TypeScript` ;
- `React` ;
- `Vite` ;
- `Tauri`.

La cible officiellement supportée pour le MVP est **Windows 10/11 x64**.

## Rationale

Le POS doit pouvoir évoluer vers un fonctionnement offline robuste, SQLite, l’impression, les périphériques locaux et des mises à jour contrôlées, tout en conservant React/TypeScript pour l’interface.

## Rejected alternatives

### PWA

Viable pour de nombreux usages, mais plus contrainte par le lifecycle navigateur et l’accès aux capacités natives.

### Electron

Viable et mature, mais avec un runtime plus lourd.

## Constraints

Tauri ne devient pas un second backend métier. Rust sert principalement aux capacités natives et d’infrastructure du terminal.
