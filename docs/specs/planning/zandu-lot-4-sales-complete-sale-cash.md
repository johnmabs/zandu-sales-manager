# Zandu Sales Manager — Lot 4 : Sales & CompleteSale cash

**Version :** 1.0  
**Statut :** Terminé — Gate M2 validé le 27 août 2026
**Langue :** Français — identifiants de code en anglais

---

# 1. Objectif du Lot 4

Le Lot 4 construit la première transaction commerciale complète de Zandu Sales Manager.

À la sortie du lot, un caissier autorisé doit pouvoir créer une vente, ajouter des lignes, résoudre produit/packaging/prix, finaliser une vente comptant en espèces, créer un `Payment` cash confirmé, consommer le stock suivi, créer le `CashMovement` correspondant et obtenir un reçu consultable.

Le workflow nominal est :

```text
Sale
  ↓
Pricing snapshot
  ↓
Payment CASH
  ↓
Inventory consumption
  ↓
CashMovement SALE_PAYMENT
  ↓
Sale COMPLETED
  ↓
Outbox
```

Le Lot 4 atteint :

```text
M2 — Première vente cash
```

Il ne doit pas encore implémenter :

```text
ReturnSale
RefundSale
Inventory Costing
Customer Credit
Payment providers
StockReservation
Purchasing
StockTransfer
StockCount
Reporting avancé
Offline
```

Le Lot 4 est terminé uniquement lorsque son gate de sortie est satisfait.

## État réel au 27 août 2026

Le parcours Sales est opérationnel de bout en bout : cycle `DRAFT`, édition des
lignes, snapshots Catalog/Pricing/fiscaux, annulation, finalisation cash,
paiement confirmé, consommation Inventory, mouvement Cash, reçu et événements
outbox. `CompleteSale` verrouille la vente, revalide Pricing, applique les
permissions et guards Store, calcule `BusinessDate`, supporte le montant remis
et retourne la monnaie à rendre.

La politique fiscale du pilote est explicitement `NO_TAX` dans l'ADR-0020. Les
tests PostgreSQL couvrent le workflow HTTP M2, le rejeu idempotent sans effet
dupliqué, les verrous concurrents, la persistance des snapshots et les RLS
Sales/Payment. Le Gate M2 est validé par la CI complète documentée dans
`IMPLEMENTATION_STATUS.md`.

---

# 2. Position dans la roadmap

```text
Lot 0 — Architecture exécutable
        ↓
Lot 1 — Administration opérationnelle
        ↓
Lot 2 — Catalog & basic Pricing
        ↓
Lot 3 — Inventory & Cash foundations
        ↓
Lot 4 — Sales & CompleteSale cash
        ↓
M2 — Première vente cash
```

---

# 3. Responsabilités des bounded contexts

## Sales

`Sales` possède :

```text
Sale
SaleLine
cycle de vie commercial
totaux
snapshots historiques
finalisation
```

Il ne possède pas le stock physique ni la caisse physique.

## Catalog / Pricing

Fournit via Application Contracts :

```text
ProductId
ProductPackagingId
SellableProductSnapshot
ProductPrice resolution
Tax resolution si disponible
```

## Inventory

Reste autorité sur :

```text
Stock
StockMovement
```

Sales appelle uniquement :

```text
Inventory\Application\Contract
```

## Cash Management

Reste autorité sur :

```text
CashSession
CashMovement
```

Sales/Payments utilise uniquement le contrat applicatif Cash.

## Payments

Le Lot 4 introduit seulement :

```text
Payment
method = CASH
purpose = SALE
```

sans provider, callback ou saga externe.

---

# 4. Invariants critiques

## 4.1 CompleteSale est atomique

`CompleteSale` cash utilise une coordinated local transaction.

```text
BEGIN
  validate Sale
  validate pricing
  validate CashSession
  create Payment CASH
  consume Inventory
  append StockMovement SALE
  confirm Payment
  append CashMovement SALE_PAYMENT
  complete Sale
  append Audit / Outbox
COMMIT
```

