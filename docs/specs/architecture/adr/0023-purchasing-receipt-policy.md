# ADR-0023 — Politique de réception fournisseur du MVP

**Status:** ACCEPTED
**Date:** 2026-08-28

## Context

Le Lot 6 doit permettre une réception fournisseur liée à un bon de commande ou
une réception directe. Il doit également empêcher par défaut qu’une réception
liée dépasse silencieusement la quantité commandée.

Le projet ne dispose pas encore d’un modèle de configuration Purchasing par
organisation, d’un workflow d’administration de cette configuration ni de
règles validées pour son historisation. Persister dès maintenant une policy
tenant-scoped inventerait ces comportements.

## Decision

Pour le MVP, `PurchasingPolicy` est une policy applicative appartenant au
bounded context Purchasing et configurée au niveau du déploiement.

La baseline est :

```text
PURCHASING_PURCHASE_ORDER_REQUIRED_FOR_RECEIPT=false
PURCHASING_OVER_RECEIPT_POLICY=FORBIDDEN
```

Une réception directe est donc autorisée par défaut. Lorsque
`purchaseOrderRequiredForReceipt` vaut `true`, toute réception doit référencer
un `PurchaseOrder` valide ; aucun fallback silencieux n’est admis.

`FORBIDDEN` signifie que l’invariant suivant s’applique :

```text
cumulativeReceivedQuantity <= orderedQuantity
```

Une exception d’over-receipt n’est jamais une configuration permissive globale.
Elle doit être accordée pour l’opération concernée avec la permission
`PURCHASING_OVER_RECEIPT`, un motif non vide et l’acteur ayant autorisé
l’exception. Ces éléments seront persistés avec la réception concernée.

Toute valeur de policy inconnue fait échouer explicitement la résolution de la
configuration. Le code ne choisit pas de comportement implicite de secours.

## Consequences

Le comportement Purchasing est déterministe et testable sans créer
prématurément un agrégat de configuration. Les déploiements exigeant un bon de
commande peuvent activer la contrainte sans modifier le code.

Une future configuration par organisation nécessitera un nouvel ADR, un modèle
versionné, des permissions d’administration et des snapshots sur les documents
historiques. Elle ne devra pas réinterpréter les réceptions déjà publiées.
