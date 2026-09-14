# Zandu Sales Manager — Frontend Lot F3 : Admin Catalog & Pricing

**Version :** 1.0
**Statut :** Backlog d’implémentation
**Surface :** Zandu Admin
**Prérequis :** Frontend Foundation + Gates F1/F2 validés
**Langue :** Français — identifiants de code en anglais

---

# 1. Objectif du Lot F3

Le Lot F3 fournit l’interface d’administration du catalogue commercial et de la tarification de base. Il couvre Categories, Products, Product Packaging, Barcodes, Price Lists, Product Prices et Effective Price.

```text
Admin
→ Catalog
   → Categories
   → Products
      → Product details
      → Packaging
      → Barcodes
→ Pricing
   → Price Lists
   → Product Prices
   → Effective Price
```

F3 constitue la première interface permettant de configurer ce qui sera réellement sélectionné et vendu dans le POS.

---

# 2. Position dans la roadmap frontend

Frontend Foundation → F1 — Admin Stores → F2 — Admin Users & Access → F3 — Admin Catalog & Pricing → F4 — Admin Inventory.

Le Frontend Foundation recommande explicitement Stores → Users / Access → Catalog / Pricing → Inventory.

---

# 3. Frontières métier à préserver

Le frontend conserve la séparation `Catalog ≠ Pricing ≠ Inventory Costing` et `selling price ≠ inventory cost`.

`Product` ne possède pas de `costPrice` générique. Le coût appartient à Inventory Costing. Ne pas créer Purchase price, Cost price ou Average cost comme simples champs du catalogue.

---

# 4. Product

La baseline définit :

```text
Product
├── ProductId
├── OrganizationId
├── ProductCode
├── name
├── description?
├── status
├── type
├── baseUnitId
├── inventoryTracked
├── taxCategoryId?
├── categoryId?
├── ProductPackaging[]
└── Version
```

Statuts : DRAFT, ACTIVE, INACTIVE, ARCHIVED. Types : PHYSICAL, SERVICE.

---

# 5. Invariants importants pour l’UX

Le frontend reflète ces invariants sans les réimplémenter comme autorité : ProductCode unique par Organization et immuable après activation ; SERVICE → inventoryTracked = false ; baseUnitId immuable après activation. Un produit archivé reste historiquement consultable.

---

# 6. ProductPackaging

Un produit peut proposer plusieurs conditionnements :

```text
ProductPackaging
├── ProductPackagingId
├── ProductId
├── code
├── name
├── unitId
├── conversionFactor
├── precision
├── minimumQuantity
├── quantityIncrement
├── allowedForSale
├── allowedForPurchase
├── barcodes[]
└── status
```

Le packaging de base possède conversionFactor = 1. Tous les mouvements physiques restent exprimés dans l’unité de base.

---

# 7. ConversionFactor

Règle critique : `baseQuantity = enteredQuantity × conversionFactor`.

Un facteur déjà utilisé ne doit pas être modifié. Le backend impose donc change conversion factor → create new packaging, et non PATCH old packaging conversionFactor. L’API de mise à jour respecte déjà cette immutabilité. Le formulaire affiche le champ en lecture seule lorsqu’il n’est plus modifiable.

---

# 8. Categories

`Category` est un aggregate séparé. La hiérarchie est autorisée, les cycles interdits. Le backend expose collection, item, create, update, move, activate, deactivate, archive.

---

# 9. Barcodes

Les codes-barres sont des chaînes : `"0012345" ≠ 12345 numeric value`. Préserver les zéros initiaux. Ils sont uniques par Organization + normalizedBarcode. Leur résolution retourne ProductId + ProductPackagingId. Le backend prend en charge ajout, suppression métier et résolution tenant-safe.

---

# 10. Pricing

Le prix cible ProductPackagingId, pas directement le produit abstrait. Le modèle distingue PriceList et ProductPrice.

---

# 11. PriceList

La liste utilise une seule devise.

```text
PriceList
├── OrganizationId
├── code
├── name
├── currency
├── status
├── scope
├── validFrom?
├── validTo?
├── priority
└── Version
```

API : GET collection, GET item, Create, Update, Activate, Deactivate, Archive.

---

# 12. ProductPrice

