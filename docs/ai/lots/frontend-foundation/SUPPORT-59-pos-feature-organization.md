# 59. POS feature organization

```text
apps/pos/src/
├── app/
├── features/
│   ├── auth/
│   ├── terminal/
│   ├── cash-session/
│   ├── product-search/
│   ├── cart/
│   ├── checkout/
│   ├── receipt/
│   └── returns/
├── platform/
│   └── tauri/
└── components/
```

Le dossier :

```text
platform/tauri
```

isole les capacités desktop.

---
