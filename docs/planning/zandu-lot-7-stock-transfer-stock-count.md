# Zandu Sales Manager — Lot 7 : StockTransfer & StockCount

**Version :** 1.0  
**Statut :** Backlog d’implémentation  
**Langue :** Français — identifiants de code en anglais

---

# 1. Objectif du Lot 7

Le Lot 7 termine le jalon :

```text
M3 — Gestion complète du stock
```

Il ajoute les deux derniers workflows opérationnels indispensables au premier MVP commercial recommandé :

```text
StockTransfer
+
StockCount
```

À la sortie du Lot 7, Zandu doit pouvoir :

- transférer du stock entre deux stores d’une même organization ;
- créer un transfert en `DRAFT` ;
- expédier atomiquement toutes ses lignes ;
- produire des `TRANSFER_OUT` au store source ;
- conserver les quantités demandées, expédiées et reçues ;
- représenter explicitement le stock en transit sans créer de troisième stock physique ;
- réceptionner une seule fois le transfert au store destination ;
- produire des `TRANSFER_IN` uniquement pour les quantités effectivement reçues ;
- conserver les écarts de réception sans créer artificiellement du stock ;
- transporter la valeur économique du store source vers le store destination ;
- conserver la valeur d’un écart comme perte potentielle en transit ;
- empêcher tout double mouvement via idempotence ;
- réaliser un inventaire physique complet ou partiel ;
- capturer un snapshot `expectedQuantity` à l’ouverture ;
- verrouiller uniquement les produits inclus dans le comptage ;
- permettre plusieurs opérateurs sur des lignes différentes ;
- enregistrer `countedQuantity`, y compris zéro ;
- distinguer `null` de zéro ;
- finaliser un inventaire via `FINALIZING` ;
- réconcilier les lignes par lots transactionnels et idempotents ;
- produire `STOCK_COUNT_CORRECTION_IN` / `OUT` lorsque nécessaire ;
- ne produire aucun mouvement pour une variance nulle ;
- reprendre après crash uniquement les lignes encore `PENDING` ;
- valoriser les corrections de comptage ;
- bloquer les opérations incompatibles pendant `OPEN` et `FINALIZING` ;
- fournir les blockers définitifs à `StoreClosure` ;
- appliquer permissions, scopes, audit, RLS, idempotence et concurrence ;
- exposer les APIs correspondantes ;
- clôturer formellement M3.

Le Lot 7 ne doit pas introduire :

```text
Customers
Customer Credit
Payment providers
StockReservation
Reporting avancé
Offline
LotTracking
StockLot
Advanced warehouse management
```

Après ce Lot, le backend du MVP commercial de base est suffisamment structuré pour commencer le frontend Admin et POS.

---

# 2. Position dans la roadmap

```text
Lot 5
Inventory Costing & Returns
        ↓
Lot 6
Purchasing & Goods Receipts
        ↓
Lot 7
StockTransfer & StockCount
        ↓
M3
Gestion complète du stock
        ↓
Frontend
Admin + POS
```

---

# 3. Responsabilité Inventory

`StockTransfer` et `StockCount` appartiennent à `Inventory`.

Inventory possède déjà :

```text
Stock
StockMovement
```

Le Lot 7 complète cette responsabilité avec :

```text
StockTransfer
StockTransferLine

StockCount
StockCountLine
OpenStockCountScope
```

Les concepts externes circulent par identifiants et Application Contracts.

Inventory ne dépend pas de :

```text
Organization\Store aggregate
Catalog\Product aggregate
Sales\Sale aggregate
Purchasing\GoodsReceipt aggregate
```

---

# 4. Principes critiques du Lot 7

## 4.1 Transfert inter-store uniquement dans un tenant

Invariant :

```text
sourceStoreId != destinationStoreId
```

et :

```text
sourceStore.organizationId
=
destinationStore.organizationId
=
StockTransfer.organizationId
```

Un transfert cross-tenant est impossible.

---

## 4.2 Workflow MVP du transfert

La baseline figée utilise :

```text
DRAFT
→ SHIPPED
→ RECEIVED
```

avec :

```text
DRAFT → CANCELLED
```

avant expédition.

Le premier cadrage fonctionnel évoquait un workflow plus riche (`APPROVED`, `IN_TRANSIT`), mais la baseline DDD a volontairement figé un MVP plus simple.

Le Lot 7 doit suivre la baseline.

---

## 4.3 Une seule expédition et une seule réception finale

Le MVP ne supporte pas :

```text
partial shipment #1
partial shipment #2

partial receipt #1
partial receipt #2
```

Chaque transfert possède :

```text
1 expédition atomique
1 réception finale atomique
```

Les réceptions partielles successives restent hors scope.

---

## 4.4 Quantités distinctes

Chaque ligne conserve :

```text
requestedQuantity
shippedQuantity
receivedQuantity
```

Invariants :

```text
requestedQuantity > 0
```

Avant expédition :

```text
shippedQuantity = null
```

Après expédition :

```text
0 <= shippedQuantity <= requestedQuantity
```

Avant réception :

```text
receivedQuantity = null
```

Après réception :

```text
0 <= receivedQuantity <= shippedQuantity
```

Une quantité zéro est une donnée valide après phase correspondante, mais ne produit jamais de mouvement nul.

---

## 4.5 Le statut n’explique pas l’écart

Le statut décrit le workflow.

Les quantités décrivent la réalité.

Exemple :

```text
requested = 10
shipped = 10
received = 8
status = RECEIVED
```

Le transfert est terminé.

L’écart :

```text
2
```

reste visible comme écart de transit.

Il ne faut pas garder artificiellement le transfert `SHIPPED` pour représenter l’écart.

---

## 4.6 Surplus à destination

Si le store destination constate :

```text
receivedQuantity > shippedQuantity
```

le surplus n’est pas accepté dans `ReceiveStockTransfer`.

Il passe par :

```text
AdjustStock
```

avec raison et permission.

---

## 4.7 StockCount bloque uniquement son périmètre

Un inventaire ne doit pas bloquer tout le store.

La baseline matérialise :

```text
OpenStockCountScope
├── OrganizationId
├── StoreId
├── ProductId
└── StockCountId
```

avec unicité :

