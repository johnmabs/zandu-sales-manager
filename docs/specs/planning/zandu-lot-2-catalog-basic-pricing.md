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

# 6. Epic 2.1 — Catalog foundation

## Objectif

Créer le bounded context `Catalog` et préparer sa persistence sans introduire de logique `Inventory` ou `Sales`.

---

## Étape 2.1.1 — Créer le module Catalog

Créer :

```text
src/Modules/Catalog/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

### Validation

- autoload valide ;
- Deptrac vert ;
- aucun import direct d’un Domain externe ;
- aucun Symfony/Doctrine dans `Catalog\Domain`.

### Commit proposé

```text
refactor(catalog): add bounded context structure
```

---

## Étape 2.1.2 — Ajouter le schéma PostgreSQL `catalog`

Créer le schéma logique :

```text
catalog
```

Ne créer que les tables nécessaires au fur et à mesure des Epics.

Ne pas créer de tables :

```text
stock
stock_movement
sale
payment
cash_session
```

### Commit proposé

```text
feat(database): add catalog schema
```

---

## Étape 2.1.3 — Étendre les architecture fitness tests

Vérifier explicitement :

```text
Catalog\Domain
✗ Organization\Domain
✗ IdentityAccess\Domain
✗ Inventory\Domain
✗ Sales\Domain
✗ CashManagement\Domain
```

Les échanges externes futurs passent par :

```text
Application\Contract
```

### Commit proposé

```text
test(architecture): protect catalog boundaries
```

---

## Definition of Done — Epic 2.1

- module `Catalog` matérialisé ;
- schema `catalog` disponible ;
- architecture tests verts ;
- aucune logique métier d’un lot ultérieur ;
- aucune dépendance cross-domain interdite.

---

# 7. Epic 2.2 — Unit of measure

## Objectif

Fournir les unités utilisées par les produits et conditionnements.

---

## Étape 2.2.1 — Ajouter `UnitOfMeasure`

Modèle cible :

```text
UnitOfMeasure
├── UnitOfMeasureId
├── OrganizationId?
├── code
├── name
├── dimension
├── precision
├── roundingMode
├── status
└── Version
```

Dimensions :

```text
COUNT
MASS
VOLUME
LENGTH
TIME
OTHER
```

Statuts :

```text
ACTIVE
INACTIVE
```

Le choix exact entre unités système globales et unités tenant-owned doit suivre la décision déjà présente dans la baseline/ADR.

Ne pas inventer un modèle hybride implicitement.

Si aucune décision définitive n’existe pour ce point, commencer avec le minimum nécessaire au MVP et documenter toute décision structurante.

### Invariants

- `code` non vide ;
- `name` non vide ;
- precision valide ;
- rounding mode valide ;
- aucune quantité n’est arrondie silencieusement ;
- une unité inactive ne peut pas être choisie pour un nouveau produit/packaging.

### Domain events

```text
UnitOfMeasureCreated
UnitOfMeasureUpdated
UnitOfMeasureActivated
UnitOfMeasureDeactivated
```

### Commit proposé

```text
feat(catalog): add unit of measure model
```

---

## Étape 2.2.2 — Persistence `UnitOfMeasure`

Ajouter :

- mapping Doctrine ;
- repository ;
- migration ;
- contraintes d’unicité pertinentes ;
- optimistic locking si nécessaire ;
- tests PostgreSQL réels.

### Commit proposé

```text
feat(catalog): persist units of measure
```

---

## Étape 2.2.3 — Use cases UnitOfMeasure

Ajouter uniquement les opérations nécessaires :

```text
CreateUnitOfMeasure
UpdateUnitOfMeasure
ActivateUnitOfMeasure
DeactivateUnitOfMeasure
```

Ne pas créer de PATCH métier générique capable de modifier `status`.

### Commits proposés

```text
feat(catalog): add unit of measure management
```

---

## Definition of Done — Epic 2.2

- modèle UnitOfMeasure disponible ;
- précision testée ;
- persistence PostgreSQL réelle ;
- transitions testées ;
- aucune utilisation de float ;
- audit/outbox intégrables ;
- tests architecture verts.

---

# 8. Epic 2.3 — Categories

## Objectif

Permettre l’organisation simple du catalogue.

---

## Étape 2.3.1 — Ajouter l’aggregate `Category`

Modèle :

```text
Category
├── CategoryId
├── OrganizationId
├── name
├── parentCategoryId?
├── status
├── createdAt
├── createdBy
├── updatedAt?
├── updatedBy?
└── Version
```

Statuts :

```text
ACTIVE
INACTIVE
ARCHIVED
```

### Invariants

- nom obligatoire ;
- tenant immutable ;
- une catégorie parent appartient au même tenant ;
- une catégorie ne peut pas être son propre parent ;
- aucun cycle hiérarchique ;
- `ARCHIVED` n’est plus sélectionnable pour un nouveau produit ;
- une catégorie archivée reste résolvable pour l’historique.

### Domain events

```text
CategoryCreated
CategoryUpdated
CategoryMoved
CategoryActivated
CategoryDeactivated
CategoryArchived
```

### Commit proposé

```text
feat(catalog): add category aggregate
```

---

## Étape 2.3.2 — Persistence Category

Ajouter :

- Doctrine mapping ;
- repository ;
- migration ;
- index tenant ;
- contraintes ;
- RLS si tenant-owned ;
- tests PostgreSQL.

Toutes les lectures utilisent au minimum :

```text
organizationId + categoryId
```

### Commit proposé

```text
feat(catalog): persist categories
```

---

## Étape 2.3.3 — Use cases Category

Ajouter :

```text
CreateCategory
UpdateCategory
MoveCategory
ActivateCategory
DeactivateCategory
ArchiveCategory
```

Le contrôle de cycle hiérarchique doit être robuste.

### Commit proposé

```text
feat(catalog): add category management
```

---

## Definition of Done — Epic 2.3

- aggregate `Category` opérationnel ;
- cycles interdits ;
- lifecycle testé ;
- tenant isolation vérifiée ;
- persistence PostgreSQL réelle ;
- RLS appliqué si nécessaire ;
- événements disponibles ;
- architecture tests verts.

---

# 9. Epic 2.4 — Product lifecycle

## Objectif

Créer le cœur du catalogue : un `Product` autonome pour chaque SKU vendable du MVP.

---

## Étape 2.4.1 — Ajouter l’aggregate `Product`

Modèle :

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
├── createdAt
├── createdBy
├── activatedAt?
├── activatedBy?
├── updatedAt?
├── updatedBy?
└── Version
```

