# F4 — Support routing et écrans

Préfixe `/admin/inventory` :

- `/positions` et `/positions/{productId}` ;
- `/movements` ;
- `/valuations` ;
- `/transfers`, `/transfers/new`, `/transfers/{id}` ;
- `/counts`, `/counts/new`, `/counts/{id}`.

La navigation n’affiche que les sections lisibles. Les écrans store-scoped
exigent un store actif accessible et conservent Catalog, Costing et Inventory
comme features séparées.

Source : spécification F4, section 12.