```text
organization + store + product
```

Pendant :

```text
OPEN
FINALIZING
```

aucun mouvement ne peut affecter les produits verrouillés.

---

## 4.8 StockCountLine séparée

`StockCount` reste petit.

Chaque produit est représenté par un aggregate séparé :

```text
StockCountLine
```

Cela permet :

- plusieurs compteurs ;
- pas de version globale géante ;
- traitement par batch ;
- reprise ciblée après incident.

---

## 4.9 Zéro ≠ non compté

```text
countedQuantity = null
```

signifie :

> pas encore compté.

```text
countedQuantity = 0
```

signifie :

> compté physiquement à zéro.

Cette distinction doit exister Domain, persistence et API.

---

# 5. Règle de commits

Exemples :

```text
feat(inventory): add stock transfer aggregate
feat(inventory): ship stock transfer
feat(inventory): receive stock transfer
feat(costing): transfer stock value between stores
test(inventory): verify stock transfer idempotence

feat(inventory): add stock count aggregate
feat(inventory): add stock count line aggregate
feat(inventory): lock open stock count scope
feat(inventory): record stock counts
feat(inventory): reconcile stock count batch
feat(costing): value stock count corrections
test(inventory): verify stock count crash recovery
```

---

# 6. Vue d’ensemble

```text
Epic 7.1 — StockTransfer foundation
Epic 7.2 — StockTransfer lifecycle
Epic 7.3 — ShipStockTransfer
Epic 7.4 — ReceiveStockTransfer
Epic 7.5 — Transfer costing
Epic 7.6 — Transfer idempotence & concurrency

Epic 7.7 — StockCount foundation
Epic 7.8 — StockCount scope & snapshot
Epic 7.9 — Counting workflow
Epic 7.10 — FINALIZING & batch reconciliation
Epic 7.11 — StockCount costing
Epic 7.12 — Locks & cross-workflow guards

Epic 7.13 — StoreClosure integration
Epic 7.14 — Authorization & audit
Epic 7.15 — APIs
Epic 7.16 — PostgreSQL / RLS / recovery tests

Lot 7 Gate
M3 Gate
```

---

# 7. Epic 7.1 — StockTransfer aggregate

Modèle :

```text
StockTransfer
├── StockTransferId
├── OrganizationId
├── sourceStoreId
├── destinationStoreId
├── status
├── lines[]
├── createdBy
├── createdAt
├── shippedBy?
├── shippedAt?
├── receivedBy?
├── receivedAt?
├── cancellationReason?
├── cancelledBy?
├── cancelledAt?
└── Version
```

## StockTransferLine

```text
StockTransferLine
├── StockTransferLineId
├── ProductId
├── requestedQuantity
├── shippedQuantity?
├── receivedQuantity?
├── shippedValueSnapshot?
├── shippedUnitCostSnapshot?
└── Version?
```

Les quantités sont en base unit.

Un produit apparaît au maximum une fois par transfert.

## Status

```text
DRAFT
SHIPPED
RECEIVED
CANCELLED
```

Commit :

```text
feat(inventory): add stock transfer aggregate
```

---

# 8. Epic 7.2 — Create / edit transfer

Commands :

```text
CreateStockTransfer
AddStockTransferLine
UpdateStockTransferLine
RemoveStockTransferLine
CancelStockTransfer
```

Autorisés uniquement en :

```text
DRAFT
```

Préconditions :

- Organization opérationnelle ;
- stores distincts ;
- même Organization ;
- stores existants ;
- source Store ACTIVE pour nouveau workflow ;
- destination Store compatible ;
- produit suivi en stock ;
- quantité demandée > 0.

Annulation :

```text
DRAFT → CANCELLED
```

raison selon policy.

Après expédition :

```text
aucune annulation simple
```

Commit :

```text
feat(inventory): add stock transfer draft lifecycle
```

---

# 9. Epic 7.3 — ShipStockTransfer

Command :

```text
ShipStockTransfer
├── stockTransferId
├── shippedQuantities[]
└── commandId
```

Les quantités expédiées peuvent être :

```text
0 <= shipped <= requested
```

Une ligne expédiée à zéro reste dans le document mais ne produit aucun mouvement.

## Transaction

```text
BEGIN

load StockTransfer DRAFT
validate source/destination

for each line with shippedQuantity > 0:
    load Stock source
    validate available quantity
    Stock.shipTransfer(...)
    append StockMovement TRANSFER_OUT

    transfer economic value from source valuation
    append valuation movement

freeze shipped quantities
StockTransfer → SHIPPED

append outbox

COMMIT
```

Un échec sur une seule ligne :

```text
ROLLBACK COMPLET
```

## Movement

```text
StockMovement
type = TRANSFER_OUT
source = TRANSFER / StockTransferId
```

Commit :

```text
feat(inventory): ship stock transfer
```

---

# 10. Epic 7.4 — Stock en transit

Le MVP ne crée pas :

```text
TransitStock
```

comme stock physique distinct.

Le transit est expliqué par :

```text
StockTransfer.status = SHIPPED
shippedQuantity
receivedQuantity = null
TRANSFER_OUT déjà créé
TRANSFER_IN absent
```

La quantité expédiée n’appartient plus au Stock source et n’appartient pas encore au Stock destination.

Pour les lectures futures, une projection pourra calculer :

```text
inTransitQuantity
```

mais aucun invariant critique ne dépend d’une projection.

---

# 11. Epic 7.5 — ReceiveStockTransfer

Command :

```text
ReceiveStockTransfer
├── stockTransferId
├── receivedQuantities[]
└── commandId
```

Préconditions :

```text
status = SHIPPED
```

Pour chaque ligne :

```text
0 <= receivedQuantity <= shippedQuantity
```

Une réception est finale.

## Transaction

```text
BEGIN

load StockTransfer SHIPPED

for each line with receivedQuantity > 0:
    create/load destination Stock
    Stock.receiveTransfer(...)
    append StockMovement TRANSFER_IN

    receive transported value
    update destination StockValuation
    append valuation movement

freeze received quantities
StockTransfer → RECEIVED

record transit discrepancy if any
append outbox

COMMIT
```

Un échec sur une ligne annule toute la réception.

## Mouvement

