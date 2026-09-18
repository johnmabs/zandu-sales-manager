# Epic F4.7 — Stock movement history

**Statut :** Terminé

Afficher le ledger append-only, filtres et pagination serveur ; dériver le sens
du type sans mutation historique.

Source : spécification F4, section 25.

Implémentation : ledger en lecture seule dans l’ordre serveur, filtre Produit
via l’endpoint dédié, pagination par curseur opaque, quantités exactes, sens
dérivé du type et erreurs corrélées sans mutation historique.