Statuts :

```text
DRAFT
ACTIVE
INACTIVE
ARCHIVED
```

Types :

```text
PHYSICAL
SERVICE
```

### Invariants

```text
ProductCode unique par Organization
```

```text
SERVICE
→ inventoryTracked = false
```

```text
ACTIVE
→ baseUnitId immutable
```

```text
ACTIVE
→ ProductCode immutable
```

```text
ARCHIVED
→ historique conservé
→ nouvelles opérations commerciales interdites
```

Un produit `INACTIVE` peut encore être référencé par un workflow historique ou déjà engagé selon les règles du contexte consommateur.

### Domain events

```text
ProductCreated
ProductUpdated
ProductActivated
ProductDeactivated
ProductReactivated
ProductArchived
```

### Commit proposé

```text
feat(catalog): add product aggregate lifecycle
```

---

## Étape 2.4.2 — Ajouter les value objects Product

Créer seulement les concepts qui apportent un invariant réel :

```text
ProductCode
ProductName
```

Éviter les wrappers sans valeur métier.

`ProductCode` :

- normalisé ;
- unique par organization ;
- non vide ;
- immuable après activation.

### Commit proposé

```text
feat(catalog): add product value objects
```

---

## Étape 2.4.3 — Persistence Product

Ajouter :

- mapping Doctrine ;
- repository ;
- migration ;
- contrainte d’unicité `(organization_id, product_code)` ;
- version optimiste ;
- index de lecture ;
- RLS tenant ;
- tests PostgreSQL.

Repository :

```text
ProductRepository

save(...)
get(OrganizationId, ProductId)
find(OrganizationId, ProductId)
findByCode(OrganizationId, ProductCode)
```

Pas de `GenericRepository`.

### Commit proposé