```text
TRANSFER_IN
```

seulement si :

```text
receivedQuantity > 0
```

Commit :

```text
feat(inventory): receive stock transfer
```

---

# 12. Epic 7.6 — Transfer discrepancy

Pour chaque ligne :

```text
transitDiscrepancy
=
shippedQuantity - receivedQuantity
```

Invariant :

```text
transitDiscrepancy >= 0
```

Cas :

```text
0
→ réception exacte
```

```text
> 0
→ perte / écart potentiel en transit
```

Le système ne crée pas automatiquement un `AdjustStock` au destination pour compenser.

La divergence reste attachée au transfert.

Une résolution métier future peut utiliser :

```text
reason
investigation
manual adjustment
```

selon permission.

---

# 13. Epic 7.7 — Transfer costing

La baseline impose :

```text
un transfert transporte la valeur du store source
jusqu’au store destination
```

## À l’expédition

Pour chaque ligne :

```text
shippedUnitCost
=
source currentAverageUnitCost
```

```text
shippedValue
=
shippedQuantity × shippedUnitCost
```

Le store source retire :

```text
quantity
+
value
```

dans la même transaction.

Conserver un snapshot :

```text
TransferLineCostSnapshot
```

ou équivalent :

```text
StockTransferLine
├── shippedUnitCostSnapshot
└── shippedValueSnapshot
```

## À la réception

La valeur reçue doit dériver du snapshot expédié.

Si :

```text
received = shipped
```

alors toute la valeur est transférée.

Si :

```text
received < shipped
```

répartir la valeur de manière déterministe.

La valeur non reçue reste attachée au transfert comme :

```text
transitLossValue
```

potentielle.

Ne jamais recalculer la valeur avec le coût moyen du store destination.

## Destination average

```text
destinationNewTotalValue
=
destinationPreviousValue
+
receivedTransferredValue
```

puis recalcul du coût moyen.

Commit :

```text
feat(costing): transfer stock value between stores
```

---

# 14. Sortie finale source

Si l’expédition vide le Stock source :

```text
source quantity = 0
→ source totalValue = 0 exactement
```

Le mécanisme de résidu du Lot 5 continue de s’appliquer.

---

# 15. Transfer idempotence

Clé logique :

```text
stockTransferId
+
productId
+
phase
```

Phases :

```text
TRANSFER_OUT
TRANSFER_IN
```

Un replay identique :

```text
→ retourne résultat existant
```

Même `commandId`, payload différent :

```text
→ IdempotencyConflict
```

Aucun double mouvement.

Commit :

```text
test(inventory): verify stock transfer idempotence
```

---

# 16. Transfer concurrency

Tester :

```text
Stock source = 10

Transfer A ships 7
Sale consumes 5 concurrently
```

ou :

```text
Transfer A ships 7
Transfer B ships 6 concurrently
```

Résultat :

```text
Stock never negative
no lost update
only compatible operations succeed
```

Utiliser la stratégie de concurrence déjà décidée dans Inventory.

Commit :

```text
test(inventory): verify concurrent transfer shipment
```

---

# 17. Store suspension & transfer

La baseline autorise pendant suspension :

```text
ReceiveStockTransfer
```

pour un transfert déjà expédié, car il s’agit d’une terminaison/remédiation.

Elle interdit de démarrer :

```text
CreateStockTransfer
ShipStockTransfer
```

comme nouvelle opération si la policy l’interdit.

Donc :

```text
Store SUSPENDED destination
+
Transfer already SHIPPED
→ réception contrôlée autorisable
```

Ce comportement doit être testé explicitement.

---

# 18. Epic 7.8 — StockCount aggregate

Modèle :

```text
StockCount
├── StockCountId
├── OrganizationId
├── StoreId
├── status
├── mode
├── scopeType
├── progress counters
├── createdBy
├── createdAt
├── startedBy?
├── startedAt?
├── finalizationStartedBy?
├── finalizationStartedAt?
├── completedBy?
├── completedAt?
├── cancelledBy?
├── cancelledAt?
└── Version
```

## Status

```text
DRAFT
OPEN
FINALIZING
COMPLETED
CANCELLED
```

## Mode

```text
BLIND
GUIDED
```

La baseline propose `BLIND` par défaut.

## Scope type

```text
FULL
PARTIAL
```

Commit :

```text
feat(inventory): add stock count aggregate
```

---

# 19. Epic 7.9 — StockCountLine

Aggregate séparé :

```text
StockCountLine
├── StockCountLineId
├── StockCountId
├── OrganizationId
├── StoreId
├── ProductId
├── expectedQuantity
├── countedQuantity?
├── countedBy?
├── countedAt?
├── revision
├── reconciliationStatus
└── Version
```

Contrainte :

```text
UNIQUE(stock_count_id, product_id)
```

## Reconciliation status

```text
PENDING
RECONCILED
```

Un modèle plus riche n’est introduit que si réellement nécessaire.

Commit :

```text
feat(inventory): add stock count line aggregate
```

---

# 20. Epic 7.10 — CreateStockCount

Command :

```text
CreateStockCount
├── storeId
├── scopeType
├── productIds[]?
└── mode?
```

## FULL

Le périmètre couvre tous les produits stockés/éligibles du store selon la règle de résolution décidée.

## PARTIAL

Le périmètre est une liste explicite de :

```text
ProductId
```

Inventory ne dépend jamais de :

```text
Catalog.Category
```

Une UI pourra résoudre une catégorie en liste de ProductId avant l’appel Inventory.

Commit :

```text
feat(inventory): add create stock count use case
```

---

# 21. Epic 7.11 — StartStockCount

Passage :

```text
DRAFT → OPEN
```

Lors de cette transaction :

1. résoudre le périmètre ;
2. créer toutes les `StockCountLine` ;
3. capturer `expectedQuantity` ;
4. créer les `OpenStockCountScope` ;
5. publier event/outbox.

Pour un produit sans position Stock :

```text
expectedQuantity = 0
```

mais :

```text
ne pas créer automatiquement Stock(quantity=0)
```

Commit :

```text
feat(inventory): start stock count with snapshot
```

---

# 22. Epic 7.12 — OpenStockCountScope

Persistence lock :

