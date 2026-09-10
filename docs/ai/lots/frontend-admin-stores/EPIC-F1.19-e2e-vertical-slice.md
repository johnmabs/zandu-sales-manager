# Epic F1.19 — E2E vertical slice

## Main scenario

```text
Login → Organization context → Store list → Create Store
→ Store details → Update → Suspend → Reactivate → Request Closure
```

## Authorization scenario

Un utilisateur sans permission rencontre une route/action protégée ; tout refus backend est géré et aucun état UI n’est corrompu.
