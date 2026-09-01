# 11. Epic F0.4 — TypeScript conventions

Configurer un socle partagé :

```text
tsconfig.base.json
```

Principes :

```text
strict = true
no implicit any
no unchecked blind casting
no duplicated API entity models when generated contract exists
```

Éviter :

```ts
const response: any = ...
```

et :

```ts
as SomeType
```

comme stratégie normale de validation réseau.

Commit :

```text
build(frontend): configure strict TypeScript
```

---