```text
OpenStockCountScope
├── OrganizationId
├── StoreId
├── ProductId
└── StockCountId
```

Unicité :

```text
UNIQUE(
    organization_id,
    store_id,
    product_id
)
```

Cela empêche deux comptages ouverts de verrouiller le même produit.

La structure n’est pas un aggregate métier autonome.

Commit :

```text
feat(inventory): lock open stock count scope
```

---

# 23. Mouvement interdit pendant comptage

Tant qu’un produit appartient à un StockCount :

```text
OPEN
ou
FINALIZING
```

interdire tout mouvement affectant sa quantité, notamment :

```text
SALE
SALE_RETURN
PURCHASE_RECEIPT
PURCHASE_RETURN
ADJUSTMENT_IN
ADJUSTMENT_OUT
TRANSFER_OUT
TRANSFER_IN
GOODS_RECEIPT_CORRECTION_IN
GOODS_RECEIPT_CORRECTION_OUT
```

Exception :

```text
STOCK_COUNT_CORRECTION_IN
STOCK_COUNT_CORRECTION_OUT
```

produits par la réconciliation du comptage lui-même.

Le contrôle doit être centralisé dans Inventory.

---

# 24. Epic 7.13 — RecordStockCount

Command :

```text
RecordStockCount
├── stockCountId
├── productId
├── countedQuantity
└── expectedLineVersion
```

ou batch équivalent.

Règles :

```text
StockCount.status = OPEN
countedQuantity >= 0
```

`countedQuantity = 0` valide.

La saisie peut être corrigée tant que :

```text
status = OPEN
```

Chaque correction :

```text
revision += 1
```

Chaque ligne possède sa version indépendante.

Commits :

```text
feat(inventory): record stock count
feat(inventory): support stock count batch entry
```

---

# 25. Blind vs Guided

## BLIND

Le compteur ne voit pas :

```text
expectedQuantity
variance
```

avant validation de sa saisie selon UX/policy.

## GUIDED

Le compteur peut voir la quantité théorique.

La distinction relève de la lecture/API et de la policy, mais ne change pas l’invariant.

Le mode doit être snapshoté dans le `StockCount`.

---

# 26. Epic 7.14 — BeginStockCountFinalization

Préconditions :

```text
status = OPEN
```

et :

```text
toutes les StockCountLine
ont countedQuantity != null
```

Transition :

```text
OPEN → FINALIZING
```

Effet :

- saisies figées ;
- aucune nouvelle correction de countedQuantity ;
- scopes restent verrouillés ;
- lancement de réconciliation par batch.

Annulation interdite à partir de :

```text
FINALIZING
```

Commit :

```text
feat(inventory): begin stock count finalization
```

---

# 27. Epic 7.15 — ReconcileStockCountBatch

Traitement interne :

```text
ReconcileStockCountBatch
```

Pour chaque ligne :

```text
reconciliationStatus = PENDING
```

faire :

```text
1. verify current Stock.quantityOnHand == expectedQuantity

2. variance =
   countedQuantity - expectedQuantity

3. Stock.reconcile(...)

4. if variance > 0:
       STOCK_COUNT_CORRECTION_IN

5. if variance < 0:
       STOCK_COUNT_CORRECTION_OUT

6. if variance = 0:
       no StockMovement

7. update Costing if movement exists

8. line → RECONCILED
```

Le batch doit être transactionnel.

Commit :

```text
feat(inventory): reconcile stock count batch
```

---

# 28. Conflit de snapshot

Lors de finalisation :

```text
Stock.quantityOnHand
must equal
StockCountLine.expectedQuantity
```

Normalement le lock empêche la divergence.

Si elle existe quand même :

```text
STOCK_COUNT_SNAPSHOT_CONFLICT
```

ou erreur équivalente.

Ne jamais écraser silencieusement un état inattendu.

Cela doit déclencher diagnostic/audit technique.

---

# 29. Epic 7.16 — Crash recovery

Pourquoi `FINALIZING` existe :

```text
10 000 lignes
```

ne doivent pas être réconciliées dans une seule transaction gigantesque.

Exemple :

```text
Batch 1 → 500 lines RECONCILED
Batch 2 → 500 lines RECONCILED
crash
```

Après redémarrage :

```text
ne reprendre que PENDING
```

Jamais rejouer les lignes déjà `RECONCILED`.

Quand :

```text
PENDING count = 0
```

alors :

```text
StockCount → COMPLETED
```

puis libérer les scopes.

Commit :

```text
test(inventory): verify stock count crash recovery
```

---

# 30. Epic 7.17 — CompleteStockCountFinalization

Préconditions :

```text
status = FINALIZING
PENDING lines = 0
```

Transaction :

```text
BEGIN

StockCount → COMPLETED
delete/release OpenStockCountScope
append StockCountCompleted
append Outbox

COMMIT
```

Les lignes restent comme preuve du comptage.

Commit :

```text
feat(inventory): complete stock count finalization
```

---

# 31. CancelStockCount

Autorisé :

```text
DRAFT → CANCELLED
OPEN → CANCELLED
```

Interdit :

```text
FINALIZING → CANCELLED
```

Lors d’une annulation depuis `OPEN` :

- supprimer/libérer les `OpenStockCountScope` ;
- conserver le StockCount et ses lignes à des fins historiques ;
- ne créer aucun mouvement.

Commit :

```text
feat(inventory): cancel stock count
```

---

# 32. Epic 7.18 — StockCount Costing

## Variance négative

```text
counted < expected
```

La sortie :

```text
STOCK_COUNT_CORRECTION_OUT
```

est valorisée au :

```text
currentAverageUnitCost
```

comme sortie économique.

## Variance positive

```text
counted > expected
```

La baseline décide :

```text
utiliser currentAverageUnitCost
```

s’il existe.

Si aucun coût moyen n’existe :

```text
manualUnitCost required
```

avec :

```text
INVENTORY_COST_ASSIGN
+
reason
```

Aucun coût arbitraire.

## Journal

Chaque correction physique valorisée produit :

```text
StockValuationMovement
```

lié à son `StockMovement`.

Commit :

```text
feat(costing): value stock count corrections
```

---

# 33. Confidentialité des coûts

Permissions spécifiques déjà décidées dans la baseline :

