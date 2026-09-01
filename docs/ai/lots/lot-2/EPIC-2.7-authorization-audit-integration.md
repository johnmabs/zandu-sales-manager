# 13. Epic 2.7 — Authorization, audit & integration

## Objectif

Intégrer Catalog/Pricing au système d’autorisation et de traçabilité du Lot 1.

---

## Étape 2.7.1 — Étendre le Permission Catalog

Ajouter uniquement les permissions réellement utilisées.

Proposition :

```text
CATALOG_READ

CATEGORY_CREATE
CATEGORY_UPDATE
CATEGORY_ARCHIVE

PRODUCT_CREATE
PRODUCT_READ
PRODUCT_UPDATE
PRODUCT_ACTIVATE
PRODUCT_DEACTIVATE
PRODUCT_ARCHIVE

PRICE_LIST_CREATE
PRICE_LIST_READ
PRICE_LIST_UPDATE
PRICE_LIST_ACTIVATE
PRICE_LIST_ARCHIVE

PRODUCT_PRICE_CREATE
PRODUCT_PRICE_READ
PRODUCT_PRICE_UPDATE
PRODUCT_PRICE_ARCHIVE
```

Éviter des permissions :

```text
STOCK_*
SALE_*
CASH_*
PURCHASE_*
```

tant que les lots correspondants ne les utilisent pas.

### Commit proposé

```text
feat(access): add catalog and pricing permissions
```

---

## Étape 2.7.2 — Mettre à jour les rôles système

Étendre les rôles existants uniquement avec les permissions pertinentes.

Exemple fonctionnel à challenger selon le catalogue actuel :

```text
ORGANIZATION_OWNER
→ toutes permissions Catalog/Pricing du Lot 2

STORE_MANAGER
→ lecture catalogue
→ éventuellement gestion produit/prix selon politique

CASHIER
→ lecture catalogue/prix nécessaire au futur POS
→ aucune administration

ACCOUNTANT
→ lecture pricing selon besoin
```

Ne pas coder ces règles dans Catalog.

Elles appartiennent aux rôles/permissions du bounded context d’accès.

### Commit proposé

```text
feat(access): grant catalog permissions to system roles
```

---

## Étape 2.7.3 — Appliquer `AuthorizationService`

Chaque handler sensible appelle :

```text
AuthorizationService.authorize(...)
```

avec la permission appropriée.

Aucun :

```php
if ($role === 'OWNER')
```

dans le code métier.

### Commit proposé

```text
feat(catalog): enforce catalog authorization
```

---

## Étape 2.7.4 — Operational guard

Toute mutation exige une Organization opérationnelle :

```text
OrganizationOperationalGuard
```

Les opérations qui ciblent éventuellement un store dans une évolution future doivent également utiliser le guard de store.

### Commit proposé

```text
feat(catalog): enforce organization operational guard
```

---

## Étape 2.7.5 — Security audit

Auditer au minimum les opérations sensibles :

```text
ProductActivated
ProductArchived
ProductPriceUpdated
PriceListActivated
CategoryArchived
```

Selon la politique du Lot 1, l’audit doit contenir les identifiants pertinents, acteur, tenant, correlationId et résultat sans exposer de secret.

### Commit proposé

```text
feat(audit): record catalog and pricing operations
```

---

## Étape 2.7.6 — Outbox

Les domain events devant sortir de leur transaction sont persistés via l’outbox.

Garantir :

```text
business mutation
+
audit
+
outbox
=
same local transaction
```

### Commit proposé

```text
feat(catalog): publish catalog events through outbox
```

---

## Definition of Done — Epic 2.7

- Permission Catalog étendu ;
- rôles système mis à jour ;
- AuthorizationService utilisé ;
- OrganizationOperationalGuard appliqué ;
- audit sensible opérationnel ;
- outbox transactionnelle ;
- aucune permission future introduite sans besoin ;
- atomicité testée.

---
