# Zandu Sales Manager — Lot 3 : Inventory & Cash foundations

**Version :** 1.0  
**Statut :** Backlog d’implémentation  
**Langue :** Français — identifiants de code en anglais

---

# 1. Objectif du Lot 3

Le Lot 3 construit les deux fondations opérationnelles nécessaires avant la première vente cash :

```text
Inventory
+
Cash Management
```

Il ne réalise pas encore la transaction commerciale `CompleteSale`.

À la sortie du Lot 3, une `Organization` doit pouvoir :

- créer et administrer des `CashRegister` par boutique ;
- ouvrir une `CashSession` sur un register actif ;
- garantir qu’un register ne possède qu’une seule session `OPEN` ;
- enregistrer des mouvements d’espèces manuels autorisés ;
- calculer le montant de caisse attendu ;
- fermer une session en comparant montant attendu et montant compté ;
- conserver tout `CashMovement` comme journal immuable ;
- créer une position `Stock` pour un produit suivi dans un store ;
- initialiser cette position une seule fois ;
- lire la quantité courante ;
- effectuer des ajustements explicites et justifiés ;
- garantir que `quantityOnHand` ne devient jamais négatif ;
- conserver chaque variation dans un `StockMovement` immuable ;
- garantir l’idempotence des opérations susceptibles d’être rejouées ;
- protéger les modifications concurrentes du stock ;
- exposer des Application Contracts utilisables par le futur Lot 4 ;
- fournir les blockers réels à `StoreClosure` ;
- appliquer permissions, scopes, guards, audit, RLS et outbox ;
- exposer les capacités Inventory et Cash par API ;
- démontrer la robustesse sur PostgreSQL réel.

Le Lot 3 prépare :

```text
Lot 4
Sales / CompleteSale cash
```

mais ne doit pas encore introduire :

```text
Sale
SaleLine
Payment
CompleteSale
```

Le Lot 3 est terminé uniquement lorsque son gate de sortie est satisfait.

---

# 2. Position dans la roadmap

Séquence :

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

Le Lot 3 représente donc la dernière fondation technique et métier avant l’intégration commerciale de bout en bout.

---

# 3. Références d’architecture

Le Lot 3 respecte la baseline DDD v1.1 et les ADR existants.

Principes essentiels :

```text
OrganizationId
= frontière stricte de tenant
```

```text
Inventory
= autorité sur les quantités physiques
```

```text
Cash Management
= autorité sur l’argent physique en caisse
```

```text
Catalog
= métadonnées produit
```

```text
Sales
= transaction commerciale
```

Le module `Inventory` peut connaître :

```text
OrganizationId
StoreId
ProductId
```

mais ne dépend jamais de :

```text
Organization\Store
Catalog\Product
Sales\Sale
```

Le module `CashManagement` peut connaître :

```text
OrganizationId
StoreId
ActorId
```

mais ne dépend jamais directement des aggregates d’Identity ou Organization.

Les échanges cross-context utilisent :

```text
Application\Contract
```

ou des events versionnés.

---

# 4. Principes DDD spécifiques au Lot 3

## 4.1 État courant + journal immuable

Pour Inventory :

```text
Stock
= état courant
```

```text
StockMovement
= preuve expliquant chaque variation
```

Pour Cash :

```text
CashSession
= état courant de responsabilité
```

```text
CashMovement
= preuve de chaque entrée/sortie d’espèces
```

Le projet n’utilise pas l’event sourcing.

L’état courant sert aux opérations efficaces.

Les ledgers immuables servent à expliquer l’histoire.

---

## 4.2 Corrections compensatoires

Interdit :

```text
UPDATE StockMovement
DELETE StockMovement
```

Interdit :

```text
UPDATE CashMovement
DELETE CashMovement
```

Une erreur est corrigée par un nouveau mouvement métier compensatoire.

---

## 4.3 Stock négatif interdit

Invariant absolu MVP :

```text
quantityOnHand >= 0
```

Aucune politique `negativeStockAllowed` n’est introduite au Lot 3.

---

## 4.4 Une position Stock = un produit dans un store

Identité logique :

```text
OrganizationId
+
StoreId
+
ProductId
```

Au maximum un `Stock` existe pour ce triplet.

L’aggregate reste volontairement petit afin de limiter la contention.

---

## 4.5 Stock suivi uniquement

Le Lot 2 fournit l’information :

```text
inventoryTracked
```

Inventory ne doit créer ou modifier une position physique que pour un produit éligible au suivi de stock.

Cette validation se fait via un contrat public Catalog approprié.

Inventory ne lit jamais `Catalog\ProductRepository`.

---

## 4.6 CashSession comme responsabilité

Une `CashSession` représente :

```text
CashRegister
+
Cashier
+
période
```

Pour le MVP :

```text
CashRegister
→ max 1 CashSession OPEN
```

Le register est durable ; les sessions successives sont temporelles.

---

## 4.7 Opening balance

`openingBalance` appartient à `CashSession`.

Il ne doit pas être dupliqué comme `CashMovement`.

Il peut apparaître comme ligne virtuelle dans des projections/reportings futurs.

---

## 4.8 Montants exacts

Tous les montants utilisent `Money`.

Interdit :

```text
float
double
```

pour :

```text
openingBalance
CashMovement.amount
expectedClosingBalance
countedClosingBalance
discrepancy
```

---

# 5. Règle de commits

Le Lot 3 conserve la discipline des Lots précédents.

Chaque commit :

- porte une seule intention ;
- laisse le repository valide ;
- inclut les tests directement liés ;
- conserve Deptrac vert ;
- conserve PHPStan vert ;
- conserve PHP-CS-Fixer vert ;
- n’introduit aucun raccourci cross-context ;
- ne mélange pas Inventory et Cash sans nécessité transactionnelle réelle ;
- ne crée pas `Sales` prématurément.

Format :

```text
<type>(<scope>): <description>
```

Exemples :

```text
refactor(inventory): add bounded context structure
feat(inventory): add stock aggregate
feat(inventory): add immutable stock movement ledger
feat(inventory): add initialize stock use case
feat(inventory): add stock adjustment
test(inventory): verify concurrent stock updates

refactor(cash): add cash management foundation
feat(cash): add cash register aggregate
feat(cash): add cash session lifecycle
feat(cash): add immutable cash movements
test(cash): enforce single open session per register
```

---

# 6. Vue d’ensemble

```text
Epic 3.1 — Inventory foundation
       ↓
Epic 3.2 — Stock aggregate & persistence
       ↓
Epic 3.3 — Stock initialization & adjustment
       ↓
Epic 3.4 — Stock concurrency & idempotence
       ↓
Epic 3.5 — CashRegister foundation
       ↓
Epic 3.6 — CashSession lifecycle
       ↓
Epic 3.7 — CashMovement ledger
       ↓
Epic 3.8 — StoreClosure integration
       ↓
Epic 3.9 — Authorization, audit & observability
       ↓
Epic 3.10 — Inventory & Cash API
       ↓
Epic 3.11 — Integration, RLS & transaction tests
       ↓
Lot 3 Gate
```

---

# 7. Epic 3.1 — Inventory foundation

## Objectif

