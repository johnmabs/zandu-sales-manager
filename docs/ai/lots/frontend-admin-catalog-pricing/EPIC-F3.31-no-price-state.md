# Epic F3.31 — No price state

**Statut :** Terminé

L’absence de prix est un état métier explicite : le backend utilise `ProductPriceNotFound`, pas un prix zéro.

UX : « Aucun prix actif — Aucun prix applicable n'est actuellement défini pour ce conditionnement. » Ne jamais afficher `0 XAF` pour signifier « pas de prix ».

## Supports ciblés

- [SUPPORT-domain-api-contract.md](SUPPORT-domain-api-contract.md)

Source : `docs/specs/planning/zandu-frontend-lot-f3-admin-catalog-pricing.md`, section 45.
