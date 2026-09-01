# ADR-0021 — Politique d’activation de la valorisation Inventory

**Status:** ACCEPTED
**Date:** 2026-08-27

## Context

Le Lot 5 introduit un coût moyen pondéré mobile par organisation, magasin et
produit après que des stocks et des mouvements physiques ont déjà pu être
enregistrés. Ces données historiques ne contiennent pas de coût fiable. Le prix
de vente, les snapshots Pricing et une valeur arbitraire ne permettent pas de
reconstituer un coût d’acquisition.

À partir de l’activation du costing, `Stock` reste l’autorité de la quantité
physique et `StockValuation` devient l’autorité de sa valeur économique. Une
variation de quantité sans variation économique correspondante rendrait ces
deux autorités incohérentes.

## Decision

### Activation des positions historiques

Une position historique dont la quantité est strictement positive est activée
uniquement par un coût d’ouverture explicite, saisi par un acteur autorisé avec
une justification obligatoire. Cette opération :

- reprend exactement la quantité courante de `Stock` sous verrou ;
- crée une unique `StockValuation` dans la devise du magasin ;
- calcule `totalValue = quantityOnHand × openingUnitCost` ;
- ajoute un `StockValuationMovement` d’ouverture append-only ;
- produit audit et outbox dans la même transaction tenant-scoped.

Une position dont la quantité est nulle peut être activée avec une valeur
totale exactement nulle. Aucun coût historique n’est alors affirmé.

Il est interdit de déduire le coût d’ouverture du prix de vente, du catalogue,
d’une moyenne globale ou d’un mouvement ne contenant pas de coût fiable. Le
mode `UNVALUED` et la reconstruction opportuniste sont rejetés pour le MVP.

Une vente ou une sortie valorisée visant un stock positif non activé échoue
avec `VALUATION_NOT_INITIALIZED`. Le déploiement du Lot 5 doit donc être suivi
d’une initialisation contrôlée des positions existantes avant la reprise de
leurs sorties.

### Mouvements après activation

Après activation, tout mouvement physique d’un produit suivi possède un effet
économique dans la même transaction :

| Mouvement physique | Politique de valorisation |
| --- | --- |
| `INITIAL_STOCK` | coût unitaire explicite |
| `ADJUSTMENT_IN` | coût unitaire explicite |
| `ADJUSTMENT_OUT` | coût moyen courant |
| `SALE` | coût moyen courant |
| `SALE_RETURN` | coût original du `SaleLineCostSnapshot` |

Une entrée positive sans coût explicite est refusée. Une sortie n’accepte pas
de coût fourni par le client. Les futurs mouvements `PURCHASE_RECEIPT` du Lot 6
apporteront leur coût depuis Purchasing.

Chaque `StockMovement` valorisé possède au plus un
`StockValuationMovement`. Les mises à jour de `Stock`, `StockValuation`, des
deux ledgers, de l’audit et de l’outbox partagent la transaction PostgreSQL. Un
échec de valorisation annule aussi le mouvement physique.

### Calculs et précision

- aucune opération n’utilise de `float` ;
- quantités et coûts unitaires intermédiaires suivent `NUMERIC(30,12)` ;
- valeurs monétaires persistées suivent `NUMERIC(30,6)` conformément à
  l’ADR-0014 ;
- le coût moyen est dérivé de la quantité et de la valeur totale, pas maintenu
  comme une troisième autorité modifiable ;
- la dernière sortie absorbe tout résidu afin que quantité nulle implique une
  valeur totale exactement nulle ;
- la devise est celle du magasin et ne peut pas changer au sein d’une
  valuation.

### Concurrence

Les produits d’une opération sont traités dans un ordre déterministe par
`ProductId`. Le workflow applique d’abord la stratégie atomique de `Stock`
définie par l’ADR-0015, puis verrouille ou met à jour la valuation associée dans
la même transaction. La version de `StockValuation` protège les workflows
d’agrégat et l’unicité sur `StockMovementId` protège les rejeux.

## Consequences

L’activation demande une opération métier explicite et peut temporairement
bloquer la vente de positions historiques non valorisées. Cette contrainte est
préférée à l’invention silencieuse d’un coût.

Les commandes Inventory existantes devront accepter un coût pour
`INITIAL_STOCK` et `ADJUSTMENT_IN`, et leurs handlers devront coordonner le
contrat applicatif Inventory Costing. Les sorties continueront de recevoir leur
coût du modèle de valorisation, jamais de l’API.

Le costing reste séparé du stock physique et du catalogue. Aucun champ
`Product.costPrice` n’est introduit.