Matérialiser le bounded context `Inventory` selon les frontières prévues depuis le Lot 0.

---

## Étape 3.1.1 — Vérifier / compléter la structure Inventory

Structure :

```text
src/Modules/Inventory/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Le squelette peut déjà exister depuis le Lot 0.

Ne pas recréer inutilement ce qui existe.

### Validation

- aucune dépendance `Inventory\Domain → Catalog\Domain` ;
- aucune dépendance `Inventory\Domain → Organization\Domain` ;
- aucun Doctrine/Symfony dans Domain ;
- architecture tests verts.

### Commit proposé

Si structure déjà présente :

```text
refactor(inventory): prepare inventory bounded context
```

Sinon :

```text
refactor(inventory): add bounded context structure
```

---

## Étape 3.1.2 — Vérifier le schéma PostgreSQL `inventory`

Le schéma existe potentiellement depuis le Lot 0.

Créer uniquement les tables nécessaires :

```text
inventory.stock
inventory.stock_movement
```

Ne pas créer encore :

```text
stock_transfer
stock_count
stock_count_line
stock_reservation
stock_valuation
```

Ces concepts appartiennent à des lots ultérieurs.

### Commit proposé

```text
feat(database): add inventory stock tables
```

---

## Étape 3.1.3 — Contrat Catalog pour Inventory

Inventory doit pouvoir vérifier au minimum :

```text
Product exists
Product belongs to Organization
Product is PHYSICAL
Product inventoryTracked = true
Product base unit / precision usable
```

Créer ou réutiliser un contrat public du Lot 2 :

```text
InventoryProductProvider
```

ou équivalent.

Sortie possible :

```text
InventoryProductDescriptor
├── productId
├── inventoryTracked
├── productType
├── baseUnitId
└── quantityPrecision
```

Ne pas transporter l’aggregate Product.

### Commit proposé

```text
feat(catalog): expose inventory product contract
```

---

## Definition of Done — Epic 3.1

- Inventory prêt pour implémentation métier ;
- schéma logique disponible ;
- contrat Catalog explicite ;
- aucune dépendance Domain externe ;
- architecture tests verts.

---

# 8. Epic 3.2 — Stock aggregate & persistence

## Objectif

Créer l’autorité opérationnelle sur la quantité courante d’un produit suivi dans un store.

---

## Étape 3.2.1 — Ajouter l’aggregate `Stock`

Modèle :

```text
Stock
├── StockId
├── OrganizationId
├── StoreId
├── ProductId
├── StockQuantity quantityOnHand
├── bool initialized
├── initializedAt?
├── initializedBy?
└── Version
```

L’usage d’un flag `initialized` explicite ou d’un état équivalent doit être cohérent avec la persistence retenue.

Identité métier :

```text
OrganizationId
+
StoreId
+
ProductId
```

### Invariants

```text
quantityOnHand >= 0
```

```text
1 Stock max
par Organization + Store + Product
```

```text
organizationId immutable
storeId immutable
productId immutable
```

Une position non initialisée ne peut pas recevoir silencieusement des opérations nécessitant un stock initial selon la politique retenue.

### Operations Domain

L’aggregate expose des intentions :

```text
initialize(...)
adjust(...)
receive(...)
consumeForSale(...)
restockFromSaleReturn(...)
shipTransfer(...)
receiveTransfer(...)
reconcile(...)
```

Mais le Lot 3 n’expose publiquement que les opérations réellement nécessaires :

```text
initialize(...)
adjust(...)
```

Les autres peuvent être préparées uniquement si nécessaires aux contrats du Lot 4.

Pas de :

```text
setQuantity(...)
increasePublic(...)
decreasePublic(...)
```

### Commit proposé

```text
feat(inventory): add stock aggregate
```

---

## Étape 3.2.2 — Value objects de quantité

Utiliser :

```text
StockQuantity
MovementQuantity
```

Invariants :

```text
StockQuantity >= 0
MovementQuantity > 0
```

Réutiliser les primitives Decimal/Quantity du SharedKernel selon les décisions issues du Spike C.

Aucune précision technique nouvelle ne doit être inventée silencieusement.

### Commit proposé

```text
feat(inventory): add stock quantity value objects
```

---

## Étape 3.2.3 — `StockRepository`

Contrat :

```text
StockRepository
```

Opérations minimales :

```text
save(...)
get(OrganizationId, StoreId, ProductId)
find(OrganizationId, StoreId, ProductId)
```

Éventuellement :

```text
getById(OrganizationId, StockId)
```

si usage réel.

Pas de `GenericRepository`.

### Commit proposé

```text
feat(inventory): add stock repository contract
```

---

## Étape 3.2.4 — Persistence Stock

Ajouter :

- Doctrine mapping ;
- PostgreSQL migration ;
- repository Doctrine/DBAL ;
- version optimiste ou mécanisme retenu par ADR ;
- contrainte unique :

```text
UNIQUE (
  organization_id,
  store_id,
  product_id
)
```

- CHECK éventuel :

```text
quantity_on_hand >= 0
```

- indexes ;
- RLS.

### Validation

- round-trip exact ;
- zéro accepté ;
- négatif impossible ;
- tenant isolation ;
- concurrency primitive fonctionnelle.

### Commit proposé

```text
feat(inventory): persist stock aggregate
```

---

## Definition of Done — Epic 3.2

- aggregate Stock opérationnel ;
- quantité exacte ;
- stock négatif interdit ;
- unicité triplet garantie en base ;
- persistence réelle ;
- version/concurrence préparée ;
- RLS actif ;
- tests verts.

---

# 9. Epic 3.3 — StockMovement ledger

## Objectif

Créer le journal immuable expliquant toute variation de stock.

---

## Étape 3.3.1 — Ajouter `StockMovement`

Modèle :

```text
StockMovement
├── StockMovementId
├── OrganizationId
├── StoreId
├── ProductId
├── StockId
├── StockMovementType type
├── MovementQuantity quantity
├── StockQuantity previousQuantity
├── StockQuantity resultingQuantity
├── StockMovementSource source
├── reason?
├── performedBy?
└── occurredAt
```

### Types nécessaires au Lot 3

```text
INITIAL_STOCK
ADJUSTMENT_IN
ADJUSTMENT_OUT
```

Les enums futurs peuvent exister uniquement si déjà décidés de façon centralisée :

```text
SALE
PURCHASE_RECEIPT
SALE_RETURN
TRANSFER_IN
TRANSFER_OUT
STOCK_COUNT_CORRECTION_IN
STOCK_COUNT_CORRECTION_OUT
```

mais le Lot 3 ne doit pas exposer leurs use cases prématurément.

### Direction

La direction est dérivée du type :

```text
INITIAL_STOCK → IN
ADJUSTMENT_IN → IN
ADJUSTMENT_OUT → OUT
```

Pas de champ `direction` libre permettant :

```text
ADJUSTMENT_OUT + IN
```

### Commit proposé

```text
feat(inventory): add immutable stock movement ledger
```

---

## Étape 3.3.2 — `StockMovementSource`

Value object :

```text
StockMovementSource
├── type
└── referenceId
```

Pour le Lot 3 :

```text
INITIALIZATION
MANUAL_ADJUSTMENT
```

Le futur Lot 4 pourra utiliser :

```text
SALE
```

Le futur Purchasing :

```text
PURCHASE_RECEIPT
```

### Commit proposé

```text
feat(inventory): add stock movement source
```

---

## Étape 3.3.3 — Persistence append-only

Ajouter :

- mapping ;
- table ;
- repository d’écriture append-only ;
- lecture query dédiée si nécessaire ;
- aucune méthode update/delete métier ;
- protections DB utiles ;
- RLS ;
- indexes sur :

```text
organization_id
store_id
product_id
stock_id
occurred_at
source
```

### Commit proposé

```text
feat(inventory): persist immutable stock movements
```

---

## Definition of Done — Epic 3.3

- StockMovement disponible ;
- journal append-only ;
- direction dérivée ;
- source structurée ;
- aucune mutation historique ;
- persistence PostgreSQL ;
- RLS ;
- tests d’immutabilité.

---

# 10. Epic 3.4 — Stock initialization & adjustment

## Objectif

Introduire les deux premières opérations réelles d’Inventory.

---

## Étape 3.4.1 — `InitializeStock`

Command :

```text
InitializeStock
├── storeId
├── productId
└── quantity
```

`organizationId` et `actorId` proviennent d’`ActorContext`.

### Préconditions

- Organization opérationnelle ;
- Store opérationnel ;
- actor autorisé sur Store ;
- Product du même tenant ;
- Product `inventoryTracked = true` ;
- Product physique ;
- aucune position déjà initialisée.

### Transaction

```text
BEGIN