```text
ProductPrice
├── PriceListId
├── ProductId
├── ProductPackagingId
├── amount
├── validity
├── status
└── Version
```

API : GET /api/product-prices ; GET /api/product-prices/{id} ; POST /api/product-prices ; PATCH /api/product-prices/{id} ; Activate, Deactivate, Archive.

---

# 13. Effective Price

Le backend fournit `GET /api/products/{productId}/packagings/{packagingId}/effective-price` avec une date métier optionnelle `at`. La résolution respecte les PriceList actives et leur priorité. Le frontend ne doit jamais recalculer current effective price à partir des listes chargées localement.

---

# 14. Architecture feature proposée

PROPOSÉ :

```text
features/
├── catalog/
│   ├── api/
│   ├── categories/
│   ├── products/
│   ├── packagings/
│   ├── barcodes/
│   ├── components/
│   ├── schemas/
│   └── tests/
└── pricing/
    ├── api/
    ├── price-lists/
    ├── product-prices/
    ├── components/
    ├── schemas/
    └── tests/
```

Ne pas créer un énorme features/products absorbant Catalog, Pricing puis Inventory.

---

# 15. Epic F3.1 — Catalog feature foundation

Créer `features/catalog` avec Categories, Products, ProductPackaging et Barcodes. Réutiliser les contrats API typés ; préserver les frontières feature sans embarquer les domaines Pricing ou Inventory.

Commit : `feat(admin): add catalog feature foundation`.

---

# 16. Epic F3.2 — Pricing feature foundation

Créer séparément `features/pricing` avec PriceLists, ProductPrices et EffectivePrice.

Commit : `feat(admin): add pricing feature foundation`.

---

# 17. Epic F3.3 — Catalog navigation

Navigation proposée : Catalogue → Produits, Catégories ; Tarification → Listes de prix, Prix produits. Packaging et Barcode restent accessibles depuis Product Details.

---

# 18. Epic F3.4 — Product list

Créer `/admin/catalog/products`. Utiliser les filtres serveur `status`, `type`, `categoryId`, `productCode`, `search`. La recherche porte sur code et nom ; le tri serveur est déterministe par code. Ne pas charger tous les produits pour filtrer localement.

---

# 19. Epic F3.5 — Product filters

Permettre au minimum Search, Status, Type et Category. Proposition : `[Search product...]` ; Status = All / Draft / Active / Inactive / Archived ; Type = All / Physical / Service.

---

# 20. Epic F3.6 — Product details

Créer `/admin/catalog/products/{productId}`. Organiser General, Packaging, Barcodes, Pricing summary et Lifecycle.

Exemple : Paracétamol 500 mg ; code MED-001 ; type PHYSICAL ; statut ACTIVE ; Inventory tracking Enabled ; catégorie Medicines.

---

# 21. Epic F3.7 — Create Product

Flux Products → New Product → formulaire → POST → Product Details. Les champs exacts viennent du contrat OpenAPI. Le frontend ne duplique pas les invariants serveur.

---

# 22. Epic F3.8 — Update Product

Adapter le formulaire au lifecycle : DRAFT → ProductCode éditable selon l’API ; ACTIVE → ProductCode et baseUnitId immuables. Distinguer clairement les champs éditables des champs métier en lecture seule.

---

# 23. Epic F3.9 — Product lifecycle

Supporter les transitions réellement exposées : Activate, Deactivate, Reactivate, Archive. Le backend expose ces huit opérations produit avec collection/item/create/update. Deactivate et Archive nécessitent une confirmation explicite.

---

# 24. Epic F3.10 — Category tree

Créer `/admin/catalog/categories` avec représentation hiérarchique. Exemple : Food → Beverages, Snacks ; Health → Medicines, Supplements. Ne pas recalculer les règles de cycle comme garantie métier côté frontend.

---

# 25. Epic F3.11 — Category management

Supporter Create, Update, Move, Activate, Deactivate, Archive selon les endpoints backend existants.

---

# 26. Epic F3.12 — Packaging list

Dans Product Details, afficher Packaging : Unit, Box of 10, Carton of 100, avec conversionFactor, allowedForSale, allowedForPurchase et status.

---

# 27. Epic F3.13 — Create Packaging

Le formulaire couvre selon contrat : code, name, unit, conversionFactor, precision, minimumQuantity, quantityIncrement, allowedForSale, allowedForPurchase. Préserver les valeurs décimales exactes.

