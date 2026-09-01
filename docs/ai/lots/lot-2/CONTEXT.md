# Zandu Sales Manager — Lot 2 : Catalog & basic Pricing

**Version :** 1.0
**Statut :** Backlog d’implémentation
**Langue :** Français — identifiants de code en anglais

---
# 1. Objectif du Lot 2

Le Lot 2 construit la première capacité commerciale de référence de Zandu Sales Manager : **décrire ce qui peut être vendu et déterminer son prix de base**, sans encore réaliser de vente ni gérer de stock physique.

À la sortie du Lot 2, une `Organization` doit pouvoir :

- structurer son catalogue ;
- gérer les unités de mesure nécessaires à ses produits ;
- créer et organiser des catégories ;
- créer un `Product` vendable ;
- distinguer les produits physiques des services ;
- déclarer si un produit physique sera suivi en stock ;
- définir son unité de base ;
- définir un ou plusieurs `ProductPackaging` ;
- associer des codes-barres aux conditionnements ;
- activer, désactiver et archiver un produit ;
- créer une `PriceList` ;
- définir un `ProductPrice` pour un conditionnement précis ;
- résoudre un produit et son conditionnement à partir d’un code-barres ;
- exposer ces capacités par API ;
- appliquer les permissions et scopes définis au Lot 1 ;
- garantir l’isolation stricte entre tenants ;
- produire les audits, domain events et messages outbox requis.

Le Lot 2 doit préparer les futurs workflows :

```text
Catalog
   ↓
Inventory
   ↓
Sales
```

sans les implémenter prématurément.

Le Lot 2 est terminé uniquement lorsque son gate de sortie est satisfait.

---
# 2. Références d’architecture

Le Lot 2 respecte la baseline DDD v1.1 et les décisions techniques déjà validées par les Lots 0 et 1.

Principes applicables :

```text
OrganizationId
= frontière stricte de tenant
```

```text
Catalog
= description commerciale du produit

Inventory
= quantité physique

Sales
= transaction commerciale

Pricing
= résolution du prix de vente
```

Un bounded context ne lit ni n’écrit directement les aggregates ou repositories internes d’un autre bounded context.

Les futurs contextes consomment des identifiants ou des contrats publics :

```text
Inventory
→ ProductId

Sales
→ ProductId
→ ProductPackagingId
→ Price resolution contract
```

et non :

```text
Inventory
→ Catalog\Product aggregate

Sales
→ Catalog\ProductRepository
```

Le Lot 2 doit réutiliser les primitives techniques déjà disponibles :

```text
OrganizationId
ActorContext
Clock
Version
Money
Decimal
TransactionManager
Outbox
SecurityAudit
CorrelationId
```

Les identifiants sont générés côté serveur.

Les handlers applicatifs suivent le modèle établi au Lot 1 :

```text
Application Handler
├── OrganizationOperationalGuard
├── AuthorizationService.authorize(...)
├── tenant-scoped repository access
├── Domain Aggregate.execute(...)
├── SecurityAudit
└── Outbox
```

Toute opération sensible doit rester atomique dans la transaction locale.

---
# 3. Décisions DDD applicables au Lot 2

## 3.1 Un SKU vendable est un `Product`

Pour le MVP :

```text
1 SKU vendable
=
1 Product
```

Ne pas introduire `ProductVariant`.

Un besoin de taille, couleur, capacité ou conditionnement est représenté dans le MVP par des `Product` distincts lorsqu’il s’agit de SKU distincts, et par `ProductPackaging` lorsqu’il s’agit uniquement de conditionnements du même produit.

---

## 3.2 `Product` ne possède pas le stock

Interdit :

```text
Product
├── quantityOnHand
├── availableStock
└── stockByStore
```

Le catalogue peut porter :

```text
inventoryTracked
```

comme intention métier indiquant qu’un produit physique sera suivi par `Inventory`.

La quantité réelle appartiendra exclusivement au bounded context `Inventory`.

---

## 3.3 Base unit explicite

Tout `Product` actif possède une unité de base :