create/load Stock

Stock.initialize(quantity)

persist Stock

append StockMovement
type = INITIAL_STOCK
previousQuantity = 0
resultingQuantity = quantity

append audit

append outbox

COMMIT
```

### Invariant

```text
InitializeStock
→ une seule fois
```

Un second appel ne remplace jamais le stock existant.

Résultat :

```text
CONFLICT
```

ou code métier stable équivalent.

### Domain events

```text
StockInitialized
```

### Commit proposé

```text
feat(inventory): add initialize stock use case
```

---

## Étape 3.4.2 — Initialisation à zéro

Une quantité initiale :

```text
0
```

doit être explicitement décidée comme valide ou interdite selon la baseline.

Comme `StockQuantity` accepte zéro, la recommandation cohérente est :

```text
InitializeStock(0)
→ valide
```

car :

```text
stock suivi mais épuisé
```

est différent de :

```text
stock non suivi
```

Cette décision doit être couverte par test.

---

## Étape 3.4.3 — `AdjustStock`

Command :

```text
AdjustStock
├── storeId
├── productId
├── delta
└── reason
```

`delta` :

```text
> 0 → ADJUSTMENT_IN
< 0 → ADJUSTMENT_OUT
= 0 → interdit
```

### Préconditions

- stock initialisé ;
- permission `INVENTORY_ADJUST` ;
- reason obligatoire ;
- Store opérationnel ;
- Product éligible ;
- résultat non négatif.

### Exemple

```text
Stock = 10

AdjustStock(+3)
→ Stock = 13
→ ADJUSTMENT_IN quantity=3
```

```text
Stock = 10

AdjustStock(-4)
→ Stock = 6
→ ADJUSTMENT_OUT quantity=4
```

```text
Stock = 2

AdjustStock(-3)
→ InsufficientStock
→ aucun effet
```

### Domain event

```text
StockAdjusted
```

### Commit proposé

```text
feat(inventory): add stock adjustment use case
```

---

## Étape 3.4.4 — Contrat de lecture de disponibilité

Préparer pour Lot 4 :

```text
StockAvailabilityProvider
```

Entrée :

```text
OrganizationId
StoreId
ProductId
```

Sortie minimale :

```text
StockAvailability
├── productId
├── quantityOnHand
├── stockVersion
└── initialized
```

Aucune réservation dans le Lot 3.

### Commit proposé

```text
feat(inventory): expose stock availability contract
```

---

## Definition of Done — Epic 3.4

- initialisation unique ;
- initialisation exacte ;
- adjustment IN/OUT ;
- reason obligatoire ;
- stock négatif impossible ;
- mouvement créé pour chaque changement ;
- Stock + Movement + Audit + Outbox atomiques ;
- contrat de lecture futur disponible.

---

# 11. Epic 3.5 — Stock concurrency & idempotence

## Objectif

Prouver que deux opérations concurrentes ne peuvent ni perdre une mise à jour ni produire un stock négatif.

---

## Étape 3.5.1 — Appliquer la stratégie issue du Spike E

Avant implémentation finale, vérifier l’ADR produit par le Spike E.

Options historiques étudiées :

```text
optimistic locking
conditional DBAL update
hybrid strategy
```

Ne pas choisir une nouvelle stratégie sans vérifier la décision réelle déjà enregistrée.

### Exemple critique

```text
Stock = 5

Transaction A
consume 4

Transaction B
consume 3
```

Résultat obligatoire :

```text
une seule consommation compatible réussit
```

et jamais :

```text
quantityOnHand = -2
```

### Commit proposé

Selon ADR :

```text
feat(inventory): enforce stock concurrency strategy
```

---

## Étape 3.5.2 — Tests PostgreSQL concurrents

Utiliser de vraies connexions/transactions parallèles.

Tester :

```text
adjust-out concurrent
```

et préparer le scénario de consommation future.

Cas :

```text
Stock = 5

A → -4
B → -3
```

Résultat :

```text
final stock >= 0
```

et somme des mouvements cohérente.

### Commit proposé

```text
test(inventory): verify concurrent stock updates
```

---

## Étape 3.5.3 — Idempotence

Toute opération externe susceptible d’être rejouée doit pouvoir porter :

```text
Idempotency-Key
```

ou une source unique.

Pour `StockMovement`, la logique future suit :

```text
movementType
+
sourceReference
+
productId
```

Le Lot 3 doit au minimum rendre :

```text
InitializeStock
AdjustStock
```

compatibles avec la stratégie globale d’idempotence lorsqu’elles sont exposées via un point d’entrée retryable.

Un retry ne doit jamais produire deux mouvements.

### Commit proposé

```text
feat(inventory): enforce stock operation idempotence
```

---

## Definition of Done — Epic 3.5

- stratégie ADR appliquée ;
- tests de concurrence réels ;
- aucun lost update ;
- aucun stock négatif ;
- idempotence prouvée ;
- ledger cohérent après concurrence.

---

# 12. Epic 3.6 — Cash Management foundation

## Objectif

Préparer le bounded context `CashManagement`.

---

## Étape 3.6.1 — Vérifier / compléter le module

Structure :

```text
src/Modules/CashManagement/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Le squelette existe potentiellement depuis le Lot 0.

### Commit proposé

```text
refactor(cash): prepare cash management bounded context
```

---

## Étape 3.6.2 — Schéma PostgreSQL

Réutiliser :

```text
cash_management
```

Créer :

```text
cash_register
cash_session
cash_movement
```

Ne pas créer encore :

```text
payment
payment_attempt
settlement
customer_receivable
```

### Commit proposé

```text
feat(database): add cash management tables
```

---

## Definition of Done — Epic 3.6

