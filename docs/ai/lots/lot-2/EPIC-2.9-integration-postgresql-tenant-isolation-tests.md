# 15. Epic 2.9 — Integration, PostgreSQL & tenant isolation tests

## Objectif

Prouver que le Lot 2 fonctionne réellement sur l’infrastructure de production cible et conserve les garanties des Lots 0 et 1.

---

## Étape 2.9.1 — Tests Domain Product

Couvrir au minimum :

```text
create draft product
activate valid product
reject activation without base packaging
reject SERVICE inventoryTracked
reject ProductCode mutation after activation
reject baseUnit mutation after activation
deactivate
reactivate
archive
reject invalid transition
```

Domain tests sans base ni réseau.

### Commit proposé

```text
test(catalog): cover product invariants
```

---

## Étape 2.9.2 — Tests UnitOfMeasure & conversion

Couvrir :

- precision ;
- conversion exacte ;
- facteur 1 ;
- facteurs décimaux ;
- quantity increment ;
- minimum quantity ;
- invalid conversions ;
- aucune perte silencieuse.

### Commit proposé

```text
test(catalog): cover packaging quantity conversion
```

---

## Étape 2.9.3 — Tests Category hierarchy

Couvrir :

```text
A → B
B → C
```

puis tentative :

```text
C → A
```

Résultat :

```text
DOMAIN_RULE_VIOLATION
```

Tester également cross-tenant parent.

### Commit proposé

```text
test(catalog): prevent category hierarchy cycles
```

---

## Étape 2.9.4 — Tests PostgreSQL ProductCode

Ouvrir PostgreSQL réel.

Vérifier :

```text
Organization A
ProductCode ABC
→ OK
```

```text
Organization A
ProductCode ABC
→ CONFLICT
```

```text
Organization B
ProductCode ABC
→ OK
```

### Commit proposé

```text
test(catalog): verify tenant product code uniqueness
```

---

## Étape 2.9.5 — Tests Barcode

Vérifier :

- zéros initiaux ;
- normalisation ;
- unicité tenant ;
- même barcode autorisé entre tenants si politique = tenant uniqueness ;
- résolution correcte ;
- packaging correct ;
- cross-tenant invisible.

### Commit proposé

```text
test(catalog): verify barcode uniqueness and resolution
```

---

## Étape 2.9.6 — Tests Pricing

Couvrir :

```text
PriceList currency XAF
ProductPrice XAF
→ OK
```

```text
PriceList XAF
ProductPrice EUR
→ reject
```

Couvrir :

- périodes ;
- statut ;
- priorité si utilisée ;
- prix absent ;
- packaging incohérent ;
- produit autre tenant.

### Commit proposé

```text
test(pricing): cover basic price resolution
```

---

## Étape 2.9.7 — Tenant isolation applicative

Créer au minimum :

```text
Tenant A
Tenant B
```

Puis vérifier :

```text
Tenant B
GET Product A
→ 404
```

```text
Tenant B
update Product A
→ 404
```

```text
Tenant B
resolve Barcode A
→ 404
```

```text
Tenant B
get PriceList A
→ 404
```

```text
Tenant B
use Packaging A
→ denied/not found
```

Aucune fuite d’existence.

### Commit proposé

```text
test(tenant): enforce catalog tenant isolation
```

---

## Étape 2.9.8 — PostgreSQL RLS

Appliquer et tester RLS sur toutes les tables tenant-owned du Lot 2.

Selon le modèle final :

```text
catalog.category
catalog.product
catalog.product_packaging
catalog.product_barcode
catalog.price_list
catalog.product_price
...
```

Vérifier :

- RLS activé ;
- RLS forcé si convention actuelle ;
- contexte tenant transaction-local ;
- deux connexions simultanées ;
- aucun accès cross-tenant ;
- rollback nettoie correctement le contexte.

### Commit proposé

```text
test(tenant): verify catalog PostgreSQL RLS
```

---

## Étape 2.9.9 — Authorization tests

Tester au minimum :

```text
ORGANIZATION_OWNER
→ administer catalog

CASHIER
→ read allowed catalog data only

actor without PRODUCT_CREATE
→ cannot create Product

actor without PRODUCT_PRICE_UPDATE
→ cannot change price
```

Tester également les `AccessScope` si les endpoints concernés sont store-scoped.

### Commit proposé

```text
test(access): verify catalog permissions
```

---

## Étape 2.9.10 — API contract tests

Tester :

- status codes ;
- payloads ;
- validation ;
- erreurs ;
- authentication ;
- authorization ;
- correlationId ;
- OpenAPI ;
- content types ;
- pagination/lists.

### Commit proposé

```text
test(api): verify catalog and pricing contracts
```

---

## Étape 2.9.11 — Atomicité métier, audit et outbox

Injecter des échecs :

```text
after domain mutation
before audit
after audit
before outbox
after outbox
before commit
```

Résultat attendu :

```text
rollback
Product/Price unchanged
no partial audit
no partial outbox
```

### Commit proposé

```text
test(catalog): verify catalog transaction atomicity
```

---

## Definition of Done — Epic 2.9

- domain tests complets ;
- PostgreSQL réel ;
- tenant isolation réelle ;
- RLS validé ;
- permissions testées ;
- API contract tests verts ;
- audit/outbox atomiques ;
- architecture tests verts ;
- PHPStan vert ;
- PHP-CS-Fixer vert ;
- Composer audit vert ;
- CI verte.

---
