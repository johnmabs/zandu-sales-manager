# Epic F3.35 — Cache invalidation

**Statut :** Backlog d’implémentation

Catalog : Product update/activation, Packaging creation/archival, Barcode mutation peuvent affecter plusieurs écrans. Pricing : PriceList activation, ProductPrice update/activation nécessitent l’invalidation des queries dépendantes.

Exemple : ProductPrice update → ProductPrice → price collection → effective-price → Product pricing summary.

## Supports ciblés

- [SUPPORT-domain-api-contract.md](SUPPORT-domain-api-contract.md)
- [SUPPORT-security-errors-cache.md](SUPPORT-security-errors-cache.md)

Source : `docs/specs/planning/zandu-frontend-lot-f3-admin-catalog-pricing.md`, section 49.
