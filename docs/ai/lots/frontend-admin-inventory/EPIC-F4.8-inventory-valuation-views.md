# Epic F4.8 — Inventory valuation views

**Statut :** Terminé

Ajouter contrats et vues de valorisation/ledger, montants exacts, devise Store
et état explicite non initialisé.

Supports : domain API, security costs. Source : spécification F4, section 26.

Implémentation : liste Stock/Costing coordonnée, état non initialisé explicite,
détail économique et ledger paginé, quantités et montants exacts, devise du
Store validée et projections tenant/store défensives sous `INVENTORY_READ`.
