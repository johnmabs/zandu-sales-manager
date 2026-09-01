# ADR-0022 — Ownership et workflow du remboursement cash

**Status:** ACCEPTED
**Date:** 2026-08-27

## Context

Le Lot 5 distingue le retour commercial et physique d’un remboursement
financier. Un `ReturnSale` décrit ce que le client retourne et si les articles
reviennent en stock. Un remboursement diminue la valeur remboursable d’un
paiement confirmé et, pour CASH, produit une sortie de caisse.

Exposer le remboursement comme une mutation de `ReturnSale` donnerait à Sales
l’autorité sur les cumuls financiers. À l’inverse, un remboursement cash sans
référence commerciale vérifiable permettrait de rembourser davantage que ce
qui a réellement été retourné dans le périmètre du MVP.

## Decision

`PaymentRefund` appartient au bounded context Payments. Il référence le
`Payment` original et conserve son propre identifiant, montant, devise, statut,
acteur, horodatage et clé d’idempotence.

Dans le Lot 5, chaque remboursement référence obligatoirement un `ReturnSale`
`COMPLETED` de la même vente, organisation et magasin. Payments obtient les
faits commerciaux nécessaires via un contrat applicatif versionné ; il ne lit
pas le domaine ou la persistence Sales directement.

Deux plafonds cumulatifs sont vérifiés sous verrou dans la transaction :

```text
refunds for Payment <= confirmed Payment amount
refunds for ReturnSale <= ReturnSale refundable amount
```

Le point d’entrée HTTP canonique est :

```text
POST /api/payments/{paymentId}/refunds
```

Le payload contient `returnSaleId`, `cashSessionId`, le montant, la devise, la
raison et un header `Idempotency-Key`. Le contexte Payment reste ainsi visible
dans l’API et aucune seconde route concurrente sous `/api/returns` n’est
introduite dans le MVP.

Un remboursement cash validé :

- exige un paiement original `CONFIRMED` et de méthode `CASH` ;
- exige une `CashSession OPEN` du même magasin ;
- crée un `PaymentRefund` confirmé ;
- crée exactement un `CashMovement REFUND` de direction `OUT` ;
- ajoute audit et outbox dans la même transaction tenant-scoped.

Le rejeu de la même clé avec le même payload retourne le résultat existant. La
même clé avec un payload différent échoue avec `IDEMPOTENCY_CONFLICT`.

`CompleteReturnSale` et `CreatePaymentRefund` restent deux commandes
transactionnelles distinctes. Un retour peut être complété sans remboursement
immédiat et un échec financier ultérieur ne réouvre ni n’annule le retour. Le
client peut reprendre explicitement le remboursement. Une orchestration
composée pourra être ajoutée plus tard sans fusionner les agrégats.

## Consequences

Returns reste l’autorité des quantités, snapshots et montants retournables ;
Payments reste l’autorité des montants remboursés ; CashManagement reste
l’autorité du ledger de caisse. Les échanges entre ces contexts passent par
des contrats applicatifs.

Le remboursement ne crée jamais de `StockMovement` et ne modifie jamais
`StockValuation`. Inversement, `restock=true` ne déclenche aucun remboursement
implicite.

Les tests doivent couvrir les cumuls concurrents par paiement et par retour,
les rejeux, les sessions fermées, les mauvais magasins et l’isolation tenant.
