# F1 — Support routing et écrans

## Routes proposées

```text
/admin/stores
/admin/stores/new
/admin/stores/{storeId}
/admin/stores/{storeId}/edit
```

Les conventions finales du Foundation prévalent si son routeur établit une forme différente. Suspension, réactivation et demande de fermeture sont de préférence des actions/dialogues depuis le détail.

## Minimum attendu

- `StoreListPage`
- `StoreDetailsPage`
- `CreateStorePage`
- `EditStorePage`
- `SuspendStoreDialog`
- `ReactivateStoreDialog`
- `RequestStoreClosureDialog`
- `StoreClosureStatus`
- `StoreClosureBlockers`