```text
INVENTORY_COST_VIEW
INVENTORY_VALUE_VIEW
SALES_MARGIN_VIEW
PURCHASING_COST_VIEW
```

Le détail :

```text
averageUnitCost
totalValue
transfer value
manual assigned cost
```

n’est pas automatiquement visible à tout acteur pouvant compter du stock.

Un compteur peut réaliser un inventaire sans permission de voir les coûts.

---

# 34. Permissions StockTransfer

Ajouter :

```text
STOCK_TRANSFER_CREATE
STOCK_TRANSFER_READ
STOCK_TRANSFER_UPDATE_DRAFT
STOCK_TRANSFER_CANCEL
STOCK_TRANSFER_SHIP
STOCK_TRANSFER_RECEIVE
```

Selon policy :

```text
STOCK_TRANSFER_DISCREPANCY_READ
```

---

# 35. Permissions StockCount

Ajouter :

```text
STOCK_COUNT_CREATE
STOCK_COUNT_READ
STOCK_COUNT_START
STOCK_COUNT_RECORD
STOCK_COUNT_FINALIZE
STOCK_COUNT_CANCEL
```

Costing :

```text
INVENTORY_COST_ASSIGN
```

si pas déjà introduite.

---

# 36. Rôles système

Mapping à adapter aux policies existantes.

Exemple :

```text
ORGANIZATION_OWNER
→ all

STORE_MANAGER
→ transfers + counts dans scope

CASHIER
→ aucun transfer/count par défaut
   ou STOCK_COUNT_RECORD uniquement si explicitement décidé

ACCOUNTANT
→ lecture inventory/cost selon permissions
```

Domain ne contient jamais de check :

```text
if role == MANAGER
```

---

# 37. Scope multi-store

Un transfert touche :

```text
sourceStoreId
destinationStoreId
```

L’autorisation doit donc valider le scope nécessaire sur les deux stores selon l’opération.

Exemple :

```text
Create/Ship
→ accès source + destination requis
```

Pour :

```text
Receive
```

la policy peut exiger destination scope, tout en tenant compte du contexte de sécurité global.

Cette règle doit être explicitement testée, pas supposée.

---

# 38. Audit

Auditer au minimum les opérations sensibles :

```text
STOCK_TRANSFER_DISCREPANCY_RESOLUTION
INVENTORY_COST_ASSIGN
STOCK_COUNT_MANUAL_COST_ASSIGNED
```

Selon policy :

```text
large variance approval
```

peut être introduite plus tard.

Le `StockMovement` explique le changement de quantité.

Le Security Audit explique l’usage d’un privilège sensible.

---

# 39. Events StockTransfer

```text
StockTransferCreated
StockTransferShipped
StockTransferReceived
StockTransferCancelled
```

Le `StockTransferReceived` peut inclure un résumé :

```text
lineCount
hasDiscrepancy
```

sans gonfler l’event avec des détails massifs inutilement.

---

# 40. Events StockCount

```text
StockCountCreated
StockCountStarted
StockCountFinalizationStarted
StockCountCompleted
StockCountCancelled
```

Les saisies individuelles ne nécessitent pas forcément un domain event public pour chaque ligne.

Audit de saisie selon policy.

---

# 41. StoreClosure integration

Le Lot 7 branche les vrais blockers finaux.

## Transfer blocker

Un Store est bloqué si lié à un transfert :

```text
SHIPPED
```

non reçu, côté source ou destination selon policy.

Code possible :

```text
STOCK_TRANSFER_IN_TRANSIT
```

## StockCount blocker

Bloquer si :

```text
OPEN
FINALIZING
```

Code :

```text
OPEN_STOCK_COUNT
```

ou codes distincts.

Avec les blockers Lot 6 + Lot 7, `StoreClosure` peut maintenant revérifier :

```text
CashSession OPEN
Purchasing documents open
StockTransfer in transit
StockCount OPEN / FINALIZING
Stock quantity remaining
```

Commit :

```text
feat(inventory): provide transfer and stock count closure blockers
```

---

# 42. API StockTransfer

```text
GET  /api/stock-transfers
POST /api/stock-transfers
GET  /api/stock-transfers/{id}

POST   /api/stock-transfers/{id}/lines
PATCH  /api/stock-transfers/{id}/lines/{lineId}
DELETE /api/stock-transfers/{id}/lines/{lineId}

POST /api/stock-transfers/{id}/ship
POST /api/stock-transfers/{id}/receive
POST /api/stock-transfers/{id}/cancel
```

## Create payload

```json
{
  "sourceStoreId": "...",
  "destinationStoreId": "..."
}
```

## Ship payload

```json
{
  "lines": [
    {
      "lineId": "...",
      "shippedQuantity": "10"
    }
  ]
}
```

## Receive payload

```json
{
  "lines": [
    {
      "lineId": "...",
      "receivedQuantity": "8"
    }
  ]
}
```

---

# 43. API StockCount

```text
GET  /api/stock-counts
POST /api/stores/{storeId}/stock-counts
GET  /api/stock-counts/{id}

POST /api/stock-counts/{id}/start
POST /api/stock-counts/{id}/counts
POST /api/stock-counts/{id}/counts/batch

POST /api/stock-counts/{id}/finalization
POST /api/stock-counts/{id}/cancel
```

Le traitement interne de reconciliation batch n’a pas besoin d’être une API publique utilisateur.

---

# 44. API lecture BLIND

En mode :

```text
BLIND
```

la réponse de saisie ne doit pas exposer :

```text
expectedQuantity
variance
```

avant la politique prévue.

Le backend doit appliquer cette règle.

Ne pas la laisser seulement au frontend.

---

# 45. Error contract

Codes possibles :