- module prêt ;
- schéma prêt ;
- aucune logique Payment/Sales ;
- architecture tests verts.

---

# 13. Epic 3.7 — CashRegister

## Objectif

Créer la caisse durable d’un store.

---

## Étape 3.7.1 — Aggregate `CashRegister`

Modèle :

```text
CashRegister
├── CashRegisterId
├── OrganizationId
├── StoreId
├── code
├── name
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

ou statuts équivalents déjà décidés.

### Invariants

- appartient exactement à un Store ;
- tenant immutable ;
- store immutable ;
- code unique dans le store ou tenant selon décision ;
- register inactif n’accepte pas de nouvelle session ;
- register avec session ouverte ne peut pas être archivé ;
- historique conservé.

### Domain events

```text
CashRegisterCreated
CashRegisterUpdated
CashRegisterActivated
CashRegisterDeactivated
CashRegisterArchived
```

### Commit proposé

```text
feat(cash): add cash register aggregate
```

---

## Étape 3.7.2 — Persistence CashRegister

Ajouter :

- mapping ;
- migration ;
- repository ;
- contraintes uniques ;
- RLS ;
- indexes ;
- tests PostgreSQL.

Repository tenant/store-safe.

### Commit proposé

```text
feat(cash): persist cash registers
```

---

## Étape 3.7.3 — Use cases CashRegister

Créer :

```text
CreateCashRegister
UpdateCashRegister
ActivateCashRegister
DeactivateCashRegister
ArchiveCashRegister
```

Pas de suppression métier.

### Commit proposé

```text
feat(cash): add cash register management
```

---

## Definition of Done — Epic 3.7

- CashRegister opérationnel ;
- lifecycle testé ;
- tenant/store safe ;
- persistence réelle ;
- RLS ;
- aucune suppression historique.

---

# 14. Epic 3.8 — CashSession lifecycle

## Objectif

Représenter la responsabilité d’un caissier sur un register pendant une période déterminée.

---

## Étape 3.8.1 — Aggregate `CashSession`

Modèle :

```text
CashSession
├── CashSessionId
├── OrganizationId
├── StoreId
├── CashRegisterId
├── cashierId
├── Money openingBalance
├── openedAt
├── status
├── Money countedClosingBalance?
├── Money expectedClosingBalance?
├── Money discrepancy?
├── closedAt?
├── closedBy?
└── Version
```

Statuts minimaux :

```text
OPEN
CLOSED
```

N’ajouter d’état intermédiaire que si nécessaire.

### Invariants

```text
openingBalance >= 0
```

selon la politique MVP retenue.

```text
CashRegister
→ max 1 CashSession OPEN
```

```text
CLOSED
→ terminal
```

Après fermeture :

- pas de nouveau CashMovement ;
- pas de modification des montants métier ;
- seulement métadonnées d’audit explicitement autorisées.

### Domain events

```text
CashSessionOpened
CashSessionClosed
```

### Commit proposé

```text
feat(cash): add cash session aggregate
```

---

## Étape 3.8.2 — Persistence CashSession

Ajouter :

- mapping ;
- migration ;
- repository ;
- RLS ;
- index register/status ;
- version ;
- contrainte garantissant une seule session ouverte.

PostgreSQL doit renforcer l’invariant.

Une approche possible :

```text
partial unique index
WHERE status = 'OPEN'
```

sur :

```text
organization_id
cash_register_id
```

si compatible avec la stratégie de mapping.

### Commit proposé

```text
feat(cash): persist cash sessions
```

---

## Étape 3.8.3 — `OpenCashSession`

Command externe :

```text
OpenCashSession
├── cashRegisterId
└── openingBalance
```

Le serveur dérive :

```text
organizationId
actorId
```

et résout :

```text
storeId
cashierId
```

selon contexte.

### Préconditions

- Organization opérationnelle ;
- Store opérationnel ;
- register actif ;
- register appartient au Store ;
- actor autorisé sur Store ;
- actor autorisé à ouvrir une caisse ;
- aucune session OPEN ;
- currency cohérente avec Store.

### Transaction

```text
BEGIN

check register
check open session invariant

create CashSession

audit

outbox CashSessionOpened

