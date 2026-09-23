# Epic F4.11 — Create and edit draft transfer

**Statut :** Terminé

Créer source/destination distinctes et gérer les lignes DRAFT avec décimaux
exacts et expectedVersion lorsque requis.

Source : spécification F4, section 29.

Implémentation : création entre deux magasins opérationnels distincts sous
`STOCK_TRANSFER_CREATE`, édition des lignes DRAFT sous `STOCK_TRANSFER_UPDATE`,
quantités décimales exactes, `expectedVersion` obligatoire au PATCH, retrait
confirmé, erreurs corrélées, résultat réseau inconnu non rejoué et caches
multi-store invalidés.
