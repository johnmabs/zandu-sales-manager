# Epic F1.9 — Store Closure blockers

## Objective

Présenter les blockers renvoyés par la réponse StoreClosure sans les recalculer.

## Requirements

- Mapper les codes API connus vers des textes utiles.
- Les codes et libellés doivent suivre le contrat réel.
- Un code inconnu produit un message générique sûr, reste diagnosticable et ne fait pas crasher l’UI.
- Ne pas implémenter les écrans métier des modules à l’origine des blockers.
