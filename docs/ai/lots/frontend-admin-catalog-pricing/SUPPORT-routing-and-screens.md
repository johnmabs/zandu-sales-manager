# F3 — Support routes et écrans

## Routes proposées dans la source

```text
/admin/catalog/products
/admin/catalog/products/new
/admin/catalog/products/{productId}
/admin/catalog/products/{productId}/edit
/admin/catalog/categories
/admin/pricing/price-lists
/admin/pricing/price-lists/new
/admin/pricing/price-lists/{priceListId}
/admin/pricing/product-prices
/admin/pricing/product-prices/new
/admin/pricing/product-prices/{productPriceId}
```

Le préfixe /admin est une proposition du backlog ; l’Admin existant utilise /app.
F3.3 retient le shell/routage établi : remplacer /admin par /app dans ces routes. Les entrées Catalogue et Tarification possèdent leurs sous-navigations ; seuls les écrans livrés dans IMPLEMENTATION_STATUS.md exposent leurs capacités métier.

## Navigation et vues

Catalogue : Produits, Catégories. Tarification : Listes de prix, Prix produits.
Packaging et Barcodes sont accessibles depuis Product Details.
Les features catalog et pricing restent séparées, chacune avec api, composants, schemas et tests.
Product Details organise General / Packaging / Barcodes / Pricing summary / Lifecycle.
ProductPackagingSelector charge les conditionnements du produit choisi et empêche les couples incohérents.

Écrans et composants proposés : source sections 59–60.
Recherche et filtres produit sont serveur ; pagination seulement si disponible ; tri déterministe.
Desktop/laptop prioritaires, tablette utilisable ; tables, arbre, formulaires, dialogues et focus accessibles.

Source : `docs/specs/planning/zandu-frontend-lot-f3-admin-catalog-pricing.md`, sections 14, 17–20, 39, 51–53, 58–60.