COMMIT
```

### Commit proposé

```text
feat(cash): add open cash session use case
```

---

## Étape 3.8.4 — Calcul du montant attendu

Formule de base :

```text
expectedCash
=
openingBalance
+
sum(IN CashMovement)
-
sum(OUT CashMovement)
```

Seuls les mouvements validés participent.

Au Lot 3, les types effectifs sont principalement manuels.

Le futur Lot 4 ajoutera :

```text
SALE_PAYMENT
```

sans changer le principe.

Ne pas stocker un solde mutable secondaire sans nécessité.

La stratégie préférée doit éviter deux sources de vérité.

### Commit proposé

```text
feat(cash): calculate expected cash balance
```

---

## Étape 3.8.5 — `CloseCashSession`

Command :

```text
CloseCashSession
├── cashSessionId
└── countedClosingBalance
```

### Calcul

```text
expectedClosingBalance
=
openingBalance + movements
```

```text
discrepancy
=
countedClosingBalance
-
expectedClosingBalance
```

### Préconditions

- session OPEN ;
- actor autorisé ;
- même tenant ;
- montant compté exact ;
- aucun mouvement concurrent non pris en compte au moment du commit.

### Résultat

```text
status = CLOSED
```

puis session immuable.

### Domain event

```text
CashSessionClosed
```

### Commit proposé

```text
feat(cash): add close cash session use case
```

---

## Étape 3.8.6 — Gestion des écarts

Le Lot 3 doit au minimum calculer et conserver :

```text
discrepancy
```

Si la baseline/permission catalog prévoit un seuil nécessitant approbation, implémenter uniquement la politique déjà décidée.

Ne pas inventer un workflow d’approbation complet non spécifié.

L’écart reste visible et auditable.

Il n’est jamais corrigé silencieusement par création automatique d’un mouvement.

---

## Definition of Done — Epic 3.8

- CashSession opérationnelle ;
- une seule OPEN par register ;
- ouverture testée ;
- fermeture testée ;
- expected balance calculé ;
- discrepancy calculé ;
- CLOSED immuable ;
- tests concurrence ouverture/fermeture ;
- persistence PostgreSQL ;
- RLS.

---

# 15. Epic 3.9 — CashMovement ledger

## Objectif

Enregistrer toute entrée ou sortie d’espèces réelle pendant une session.

---

## Étape 3.9.1 — Ajouter `CashMovement`

Modèle :

```text
CashMovement
├── CashMovementId
├── OrganizationId
├── StoreId
├── CashSessionId
├── CashMovementType type
├── direction
├── Money amount
├── sourceReference?
├── reason?
├── performedBy
├── approvedBy?
└── occurredAt
```

Les types du Lot 3 doivent rester limités aux mouvements réellement utilisables.

Proposition minimale :

```text
CASH_IN
CASH_OUT
CASH_WITHDRAWAL
```

Les futurs types :

```text
SALE_PAYMENT
DEBT_PAYMENT
REFUND
```

sont consommés par les lots correspondants.

### Invariants

```text
amount > 0
```

```text
session.status = OPEN
```

```text
currency = store/session currency
```

```text
direction
= dérivée du type
```

### Commit proposé

```text
feat(cash): add immutable cash movement ledger
```

---

## Étape 3.9.2 — `RecordCashIn`

Command :

```text
RecordCashIn
├── cashSessionId
├── amount
└── reason
```

Préconditions :

- session OPEN ;
- permission ;
- store scope ;
- reason obligatoire selon politique.

Type :

```text
CASH_IN
```

### Commit proposé

```text
feat(cash): add manual cash in
```

---

## Étape 3.9.3 — `RecordCashOut`

Command :

```text
RecordCashOut
├── cashSessionId
├── amount
└── reason
```

Type :

```text
CASH_OUT
```

Permission explicite.

Si seuil d’approbation déjà décidé :

```text
approvedBy
```

doit être renseigné selon policy.

### Commit proposé

```text
feat(cash): add manual cash out
```

---

## Étape 3.9.4 — `CashWithdrawal`

Si distingué dans la baseline :

```text
CashWithdrawal
```

représente une sortie structurée distincte d’un simple `CASH_OUT`.

Ne pas fusionner les types si la distinction métier est utile.

### Commit proposé

```text
feat(cash): add cash withdrawal
```

---

## Étape 3.9.5 — Persistence append-only

Ajouter :

- mapping ;
- migration ;
- repository append-only ;
- RLS ;
- indexes ;
- aucune update/delete métier.

### Commit proposé

```text
feat(cash): persist immutable cash movements
```

---

## Étape 3.9.6 — Idempotence CashMovement

Toute intégration future doit utiliser une référence idempotente.

Préparer :

```text
sourceReference
```

pour :

```text
SALE_PAYMENT / SaleId
REFUND / RefundId
DEBT_PAYMENT / CustomerPaymentId
```

Pour les mouvements manuels API, utiliser la stratégie globale d’idempotency key lorsque retry possible.

### Commit proposé

```text
feat(cash): enforce cash movement idempotence
```

---

## Definition of Done — Epic 3.9

- ledger CashMovement opérationnel ;
- append-only ;
- types manuels ;
- direction cohérente ;
- session OPEN obligatoire ;
- expected balance impacté correctement ;
- idempotence préparée ;
- audit ;
- persistence PostgreSQL ;
- RLS.

---

# 16. Epic 3.10 — StoreClosure integration

## Objectif

Brancher les vrais blockers Inventory et Cash au `StoreClosure` préparé au Lot 1.

---

## Étape 3.10.1 — Inventory blocker provider

Implémenter le contrat public attendu par Organization :

```text
StoreClosureBlockerProvider
```

ou son équivalent existant.

Inventory retourne un blocker si :

```text
exists Stock
where organizationId = ?
and storeId = ?
and quantityOnHand > 0
```

Blocker possible :

```text
STOCK_REMAINING
```

avec uniquement les informations non sensibles nécessaires.

### Commit proposé

```text
feat(inventory): provide store closure stock blocker
```

---

## Étape 3.10.2 — Cash blocker provider

Cash Management retourne un blocker si :

```text
CashSession OPEN
```

pour le store.

Blocker :

```text
OPEN_CASH_SESSION
```

### Commit proposé

```text
feat(cash): provide store closure cash blocker
```

---

## Étape 3.10.3 — Store suspendu

Une suspension de Store doit :

```text
refuser
OpenCashSession
InitializeStock
AdjustStock
```

sauf opérations de terminaison/remédiation explicitement autorisées.

Doit rester autorisé selon baseline :

```text
CloseCashSession
```

Un stock existant reste consultable.

### Commit proposé

```text
feat(operations): enforce inventory and cash store guards
```

---

## Definition of Done — Epic 3.10

- Stock restant bloque fermeture ;
- session cash ouverte bloque fermeture ;
- CloseCashSession reste possible pendant suspension ;
- aucun repository externe importé par Organization ;
- contrats publics utilisés ;
- tests StoreClosure consolidés.

---

# 17. Epic 3.11 — Authorization, audit & permissions

## Objectif

Étendre le modèle d’accès du Lot 1 aux capacités Inventory/Cash réellement disponibles.

---

## Étape 3.11.1 — Permissions Inventory

Ajouter uniquement :

```text
INVENTORY_READ
INVENTORY_INITIALIZE
INVENTORY_ADJUST
STOCK_MOVEMENT_READ
```

Ne pas encore ajouter si inutilisées :

```text
STOCK_TRANSFER_*
STOCK_COUNT_*
PURCHASE_*
```

### Commit proposé

```text
feat(access): add inventory permissions
```

---

## Étape 3.11.2 — Permissions Cash

Ajouter :

```text
CASH_REGISTER_CREATE
CASH_REGISTER_READ
CASH_REGISTER_UPDATE
CASH_REGISTER_MANAGE

CASH_SESSION_OPEN
CASH_SESSION_READ
CASH_SESSION_CLOSE

CASH_MOVEMENT_READ
CASH_IN_RECORD
CASH_OUT_RECORD
CASH_WITHDRAWAL_RECORD
```

Adapter les noms au catalogue existant sans multiplier les synonymes.

### Commit proposé

```text
feat(access): add cash management permissions
```

---

## Étape 3.11.3 — Rôles système

Exemple cible :

```text
ORGANIZATION_OWNER
→ toutes permissions Lot 3

STORE_MANAGER
→ Inventory read/adjust selon politique
→ Cash supervision selon politique

CASHIER
→ CashSession OPEN/CLOSE
→ Cash movement limité
→ Inventory read éventuellement

ACCOUNTANT
→ lecture Cash/Stock selon politique
```

Les Domain modules ne connaissent jamais ces noms de rôles.

### Commit proposé

```text
feat(access): grant inventory and cash permissions
```

---

## Étape 3.11.4 — Audit sensible

Auditer au minimum :

```text
STOCK_INITIALIZED
STOCK_ADJUSTED
CASH_REGISTER_ARCHIVED
CASH_SESSION_OPENED
CASH_SESSION_CLOSED
CASH_IN_RECORDED
CASH_OUT_RECORDED
CASH_WITHDRAWAL_RECORDED
```

Les business ledgers restent distincts du security audit.

`StockMovement` ne remplace pas `SecurityAuditEntry`.

`CashMovement` ne remplace pas `SecurityAuditEntry`.

### Commit proposé

```text
feat(audit): record inventory and cash operations
```

---

## Definition of Done — Epic 3.11

- permission catalog étendu ;
- scopes Store appliqués ;
- rôles mis à jour ;
- AuthorizationService utilisé ;
- audits sensibles ;
- aucun rôle codé dans Domain.

---

# 18. Epic 3.12 — Application Contracts pour Lot 4

## Objectif

Préparer les interfaces synchrones dont `Sales` aura besoin sans implémenter Sales.

---

## Étape 3.12.1 — Inventory consumption contract

Préparer :

```text
InventoryStockConsumer
```

Intention future :

```text
consumeStockForSale(...)
```

Entrée possible :

```text
ConsumeStockForSale
├── organizationId
├── storeId
├── saleId
└── items[]
    ├── productId
    └── baseQuantity
