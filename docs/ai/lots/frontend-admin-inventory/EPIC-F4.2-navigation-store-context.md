# Epic F4.2 — Inventory navigation and store context

**Statut :** Terminé

Créer les routes et la sous-navigation permission-aware ; exiger un store actif
accessible et isoler les caches lors de son changement.

Implémenté avec les routes Inventory protégées, une navigation filtrée selon le
scope organisation/store, le refus des magasins absents ou inaccessibles et une
transition de cache dédiée au changement de magasin.

Supports : routing, security. Source : spécification F4, section 20.
