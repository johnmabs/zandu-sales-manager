# F1 — Support gate et livraison

## Recommended order

F1.1 foundation → F1.2 list → F1.3 details → F1.4 create → F1.5 update → F1.6 suspend → F1.7 reactivate → F1.8 request closure → F1.9 blockers → F1.10 cancel if API available → F1.11 permissions → F1.12 context sync → F1.13 states → F1.14 safety → F1.15 responsive → F1.16 accessibility → F1.17 unit/component → F1.18 integration → F1.19 E2E → F1.20 observability.

## Gate F1

La démonstration doit prouver : login et organisation résolus ; liste et détail chargés ; création visible dans la liste ; mise à jour sans modification des champs immuables ; suspension et réactivation ; demande, statut et blockers de fermeture ; permissions et scopes respectés ; loading, empty, erreurs métier/réseau et `correlationId` corrects ; `StoreContext` resynchronisé ; aucun invariant métier dupliqué.

La livraison exige également : tests unitaires/composants, intégration et E2E au vert, puis lint, typecheck et build Admin au vert.

## Commits

Conserver des commits atomiques par capacité (`feat(admin): ...`) et séparer les validations consolidées (`test(admin): ...`). Chaque commit inclut les tests directement liés et laisse le dépôt valide.
