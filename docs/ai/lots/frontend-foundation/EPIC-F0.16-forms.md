# 41. Epic F0.16 — Forms

Standardiser :

```text
field state
validation
backend field errors
dirty state
submission
disabled state
unsaved changes
```

## Validation

Deux niveaux :

```text
client validation
→ UX rapide
```

```text
server validation
→ authoritative
```

Le frontend ne doit pas recopier des invariants complexes pour devenir source de vérité.

---
