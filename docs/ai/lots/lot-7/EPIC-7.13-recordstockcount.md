# 24. Epic 7.13 — RecordStockCount

Command :

```text
RecordStockCount
├── stockCountId
├── productId
├── countedQuantity
└── expectedLineVersion
```

ou batch équivalent.

Règles :

```text
StockCount.status = OPEN
countedQuantity >= 0
```

`countedQuantity = 0` valide.

La saisie peut être corrigée tant que :

```text
status = OPEN
```

Chaque correction :

```text
revision += 1
```

Chaque ligne possède sa version indépendante.

Commits :

```text
feat(inventory): record stock count
feat(inventory): support stock count batch entry
```

---
