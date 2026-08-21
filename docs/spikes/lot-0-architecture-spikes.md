# Résultats des spikes architecturaux du Lot 0

**Date :** 2026-08-21  
**Statut :** VALIDÉ

## Spike A — Transaction CompleteSale

Une transaction PostgreSQL unique applique `Sale`, `Stock`,
`StockMovement`, `CashSession`, `CashMovement` et `OutboxMessage`.

Des erreurs injectées après les phases Inventory, Cash et Outbox provoquent un
rollback complet : aucun effet métier partiel ne subsiste et les soldes initiaux
sont restaurés. Le scénario nominal commit les six effets ensemble.

## Spike B — Outbox

Le prototype confirme :

- le claiming multi-worker par `FOR UPDATE SKIP LOCKED` ;
- la remise à disposition après rollback simulant un crash ;
- la livraison au moins une fois ;
- l’idempotence du consumer par unicité `(consumer, message_id)` ;
- les retries comptabilisés et le passage en dead letter au seuil configuré.

La stratégie retenue est détaillée dans l’ADR-0016.

## Spike C — Decimal, Money et Quantity

Le round-trip `string → Decimal → NUMERIC(30,12) → string` est exact pour les
unités, kilogrammes, grammes, litres, mètres, cartons, conditionnements
fractionnaires et grandes valeurs. Le corpus couvre aussi taxes, remises,
conversion, résidus d’allocation, coût moyen et remboursement.

Les divisions et réductions de scale exigent toujours un `RoundingMode`.
L’ADR-0014 fixe les précisions techniques initiales.

## Spike E — Concurrence Stock

Avec un stock initial de 5, les consommations concurrentes de 4 et 3 ne peuvent
pas toutes deux réussir :

- l’optimistic locking rejette la seconde version obsolète ;
- l’UPDATE DBAL conditionnel rejette directement la seconde consommation sans
  charger un snapshot.

La quantité finale vaut 1 et la contrainte PostgreSQL interdit toute quantité
négative. L’ADR-0015 retient l’UPDATE conditionnel pour la consommation chaude,
tout en conservant l’optimistic locking pour les workflows d’aggregate.
