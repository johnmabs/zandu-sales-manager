# Zandu Sales Manager — Lot 3 : Inventory & Cash foundations

**Version :** 1.0  
**Statut :** Terminé — Gate Lot 3 validé le 26 août 2026
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


## Dependencies

Dependencies are capability-based. Follow dependencies explicitly named by the current Epic or support file; do not infer dependencies from Lot numbering.


## Source of truth

`docs/specs/planning/zandu-lot-3-inventory-cash-foundations.md`

Open this complete specification only when the targeted AI files do not answer a required business question.