Toute erreur avant commit :

```text
ROLLBACK TOTAL
```

Interdit :

```text
Sale COMPLETED sans stock consommé
Stock consommé sans paiement
Payment confirmé sans CashMovement
CashMovement sans Sale finalisée
```

## 4.2 Idempotence

Une vente n’est finalisée qu’une fois.

Un retry de `CompleteSale` ne crée jamais :

```text
2 Payment
2 CashMovement
2 StockMovement SALE
```

## 4.3 Stock suivi facultatif

```text
inventoryTracked = false
→ aucun contrôle de stock
→ aucun StockMovement SALE
```

Un `SERVICE` ne produit aucun effet Inventory.

Pour un produit suivi :

```text
quantityOnHand >= requiredQuantity
```

sinon :

```text
INSUFFICIENT_STOCK
→ rollback complet
```

## 4.4 Snapshots

Une vente finalisée conserve ses snapshots de packaging, conversion, prix, taxe/remise si applicables et totaux.

Une modification future du catalogue ou du pricing ne réécrit jamais l’historique.

## 4.5 Pricing

`SalePricingCalculator` est pur et déterministe.

`CompleteSale` ne reprice jamais silencieusement.

Si le prix applicable a changé :

```text
SALE_PRICING_CHANGED
```

## 4.6 Cash

Un paiement cash confirmé produit exactement un :

```text
CashMovement
type = SALE_PAYMENT
direction = IN
```

sur une `CashSession OPEN`.

---

# 5. Fiscalité

La fiscalité du store pilote doit être explicitement décidée avant validation finale du Lot 4.

Si les règles sont disponibles, chaque `SaleLine` conserve un `TaxSnapshot`.

Si la fiscalité reste ouverte, il est interdit d’inventer silencieusement :

```text
tax = 0
```

comme règle générale.

Une décision documentaire/ADR est requise avant le Gate M2.

---

# 6. Commits

Format :

```text
<type>(<scope>): <description>
```

Exemples :

```text
refactor(sales): prepare sales bounded context
feat(sales): add sale aggregate
feat(sales): add sale line snapshots
feat(sales): add deterministic sale pricing
feat(payments): add cash sale payment
feat(sales): add CompleteSale cash workflow
test(sales): verify CompleteSale rollback
test(sales): verify CompleteSale idempotence
test(sales): verify concurrent cash sales
feat(api): expose cash sale workflow
```

---

# 7. Vue d’ensemble

```text
Epic 4.1 — Sales foundation
Epic 4.2 — Sale aggregate & lifecycle
Epic 4.3 — SaleLine & snapshots
Epic 4.4 — Sale pricing
Epic 4.5 — Minimal Payment CASH
Epic 4.6 — Inventory integration
Epic 4.7 — Cash integration
Epic 4.8 — CompleteSale coordinated transaction
Epic 4.9 — Idempotence & concurrency
Epic 4.10 — Authorization & audit
Epic 4.11 — Sales API
Epic 4.12 — PostgreSQL, RLS & rollback tests
Lot 4 Gate
M2 — Première vente cash
```

---

# 8. Epic 4.1 — Sales foundation

## Étape 4.1.1 — Préparer le module Sales

```text
src/Modules/Sales/
├── Domain/
├── Application/
│   └── Contract/
├── Infrastructure/
└── Presentation/
    └── Api/
```

Interdit :

```text
Sales\Domain → Inventory\Domain
Sales\Domain → CashManagement\Domain
Sales\Domain → Catalog\Domain
```

Autorisé :

```text
Sales\Application → */Application/Contract
```

Commit :

```text
refactor(sales): prepare sales bounded context
```

## Étape 4.1.2 — Persistence foundation

Créer dans `sales` :

```text
sale
sale_line
```

Ne pas introduire Returns/Refunds/Reservations.

Commit :

