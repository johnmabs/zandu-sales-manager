# Pricing feature

Owns PriceLists, ProductPrices and EffectivePrice, separately from Catalog and Inventory Costing.
Generated OpenAPI types and FoundationApi provide transport, authentication, error and correlation handling.
Money amounts remain decimal strings. Effective prices are resolved by Symfony; no local priority/validity engine and no zero fallback for missing prices.
ProductPrice uses the real packagingId field. EffectivePrice returns priceListId, productPriceId, amount, currency and sourceVersion.
List providers return arrays. Item provider response shape must be checked before future details implementation.
Public contracts are the only cross-feature collaboration surface. No import of Catalog internals.

The public barrel exposes all three Pricing projections, mutation inputs and closed lifecycle/scope unions. Runtime decoders reject unknown statuses, non-organization scopes and numeric money values.
