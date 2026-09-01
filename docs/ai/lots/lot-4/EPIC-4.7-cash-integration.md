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