```text
feat(catalog): persist product aggregate
```

---

## Étape 2.4.4 — Use case `CreateProduct`

Command :

```text
CreateProduct
├── productCode
├── name
├── description?
├── type
├── baseUnitId
├── inventoryTracked
├── categoryId?
└── taxCategoryId?
```

`organizationId` et `actorId` viennent de `ActorContext`.

Le produit est créé :

```text
status = DRAFT
```

### Validation applicative

- organization opérationnelle ;
- permission suffisante ;
- base unit existante et autorisée ;
- category éventuelle du même tenant ;
- `SERVICE` interdit `inventoryTracked = true`.

### Commit proposé

```text
feat(catalog): add create product use case
```

---

## Étape 2.4.5 — Update Product

Command :

```text
UpdateProduct
```

Peut modifier uniquement les propriétés autorisées selon l’état.

Éviter :

```text
PATCH
status = ...
```

sans intention métier explicite.

### Commit proposé

```text
feat(catalog): add product profile update
```

---

## Étape 2.4.6 — Activate Product

Command :

```text
ActivateProduct
```

Avant activation :

```text
Product
├── ProductCode valide
├── name valide
├── baseUnitId valide
├── base packaging présent
└── règles type/inventoryTracked valides
```

Le produit ne doit pas être activé dans un état incohérent.

### Commit proposé

```text
feat(catalog): add product activation
```

---

## Étape 2.4.7 — Deactivate / Reactivate / Archive Product

Créer :

```text
DeactivateProduct
ReactivateProduct
ArchiveProduct
```

`ARCHIVED` est terminal dans le workflow normal du MVP.

Aucun delete métier.

### Commit proposé

```text
feat(catalog): add product availability lifecycle
```

---

## Definition of Done — Epic 2.4

- Product aggregate fonctionnel ;
- 1 SKU vendable = 1 Product ;
- aucun ProductVariant ;
- lifecycle testé ;
- ProductCode unique par tenant ;
- ProductCode immutable après activation ;
- baseUnit immutable après activation ;
- SERVICE incompatible avec inventoryTracked ;
- aucun delete métier ;
- persistence PostgreSQL réelle ;
- RLS actif ;
- tests concurrence utiles ;
- domain events disponibles.

---

# 10. Epic 2.5 — Product packaging & barcode

## Objectif

Permettre de vendre ou acheter un même produit selon différents conditionnements tout en conservant une base quantity cohérente.

---

## Étape 2.5.1 — Ajouter `ProductPackaging`

Modèle :

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
├── status
└── Version
```

Statuts :

```text
ACTIVE
INACTIVE
ARCHIVED
```

### Invariants

- appartient à un Product ;
- `conversionFactor > 0` ;
- décimal exact ;
- quantité convertie compatible avec précision métier ;
- `minimumQuantity > 0` ;
- `quantityIncrement > 0` ;
- un packaging utilisé historiquement n’est jamais réinterprété ;
- un changement de facteur significatif conduit à créer un nouveau packaging plutôt qu’à réécrire l’ancien.

### Commit proposé

```text
feat(catalog): add product packaging
```

---

## Étape 2.5.2 — Base packaging

Chaque produit activable doit posséder exactement un packaging de base cohérent avec :

```text
baseUnitId
```

et :

```text
conversionFactor = 1
```

Le packaging de base doit être explicite.

### Validation

```text
Product.baseUnitId
=
BasePackaging.unitId
```

```text
BasePackaging.conversionFactor
=
1
```

### Commit proposé

```text
feat(catalog): enforce base product packaging
```

---

## Étape 2.5.3 — Conversion de quantité

Introduire un service/value object de conversion pure :

```text
baseQuantity
=
enteredQuantity × conversionFactor
```

Aucun arrondi silencieux.

Exemple :

```text
enteredQuantity = 2
packaging = CARTON_24
factor = 24

