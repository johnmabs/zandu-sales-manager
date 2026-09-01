# 23. ErrorMapper

Créer une couche :

```text
ErrorMapper
```

ou abstraction équivalente.

Entrée :

```text
ApiError
```

Sortie :

```text
UiError
├── title
├── message
├── fieldErrors?
├── correlationId?
├── retryable
└── action?
```

Les features peuvent enrichir le mapping pour leurs codes métier.

---