```text
feat(database): add sale persistence tables
```

### DoD Epic 4.1

- module prêt ;
- schema prêt ;
- architecture tests verts.

---

# 9. Epic 4.2 — Sale aggregate & lifecycle

## Aggregate `Sale`

```text
Sale
├── SaleId
├── OrganizationId
├── StoreId
├── status
├── currency
├── customerId?
├── lines
├── subtotal
├── discountTotal
├── taxTotal
├── total
├── createdBy
├── createdAt
├── completedBy?
├── completedAt?
├── cancelledBy?
├── cancelledAt?
├── BusinessDate?
└── Version
```

Statuts :

```text
DRAFT
AWAITING_PAYMENT
COMPLETED
CANCELLED
```

Le vertical slice cash peut utiliser directement :

```text
DRAFT → COMPLETED
```

si le paiement complet est atomique.

### Invariants

- Organization et Store immuables ;
- vente vide non finalisable ;
- currency unique ;
- `COMPLETED` non éditable ;
- `CANCELLED` non finalisable ;
- aucune suppression d’une vente finalisée.

### Events

```text
SaleCreated
SaleUpdated
SaleCancelled
SaleCompleted
```

Commit :

```text
feat(sales): add sale aggregate lifecycle
```

## CreateSale

```text
CreateSale
├── storeId
└── customerId?
```

`organizationId`, `actorId`, `currency` sont résolus côté serveur.

Commit :

```text
feat(sales): add create sale use case
```

## CancelSale

Uniquement avant finalisation.

Commit :

```text
feat(sales): add cancel draft sale
```

### DoD Epic 4.2

- lifecycle complet ;
- persistence PostgreSQL ;
- RLS ;
- aucune suppression métier.

---

# 10. Epic 4.3 — SaleLine & snapshots

## SaleLine

```text
SaleLine
├── SaleLineId
├── ProductId
├── ProductPackagingId
├── productCodeSnapshot?
├── productNameSnapshot?
├── packagingCodeSnapshot
├── packagingNameSnapshot?
├── unitIdSnapshot
├── enteredQuantity
├── conversionFactorSnapshot
├── baseQuantity
├── unitPrice
├── priceListId?
├── productPriceId?
├── discountSnapshot?
├── promotionSnapshot?
├── taxSnapshot?
├── subtotal
├── discountAmount
├── taxableAmount
├── taxAmount
├── total
└── sourceVersions
```

Règle :

```text
baseQuantity
=
enteredQuantity × conversionFactorSnapshot
```

## Contrat Catalog

Créer/réutiliser :

```text
SaleProductProvider
```

retournant un DTO applicatif, jamais l’aggregate Product.

## AddSaleLine

```text
AddSaleLine
├── saleId
├── productId
├── productPackagingId
└── quantity
```

Préconditions :

- Sale modifiable ;
- produit vendable ;
- packaging vendable ;
- quantité conforme à precision/minimum/increment ;
- prix résolvable.

Commits :

```text
feat(catalog): expose sellable product contract
feat(sales): add sale line snapshots
feat(sales): add sale line use case
feat(sales): add draft sale line editing
```

### DoD Epic 4.3

- snapshots historiques ;
- conversion exacte ;
- lignes immuables après completion.

---

# 11. Epic 4.4 — Sale pricing

## SalePricingCalculator

Service pur et déterministe.

Ordre :

```text
1. quantity × unit price
2. line discounts
3. sale discount allocations
4. taxable amount
5. tax
6. gross line and sale totals
```

Le Lot 4 n’implémente que les mécanismes effectivement disponibles depuis le Lot 2.

## Price resolution

Utiliser :

```text
ProductPriceResolver
```

Snapshot :

```text
priceListId
productPriceId
unitPrice
currency
sourceVersion
```

## Protection contre prix obsolète

Avant CompleteSale :

```text
pricing snapshot accepté
≠ pricing applicable
→ SALE_PRICING_CHANGED
```

