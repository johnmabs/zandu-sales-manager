# 28. Epic F0.12 — OrganizationContext

Après authentification, l’utilisateur peut appartenir à une ou plusieurs organizations selon évolution du produit.

Créer un contexte :

```text
OrganizationContext
├── activeOrganizationId
├── organizations
└── status
```

La sélection active doit être explicite.

Ne pas inférer une Organization depuis une route non validée.

---
