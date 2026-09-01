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


## Dependencies

Dependencies are capability-based. Follow dependencies explicitly named by the current Epic or support file; do not infer dependencies from Lot numbering.


## Source of truth

`docs/specs/planning/zandu-lot-4-sales-complete-sale-cash.md`

Open this complete specification only when the targeted AI files do not answer a required business question.
