# 34. Can component / hook

API possible :

```ts
const allowed = useCan('STORE_UPDATE', {
  storeId,
});
```

ou composant :

```tsx
<Can permission="STORE_UPDATE" storeId={storeId}>
  <EditStoreButton />
</Can>
```

Le hook doit considérer :

```text
permission
+
scope
```

---
