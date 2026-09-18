# Epic F4.5 — Initialize stock

**Statut :** Terminé

Initialiser une seule fois avec quantité et coût exacts, confirmation, erreurs
métier, mutation à résultat inconnu non rejouée et invalidations coordonnées.

Supports : domain API, security. Source : spécification F4, section 23.

Implémentation : formulaire intégré au détail d’une position absente, décimaux
exacts conservés jusqu’au contrat API, confirmation explicite, erreurs métier et
de champs corrélées, résultat réseau inconnu non rejouable et invalidation des
projections Stock, mouvements et valorisation.
