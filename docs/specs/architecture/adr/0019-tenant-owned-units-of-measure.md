# ADR-0019 — Unités de mesure propres au tenant

**Status:** ACCEPTED  
**Date:** 2026-08-25

## Context

Le catalogue a besoin d'unités de mesure configurables. La documentation ne
tranchait pas entre des unités système globales, des unités propres à chaque
organisation ou un modèle hybride.

Un modèle hybride introduirait dès le MVP deux règles de propriété, de
visibilité et d'unicité. Des unités globales modifiables compliqueraient aussi
l'isolation tenant imposée par l'ADR-0017.

## Decision

Pour le MVP, toute `UnitOfMeasure` appartient obligatoirement à une
`Organization` : son `OrganizationId` n'est jamais nul.

- le code d'une unité est unique à l'intérieur de son organisation ;
- les unités sont soumises aux mêmes règles RLS que les autres données tenant ;
- aucune ligne globale et aucun tenant système implicite ne sont introduits ;
- un futur catalogue d'unités standard pourra servir de modèle de copie lors de
  l'initialisation d'une organisation, sans devenir une source partagée à
  l'exécution.

La précision d'une unité exprime le nombre maximal de décimales autorisées pour
ses quantités. Elle est comprise entre 0 et 12, conformément à la précision de
persistence définie par l'ADR-0014. Toute réduction de précision exige le mode
d'arrondi explicite associé à l'unité, conformément à l'ADR-0008.

## Consequences

L'isolation, la personnalisation et les contraintes d'unicité restent simples
et testables par organisation. Les unités usuelles devront être créées ou
copiées pour chaque tenant.

Le partage futur d'unités globales nécessitera une nouvelle ADR et une migration
explicite vers un modèle global ou hybride ; il ne pourra pas être obtenu en
rendant simplement `organization_id` nullable.