Pas de mise à jour silencieuse.

## Price override

Si activé :

```text
SALE_PRICE_OVERRIDE
+
reason
+
SecurityAuditEntry
```

Commits :

```text
feat(sales): add deterministic sale pricing calculator
feat(sales): resolve product price for sale lines
feat(sales): prevent silent sale repricing
```

### DoD Epic 4.4

- pricing déterministe ;
- Money exact ;
- aucune devise implicite ;
- snapshots conservés.

---

# 12. Epic 4.5 — Minimal Payment CASH

## Aggregate Payment

```text
Payment
├── PaymentId
├── OrganizationId
├── purpose = SALE
├── targetReference = SaleId
├── method = CASH
├── status
├── amount
├── currency
├── createdAt
├── confirmedAt?
├── createdBy
└── Version
```

Statuts nécessaires :

```text
CREATED
CONFIRMED
CANCELLED
```

### Invariants

- amount > 0 ;
- devise = Sale.currency ;
- cible même tenant ;
- confirmation unique ;
- paiement confirmé non réécrit rétroactivement.

Events :

```text
PaymentCreated
PaymentConfirmed
```

Commits :

```text
feat(payments): add cash sale payment
feat(payments): persist cash payments
```

### Hors scope Payment

```text
CARD
MOBILE_MONEY
BANK_TRANSFER
PaymentAttempt
PaymentRefund
provider callback
```

---

# 13. Epic 4.6 — Inventory integration

Utiliser le contrat Lot 3 :

```text
InventoryStockConsumer
```

Commande :

```text
ConsumeStockForSale
├── organizationId
├── storeId
├── saleId
└── items[]
    ├── productId
    └── baseQuantity
```

Pour chaque produit suivi :

```text
Stock.consumeForSale(...)
StockMovement
  type = SALE
  source = SALE / SaleId
```

Pour produit non suivi/service :

```text
aucun effet Inventory
```

Idempotence logique :

```text
SALE + saleId + productId
```

Commits :

```text
feat(inventory): consume stock for cash sale
feat(inventory): make sale stock consumption idempotent
test(inventory): verify concurrent sale consumption
```

### DoD Epic 4.6

- stock suivi décrémenté ;
- untracked/service ignorés ;
- mouvement SALE ;
- aucun stock négatif ;
- concurrence sûre.

---

# 14. Epic 4.7 — Cash integration

Utiliser :

```text
CashMovementRecorder
```

Entrée :

```text
organizationId
storeId
cashSessionId
saleId
paymentId
amount
actorId
```

Préconditions :

- CashSession existe ;
- status OPEN ;
- même Store ;
- même tenant ;
- même devise.

Créer :

```text
CashMovement
type = SALE_PAYMENT
direction = IN
sourceReference = PaymentId ou SaleId
```

Un Payment ne produit qu’un seul mouvement cash.

Commits :

```text
feat(cash): record cash sale payment movement
feat(cash): make sale payment movement idempotent
```

### DoD Epic 4.7

- SALE_PAYMENT exact ;
- CashSession OPEN obligatoire ;
- expected cash augmenté ;
- idempotence.

---

# 15. Epic 4.8 — CompleteSale

## Command

```text
CompleteSale
├── saleId
├── cashSessionId
├── payment
│   └── amount
└── tenderedAmount?
```

L’organisation, le store et l’acteur sont résolus côté serveur.

## Préconditions

```text
Organization ACTIVE
Store ACTIVE
actor autorisé
Sale tenant-safe
Sale completable
Sale non vide
CashSession OPEN
CashSession du même Store
pricing accepté
```

## Paiement

Recommandation du premier vertical slice :

```text
1 Payment CASH
couvrant 100 % du total
```

Le cadrage global permet des split payments, mais ils peuvent rester pour une extension ultérieure afin de ne pas élargir M2.

## Monnaie

Si :

```text
tenderedAmount
```

alors :

```text
tenderedAmount >= total
changeAmount = tenderedAmount - total
```