---

# 28. Epic F3.14 — Edit Packaging

Éditer les propriétés commerciales autorisées. Ne pas présenter conversionFactor comme modifiable lorsque le backend l’interdit.

Message proposé : « Le facteur de conversion de ce conditionnement ne peut plus être modifié. Créez un nouveau conditionnement si la conversion change. »

---

# 29. Epic F3.15 — Packaging lifecycle

Supporter Deactivate et Archive selon les endpoints disponibles. Un packaging archivé reste visible dans les historiques appropriés.

---

# 30. Epic F3.16 — Barcode management

Depuis Packaging → Codes-barres : Add barcode et Remove barcode. Ne jamais convertir un barcode en nombre : `0012345678905` reste exactement cette chaîne.

---

# 31. Epic F3.17 — Barcode validation UX

Préserver les zéros initiaux, appliquer le trim selon contrat, éviter tout parsing numérique. L’unicité réelle reste serveur ; mapper l’erreur de doublon depuis le contrat API.

---

# 32. Epic F3.18 — Price List list

Créer `/admin/pricing/price-lists`. Afficher code, name, currency, status, scope, priority et validity.

---

# 33. Epic F3.19 — Price List details

Créer `/admin/pricing/price-lists/{priceListId}` avec General, Validity, Priority, Status et Prices.

---

# 34. Epic F3.20 — Create Price List

Formulaire : code, name, currency, scope, priority, validFrom?, validTo?. Le backend valide les périodes et devises.

---

# 35. Epic F3.21 — Update Price List

Réutiliser le contrat réel de mise à jour. Après mutation : invalider PriceList, sa collection et les queries effective-price affectées.

---

# 36. Epic F3.22 — Price List lifecycle

Supporter Activate, Deactivate, Archive avec confirmations lorsque nécessaire.

---

# 37. Epic F3.23 — Product Price list

Créer `/admin/pricing/product-prices`. Afficher selon contrat Product, Packaging, Price List, Amount, Currency, Status et Validity.

---

# 38. Epic F3.24 — Create Product Price

Flux recommandé : Price List → Add price → Product → Packaging → Amount → Validity → Create. Un prix cible obligatoirement un packaging, pas seulement un Product.

---

# 39. Epic F3.25 — Product / Packaging selector

Créer la primitive feature `ProductPackagingSelector` : Select Product → Load Product Packagings → Select Packaging. Ne pas permettre un couple Product A + Packaging de Product B.

---

# 40. Epic F3.26 — Money input

Réutiliser les primitives Money du Foundation. `parseFloat(...)` est interdit comme représentation métier durable ; les montants backend utilisent une représentation décimale exacte.

---

# 41. Epic F3.27 — Product Price lifecycle

Supporter Activate, Deactivate, Archive. Les prix archivés restent historiquement pertinents.

---

# 42. Epic F3.28 — Effective Price display

Depuis Product Details ou l’administration Product Price, appeler directement `/api/products/{productId}/packagings/{packagingId}/effective-price`.

Exemple : Packaging Box 10 ; Effective price 5 000 XAF ; Source Retail Price List, si ces informations sont exposées par le contrat.

---

# 43. Epic F3.29 — Effective price at date

Si l’UX l’exige, proposer Price at date via `?at=`. Ne pas implémenter la priorité des PriceList côté frontend.

---

# 44. Epic F3.30 — Product pricing summary

PROPOSÉ : dans Product Details → Pricing, afficher pour chaque packaging vendable Packaging, Effective price et Status. Préparer le futur POS sans mélanger POS et Admin.

---

# 45. Epic F3.31 — No price state

L’absence de prix est un état métier explicite : le backend utilise `ProductPriceNotFound`, pas un prix zéro.

UX : « Aucun prix actif — Aucun prix applicable n'est actuellement défini pour ce conditionnement. » Ne jamais afficher `0 XAF` pour signifier « pas de prix ».

---

# 46. Epic F3.32 — Permission-aware Catalog

Utiliser les permissions Foundation : `PRODUCT_READ` est exigé par l’API produit. Même principe pour les mutations Category, Packaging et Barcode selon le catalogue backend réel. L’UX ne remplace jamais l’autorisation serveur.

---

