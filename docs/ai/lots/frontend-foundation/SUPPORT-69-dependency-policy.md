# 69. Dependency policy

Le Foundation doit éviter l’empilement de librairies pour la même responsabilité.

Exemple interdit :

```text
3 form libraries
2 query libraries
multiple competing UI kits
```

Chaque dépendance structurante doit justifier :

```text
problem solved
maintenance
bundle impact
desktop compatibility
Next.js compatibility
Tauri compatibility
```

---