```

Le contrat doit être :

- versionnable ;
- idempotent ;
- transaction-compatible ;
- sans aggregate Catalog/Sales.

Au Lot 3, il peut être contract-testé sans être exposé par API commerciale.

### Commit proposé

```text
feat(inventory): expose sale stock consumption contract
```

---

## Étape 3.12.2 — Cash sale movement contract

Préparer :

```text
CashMovementRecorder
```

Intention future :

```text
recordSalePayment(...)
```

Entrée :

```text
organizationId
storeId
cashSessionId
saleId
amount
actorId
```

Résultat idempotent.

Ne pas créer `Payment`.

### Commit proposé

```text
feat(cash): expose sale cash movement contract
```

---

## Étape 3.12.3 — Contract tests

Prouver :

- aucune dépendance Domain croisée ;
- version contractuelle ;
- idempotence ;
- erreurs métier stables ;
- transaction locale compatible avec futur coordinateur Sales.

### Commit proposé

```text
test(contracts): verify inventory and cash application contracts
```

---

## Definition of Done — Epic 3.12

- contrats Lot 4 disponibles ;
- versionnés ;
- contract tests ;
- aucune classe Sales introduite ;
- aucune transaction distribuée supposée.

---

# 19. Epic 3.13 — Inventory & Cash API

## Objectif

Exposer les capacités administratives et opérationnelles du Lot 3.

---

## Étape 3.13.1 — API Stock

Endpoints recommandés :

```text
GET  /api/stores/{storeId}/stocks
GET  /api/stores/{storeId}/stocks/{productId}

POST /api/stores/{storeId}/stocks/{productId}/initialize
POST /api/stores/{storeId}/stocks/{productId}/adjust
```

Initialisation payload :

```json
{
  "quantity": "100"
}
```

Ajustement :

```json
{
  "delta": "-3",
  "reason": "Damaged items found"
}
```

Les quantités sont des strings décimales dans JSON selon convention Decimal existante.

### Commit proposé

```text
feat(api): expose stock operations
```

---

## Étape 3.13.2 — API StockMovement

Lecture :

```text
GET /api/stores/{storeId}/stock-movements
GET /api/stores/{storeId}/stocks/{productId}/movements
```

Filtres utiles :

```text
productId
type
occurredFrom
occurredTo
```

Aucune API :

```text
PATCH StockMovement
DELETE StockMovement
```

### Commit proposé

```text
feat(api): expose stock movement history
```

---

## Étape 3.13.3 — API CashRegister

Endpoints :

```text
GET    /api/stores/{storeId}/cash-registers
POST   /api/stores/{storeId}/cash-registers
GET    /api/stores/{storeId}/cash-registers/{id}
PATCH  /api/stores/{storeId}/cash-registers/{id}

POST   /api/stores/{storeId}/cash-registers/{id}/activate
POST   /api/stores/{storeId}/cash-registers/{id}/deactivate
POST   /api/stores/{storeId}/cash-registers/{id}/archive
```

### Commit proposé

```text
feat(api): expose cash register management
```

---

## Étape 3.13.4 — API CashSession

Endpoints :

```text
POST /api/stores/{storeId}/cash-registers/{cashRegisterId}/sessions/open

GET  /api/cash-sessions/{id}
GET  /api/stores/{storeId}/cash-sessions

POST /api/cash-sessions/{id}/close
```

Open :

```json
{
  "openingBalance": {
    "amount": "50000",
    "currency": "XAF"
  }
}
```

Close :

```json
{
  "countedClosingBalance": {
    "amount": "73500",
    "currency": "XAF"
  }
}
```

### Commit proposé

```text
feat(api): expose cash session lifecycle
```

---

## Étape 3.13.5 — API CashMovement

Endpoints intentionnels :

```text
GET /api/cash-sessions/{id}/movements

POST /api/cash-sessions/{id}/cash-in
POST /api/cash-sessions/{id}/cash-out
POST /api/cash-sessions/{id}/withdrawals
```

Pas de :

```text
POST /api/cash-movements
```

générique permettant de choisir arbitrairement le type.

Pas de PATCH/DELETE.

### Commit proposé

```text
feat(api): expose cash movement operations
```

---

## Étape 3.13.6 — Contrat d’erreurs

Réutiliser :

```text
400 VALIDATION_ERROR
401 UNAUTHENTICATED
403 FORBIDDEN
404 NOT_FOUND
409 CONFLICT
422 DOMAIN_RULE_VIOLATION
```

Codes métier possibles :

```text
STOCK_NOT_INITIALIZED
STOCK_ALREADY_INITIALIZED
INSUFFICIENT_STOCK
PRODUCT_NOT_INVENTORY_TRACKED
INVALID_STOCK_ADJUSTMENT

CASH_REGISTER_INACTIVE
CASH_SESSION_ALREADY_OPEN
CASH_SESSION_NOT_OPEN
CASH_SESSION_ALREADY_CLOSED
CASH_CURRENCY_MISMATCH
```

### Commit proposé

```text
docs(api): document inventory and cash errors
```

---

## Étape 3.13.7 — OpenAPI

Documenter :

- Decimal/Quantity ;
- Money ;
- permissions ;
- tenant behavior ;
- scopes Store ;
- lifecycle ;
- immutabilité ;
- idempotency ;
- erreurs ;
- exemples.

### Commit proposé

```text
docs(api): document inventory and cash endpoints
```

---

## Definition of Done — Epic 3.13

- API Stock ;
- API StockMovement lecture ;
- API CashRegister ;
- API CashSession ;
- API CashMovement ;
- endpoints intentionnels ;
- pas de CRUD générique ledger ;
- OpenAPI complet ;
- DTO séparés Domain/Doctrine.

---

# 20. Epic 3.14 — Integration, PostgreSQL & RLS tests

## Objectif

Prouver le Lot 3 sur l’infrastructure réelle.

---

## Étape 3.14.1 — Tests Domain Stock

Couvrir :

```text
initialize once
initialize zero
reject second initialization
adjust positive
adjust negative
reject zero adjustment
reject negative resulting stock
preserve identifiers
```

### Commit proposé

```text
test(inventory): cover stock invariants
```

---

## Étape 3.14.2 — Tests ledger StockMovement

Prouver :

```text
Stock 10
Adjust -3

