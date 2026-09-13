# F3 — Support domaines et contrat API

## Catalog

Product : ProductId, OrganizationId, ProductCode, name, description?, status, type, baseUnitId, inventoryTracked, taxCategoryId?, categoryId?, ProductPackaging[], Version.
Statuts DRAFT / ACTIVE / INACTIVE / ARCHIVED ; types PHYSICAL / SERVICE.
ProductCode est unique par organisation ; ProductCode et baseUnitId sont immuables après activation.
SERVICE implique inventoryTracked = false.

Category est un aggregate distinct : collection/item/create/update/move/activate/deactivate/archive. La hiérarchie ne doit pas contenir de cycle ; le serveur valide cette règle.

ProductPackaging : id, productId, code, name, unitId, conversionFactor, precision, minimumQuantity, quantityIncrement, allowedForSale, allowedForPurchase, barcodes, status.
Le packaging de base a conversionFactor = 1 ; baseQuantity = enteredQuantity × conversionFactor.
Ne pas envoyer de modification du facteur si le contrat la refuse ; un changement exige un nouveau packaging.

Barcode est une chaîne conservant les zéros initiaux. Unicité Organization + normalizedBarcode ; résolution tenant-safe vers ProductId + ProductPackagingId.

## Pricing

PriceList : OrganizationId, code, name, currency, status, scope, validFrom?, validTo?, priority, Version ; une seule devise.
ProductPrice : PriceListId, ProductId, ProductPackagingId, amount, validity, status, Version.
Les prix ciblent le packaging ; les listes/prix proposent collection/item/create/update/activate/deactivate/archive.

GET /api/products/{productId}/packagings/{packagingId}/effective-price accepte la date métier optionnelle at.
La priorité et la validité sont résolues uniquement par le serveur.
ProductPriceNotFound désigne l’absence de prix ; ne pas la convertir en montant nul.

## Vérification à l’implémentation

Les capacités ci-dessus viennent du backlog. Vérifier les DTO OpenAPI et opérations réellement présents avant de construire un formulaire ; ne pas inventer champs, endpoints, permissions, pagination ou transitions.
Inspecter les patterns Foundation/F1/F2 les plus proches.
Le résumé Pricing de Product Details consomme les contrats explicites de Pricing ; aucune règle de tarification dans Catalog.
Les unités sont propres au tenant (ADR-0019). Money et quantités gardent leur représentation exacte (ADR-0008).

Source : `docs/specs/planning/zandu-frontend-lot-f3-admin-catalog-pricing.md`, sections 3–13.