Le Payment porte le montant de la vente, pas le montant remis avant rendu de monnaie.

## Workflow

```text
BEGIN

load Sale
validate Sale
validate pricing
validate CashSession

create Payment

consume Inventory tracked lines
append StockMovement SALE

confirm Payment
append CashMovement SALE_PAYMENT

Sale.complete(...)
persist Sale

append required audit
append outbox events

COMMIT
```

## BusinessDate

Calculée avec :

```text
Store.timeZone
```

et distincte des timestamps UTC.

## Events

```text
SaleCompleted
PaymentConfirmed
```

Commit :

```text
feat(sales): add CompleteSale cash workflow
```

### DoD Epic 4.8

- coordinated transaction ;
- Payment + Stock + Cash + Sale + Outbox atomiques ;
- BusinessDate ;
- zéro effet partiel.

---

# 16. Epic 4.9 — Idempotence & concurrency

## Idempotency-Key

`CompleteSale` utilise la fondation du Lot 0.

```text
same key + same payload
→ same result
```

```text
same key + different payload
→ IDEMPOTENCY_CONFLICT
```

## Retry après commit inconnu

```text
server COMMIT
connection lost
client retry
```

Résultat :

```text
Sale COMPLETED
1 Payment
1 CashMovement
1 Stock effect par produit
```

## Double completion concurrente

Deux CompleteSale simultanés sur le même Sale :

```text
→ un seul effet métier
```

## Deux ventes concurrentes

```text
Stock = 5
Sale A = 4
Sale B = 3
```

Résultat :

```text
quantityOnHand jamais négatif
aucun lost update
```

Commits :

```text
feat(sales): enforce CompleteSale idempotency
test(sales): verify CompleteSale retry after commit
test(sales): verify concurrent sale completion
test(sales): verify concurrent CompleteSale stock safety
```

---

# 17. Epic 4.10 — Authorization & audit

Permissions :

```text
SALE_CREATE
SALE_READ
SALE_UPDATE_DRAFT
SALE_CANCEL_DRAFT
SALE_COMPLETE
SALE_PRICE_OVERRIDE
```

`SALE_PRICE_OVERRIDE` seulement si la capacité existe.

Rôles système :

```text
ORGANIZATION_OWNER
STORE_MANAGER
CASHIER
ACCOUNTANT
```

reçoivent les permissions selon la policy existante, jamais par checks de rôle dans Sales Domain.

Guards :

```text
OrganizationOperationalGuard
StoreOperationalGuard
```

Store suspendu :

```text
CreateSale
CompleteSale
→ refusés
```

Audit sensible :

```text
SALE_PRICE_OVERRIDE
approvals éventuelles
```

Les ledgers métier restent distincts du Security Audit.

Commits :

```text
feat(access): add cash sales permissions
feat(access): grant cash sales permissions
feat(sales): enforce sales authorization
feat(sales): publish sale completion events
```

---

# 18. Epic 4.11 — Sales API

## Create

```text
POST /api/stores/{storeId}/sales
```

## Read

```text
GET /api/sales/{id}
```

## Lines

```text
POST   /api/sales/{id}/lines
PATCH  /api/sales/{id}/lines/{lineId}
DELETE /api/sales/{id}/lines/{lineId}
```

Le DELETE concerne uniquement une ligne DRAFT.

## Cancel

```text
POST /api/sales/{id}/cancel
```

Pas de :

```text
DELETE /api/sales/{id}
```

## Complete cash sale

```text
POST /api/sales/{id}/complete
```

Headers :

```text
Authorization
X-Correlation-ID
Idempotency-Key
```

Payload :

```json
{
  "cashSessionId": "...",
  "payment": {
    "method": "CASH",
    "amount": {
      "amount": "12500",
      "currency": "XAF"
    }
  },
  "tenderedAmount": {
    "amount": "15000",
    "currency": "XAF"
  }
}
```

