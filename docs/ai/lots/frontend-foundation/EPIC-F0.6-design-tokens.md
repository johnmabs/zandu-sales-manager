# 13. Epic F0.6 — Design tokens

Créer :

```text
packages/design-tokens
```

Contenir uniquement les primitives visuelles stables :

```text
spacing
radius
typography
breakpoints
z-index
semantic colors/tokens
```

Ne pas coupler les tokens à une feature métier.

Objectif :

```text
Admin
+
POS
```

partagent l’identité Zandu, même si leurs composants et densités diffèrent.

Commit :

```text
feat(ui): add shared design tokens
```

---