```text
STOCK_TRANSFER_NOT_EDITABLE
STOCK_TRANSFER_SAME_STORE
STOCK_TRANSFER_ALREADY_SHIPPED
STOCK_TRANSFER_NOT_SHIPPED
STOCK_TRANSFER_ALREADY_RECEIVED
TRANSFER_SHIPPED_QUANTITY_EXCEEDS_REQUESTED
TRANSFER_RECEIVED_QUANTITY_EXCEEDS_SHIPPED
TRANSFER_INSUFFICIENT_STOCK
TRANSFER_SCOPE_FORBIDDEN

STOCK_COUNT_ALREADY_OPEN_FOR_PRODUCT
STOCK_COUNT_NOT_OPEN
STOCK_COUNT_NOT_FINALIZABLE
STOCK_COUNT_HAS_UNCOUNTED_LINES
STOCK_COUNT_FINALIZING
STOCK_COUNT_SNAPSHOT_CONFLICT
STOCK_COUNT_PRODUCT_LOCKED

INVENTORY_COST_REQUIRED
INVENTORY_COST_ASSIGN_FORBIDDEN

IDEMPOTENCY_CONFLICT
```

---

# 46. Persistence StockTransfer

Tables :

```text
inventory.stock_transfer
inventory.stock_transfer_line
```

Contraintes :

```text
source_store_id != destination_store_id
unique transfer + product
```

Versions pour concurrency.

Indexes :

```text
organization
source store
destination store
status
createdAt
```

RLS.

---

# 47. Persistence StockCount

Tables :

```text
inventory.stock_count
inventory.stock_count_line
inventory.open_stock_count_scope
```

Contraintes :

```text
UNIQUE(stock_count_id, product_id)
```

et :

```text
UNIQUE(
 organization_id,
 store_id,
 product_id
)
```

sur `open_stock_count_scope`.

Index :

```text
status
store
reconciliationStatus
```

RLS sur toutes les données tenant-owned.

---

# 48. Tests Domain StockTransfer

Couvrir :

```text
create transfer
same store rejected
cross-tenant stores rejected
add line
duplicate product rejected
requested quantity > 0
cancel draft
reject cancel shipped
ship
receive
reject second ship
reject second receive
zero shipped line no movement
zero received line no movement
received <= shipped
shipped <= requested
```

Commit :

```text
test(inventory): cover stock transfer lifecycle
```

---

# 49. Tests Transfer Inventory

Cas :

```text
Source Stock = 20
Transfer requested = 10
Ship = 8
Receive = 7
```

Après ship :

```text
source = 12
TRANSFER_OUT = 8
status = SHIPPED
```

Après receive :

```text
destination += 7
TRANSFER_IN = 7
status = RECEIVED
discrepancy = 1
```

Aucun mouvement automatique pour l’unité manquante.

---

# 50. Tests Transfer Costing

Exemple :

```text
Source:
quantity = 10
value = 50 000
avg = 5 000

Ship = 4
```

Après ship :

```text
source quantity = 6
source value = 30 000
transferred value snapshot = 20 000
```

Receive 4 dans destination :

```text
destination receives
quantity +4
value +20 000
```

Receive seulement 3 :

```text
destination receives deterministic value for 3
remaining value stays transit discrepancy
```

Tester les résidus.

Commit :

```text
test(costing): verify stock transfer value transport
```

---

# 51. Tests StockCount lifecycle

Couvrir :

```text
create DRAFT
start OPEN
capture expected quantity
product with no Stock => expected 0
record count
record zero count
update count while OPEN
revision increment
begin finalization
reject uncounted lines
freeze lines FINALIZING
reject cancel FINALIZING
complete after all reconciled
cancel OPEN releases scopes
```

Commit :

```text
test(inventory): cover stock count lifecycle
```

---

# 52. Tests lock

Pendant `OPEN` ou `FINALIZING`, tenter :

```text
sale
goods receipt
adjustment
sale return
purchase return
transfer ship
transfer receive
receipt correction
```

sur un produit verrouillé.

Résultat :

```text
STOCK_COUNT_PRODUCT_LOCKED
```

Même opération sur autre produit :

```text
allowed
```

Commit :

```text
test(inventory): enforce stock count product lock
```

---

# 53. Tests reconciliation

Exemple :

```text
expected = 10
counted = 12
variance = +2
```

Résultat :

```text
STOCK_COUNT_CORRECTION_IN = 2
Stock = 12
```

Exemple :

```text
expected = 10
counted = 7
variance = -3
```

Résultat :

```text
STOCK_COUNT_CORRECTION_OUT = 3
Stock = 7
```

Variance zéro :

```text
no StockMovement
line RECONCILED
```

Commit :

```text
test(inventory): verify stock count reconciliation
```

---

# 54. Tests Costing StockCount

Correction OUT :

```text
value out at current average
```

Correction IN avec avg existant :

```text
value in at current average
```

Correction IN sans avg :

```text
INVENTORY_COST_REQUIRED
```

Puis avec :

```text
INVENTORY_COST_ASSIGN
manualUnitCost
reason
```

réconciliation autorisée.

Commit :

```text
test(costing): verify stock count correction valuation
```

---

# 55. Failure matrix ShipStockTransfer

Injecter erreur :

```text
after line 1 Stock update
after line 1 StockMovement
after source StockValuation
after valuation movement
after transfer status
after Outbox
before COMMIT
```

Résultat :

```text
ROLLBACK TOTAL
```

Aucune expédition partielle.

---

# 56. Failure matrix ReceiveStockTransfer

Même logique :

```text
destination Stock
TRANSFER_IN
destination valuation
transfer RECEIVED
discrepancy
outbox
```

Tout rollback.

---

# 57. Failure matrix StockCount start

Injecter erreur pendant :

```text
snapshot lines
scope locks
StockCount OPEN
outbox
```

Résultat :

```text
aucun lock orphelin
aucun StockCount partiellement ouvert
```

---

# 58. Failure matrix reconciliation batch

Une transaction de batch doit garantir :

```text
Stock
StockMovement
StockValuation
StockValuationMovement
StockCountLine.reconciliationStatus
```

atomiques pour les lignes du batch.

Un crash entre batches est acceptable.

Un crash dans un batch rollback le batch.

---

# 59. Crash recovery test

Créer :

```text
1 200 StockCountLine
batch size = 200
```

Simuler :

```text
4 batches success
crash
```

Après reprise :

```text
800 lines RECONCILED
400 lines PENDING
```

Le système traite uniquement les 400 restantes.

Aucun mouvement doublé.

---

# 60. Concurrency StockCountLine

Deux opérateurs comptent deux produits différents :

```text
→ no contention global
```

Deux opérateurs modifient la même ligne :