movement:
previous = 10
quantity = 3
resulting = 7
type = ADJUSTMENT_OUT
```

Et impossibilité d’update/delete métier.

### Commit proposé

```text
test(inventory): verify stock movement ledger
```

---

## Étape 3.14.3 — Atomicité Stock

Injecter échec :

```text
after Stock update
before Movement insert
```

puis :

```text
after Movement
before Audit
```

puis :

```text
after Audit
before Outbox
```

puis avant commit.

Résultat :

```text
rollback total
```

### Commit proposé

```text
test(inventory): verify stock transaction atomicity
```

---

## Étape 3.14.4 — Concurrence Stock

PostgreSQL réel, deux transactions simultanées.

Prouver :

```text
quantityOnHand >= 0
```

et :

```text
ledger
=
état final explicable
```

### Commit proposé

```text
test(inventory): verify stock concurrency
```

---

## Étape 3.14.5 — Tests Domain CashSession

Couvrir :

```text
open
reject second open session
close
calculate expected balance
calculate discrepancy
reject movement after close
reject second close
```

### Commit proposé

```text
test(cash): cover cash session invariants
```

---

## Étape 3.14.6 — Concurrence OpenCashSession

Deux requêtes simultanées sur même CashRegister.

Résultat :

```text
exactly one OPEN session
```

Le contrôle doit être renforcé par PostgreSQL, pas uniquement l’application.

### Commit proposé

```text
test(cash): verify concurrent session opening
```

---

## Étape 3.14.7 — Tests CashMovement

Couvrir :

```text
cash in
cash out
withdrawal
invalid amount
closed session
wrong tenant
wrong store
currency mismatch
expected balance
```

### Commit proposé

```text
test(cash): verify cash movements
```

---

## Étape 3.14.8 — Atomicité Cash

Injecter erreurs entre :

```text
CashMovement insert
Audit
Outbox
CashSession close
```

Aucun effet partiel.

### Commit proposé

```text
test(cash): verify cash transaction atomicity
```

---

## Étape 3.14.9 — Tenant isolation

Créer :

```text
Tenant A
Tenant B
```

Tester :

```text
Tenant B reads Stock A
→ NOT_FOUND
```

```text
Tenant B adjusts Stock A
→ NOT_FOUND
```

```text
Tenant B reads CashSession A
→ NOT_FOUND
```

```text
Tenant B records movement in Session A
→ NOT_FOUND
```

Aucune fuite d’existence.

### Commit proposé

```text
test(tenant): enforce inventory and cash isolation
```

---

## Étape 3.14.10 — RLS

Tester toutes les tables tenant-owned :

```text
inventory.stock
inventory.stock_movement

cash_management.cash_register
cash_management.cash_session
cash_management.cash_movement
```

Vérifier :

- RLS active ;
- FORCE RLS selon convention ;
- transaction-local tenant context ;
- deux connexions ;
- rollback ;
- lecture et écriture cross-tenant impossibles.

### Commit proposé

```text
test(tenant): verify inventory and cash PostgreSQL RLS
```

---

## Étape 3.14.11 — Scope Store

Utilisateur limité :

```text
SELECTED_STORES
→ Store A
```

Peut :

```text
read/adjust Store A selon permissions
```

Ne peut pas :

```text
read/adjust Store B
open register Store B
```

### Commit proposé

```text
test(access): verify inventory and cash store scopes
```

---

## Étape 3.14.12 — StoreClosure

Tester :

```text
Stock > 0
→ blocker
```

```text
CashSession OPEN
→ blocker
```

```text
Stock = 0
+
no open session
→ ces blockers absents
```

### Commit proposé

```text
test(store): verify inventory and cash closure blockers
```

---

## Étape 3.14.13 — Contract tests Lot 4

Tester les contrats :

```text
InventoryStockConsumer
CashMovementRecorder
```

sans Domain Sales.

### Commit proposé

```text
test(contracts): verify Lot 4 inventory and cash contracts
```

---

## Definition of Done — Epic 3.14

- domain tests ;
- persistence tests ;
- real PostgreSQL ;
- concurrency tests ;
- idempotence ;
- rollback ;
- RLS ;
- tenant isolation ;
- Store scopes ;
- StoreClosure ;
- contract tests ;
- architecture tests ;
- CI verte.

---

# 21. Observabilité Lot 3

Ajouter des métriques seulement si utiles opérationnellement.

Exemples :

```text
inventory_adjustment_count
inventory_concurrency_conflict_count
inventory_insufficient_stock_count

cash_open_session_count
cash_session_close_count
cash_session_discrepancy_count
cash_movement_count
```

Ne pas exposer :

- amounts sensibles dans labels ;
- product IDs haute cardinalité sans justification ;
- tenant IDs bruts en métriques publiques.

Les logs structurés portent :

```text
correlationId
operation
result
duration
```

sans transformer les logs en ledger métier.

---

# 22. Démonstration consolidée du Lot 3

Scénario Inventory :

```text
1. Owner login.

2. Store A existe.

3. Product A du Lot 2 :
   PHYSICAL
   inventoryTracked = true.

4. InitializeStock:
   quantity = 100

5. Résultat:
   Stock.quantityOnHand = 100

6. StockMovement:
   INITIAL_STOCK
   previous = 0
   resulting = 100

7. Second InitializeStock(50)
   → CONFLICT

8. AdjustStock(-5, "Damaged items")
   → quantityOnHand = 95
   → ADJUSTMENT_OUT = 5

9. AdjustStock(+2, "Correction")
   → quantityOnHand = 97
   → ADJUSTMENT_IN = 2

10. AdjustStock(-100)
    → INSUFFICIENT_STOCK
    → no partial effect

11. Tenant B tries to read Stock.
    → NOT_FOUND
```

Scénario Cash :

```text
12. Owner creates CashRegister:
    REGISTER-01
    Store A

13. Cashier authorized on Store A
    opens session:
    openingBalance = 50 000 XAF

14. Second concurrent OpenCashSession
    on REGISTER-01
    → rejected

15. RecordCashIn:
    +10 000 XAF
    reason = "Additional float"

16. RecordCashOut:
    -5 000 XAF
    reason = "Petty cash"

17. expected balance:
    55 000 XAF

18. CloseCashSession:
    countedClosingBalance = 54 500 XAF

19. discrepancy:
    -500 XAF

20. session = CLOSED

21. RecordCashIn after close
    → rejected
```

Scénario StoreClosure :

```text
22. StoreClosure requested.

23. Stock.quantityOnHand = 97
    → STOCK_REMAINING blocker

24. Si CashSession OPEN
    → OPEN_CASH_SESSION blocker
```

Scénario sécurité :

```text
25. User scoped only to Store B
    tries Inventory/Cash on Store A
    → NOT_FOUND/FORBIDDEN selon contrat public

26. Every sensitive mutation has:
    SecurityAuditEntry
    correlationId
    outbox message

27. No Sale exists.

28. No Payment exists.

29. No CompleteSale exists.
```

---

# 23. CI minimale du Lot 3

Pipeline :

```text
architecture fitness tests
        ↓
Inventory domain tests
        ↓
Cash domain tests
        ↓
authorization tests
        ↓
contract tests
        ↓
PostgreSQL integration tests
        ↓
stock concurrency tests
        ↓
cash concurrency tests
        ↓
tenant isolation / RLS
        ↓
transaction / rollback tests
        ↓
API contract tests
```

Validations :

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

Adapter aux commandes réelles du repository.

---

# 24. Gate de sortie du Lot 3

Le Lot 3 est `DONE` uniquement lorsque :

```text
[ ] Inventory module opérationnel
[ ] CashManagement module opérationnel
[ ] architecture boundaries protégées

[ ] Catalog → Inventory contract disponible
[ ] aucun import Catalog Domain dans Inventory

[ ] Stock aggregate opérationnel
[ ] unicité Organization + Store + Product
[ ] StockQuantity >= 0
[ ] MovementQuantity > 0
[ ] aucun float
[ ] persistence Stock PostgreSQL

[ ] StockMovement append-only
[ ] direction dérivée du type
[ ] source structurée
[ ] aucun update/delete métier

