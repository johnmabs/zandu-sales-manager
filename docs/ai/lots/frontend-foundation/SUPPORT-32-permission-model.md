# 32. Permission model

Ne jamais faire :

```ts
if (user.role === 'MANAGER') {
    showButton();
}
```

Utiliser :

```ts
can('PRODUCT_CREATE')
```

ou :

```ts
can('STOCK_TRANSFER_SHIP', {
  storeId
})
```

Les rôles ne doivent pas devenir la logique applicative frontend.

---