```text
ExpectedVersion
→ one succeeds
→ one conflict
```

Le client recharge et revalide.

---

# 61. Tenant & scope tests

Tester :

```text
Tenant B reads transfer A
→ NOT_FOUND
```

```text
Tenant B receives transfer A
→ NOT_FOUND
```

```text
Tenant B reads StockCount A
→ NOT_FOUND
```

```text
Manager scoped Store A
creates transfer A → B without B scope
→ denied according to policy
```

```text
Counter scoped Store A
records count Store B
→ denied
```

RLS réelle avec rôle runtime.

---

# 62. Observabilité

Métriques :

```text
stock_transfer_created_count
stock_transfer_shipped_count
stock_transfer_received_count
stock_transfer_discrepancy_count
stock_transfer_concurrency_conflict_count

stock_count_open_count
stock_count_finalizing_count
stock_count_reconciliation_pending_count
stock_count_variance_count
stock_count_lock_conflict_count
stock_count_reconciliation_failure_count
```

Éviter les ProductId comme labels de métriques.

Utiliser logs/traces pour forte cardinalité.

---

# 63. Démonstration StockTransfer

```text
1. Organization A possède Store A et Store B.

2. Product P suivi.

3. Store A :
   Stock = 20
   avg cost = 5 000
   value = 100 000.

4. Manager crée Transfer A → B.

5. requested = 10.

6. Ship quantity = 8.

7. Store A :
   Stock 20 → 12.
   TRANSFER_OUT = 8.
   value transported = 40 000.

8. Transfer → SHIPPED.

9. Retry Ship :
   aucun nouvel effet.

10. Destination reçoit 7.

11. Store B :
    Stock +7.
    TRANSFER_IN = 7.
    valeur reçue transportée.

12. Transfer → RECEIVED.

13. discrepancy = 1.

14. La quantité manquante n’est pas créée.

15. Retry Receive :
    aucun nouvel effet.

16. Tenant B :
    NOT_FOUND.
```

---

# 64. Démonstration StockCount

```text
1. Store A contient :
   Product A = 12
   Product B = 5
   Product C = 0/non créé selon état.

2. Manager crée StockCount PARTIAL :
   A, B, C.

3. StartStockCount.

4. Snapshots :
   A expected = 12
   B expected = 5
   C expected = 0

5. Scope locks créés.

6. Tentative Sale Product A :
   blocked.

7. Tentative receipt Product B :
   blocked.

8. Mouvement sur Product D hors scope :
   allowed.

9. Compteur saisit :
   A = 11
   B = 5
   C = 2

10. Begin finalization.

11. A :
    variance -1
    STOCK_COUNT_CORRECTION_OUT.

12. B :
    variance 0
    aucun movement.

13. C :
    variance +2
    STOCK_COUNT_CORRECTION_IN.

14. Costing est ajusté.

15. Crash simulé après un batch.

16. Reprise :
    seulement PENDING.

17. Toutes lignes RECONCILED.

18. StockCount → COMPLETED.

19. Locks supprimés.

20. Sale Product A de nouveau autorisable.

21. Historique des lignes conservé.
```

---

# 65. Gate de sortie Lot 7

Le Lot 7 est `DONE` uniquement lorsque :

```text
[ ] StockTransfer aggregate opérationnel
[ ] DRAFT
[ ] SHIPPED
[ ] RECEIVED
[ ] CANCELLED
[ ] source != destination
[ ] stores même tenant
[ ] produit unique par transfert

[ ] requestedQuantity > 0
[ ] shippedQuantity bornée
[ ] receivedQuantity bornée
[ ] zéro conservé sans mouvement nul

[ ] ShipStockTransfer atomique
[ ] TRANSFER_OUT créé
[ ] source Stock décrémenté
[ ] source Valuation décrémentée
[ ] snapshot coût/valeur transportée
[ ] aucune expédition partielle transactionnelle

[ ] ReceiveStockTransfer atomique
[ ] TRANSFER_IN créé
[ ] destination Stock incrémenté
[ ] valeur transférée au destination
[ ] réception finale unique
[ ] aucune réception partielle successive

[ ] discrepancy conservée
[ ] surplus non créé automatiquement
[ ] transit value discrepancy conservée
[ ] transfer idempotent
[ ] transfer concurrency safe

[ ] réception transfert déjà expédié
    autorisable pendant suspension selon baseline

[ ] StockCount aggregate opérationnel
[ ] StockCountLine aggregate séparé
[ ] FULL
[ ] PARTIAL
[ ] BLIND
[ ] GUIDED si activé
[ ] DRAFT
[ ] OPEN
[ ] FINALIZING
[ ] COMPLETED
[ ] CANCELLED

[ ] snapshot expectedQuantity à OPEN
[ ] produit sans Stock => expected 0
[ ] aucun Stock vide créé inutilement
[ ] OpenStockCountScope matérialisé
[ ] unique lock org/store/product

[ ] tous mouvements concurrents bloqués
    pour produits OPEN/FINALIZING
[ ] mouvements hors scope autorisés

[ ] countedQuantity null = non compté
[ ] countedQuantity zero = compté à zéro
[ ] correction saisie possible en OPEN
[ ] line revision/version opérationnelle

[ ] finalization exige toutes lignes comptées
[ ] FINALIZING fige les saisies
[ ] annulation FINALIZING interdite

[ ] reconciliation par batch
[ ] PENDING / RECONCILED
[ ] snapshot conflict détecté
[ ] variance IN correcte
[ ] variance OUT correcte
[ ] variance zero sans mouvement
[ ] Stock.reconcile utilisé

[ ] StockCount costing intégré
[ ] correction OUT au coût moyen
[ ] correction IN au coût moyen si existant
[ ] manual cost protégé si aucun coût
[ ] INVENTORY_COST_ASSIGN
[ ] raison obligatoire
[ ] StockValuationMovement créé

[ ] crash recovery prouvée
[ ] lignes RECONCILED jamais rejouées
[ ] scopes libérés seulement à completion/cancel autorisé

[ ] StoreClosure transfer blocker actif
[ ] StoreClosure stock count blocker actif

[ ] permissions Transfer ajoutées
[ ] permissions StockCount ajoutées
[ ] Store scopes appliqués
[ ] coût protégé par permissions
[ ] audit sensible disponible

[ ] API StockTransfer
[ ] API StockCount
[ ] BLIND protégé backend
[ ] OpenAPI à jour
[ ] error contract stable

[ ] persistence PostgreSQL réelle
[ ] indexes / uniques
[ ] RLS actif
[ ] cross-tenant NOT_FOUND

[ ] domain tests verts
[ ] transfer integration tests verts
[ ] costing transfer tests verts
[ ] stock count tests verts
[ ] lock tests verts
[ ] reconciliation tests verts
[ ] crash recovery tests verts
[ ] rollback matrices vertes
[ ] idempotence tests verts
[ ] concurrency tests verts
[ ] tenant isolation tests verts
[ ] RLS tests verts
[ ] architecture fitness tests verts
[ ] PHPStan vert
[ ] PHP-CS-Fixer vert
[ ] Deptrac vert
[ ] Composer audit vert
[ ] CI verte

[ ] démonstration StockTransfer réussie
[ ] démonstration StockCount réussie

[ ] M3 formellement validé

[ ] aucun Customers
[ ] aucun Credit
[ ] aucun provider Payment
[ ] aucun StockReservation
[ ] aucun Reporting avancé
[ ] aucun Offline
[ ] aucun StockLot/LotTracking

[ ] IMPLEMENTATION_STATUS.md mis à jour
[ ] ADR mis à jour si décision structurante modifiée
```

