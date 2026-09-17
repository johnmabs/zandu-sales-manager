# Frontend Lot F4 — Admin Inventory — Context

## Statut et objectif

Version 1.0. F4.1–F4.4 terminés ; F4.5 est le prochain Epic. Gate F3 encore en
attente des preuves Chromium CI.

Administrer les positions et mouvements de stock, la valorisation, les
transferts inter-store et les inventaires physiques depuis Zandu Admin.

## Boundaries

- Inventory : quantité physique, mouvements, transferts et comptages.
- InventoryCosting : valeur économique et ledger de valorisation.
- Catalog/Stores : métadonnées consommées par contrats publics uniquement.
- Aucun calcul métier de stock ou de coût dans le frontend.

## Dependencies

- APIs Stock, StockMovement, InventoryValuation, StockTransfer et StockCount ;
- contrats frontend Catalog Product et Stores accessibles ;
- Foundation : session, organisation/store actifs, permissions, API, cache,
  formulaires, tables, notifications et tests.

## Lot-wide invariants

- quantités, coûts et valeurs sont des chaînes décimales exactes ;
- ledgers append-only ; aucune correction historique en place ;
- projections tenant/store défensives ;
- lecture des coûts et valeurs protégée par `INVENTORY_READ` selon le contrat
  serveur actuel ; initialisation et attribution gardées par leurs permissions
  d’opération dédiées ;
- mode BLIND sans fuite de quantité attendue ou variance ;
- ordre, filtres et pagination restent serveur ;
- changement d’organisation/store sépare et purge les caches.

## Source of truth

`docs/specs/planning/zandu-frontend-lot-f4-admin-inventory.md`

Ouvrir la source uniquement si les fichiers compacts ne répondent pas à une
question métier requise.