baseQuantity = 48
```

Tester :

- entiers ;
- décimaux ;
- précision maximum ;
- facteurs non entiers autorisés selon unité ;
- quantités incompatibles ;
- résidus interdits selon précision.

### Commit proposé

```text
feat(catalog): add exact packaging quantity conversion
```

---

## Étape 2.5.4 — Persistence ProductPackaging

Ajouter :

- mapping ;
- migration ;
- contraintes d’unicité ;
- repository si aggregate indépendant techniquement ;
- index ;
- tests PostgreSQL ;
- RLS indirect ou explicite selon mapping choisi.

Éviter une collection Doctrine non bornée si le modèle de persistence devient problématique.

Le design doit rester compatible avec l’utilisation future en snapshots transactionnels.

### Commit proposé

```text
feat(catalog): persist product packaging
```

---

## Étape 2.5.5 — Ajouter les barcodes

Modèle minimal :

```text
ProductBarcode
├── ProductBarcodeId
├── OrganizationId
├── ProductId
├── ProductPackagingId
├── rawBarcode
├── normalizedBarcode
├── status
└── Version
```

Le choix exact aggregate/entity doit respecter les décisions de la baseline et rester cohérent avec l’unicité globale au tenant.

### Invariants

```text
unique(
    organizationId,
    normalizedBarcode
)
```

Le barcode :

- est une string ;
- conserve les zéros initiaux ;
- appartient à un packaging du même Product ;
- appartient au même tenant ;
- n’est jamais interprété comme un nombre.

### Domain events

```text
ProductBarcodeAdded
ProductBarcodeRemoved
```

Si un barcode historique ne doit pas être physiquement supprimé, utiliser un statut/inactivation selon la décision retenue.

### Commit proposé

```text
feat(catalog): add product barcodes
```

---

## Étape 2.5.6 — Barcode resolver

Exposer un contrat applicatif :

```text
BarcodeResolver
```

Entrée :

```text
OrganizationId
Barcode
```

Sortie :

```text
BarcodeResolution
├── ProductId
└── ProductPackagingId
```

Aucune fuite cross-tenant.

Barcode d’un autre tenant :

```text
NOT_FOUND
```

### Commit proposé

```text
feat(catalog): add barcode resolution
```

---

## Definition of Done — Epic 2.5

- ProductPackaging opérationnel ;
- base packaging explicite ;
- conversionFactor exact ;
- aucune quantité arrondie silencieusement ;
- facteur historique protégé ;
- barcode string ;
- zéros initiaux préservés ;
- barcode unique par tenant ;
- résolution barcode tenant-safe ;
- persistence PostgreSQL testée ;
- architecture tests verts.

---

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

# 12. Fiscalité dans le Lot 2

La baseline indique que :

```text
Product
→ TaxCategoryId?
```

et qu’un futur `SaleLine` conservera un `TaxSnapshot`.

Cependant, la réglementation fiscale du pilote doit être validée avant la vente finale.

Le Lot 2 ne doit donc pas inventer silencieusement des règles fiscales nationales.

Deux cas sont possibles au démarrage de l’implémentation :

### Cas A — règles fiscales déjà décidées dans les ADR / documentation à jour

Implémenter la fondation minimale nécessaire :

```text
TaxCategory
TaxRule
TaxResolver
```

sans moteur promotionnel.

### Cas B — règles encore ouvertes

Conserver :

```text
taxCategoryId?
```

dans Product uniquement si le contrat de référence le prévoit, mais reporter la résolution fiscale définitive jusqu’à décision explicite avant le Lot 4.

Dans tous les cas :

- ne pas coder un taux fiscal en dur dans Product ;
- ne pas stocker durablement seulement un pourcentage dans Product ;
- toute décision locale nouvelle doit être documentée ;
- `Sale` devra plus tard conserver un snapshot fiscal déterministe.

---

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

# 16. Démonstration métier consolidée du Lot 2

La gate doit contenir une démonstration exécutable de bout en bout.

Scénario recommandé :

```text
1. Owner se connecte.

2. Owner crée Category :
   "Boissons"

3. Owner crée Product :
   code = COCA-33
   name = Coca-Cola 33cl
   type = PHYSICAL
   inventoryTracked = true
   status initial = DRAFT

4. Owner crée le packaging de base :
   CAN
   conversionFactor = 1

5. Owner ajoute :
   PACK_6
   conversionFactor = 6

6. Owner ajoute :
   CARTON_24
   conversionFactor = 24

7. Owner ajoute le barcode :
   "00012345678905"

8. Owner active le Product.