---

# 66. Gate M3 — Gestion complète du stock

Le jalon M3 est atteint lorsque les Lots 5, 6 et 7 sont tous validés.

Le système sait alors expliquer et valoriser :

```text
INITIAL_STOCK

PURCHASE_RECEIPT
GOODS_RECEIPT_CORRECTION_IN
GOODS_RECEIPT_CORRECTION_OUT
PURCHASE_RETURN

SALE
SALE_RETURN

ADJUSTMENT_IN
ADJUSTMENT_OUT

TRANSFER_OUT
TRANSFER_IN

STOCK_COUNT_CORRECTION_IN
STOCK_COUNT_CORRECTION_OUT
```

Chaque changement :

- possède une cause ;
- respecte le tenant ;
- respecte le store ;
- est idempotent ;
- est transactionnel ;
- conserve un ledger ;
- maintient Stock et Costing cohérents ;
- ne rend jamais le stock négatif ;
- est testé sur PostgreSQL réel.

---

# 67. Ce que M3 permet côté produit

Après Lot 7, Zandu dispose du noyau opérationnel nécessaire à une première vraie interface :

```text
ADMIN
├── Organization
├── Stores
├── Users / permissions
├── Catalog
├── Pricing
├── Suppliers
├── Purchasing
├── Inventory
├── Transfers
├── Stock Counts
├── Cash registers
└── Monitoring opérationnel

POS
├── CashSession
├── Catalog search
├── Cart / Sale
├── CompleteSale cash
├── Receipt
└── Return / Refund essentiel
```

Il devient alors raisonnable de basculer l’effort vers :

```text
Frontend Foundation
→ Admin interface
→ POS interface
```

sans attendre Customers, providers, Reporting avancé ou Offline.

---

# 68. Hors périmètre Lot 7

```text
Customer
CustomerAccount
CustomerReceivable
CreditPolicy

Payment provider
PaymentAttempt
provider callbacks

StockReservation

Reporting projections avancées

Offline
DeviceRegistration
Synchronization

StockLot
LotTracking
Expiration enforcement

Wave picking
Bin locations
Warehouse zones
Advanced WMS
```

---

# 69. Transition après Lot 7

Après validation :

```text
M3 — DONE
```

Étape recommandée :

```text
Frontend Foundation
```

puis deux surfaces :

```text
Zandu Admin
```

et :

```text
Zandu POS
```

Les Lots backend 8+ pourront être repris ensuite sans bloquer la création d’une première expérience utilisateur complète.

---

# 70. Premier point d’entrée d’implémentation

Ordre recommandé :

```text
1. StockTransfer aggregate
2. draft lifecycle
3. ShipStockTransfer
4. Transfer costing
5. ReceiveStockTransfer
6. discrepancy
7. transfer concurrency/idempotence

8. StockCount aggregate
9. StockCountLine
10. Create/Start snapshot
11. OpenStockCountScope
12. record counts
13. Begin FINALIZING
14. reconcile batches
15. costing corrections
16. crash recovery
17. Complete finalization
18. StoreClosure blockers
19. APIs
20. Gate M3
```

Premiers commits :

```text
feat(inventory): add stock transfer aggregate
feat(inventory): add stock transfer draft lifecycle
feat(inventory): ship stock transfer
feat(costing): transfer stock value between stores
feat(inventory): receive stock transfer
test(inventory): verify stock transfer idempotence

feat(inventory): add stock count aggregate
feat(inventory): add stock count line aggregate
feat(inventory): start stock count with snapshot
feat(inventory): lock open stock count scope
feat(inventory): record stock count
feat(inventory): begin stock count finalization
feat(inventory): reconcile stock count batch
feat(costing): value stock count corrections
test(inventory): verify stock count crash recovery
```

---

# 71. Principe de travail

Pour chaque étape :

1. vérifier baseline et ADR ;
2. vérifier l’état réel des Lots 3, 5 et 6 ;
3. ne pas réinventer les Application Contracts ;
4. garder StockTransfer dans Inventory ;
5. utiliser baseQuantity ;
6. transporter la valeur avec le transfert ;
7. ne jamais créer automatiquement la quantité manquante ;
8. garder StockCountLine séparée ;
9. verrouiller seulement le périmètre compté ;
10. préserver zéro vs null ;
11. réconcilier en batches idempotents ;
12. tester crash recovery ;
13. tester concurrence réelle PostgreSQL ;
14. tester les matrices de rollback ;
15. tester RLS et tenant isolation ;
16. exécuter architecture tests, PHPStan, PHP-CS-Fixer, Deptrac et Composer audit ;
17. faire un commit atomique ;
18. mettre à jour `IMPLEMENTATION_STATUS.md` ;
19. valider formellement M3 avant de démarrer le frontend.
