# 20. API boundary

Structure possible :

```text
api-client/
├── generated/
├── transport/
├── errors/
└── resources/
```

Exemple :

```ts
salesApi.completeSale(...)
```

plutôt que :

```ts
generatedClient.someGeneratedOperationName(...)
```

partout dans l’UI.

Une petite façade stable peut protéger les features des changements du générateur.

---