Réponse :

```json
{
  "saleId": "...",
  "status": "COMPLETED",
  "total": {
    "amount": "12500",
    "currency": "XAF"
  },
  "paymentId": "...",
  "changeAmount": {
    "amount": "2500",
    "currency": "XAF"
  }
}
```

## Receipt

```text
GET /api/sales/{id}/receipt
```

Le reçu expose les snapshots historiques nécessaires.

## Error codes

```text
SALE_NOT_EDITABLE
SALE_EMPTY
SALE_ALREADY_COMPLETED
SALE_CANCELLED

PRODUCT_NOT_SELLABLE
PACKAGING_NOT_SELLABLE
PRODUCT_PRICE_NOT_FOUND
SALE_PRICING_CHANGED

STOCK_NOT_INITIALIZED
INSUFFICIENT_STOCK

CASH_SESSION_NOT_OPEN
CASH_SESSION_STORE_MISMATCH

PAYMENT_AMOUNT_MISMATCH
PAYMENT_CURRENCY_MISMATCH

IDEMPOTENCY_CONFLICT
```

Commits :

```text
feat(api): expose sale creation
feat(api): expose sale details
feat(api): expose draft sale line editing
feat(api): expose CompleteSale cash
feat(api): expose sale receipt
docs(api): document cash sales workflow
```

---

# 19. Epic 4.12 — Persistence & RLS

Tables tenant-owned :

```text
sales.sale
sales.sale_line
payment tables utilisées par Lot 4
```

Persistence :

- mappings Doctrine ;
- UUID v7 ;
- optimistic version ;
- indexes tenant/store/status/business date ;
- snapshots historiques ;
- RLS ;
- contraintes idempotence.

Une archive ou modification du catalogue ne doit pas casser la lecture d’une vente finalisée.

Commits :

```text
feat(sales): persist sale aggregate
feat(sales): persist sale line snapshots
test(tenant): verify sales PostgreSQL RLS
```

---

# 20. Tests Domain

Couvrir :

```text
create draft
add line
update line
remove line
cancel draft
reject empty completion
reject cancelled completion
reject completed edit
reject second completion
```

SaleLine :

```text
quantity precision
minimumQuantity
quantityIncrement
conversion exactness
snapshot immutability
```

Pricing :

```text
price resolution
price not found
pricing changed
currency mismatch
deterministic recalculation
```

Commits :

```text
test(sales): cover sale lifecycle
test(sales): cover sale line snapshots
test(sales): cover sale pricing
```

---

# 21. CompleteSale integration tests

## Cas nominal tracked

```text
Stock = 10
Sale quantity = 2
Total = 5 000 XAF
CashSession OPEN
```

Après :

```text
Sale COMPLETED
Payment CONFIRMED
CashMovement +5 000
Stock = 8
StockMovement SALE = 2
Outbox présents
```

## Produit non suivi

```text
Sale COMPLETED
Payment CONFIRMED
CashMovement created
no StockMovement
```

## Service

```text
no Inventory effect
```

## Insufficient stock

```text
Stock = 1
required = 2

→ INSUFFICIENT_STOCK
→ Sale not completed
→ no Payment confirmed
→ no CashMovement
→ Stock unchanged
```

## Failure matrix

Injecter des erreurs :

```text
after Payment creation
after Stock update
after StockMovement
after Payment confirmation
after CashMovement
after Sale.complete
after Audit
after Outbox
before COMMIT
```

Tout échec pré-commit :

```text
ROLLBACK TOTAL
```

Commits :

```text
test(sales): complete tracked cash sale
test(sales): complete untracked product sale
test(sales): complete service cash sale
test(sales): rollback on insufficient stock
test(sales): rollback on cash failure
test(sales): rollback on outbox failure
test(sales): verify CompleteSale failure matrix
```

---

# 22. Tenant & scope tests

Tester :

```text
CASHIER Store A
→ Sale Store A OK
```