# 47. Epic F3.33 — Permission-aware Pricing

Utiliser `PRICE_LIST_READ`, `PRODUCT_PRICE_READ` et les permissions de mutation dédiées du backend. Le frontend adapte l’UX, le serveur reste autorité.

---

# 48. Epic F3.34 — Error handling

Appliquer le contrat standardisé 400, 401, 403, 404, 409, 422. Cas : duplicate product code, duplicate barcode, invalid hierarchy, invalid lifecycle transition, invalid packaging, price not found, price conflict, permission denied, cross-tenant not found. Préserver correlationId.

---

# 49. Epic F3.35 — Cache invalidation

Catalog : Product update/activation, Packaging creation/archival, Barcode mutation peuvent affecter plusieurs écrans. Pricing : PriceList activation, ProductPrice update/activation nécessitent l’invalidation des queries dépendantes.

Exemple : ProductPrice update → ProductPrice → price collection → effective-price → Product pricing summary.

---

# 50. Epic F3.36 — Loading / empty states

États spécifiques : no products, no categories, no packaging, no barcodes, no price lists, no product prices, no effective price. Chaque empty state indique la prochaine action possible.

---

# 51. Epic F3.37 — Tables and large collections

Products et ProductPrices peuvent être volumineux. Utiliser server filters, server pagination when available et deterministic sorting. Éviter load all products → filter in browser.

---

# 52. Epic F3.38 — Accessibility

Couvrir tables clavier, navigation catégorie/arbre accessible, formulaires produit, dialogues lifecycle accessibles, money inputs avec labels, barcode inputs, restauration du focus et association des erreurs.

---

# 53. Epic F3.39 — Responsive Admin

Priorité desktop, laptop, tablette utilisable. Product Details peut utiliser tabs/panels General, Packaging, Pricing sur petits écrans.

---

# 54. Epic F3.40 — Unit/component tests

Couvrir ProductStatusBadge, ProductTypeBadge, Product filters, Product form, immutable activated fields, Category tree, Packaging form, conversionFactor read-only behavior, Barcode input preservation, PriceList form, ProductPrice form, ProductPackagingSelector, EffectivePrice state, NoPrice state.

---

# 55. Epic F3.41 — Integration tests

Scénarios : product list loads ; product search/filtering works ; product creation/activation/update succeeds ; category create/move works ; packaging creation succeeds ; barcode preserves leading zeros ; duplicate barcode fails safely ; price list creation/activation succeeds ; product price creation succeeds ; effective price resolves ; no-price case displayed correctly ; permission denied handled.

---

# 56. Epic F3.42 — E2E Catalog flow

Admin login → Catalog → Create category → Create product → Create packaging → Add barcode → Activate product → Product appears in search.

---

# 57. Epic F3.43 — E2E Pricing flow

Admin → Create Price List → Activate Price List → Select Product → Select Packaging → Create Product Price → Activate Product Price → Effective Price resolves.

---

# 58. Routing proposé

Décision d’intégration F3.3 : le préfixe retenu est `/app`, conformément au shell Admin existant. Les routes proposées ci-dessous se déclinent sous `/app/catalog` et `/app/pricing` ; aucune migration globale vers `/admin`.

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

---

# 59. Écrans minimaux

```text
ProductListPage
ProductDetailsPage
CreateProductPage
EditProductPage
CategoryManagementPage
PriceListPage
PriceListDetailsPage
CreatePriceListPage
ProductPriceListPage
CreateProductPricePage
ProductPriceDetailsPage
```

---

# 60. Composants feature importants

```text
ProductStatusBadge
ProductTypeBadge
CategorySelector
CategoryTree
PackagingTable
PackagingForm
BarcodeList
BarcodeInput
PriceListStatusBadge
ProductPackagingSelector
MoneyInput
EffectivePriceCard
NoEffectivePriceState
```

---

# 61. Hors scope F3

Ne pas introduire :

```text
Stock quantities
Stock movements
Stock adjustments
Stock valuation
Average cost
Purchasing
Goods receipts
Sales cart
POS product search
Promotions management
Advanced taxes management
Inventory dashboards
```

Même si Product ou Pricing possède des liens métier avec ces concepts.

---

# 62. Taxes et promotions

