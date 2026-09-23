# Epic F4.10 — Stock transfer list and details

**Statut :** Terminé

Lister/détailler stores, statut, lignes et écarts en distinguant requested,
shipped et received.

Source : spécification F4, section 28.

Implémentation : contrats stricts et pagination opaque, liste et détail sous
`STOCK_TRANSFER_READ`, magasins source/destination, cycle de statut, dates,
lignes et écarts avec quantités exactes, projection multi-store défensive et
erreurs corrélées.
