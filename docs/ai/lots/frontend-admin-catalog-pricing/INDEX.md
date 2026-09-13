# Frontend Lot F3 — Admin Catalog & Pricing — AI routing index

> Lire uniquement CONTEXT.md, l’Epic courant et les supports nécessaires.

## Source specification

`docs/specs/planning/zandu-frontend-lot-f3-admin-catalog-pricing.md`

## Read first

- [CONTEXT.md](CONTEXT.md)

## Epics

- [EPIC-F3.1-catalog-feature-foundation.md](EPIC-F3.1-catalog-feature-foundation.md) — Catalog feature foundation.
- [EPIC-F3.2-pricing-feature-foundation.md](EPIC-F3.2-pricing-feature-foundation.md) — Pricing feature foundation.
- [EPIC-F3.3-catalog-navigation.md](EPIC-F3.3-catalog-navigation.md) — Catalog navigation.
- [EPIC-F3.4-product-list.md](EPIC-F3.4-product-list.md) — Product list.
- [EPIC-F3.5-product-filters.md](EPIC-F3.5-product-filters.md) — Product filters.
- [EPIC-F3.6-product-details.md](EPIC-F3.6-product-details.md) — Product details.
- [EPIC-F3.7-create-product.md](EPIC-F3.7-create-product.md) — Create Product.
- [EPIC-F3.8-update-product.md](EPIC-F3.8-update-product.md) — Update Product.
- [EPIC-F3.9-product-lifecycle.md](EPIC-F3.9-product-lifecycle.md) — Product lifecycle.
- [EPIC-F3.10-category-tree.md](EPIC-F3.10-category-tree.md) — Category tree.
- [EPIC-F3.11-category-management.md](EPIC-F3.11-category-management.md) — Category management.
- [EPIC-F3.12-packaging-list.md](EPIC-F3.12-packaging-list.md) — Packaging list.
- [EPIC-F3.13-create-packaging.md](EPIC-F3.13-create-packaging.md) — Create Packaging.
- [EPIC-F3.14-edit-packaging.md](EPIC-F3.14-edit-packaging.md) — Edit Packaging.
- [EPIC-F3.15-packaging-lifecycle.md](EPIC-F3.15-packaging-lifecycle.md) — Packaging lifecycle.
- [EPIC-F3.16-barcode-management.md](EPIC-F3.16-barcode-management.md) — Barcode management.
- [EPIC-F3.17-barcode-validation-ux.md](EPIC-F3.17-barcode-validation-ux.md) — Barcode validation UX.
- [EPIC-F3.18-price-list-list.md](EPIC-F3.18-price-list-list.md) — Price List list.
- [EPIC-F3.19-price-list-details.md](EPIC-F3.19-price-list-details.md) — Price List details.
- [EPIC-F3.20-create-price-list.md](EPIC-F3.20-create-price-list.md) — Create Price List.
- [EPIC-F3.21-update-price-list.md](EPIC-F3.21-update-price-list.md) — Update Price List.
- [EPIC-F3.22-price-list-lifecycle.md](EPIC-F3.22-price-list-lifecycle.md) — Price List lifecycle.
- [EPIC-F3.23-product-price-list.md](EPIC-F3.23-product-price-list.md) — Product Price list.
- [EPIC-F3.24-create-product-price.md](EPIC-F3.24-create-product-price.md) — Create Product Price.
- [EPIC-F3.25-product-packaging-selector.md](EPIC-F3.25-product-packaging-selector.md) — Product / Packaging selector.
- [EPIC-F3.26-money-input.md](EPIC-F3.26-money-input.md) — Money input.
- [EPIC-F3.27-product-price-lifecycle.md](EPIC-F3.27-product-price-lifecycle.md) — Product Price lifecycle.
- [EPIC-F3.28-effective-price-display.md](EPIC-F3.28-effective-price-display.md) — Effective Price display.
- [EPIC-F3.29-effective-price-at-date.md](EPIC-F3.29-effective-price-at-date.md) — Effective price at date.
- [EPIC-F3.30-product-pricing-summary.md](EPIC-F3.30-product-pricing-summary.md) — Product pricing summary.
- [EPIC-F3.31-no-price-state.md](EPIC-F3.31-no-price-state.md) — No price state.
- [EPIC-F3.32-permission-aware-catalog.md](EPIC-F3.32-permission-aware-catalog.md) — Permission-aware Catalog.
- [EPIC-F3.33-permission-aware-pricing.md](EPIC-F3.33-permission-aware-pricing.md) — Permission-aware Pricing.
- [EPIC-F3.34-error-handling.md](EPIC-F3.34-error-handling.md) — Error handling.
- [EPIC-F3.35-cache-invalidation.md](EPIC-F3.35-cache-invalidation.md) — Cache invalidation.
- [EPIC-F3.36-loading-empty-states.md](EPIC-F3.36-loading-empty-states.md) — Loading / empty states.
- [EPIC-F3.37-tables-large-collections.md](EPIC-F3.37-tables-large-collections.md) — Tables and large collections.
- [EPIC-F3.38-accessibility.md](EPIC-F3.38-accessibility.md) — Accessibility.
- [EPIC-F3.39-responsive-admin.md](EPIC-F3.39-responsive-admin.md) — Responsive Admin.
- [EPIC-F3.40-unit-component-tests.md](EPIC-F3.40-unit-component-tests.md) — Unit/component tests.
- [EPIC-F3.41-integration-tests.md](EPIC-F3.41-integration-tests.md) — Integration tests.
- [EPIC-F3.42-e2e-catalog-flow.md](EPIC-F3.42-e2e-catalog-flow.md) — E2E Catalog flow.
- [EPIC-F3.43-e2e-pricing-flow.md](EPIC-F3.43-e2e-pricing-flow.md) — E2E Pricing flow.

## Support files

- [SUPPORT-domain-api-contract.md](SUPPORT-domain-api-contract.md)
- [SUPPORT-routing-and-screens.md](SUPPORT-routing-and-screens.md)
- [SUPPORT-security-errors-cache.md](SUPPORT-security-errors-cache.md)
- [SUPPORT-gate-and-delivery.md](SUPPORT-gate-and-delivery.md)
- [SUPPORT-out-of-scope-and-next.md](SUPPORT-out-of-scope-and-next.md)

## Dependencies

Les capacités explicites sont dans CONTEXT.md. Foundation et Gates F1/F2 validés sont des prérequis.
Ne pas inférer de dépendances à partir des numéros de Lots.

## Relevant ADRs

ADR-0005 (OpenAPI), ADR-0006 (session), ADR-0008 (décimaux exacts), ADR-0009 (Admin Web), ADR-0013 (observabilité), ADR-0017 (tenant), ADR-0018 (organisation active), ADR-0019 (unités tenant) et ADR-0024 (workspace pnpm). Résoudre les chemins via docs/ai/ADR_INDEX.md et lire seulement les ADR directement pertinents.