9. Owner crée PriceList :
   RETAIL_XAF
   currency = XAF

10. Owner ajoute :
    CAN → 500 XAF
    PACK_6 → 2 750 XAF
    CARTON_24 → 10 000 XAF

11. Résolution barcode :
    "00012345678905"

    → ProductId COCA-33
    → ProductPackagingId attendu

12. Résolution prix :
    CAN
    → 500 XAF

13. Tenant B tente d'accéder au produit Tenant A.
    → NOT_FOUND

14. Un utilisateur sans permission tente de modifier le prix.
    → FORBIDDEN

15. ProductCode est modifié après activation.
    → DOMAIN_RULE_VIOLATION

16. SERVICE avec inventoryTracked=true.
    → DOMAIN_RULE_VIOLATION

17. Toutes les mutations sensibles possèdent :
    audit
    correlationId
    outbox event

18. Aucun Stock n'a été créé.

19. Aucune Sale n'a été créée.

20. Aucun CashMovement n'a été créé.
```

---

# 17. CI minimale du Lot 2

Le pipeline existant reste actif.

Ajouter progressivement :

```text
architecture fitness tests
        ↓
Catalog domain tests
        ↓
Pricing domain tests
        ↓
authorization tests
        ↓
PostgreSQL integration tests
        ↓
tenant isolation / RLS tests
        ↓
transaction / outbox tests
        ↓
API contract tests
```

Validations minimales :

```bash
php bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/deptrac analyse
composer validate --no-check-publish
composer audit
php bin/console lint:container
php bin/console doctrine:schema:validate
```

Adapter les commandes exactes aux scripts déjà présents dans le repository.

---

# 18. Gate de sortie du Lot 2

Le Lot 2 est `DONE` uniquement lorsque :

```text
[ ] module Catalog opérationnel
[ ] schema catalog disponible
[ ] architecture boundaries protégées

[ ] UnitOfMeasure opérationnel
[ ] précision et rounding explicites
[ ] aucun float utilisé

[ ] Category aggregate opérationnel
[ ] hiérarchie Category testée
[ ] cycles Category interdits

[ ] Product aggregate opérationnel
[ ] Product lifecycle testé
[ ] 1 SKU vendable = 1 Product
[ ] aucun ProductVariant MVP
[ ] ProductCode unique par tenant
[ ] ProductCode immuable après activation
[ ] baseUnit immutable après activation
[ ] SERVICE implique inventoryTracked=false
[ ] aucun delete métier de Product

[ ] ProductPackaging opérationnel
[ ] base packaging explicite
[ ] conversionFactor = 1 pour base packaging
[ ] conversions exactes testées
[ ] facteurs historiques protégés

[ ] Barcode opérationnel
[ ] barcode stocké comme string
[ ] zéros initiaux préservés
[ ] barcode unique par tenant
[ ] barcode resolver tenant-safe

[ ] PriceList opérationnel
[ ] ProductPrice opérationnel
[ ] prix cible ProductPackagingId
[ ] devise PriceList cohérente
[ ] Money exact
[ ] ProductPriceNotFound explicite
[ ] résolution de prix déterministe

[ ] permissions Catalog/Pricing ajoutées
[ ] rôles système mis à jour
[ ] AuthorizationService utilisé
[ ] OrganizationOperationalGuard actif

[ ] SecurityAuditEntry produit pour opérations sensibles
[ ] domain events disponibles
[ ] outbox transactionnelle
[ ] métier + audit + outbox atomiques

[ ] API Categories disponible
[ ] API Products disponible
[ ] API ProductPackaging disponible
[ ] API Barcode disponible
[ ] API PriceList disponible
[ ] API ProductPrice disponible
[ ] OpenAPI à jour
[ ] contrat d'erreurs stable

[ ] persistence PostgreSQL réelle
[ ] indexes et contraintes utiles
[ ] optimistic locking appliqué lorsque nécessaire
[ ] RLS activé sur données tenant-owned
[ ] cross-tenant retourne NOT_FOUND
[ ] deux connexions PostgreSQL confirment isolation locale du tenant