La spécification DDD définit taxes, remises et promotions, mais les documents fournis ne démontrent pas ici une API d’administration complète comparable aux APIs Product/PriceList/ProductPrice. Tax administration UI, Promotion administration UI et Discount configuration UI restent hors scope F3 tant que les contrats backend correspondants ne sont pas explicitement disponibles.

---

# 63. Ordre d’implémentation recommandé

```text
F3.1  Catalog feature foundation
F3.2  Pricing feature foundation
F3.3  Catalog navigation
F3.4  Product list
F3.5  Product filters
F3.6  Product details
F3.7  Create Product
F3.8  Update Product
F3.9  Product lifecycle
F3.10  Category tree
F3.11  Category management
F3.12  Packaging list
F3.13  Create Packaging
F3.14  Edit Packaging
F3.15  Packaging lifecycle
F3.16  Barcode management
F3.17  Barcode validation UX
F3.18  Price List list
F3.19  Price List details
F3.20  Create Price List
F3.21  Update Price List
F3.22  Price List lifecycle
F3.23  Product Price list
F3.24  Create Product Price
F3.25  Product / Packaging selector
F3.26  Money input
F3.27  Product Price lifecycle
F3.28  Effective Price display
F3.29  Effective price at date
F3.30  Product pricing summary
F3.31  No price state
F3.32  Permission-aware Catalog
F3.33  Permission-aware Pricing
F3.34  Error handling
F3.35  Cache invalidation
F3.36  Loading / empty states
F3.37  Tables and large collections
F3.38  Accessibility
F3.39  Responsive Admin
F3.40  Unit/component tests
F3.41  Integration tests
F3.42  E2E Catalog flow
F3.43  E2E Pricing flow
```

---

# 64. Commits proposés

```text
feat(admin): add catalog feature foundation
feat(admin): add product administration
feat(admin): add category administration
feat(admin): add product packaging management
feat(admin): add barcode management
feat(admin): add pricing feature foundation
feat(admin): add price list administration
feat(admin): add product price administration
feat(admin): display effective product prices
test(admin): cover catalog administration
test(admin): cover pricing administration
test(admin): verify catalog pricing vertical slice
```

---

# 65. Gate F3

Le Lot F3 est terminé lorsque la démonstration prouve :

1. Catalog navigation works
2. Product list loads
3. Search works server-side
4. Product filters work
5. Product can be created
6. Product can be updated
7. lifecycle transitions work
8. immutable fields are represented correctly
9. Categories load
10. Category can be created
11. Category can be moved
12. invalid hierarchy is handled safely
13. Packaging list loads
14. Packaging can be created
15. commercial fields can be updated
16. conversionFactor immutability is respected
17. packaging can be deactivated/archived
18. barcode can be added
19. leading zeros are preserved
20. barcode can be removed
21. duplicate/conflict is handled
22. Price Lists load
23. Price List can be created
24. Price List can be updated
25. activation/deactivation/archive work
26. Product Prices load
27. Product Price can be created
28. Product + Packaging relationship is valid
29. exact Money handling is preserved
30. Product Price lifecycle works
31. Effective Price resolves from server
32. no-price state is distinct from zero price
33. cache invalidation keeps displayed price fresh
34. Catalog permissions behave correctly
35. Pricing permissions behave correctly
36. cross-tenant resources remain NOT_FOUND
37. business errors preserve correlationId
38. loading states work
39. empty states work
40. accessibility checks pass
41. unit/component tests pass
42. integration tests pass
43. Catalog E2E passes
44. Pricing E2E passes
45. lint passes
46. typecheck passes
47. Admin build passes

---

# 66. Résultat attendu

À la sortie de F3, Zandu Admin permet à une organisation de préparer complètement son offre commerciale de base : Category → Product → Packaging → Barcode ; PriceList → ProductPrice → Effective Price.

Le système permet de répondre clairement : « Que vend-on ? Dans quel conditionnement ? Comment l’identifier ? À quel prix est-il vendu maintenant ? », sans introduire encore la gestion physique du stock.

---

# 67. Étape suivante

Après le Gate F3 : Frontend Lot F4 — Admin Inventory. F4 couvrira Stock positions, Stock movements, Initial stock / adjustments, StockTransfer, StockCount et Inventory valuation visibility, avec une attention particulière aux permissions de consultation des coûts et valeurs.
