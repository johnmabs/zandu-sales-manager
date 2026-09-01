# 11. Epic 2.6 — Basic Pricing

## Objectif

Permettre à une organisation de définir le prix de vente de base d’un `ProductPackaging`.

Ce Lot n’implémente pas encore l’ensemble du moteur commercial de remises, promotions, overrides ou taxation finale de `Sale`.

---

## Étape 2.6.1 — Créer le sous-domaine / module logique Pricing

Selon la structure retenue par le repository et les décisions DDD, `Pricing` peut être matérialisé comme sous-ensemble clairement séparé dans le bounded context commercial correspondant.

La frontière doit rester explicite.

Le modèle minimal du Lot 2 :

```text
PriceList
ProductPrice
```

### Commit proposé

```text
refactor(pricing): add basic pricing structure
```

---

## Étape 2.6.2 — Ajouter `PriceList`

Aggregate :

```text
PriceList
├── PriceListId
├── OrganizationId
├── code
├── name
├── currency
├── status
├── scope
├── validFrom?
├── validTo?
├── priority
├── createdAt
├── createdBy
└── Version
```

Statuts :

```text
DRAFT
ACTIVE
INACTIVE
ARCHIVED
```

Scope minimal :

```text
ORGANIZATION
```

Un scope magasin ne doit être introduit dans le Lot 2 que si la baseline et le besoin MVP le rendent nécessaire.

Ne pas sur-construire un moteur de résolution multi-scope avant son usage réel.

### Invariants

- code unique par organization ;
- devise unique par PriceList ;
- validTo >= validFrom ;
- priority valide ;
- archived non sélectionnable ;
- tenant immutable.

### Domain events

```text
PriceListCreated
PriceListUpdated
PriceListActivated
PriceListDeactivated
PriceListArchived
```

### Commit proposé

```text
feat(pricing): add price list aggregate
```

---

## Étape 2.6.3 — Persistence PriceList

Ajouter :

- mapping Doctrine ;
- migration ;
- repository ;
- index ;
- unicité `(organization_id, code)` ;
- RLS ;
- optimistic locking ;
- tests PostgreSQL.

### Commit proposé

```text
feat(pricing): persist price lists
```

---

## Étape 2.6.4 — Ajouter `ProductPrice`

Aggregate :

```text
ProductPrice
├── ProductPriceId
├── OrganizationId
├── PriceListId
├── ProductId
├── ProductPackagingId
├── Money amount
├── status
├── validFrom?
├── validTo?
├── createdAt
├── createdBy
└── Version
```

Statuts :

```text
ACTIVE
INACTIVE
ARCHIVED
```

### Invariants

- PriceList du même tenant ;
- Product du même tenant ;
- ProductPackaging appartient au Product ;
- devise de `Money` identique à celle de PriceList ;
- amount >= 0 selon politique décidée ;
- validTo >= validFrom ;
- aucune ambiguïté silencieuse de périodes valides ;
- aucune modification d’un prix historique ne réécrit une vente future déjà snapshotée.

### Domain events

```text
ProductPriceCreated
ProductPriceUpdated
ProductPriceActivated
ProductPriceDeactivated
ProductPriceArchived
```

### Commit proposé

```text
feat(pricing): add product price aggregate
```

---

## Étape 2.6.5 — Persistence ProductPrice

Ajouter :

- mapping ;
- migration ;
- repository ;
- contraintes ;
- indexes adaptés à la résolution ;
- RLS ;
- tests PostgreSQL.

### Commit proposé

```text
feat(pricing): persist product prices
```

---

## Étape 2.6.6 — Price resolver de base

Créer un contrat applicatif :

```text
ProductPriceResolver
```

Entrée minimale :

```text
organizationId
productId
productPackagingId
businessInstant
```

Sortie :

```text
ResolvedProductPrice
├── priceListId
├── productPriceId
├── amount
├── currency
└── sourceVersion
```

Le resolver doit être déterministe pour les règles réellement introduites au Lot 2.

Absence de prix :

```text
ProductPriceNotFound
```

Ne pas retourner arbitrairement :

```text
0
```

Ne pas deviner un prix.

### Commit proposé

```text
feat(pricing): add basic product price resolution
```

---

## Étape 2.6.7 — Préparer les snapshots futurs

Le Lot 2 n’implémente pas `SaleLine`, mais les contrats doivent permettre au futur `Sales` de conserver :

```text
ProductId
ProductPackagingId
packaging factor snapshot
priceListId
productPriceId
price amount
currency
source versions
```

Ne pas introduire les classes du Domain Sales dans Catalog/Pricing.

### Commit proposé

```text
feat(pricing): expose pricing snapshot contract
```

---

## Definition of Done — Epic 2.6

- PriceList fonctionnelle ;
- ProductPrice fonctionnel ;
- prix rattaché au packaging ;
- Money exact ;
- aucune devise incohérente ;
- resolver déterministe ;
- absence de prix explicite ;
- persistence PostgreSQL ;
- tenant isolation ;
- contrats futurs Sales disponibles sans dépendance Domain.

---