```text
baseUnitId
```

Le conditionnement de base est explicite et possède :

```text
conversionFactor = 1
```

Tous les futurs mouvements de stock seront exprimés en base unit.

Exemple :

```text
Product
Coca-Cola 33cl

Base unit
CAN

Packaging
CAN
conversionFactor = 1

Packaging
PACK_6
conversionFactor = 6

Packaging
CARTON_24
conversionFactor = 24
```

Le futur `Inventory` pourra donc conserver :

```text
quantityOnHand = 240 CAN
```

quelle que soit la manière dont le produit a été acheté ou vendu.

---

## 3.4 Quantités et conversions exactes

Les quantités et facteurs de conversion utilisent des décimaux exacts.

Interdit :

```text
float
double
```

pour :

```text
Quantity
conversionFactor
minimumQuantity
quantityIncrement
price amount
```

Aucune quantité physique n’est arrondie silencieusement.

Toute règle de précision doit être explicite.

Si la précision technique définitive de `Quantity` n’est pas déjà figée dans les ADR issus du Spike C, elle doit être vérifiée avant la persistence définitive de `UnitOfMeasure` et `ProductPackaging`.

Une modification d’une décision `DÉCIDÉ` exige une mise à jour d’ADR.

---

## 3.5 Pricing cible `ProductPackagingId`

Un prix ne cible pas seulement :

```text
ProductId
```

mais :

```text
ProductId
+
ProductPackagingId
```

Exemple :

```text
Coca-Cola 33cl

CAN
→ 500 XAF

PACK_6
→ 2 750 XAF

CARTON_24
→ 10 000 XAF
```

`PriceList` et `ProductPrice` sont des aggregates distincts.

---

## 3.6 Barcode

Un code-barres :

- est une chaîne ;
- conserve les zéros initiaux ;
- est normalisé avant comparaison ;
- est unique par `OrganizationId + normalizedBarcode` ;
- résout vers `ProductId + ProductPackagingId`.

Interdit :

```text
barcode: int
barcode: bigint
```

---
# 4. Règle de commits

Le développement du Lot 2 suit la même logique de **commits atomiques** que les Lots 0 et 1.

Un commit doit :

- représenter une seule intention cohérente ;
- laisser le repository dans un état valide ;
- inclure les tests directement liés ;
- conserver les architecture fitness tests au vert ;
- conserver PHPStan et PHP-CS-Fixer au vert ;
- respecter les bounded context boundaries ;
- ne pas introduire `Inventory`, `CashManagement` ou `Sales` avant leur étape ;
- ne pas contourner `Application\Contract` pour un échange cross-context futur ;
- ne pas exposer directement les aggregates ou entités Doctrine dans l’API.

Format :

```text
<type>(<scope>): <description>
```

Exemples :

```text
refactor(catalog): add bounded context structure
feat(catalog): add unit of measure model
feat(catalog): add product aggregate lifecycle
feat(catalog): add product packaging
feat(catalog): add barcode resolution
feat(pricing): add price list aggregate
feat(pricing): add product price aggregate
test(tenant): enforce catalog tenant isolation
```

Les messages restent en anglais.

---
# 5. Vue d’ensemble

```text
Epic 2.1 — Catalog foundation
       ↓
Epic 2.2 — Unit of measure
       ↓
Epic 2.3 — Categories
       ↓
Epic 2.4 — Product lifecycle
       ↓
Epic 2.5 — Product packaging & barcode
       ↓
Epic 2.6 — Basic Pricing
       ↓
Epic 2.7 — Authorization, audit & integration
       ↓
Epic 2.8 — Catalog & Pricing API
       ↓
Epic 2.9 — Integration, PostgreSQL & tenant isolation tests
       ↓
Lot 2 Gate
```

---


## Dependencies

Dependencies are capability-based. Follow dependencies explicitly named by the current Epic or support file; do not infer dependencies from Lot numbering.


## Source of truth

`docs/specs/planning/zandu-lot-2-catalog-basic-pricing.md`

Open this complete specification only when the targeted AI files do not answer a required business question.