```text
CASHIER Store A
→ Sale Store B denied
```

```text
Tenant B → Sale Tenant A
→ NOT_FOUND
```

```text
Tenant B → CashSession Tenant A
→ NOT_FOUND
```

Aucune fuite d’existence cross-tenant.

Commit :

```text
test(access): verify cash sales permissions and scopes
```

---

# 23. Démonstration consolidée — M2

```text
1. Organization A existe.
2. Store A ACTIVE.
3. Product Coca-Cola 33cl existe.
4. PHYSICAL, inventoryTracked=true.
5. Packaging CAN.
6. Prix CAN = 500 XAF.
7. Stock Store A = 20.
8. Cashier autorisé sur Store A.
9. Cashier ouvre CashSession à 50 000 XAF.
10. Il crée une Sale.
11. Il ajoute 3 CAN.
12. Total = 1 500 XAF selon pricing/tax policy.
13. CompleteSale avec Payment CASH 1 500 XAF.
14. tenderedAmount = 2 000 XAF.
15. changeAmount = 500 XAF.
16. Sale → COMPLETED.
17. Payment → CONFIRMED.
18. CashMovement SALE_PAYMENT → +1 500 XAF.
19. expected cash → 51 500 XAF.
20. Stock → 17.
21. StockMovement SALE → 3.
22. Receipt consultable.
23. Retry même Idempotency-Key → aucun doublon.
24. Vente concurrente → aucun stock négatif.
25. Tenant B → NOT_FOUND.
26. Erreur injectée avant commit → aucun effet partiel.
```

---

# 24. Gate de sortie du Lot 4

Le Lot 4 est `DONE` uniquement lorsque :

```text
[x] Sales bounded context opérationnel
[x] architecture boundaries protégées

[x] Sale aggregate opérationnel
[x] lifecycle Sale testé
[x] DRAFT disponible
[x] COMPLETED non éditable
[x] CANCELLED non finalisable
[x] aucune suppression de vente finalisée

[x] SaleLine opérationnelle
[x] packaging snapshot conservé
[x] conversionFactor snapshot conservé
[x] baseQuantity conservée
[x] prix snapshoté
[x] tax snapshot selon politique
[x] historique indépendant du catalogue courant

[x] SalePricingCalculator déterministe
[x] Money exact
[x] Quantity exacte
[x] aucun repricing silencieux
[x] ProductPriceNotFound géré
[x] SalePricingChanged géré

[x] fiscalité pilote explicitement décidée

[x] Payment CASH minimal opérationnel
[x] purpose SALE
[x] status CONFIRMED
[x] Payment distinct de CashMovement

[x] Inventory Application Contract utilisé
[x] aucun StockRepository importé dans Sales
[x] tracked product consomme Stock
[x] untracked product ne touche pas Stock
[x] Service ne touche pas Stock
[x] StockMovement SALE créé
[x] stock négatif impossible
[x] effet Inventory idempotent

[x] Cash Application Contract utilisé
[x] aucune mutation directe CashSession depuis Sales
[x] CashSession OPEN obligatoire
[x] CashMovement SALE_PAYMENT créé
[x] expected cash mis à jour
[x] effet Cash idempotent

[x] CompleteSale cash opérationnel
[x] coordinated local transaction
[x] Sale + Payment + Inventory + Cash + Outbox atomiques
[x] rollback Inventory failure
[x] rollback Cash failure
[x] rollback Outbox failure
[x] failure matrix verte

[x] CompleteSale idempotent
[x] Idempotency-Key supportée
[x] retry après commit sûr
[x] double completion concurrente sûre
[x] stock concurrency PostgreSQL réelle

[x] BusinessDate calculée avec Store.timeZone
[x] timestamps UTC séparés

[x] permissions Sales disponibles
[x] Store scopes appliqués
[x] OrganizationOperationalGuard actif
[x] StoreOperationalGuard actif

[x] API CreateSale
[x] API ReadSale
[x] API SaleLines
[x] API CancelSale
[x] API CompleteSale cash
[x] API Receipt
[x] OpenAPI à jour
[x] contrat d’erreurs stable

[x] RLS Sales actif
[x] RLS Payment actif
[x] cross-tenant NOT_FOUND

[x] domain tests verts
[x] persistence tests verts
[x] API contract tests verts
[x] integration tests verts
[x] rollback tests verts
[x] concurrency tests verts
[x] idempotence tests verts
[x] tenant isolation tests verts
[x] RLS tests verts
[x] architecture fitness tests verts
[x] PHPStan vert
[x] PHP-CS-Fixer vert
[x] Composer audit vert
[x] CI verte

[x] démonstration M2 réussie

[x] aucun ReturnSale
[x] aucun RefundSale
[x] aucun Customer Credit
[x] aucun payment provider
[x] aucun StockReservation
[x] aucun Inventory Costing
[x] aucun Purchasing
[x] aucun StockTransfer
[x] aucun StockCount
[x] aucun Reporting avancé
[x] aucun Offline

[x] IMPLEMENTATION_STATUS.md mis à jour
[x] ADR mis à jour si décision DÉCIDÉ modifiée
```

