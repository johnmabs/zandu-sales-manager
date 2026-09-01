# 14. Epic 2.8 — Catalog & Pricing API

## Objectif

Exposer le Lot 2 par une API REST intentionnelle, tenant-safe et documentée avec OpenAPI.

Les DTO API restent dans :

```text
Presentation/Api
```

Les aggregates et entités Doctrine ne sont jamais exposés directement.

---

## Étape 2.8.1 — API Categories

Endpoints recommandés :

```text
GET    /api/categories
POST   /api/categories
GET    /api/categories/{id}
PATCH  /api/categories/{id}
POST   /api/categories/{id}/move
POST   /api/categories/{id}/activate
POST   /api/categories/{id}/deactivate
POST   /api/categories/{id}/archive
```

Toutes les ressources sont tenant-scoped.

### Commit proposé

```text
feat(api): expose category management
```

---

## Étape 2.8.2 — API Products

Endpoints :

```text
GET    /api/products
POST   /api/products
GET    /api/products/{id}
PATCH  /api/products/{id}

POST   /api/products/{id}/activate
POST   /api/products/{id}/deactivate
POST   /api/products/{id}/reactivate
POST   /api/products/{id}/archive
```

Filtres utiles :

```text
status
type
categoryId
productCode
search
```

Ne pas créer des filtres arbitraires non indexés sans usage.

### Commit proposé

```text
feat(api): expose product management
```

---

## Étape 2.8.3 — API ProductPackaging

Endpoints :

```text
GET    /api/products/{productId}/packagings
POST   /api/products/{productId}/packagings
GET    /api/products/{productId}/packagings/{id}
PATCH  /api/products/{productId}/packagings/{id}
POST   /api/products/{productId}/packagings/{id}/deactivate
POST   /api/products/{productId}/packagings/{id}/archive
```

Les modifications de `conversionFactor` déjà utilisé doivent respecter l’invariant historique.

### Commit proposé

```text
feat(api): expose product packaging management
```

---

## Étape 2.8.4 — API Barcode

Endpoints possibles :

```text
POST   /api/products/{productId}/packagings/{packagingId}/barcodes
DELETE /api/products/{productId}/packagings/{packagingId}/barcodes/{id}
GET    /api/catalog/barcodes/{barcode}
```

Le DELETE physique n’est utilisé que si la décision métier le permet avant usage historique.

Sinon utiliser une opération intentionnelle d’inactivation.

Résolution :

```text
GET /api/catalog/barcodes/0012345678905
```

réponse :

```json
{
  "productId": "...",
  "productPackagingId": "..."
}
```

### Commit proposé

```text
feat(api): expose barcode management and resolution
```

---

## Étape 2.8.5 — API PriceList

Endpoints :

```text
GET    /api/price-lists
POST   /api/price-lists
GET    /api/price-lists/{id}
PATCH  /api/price-lists/{id}
POST   /api/price-lists/{id}/activate
POST   /api/price-lists/{id}/deactivate
POST   /api/price-lists/{id}/archive
```

### Commit proposé

```text
feat(api): expose price list management
```

---

## Étape 2.8.6 — API ProductPrice

Endpoints :

```text
GET    /api/product-prices
POST   /api/product-prices
GET    /api/product-prices/{id}
PATCH  /api/product-prices/{id}
POST   /api/product-prices/{id}/deactivate
POST   /api/product-prices/{id}/archive
```

Résolution :

```text
GET /api/products/{productId}/packagings/{packagingId}/price
```

ou un endpoint de query équivalent cohérent avec les conventions existantes.

### Commit proposé

```text
feat(api): expose product price management
```

---

## Étape 2.8.7 — Contrat d’erreurs

Réutiliser le contrat du Lot 1 :

```text
400 VALIDATION_ERROR
401 UNAUTHENTICATED
403 FORBIDDEN
404 NOT_FOUND
409 CONFLICT
422 DOMAIN_RULE_VIOLATION
```

Ajouter des codes métier stables uniquement lorsqu’ils apportent une valeur réelle :

```text
PRODUCT_CODE_ALREADY_EXISTS
BARCODE_ALREADY_EXISTS
PRODUCT_PRICE_NOT_FOUND
INVALID_PACKAGING_CONVERSION
PRODUCT_NOT_ACTIVATABLE
```

Le format JSON reste cohérent avec le socle API.

Aucune exception interne brute n’est exposée.

---

## Étape 2.8.8 — OpenAPI

Documenter :

- payloads ;
- réponses ;
- erreurs ;
- permissions ;
- exemples ;
- statuts ;
- contraintes de conversion ;
- barcode string ;
- Money ;
- dates de validité.

### Commit proposé

```text
docs(api): document catalog and pricing endpoints
```

---

## Definition of Done — Epic 2.8

- API Category disponible ;
- API Product disponible ;
- API Packaging disponible ;
- API Barcode disponible ;
- API PriceList disponible ;
- API ProductPrice disponible ;
- OpenAPI à jour ;
- DTO séparés du Domain ;
- aucun aggregate Doctrine exposé ;
- contrat d’erreur stable ;
- endpoints tenant-safe.

---
