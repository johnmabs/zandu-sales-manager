# Frontend Lot F3 — Admin Catalog & Pricing — Context

## Statut et objectif

Version 1.0. F3.1 à F3.43 implémentés ; scénarios F3.42/F3.43 avec validation
Chromium attendue en CI ; Gate F3 en attente.
Surface Zandu Admin ; français et identifiants de code anglais.
Administrer l’offre commerciale de base : catégories, produits, conditionnements, codes-barres, listes de prix, prix par packaging et prix effectif.

## Frontières et capacités

Deux features : catalog (Category, Product, ProductPackaging, Barcode) et pricing (PriceList, ProductPrice, EffectivePrice).
Aucune gestion physique du stock ni Inventory Costing dans ces features.

## Dependencies

- Frontend Foundation : API typée, auth/session, organisation active, permissions, forms, Money, server state, erreurs, tables, dialogues, notifications, tests/CI.
- Gate F1 validé : shell Admin, Stores et patterns de listes/détails/mutations.
- Gate F2 validé : navigation et autorisations Admin, patterns de confirmations et tests.
- Contrats backend Catalog/Pricing réels, DTO OpenAPI, permissions et lifecycle exposés.
- Références d’unités propres au tenant selon ADR-0019 ; ne pas inventer leur API.
- Consulter IMPLEMENTATION_STATUS.md pour vérifier les Gates. Leur validation est un prérequis, pas une conséquence de l’ajout de ce backlog.

## Relevant ADRs

ADR-0005 (OpenAPI), ADR-0006 (session), ADR-0008 (décimaux exacts), ADR-0009 (Admin Web), ADR-0013 (observabilité), ADR-0017 (tenant), ADR-0018 (organisation active), ADR-0019 (unités tenant) et ADR-0024 (workspace pnpm).
Lire uniquement les décisions qui gouvernent l’Epic courant via docs/ai/ADR_INDEX.md.

## Invariants partagés

- Catalog ≠ Pricing ≠ Inventory Costing ; aucun coût générique dans Product.
- ProductCode unique par organisation ; code et unité de base immuables après activation ; SERVICE sans inventoryTracked.
- Conversion exacte ; baseQuantity = enteredQuantity × conversionFactor ; changer une conversion interdite impose un nouveau packaging.
- Barcodes chaînes avec zéros initiaux ; unicité et résolution tenant-safe serveur.
- ProductPrice cible ProductPackagingId ; Product et Packaging doivent correspondre ; devise unique par liste.
- EffectivePrice vient du serveur ; absence de prix distincte de zéro ; aucun recalcul local des priorités.
- Autorisation, unicité, hiérarchie et lifecycle restent serveur ; le frontend reflète les capacités du contrat.
- Utiliser les filtres serveur et la pagination si disponible ; invalider les caches dépendants après mutation.
- Les ressources archivées restent consultables historiquement.
- Les routes protégées Admin utilisent le préfixe `/admin`, y compris Catalog et Pricing (voir SUPPORT-routing-and-screens.md).

## Source of truth

`docs/specs/planning/zandu-frontend-lot-f3-admin-catalog-pricing.md`

N’ouvrir que la section requise si le contexte compact ne suffit pas.