---

# 25. Hors périmètre

```text
ReturnSale
RefundSale
PaymentRefund

Inventory Costing
StockValuation
StockValuationMovement
SaleLineCostSnapshot

Customer
CustomerAccount
CustomerReceivable
CreditPolicy

PaymentAttempt
PaymentProvider
PaymentSettlement provider
CARD processing
MOBILE_MONEY processing
BANK_TRANSFER workflow

StockReservation

Supplier
PurchaseOrder
GoodsReceipt
PurchaseReturn

StockTransfer
StockCount

Reporting avancé
Offline
```

---

# 26. Transition vers la suite

Après validation :

```text
M2 — Première vente cash
```

Capacités obtenues :

```text
tenant
stores
users & permissions
catalog
pricing
stock
cash register/session
cash sale
payment
stock decrement
cash movement
receipt
audit/outbox
```

Suite recommandée :

```text
Lot 5 — Inventory Costing & Returns
Lot 6 — Purchasing & Goods Receipts
Lot 7 — StockTransfer & StockCount
        ↓
M3 — Gestion complète du stock
```

---

# 27. Principe de travail

Pour chaque étape :

1. vérifier baseline et ADR ;
2. vérifier l’état réel des Lots 2 et 3 ;
3. réutiliser les Application Contracts existants ;
4. définir les invariants avant le code ;
5. préserver les snapshots ;
6. rendre les commandes retry-safe ;
7. tester PostgreSQL réel ;
8. tester les échecs à chaque point critique ;
9. tester concurrence et idempotence ;
10. exécuter architecture tests ;
11. exécuter PHPStan / PHP-CS-Fixer / Composer audit ;
12. faire un commit atomique ;
13. mettre à jour `IMPLEMENTATION_STATUS.md` ;
14. documenter toute décision structurante.

---

# 28. Premier point d’entrée

Ordre recommandé :

```text
Sales foundation
→ Sale
→ SaleLine snapshots
→ Pricing calculator
→ Payment CASH
→ Inventory integration
→ Cash integration
→ CompleteSale
→ rollback tests
→ idempotence
→ concurrency
→ API
→ Gate M2
```

Premiers commits :

```text
refactor(sales): prepare sales bounded context
feat(database): add sale persistence tables
feat(sales): add sale aggregate lifecycle
feat(catalog): expose sellable product contract
feat(sales): add sale line snapshots
feat(sales): add deterministic sale pricing
feat(payments): add cash sale payment
feat(inventory): consume stock for cash sale
feat(cash): record cash sale payment movement
feat(sales): add CompleteSale cash workflow
test(sales): verify CompleteSale failure matrix
test(sales): verify CompleteSale idempotence
test(sales): verify concurrent CompleteSale stock safety
```
