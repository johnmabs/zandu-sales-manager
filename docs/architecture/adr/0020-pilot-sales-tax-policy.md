# ADR-0020 — Politique fiscale du pilote Sales

**Status:** ACCEPTED
**Date:** 2026-08-27

## Context

Le Lot 4 exige une politique fiscale explicite avant la première vente cash. Le
projet ne dispose toutefois d'aucune règle juridictionnelle validée, ni de taux,
ni de mécanisme de taxe inclus/exclu. Inventer une TVA nationale dans le code
rendrait les reçus juridiquement ambigus.

## Decision

Le pilote utilise explicitement la politique `NO_TAX`, configurée par
`SALE_TAX_POLICY`. Elle signifie qu'aucune taxe n'est calculée par Zandu pendant
le pilote : le montant taxable reste égal au sous-total net et `taxAmount` vaut
zéro. Le code de politique est conservé dans `SaleLine.sourceVersions` afin que
le snapshot historique ne soit jamais silencieux.

Le service échoue au démarrage ou au calcul si une valeur différente et non
implémentée est configurée. Aucun taux par défaut, aucune TVA implicite et
aucune déduction fiscale ne sont autorisés.

## Consequences

Le parcours M2 est déterministe et son absence de taxe est explicite dans la
configuration et les snapshots. Il ne doit pas être utilisé dans une
juridiction imposant une taxe collectée tant qu'une nouvelle ADR, un modèle de
taux datés et les mentions légales du reçu n'ont pas été livrés.

L'introduction future de taxes nécessitera une nouvelle politique versionnée et
ne modifiera jamais les lignes de ventes déjà finalisées.