[ ] domain unit tests verts
[ ] integration tests verts
[ ] API contract tests verts
[ ] tenant isolation tests verts
[ ] RLS tests verts
[ ] architecture fitness tests verts
[ ] PHPStan vert
[ ] PHP-CS-Fixer vert
[ ] Composer audit vert
[ ] CI verte

[ ] démonstration consolidée du Lot 2 réussie

[ ] aucune logique Inventory prématurée
[ ] aucune logique Cash Management prématurée
[ ] aucune logique Sales prématurée
[ ] aucune logique Purchasing prématurée
[ ] aucune logique Reporting prématurée
[ ] aucune logique Offline prématurée

[ ] IMPLEMENTATION_STATUS.md mis à jour
[ ] documentation d’architecture mise à jour si décision DÉCIDÉ modifiée
[ ] ADR créé ou mis à jour pour toute nouvelle décision structurante
```

---

# 19. Hors périmètre du Lot 2

Ne pas introduire prématurément :

```text
Stock
StockMovement
StockTransfer
StockCount
StockReservation

Inventory Costing
StockValuation
StockValuationMovement

CashRegister
CashSession
CashMovement

Sale
SaleLine
CompleteSale
ReturnSale
RefundSale

Payment
PaymentAttempt
Settlement
PaymentRefund

Customer
CustomerAccount
Receivable

Supplier
PurchaseOrder
GoodsReceipt
PurchaseReturn

Reporting projections

OfflineCommand
SyncState
DeviceGrant
LocalOperationLedger
```

Ne pas introduire non plus sans besoin réel :

```text
ProductVariant
complex promotion engine
custom role engine changes
store-specific pricing engine
provider payments
negative stock configuration
```

Des contrats publics minimaux peuvent être préparés lorsqu’ils sont nécessaires pour le Lot 3 ou le Lot 4, mais aucun bounded context futur ne doit être implémenté par anticipation.

---

# 20. Transition vers le Lot 3

Après validation du Lot 2 :

```text
Organization
      ↓
Catalog
      ↓
Product + Packaging + Price
      ↓
Lot 3
Inventory & Cash foundations
```

Le Lot 3 pourra introduire :

```text
Inventory
├── Stock
├── StockMovement
├── InitializeStock
├── stock availability
└── stock consumption foundations
```

et :

```text
Cash Management
├── CashRegister
├── CashSession
├── OpenCashSession
├── CloseCashSession
└── CashMovement
```

sans encore finaliser la transaction commerciale complète.

La séquence cible :

```text
Lot 1
Identity / Organization / Store
        ↓
Lot 2
Catalog / basic Pricing
        ↓
Lot 3
Inventory / Cash foundations
        ↓
Lot 4
Sales / CompleteSale cash
        ↓
M2
Première vente cash
```

---

# 21. Principe de travail pour l’implémentation

Pour chaque étape :

1. vérifier la baseline DDD concernée ;
2. vérifier les ADR techniques et métier applicables ;
3. identifier le bounded context propriétaire ;
4. définir les invariants avant le code ;
5. implémenter la plus petite tranche cohérente ;
6. ajouter les tests directement liés dans le même commit ;
7. exécuter les tests domaine et architecture ;
8. exécuter les tests PostgreSQL lorsque persistence concernée ;
9. exécuter tenant isolation / RLS lorsque données tenant-owned ;
10. exécuter PHPStan, PHP-CS-Fixer et Deptrac ;
11. proposer un commit atomique ;
12. mettre à jour `IMPLEMENTATION_STATUS.md` avec l’état réel ;
13. ne passer à l’étape suivante qu’après validation ;
14. créer ou mettre à jour un ADR lorsqu’une décision structurante change ;
15. ne jamais transformer silencieusement une question ouverte en décision.

Le repository réel reste la source de vérité sur l’avancement d’implémentation.

---

# 22. Premier point d’entrée d’implémentation

Le Lot 2 commence par :

```text
Epic 2.1 — Catalog foundation

Étape 2.1.1
Créer le bounded context Catalog
```

Premier commit recommandé :

```text
refactor(catalog): add bounded context structure
```

Puis seulement :

```text
UnitOfMeasure
→ Category
→ Product
→ ProductPackaging
→ Barcode
→ PriceList
→ ProductPrice
→ API
→ Gate
```