[ ] InitializeStock opérationnel
[ ] InitializeStock possible une seule fois
[ ] initialisation à zéro couverte explicitement
[ ] AdjustStock opérationnel
[ ] delta zéro interdit
[ ] reason obligatoire
[ ] résultat négatif interdit

[ ] Stock + StockMovement + Audit + Outbox atomiques
[ ] idempotence Stock prouvée
[ ] stratégie concurrence du Spike E appliquée
[ ] tests PostgreSQL concurrents verts

[ ] CashRegister aggregate opérationnel
[ ] lifecycle CashRegister testé
[ ] register tenant/store scoped
[ ] persistence CashRegister PostgreSQL

[ ] CashSession aggregate opérationnel
[ ] max une session OPEN par CashRegister
[ ] contrainte base renforçant unicité OPEN
[ ] OpenCashSession opérationnel
[ ] CloseCashSession opérationnel
[ ] expectedClosingBalance calculé
[ ] discrepancy calculé
[ ] CLOSED immuable

[ ] CashMovement append-only
[ ] CASH_IN opérationnel
[ ] CASH_OUT opérationnel
[ ] CASH_WITHDRAWAL opérationnel si retenu
[ ] amount > 0
[ ] movement interdit sur session CLOSED
[ ] CashMovement idempotence préparée
[ ] openingBalance non dupliqué en movement

[ ] Cash mutation + Audit + Outbox atomiques

[ ] Inventory StoreClosure blocker opérationnel
[ ] Stock > 0 bloque fermeture Store
[ ] Cash StoreClosure blocker opérationnel
[ ] CashSession OPEN bloque fermeture Store
[ ] CloseCashSession autorisé pendant suspension selon politique

[ ] permissions Inventory disponibles
[ ] permissions Cash disponibles
[ ] rôles système mis à jour
[ ] AuthorizationService utilisé
[ ] Store scopes appliqués
[ ] OrganizationOperationalGuard actif
[ ] StoreOperationalGuard actif

[ ] API Stock disponible
[ ] API StockMovement read-only disponible
[ ] API CashRegister disponible
[ ] API CashSession disponible
[ ] API CashMovement intentionnelle disponible
[ ] aucun PATCH/DELETE ledger
[ ] OpenAPI à jour
[ ] contrat d’erreurs stable

[ ] application contracts pour Lot 4 disponibles
[ ] InventoryStockConsumer contract-testé
[ ] CashMovementRecorder contract-testé
[ ] aucun Sales Domain introduit

[ ] RLS Inventory actif
[ ] RLS Cash actif
[ ] cross-tenant NOT_FOUND
[ ] deux connexions PostgreSQL prouvent isolation
[ ] optimistic/version/conditional strategy testée

[ ] domain unit tests verts
[ ] integration tests verts
[ ] concurrency tests verts
[ ] rollback tests verts
[ ] tenant isolation tests verts
[ ] RLS tests verts
[ ] API contract tests verts
[ ] architecture fitness tests verts
[ ] PHPStan vert
[ ] PHP-CS-Fixer vert
[ ] Composer audit vert
[ ] CI verte

[ ] démonstration consolidée Lot 3 réussie

[ ] aucun Sale
[ ] aucun SaleLine
[ ] aucun Payment
[ ] aucun CompleteSale

[ ] aucun StockTransfer
[ ] aucun StockCount
[ ] aucun StockReservation
[ ] aucun Inventory Costing
[ ] aucun Purchasing workflow

[ ] IMPLEMENTATION_STATUS.md mis à jour
[ ] ADR mis à jour si décision DÉCIDÉ modifiée
[ ] nouvelles décisions structurantes documentées
```

---

# 25. Hors périmètre du Lot 3

Ne pas introduire :

```text
Sale
SaleLine
SalePricingCalculator
CompleteSale
ReturnSale
RefundSale

Payment
PaymentAttempt
PaymentSettlement
PaymentRefund

Customer
CustomerAccount
CustomerReceivable

StockTransfer
StockCount
StockCountLine
StockReservation

StockValuation
StockValuationMovement
InventoryCosting

Supplier
PurchaseOrder
GoodsReceipt
PurchaseReturn

Reporting projections

OfflineCommand
DeviceGrant
SyncState
LocalCashSession
LocalStockPosition
```

Des enums ou contracts préparatoires peuvent contenir des références futures déjà décidées, mais aucun workflow futur ne doit être implémenté par anticipation.

---

# 26. Transition vers le Lot 4

Après le Gate Lot 3 :

```text
Catalog
├── Product
├── ProductPackaging
└── ProductPrice

Inventory
├── Stock
├── StockMovement
└── consume contract

Cash Management
├── CashRegister
├── CashSession
├── CashMovement
└── sale cash contract
```

Le Lot 4 pourra enfin construire :

```text
Sales
├── Sale
├── SaleLine
├── Payment cash minimal
└── CompleteSale
```

Workflow cible :

```text
Create Sale
    ↓
Add lines
    ↓
Resolve product/packaging/price
    ↓
CompleteSale
    │
    ├── validate sale
    ├── consume Inventory
    ├── register cash payment
    ├── create CashMovement
    ├── complete Sale
    └── Outbox
            ↓
          COMMIT
```

Invariant Lot 4 :

```text
si Inventory échoue
→ Sale non completed

si Cash échoue
→ Sale non completed

si Outbox transactionnelle échoue avant commit
→ aucun effet partiel
```

Le Lot 4 atteindra :

```text
M2
Première vente cash
```

---

# 27. Principe de travail pour l’implémentation

Pour chaque étape :

1. vérifier baseline et ADR ;
2. vérifier l’état réel du repository ;
3. identifier le bounded context propriétaire ;
4. formaliser les invariants ;
5. écrire la plus petite tranche cohérente ;
6. ajouter les tests dans le même commit ;
7. utiliser PostgreSQL réel pour persistence/concurrence/RLS ;
8. tester échecs et rollback ;
9. exécuter architecture tests ;
10. exécuter PHPStan, PHP-CS-Fixer et Composer audit ;
11. proposer un commit atomique ;
12. mettre à jour `IMPLEMENTATION_STATUS.md` ;
13. ne passer à l’étape suivante qu’après validation ;
14. mettre à jour l’ADR si une décision DÉCIDÉ change ;
15. ne jamais transformer silencieusement une question ouverte en décision.

Le repository réel reste la source de vérité.

---

# 28. Premier point d’entrée d’implémentation

Commencer par :

```text
Epic 3.1 — Inventory foundation
```

puis :

```text
Stock
→ StockMovement
→ InitializeStock
→ AdjustStock
→ concurrency/idempotence
```

avant :

```text
CashRegister
→ CashSession
→ CashMovement
```

Ordre recommandé des premiers commits :

```text
refactor(inventory): prepare inventory bounded context
feat(database): add inventory stock tables
feat(catalog): expose inventory product contract
feat(inventory): add stock aggregate
feat(inventory): add immutable stock movement ledger
feat(inventory): add initialize stock use case
feat(inventory): add stock adjustment use case
test(inventory): verify stock concurrency
```

Puis seulement démarrer la verticale Cash.
