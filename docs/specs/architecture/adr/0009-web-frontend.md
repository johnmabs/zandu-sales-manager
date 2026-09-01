# ADR-0009 — Frontend Web

**Status:** ACCEPTED  
**Date:** 2026-08-19

## Decision

Le back-office utilise **TypeScript + React + Next.js**.

Il couvre principalement l’administration, la gestion, la configuration et le reporting.

## Rationale

Cette stack permet de construire une interface web structurée tout en partageant l’écosystème React/TypeScript avec le POS.

## Constraints

Symfony reste l’autorité métier. Les fonctionnalités serveur de Next.js ne doivent pas créer un second backend métier ni dupliquer les invariants du `Domain`.
