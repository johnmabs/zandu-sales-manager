# Catalog feature

Owns Categories, Products, ProductPackaging and Barcodes. Reuses generated OpenAPI types and FoundationApi HTTP/session/error handling. Symfony remains authoritative for tenant isolation, lifecycle, codes, units and conversions.

Pricing rules and Inventory Costing do not belong here. No generic costPrice on Product. Cross-feature collaboration must use public contracts; no imports of Pricing internals.

Product filters are server-owned: status, type, categoryId, productCode and search. ProductProvider currently returns an array sorted by code, without server pagination; do not invent pagination or sort parameters. Keep server order and tenant-filter defensively, as Stores/Access do.

Packagings and barcodes are separate server resources, not embedded arrays on the ProductResource response. Decimal fields and barcodes remain strings.
