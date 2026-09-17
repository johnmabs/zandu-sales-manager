# Epic F4.3 — Stock positions workspace

**Statut :** Terminé

Lister et filtrer les positions côté serveur, avec métadonnées Produit obtenues
par contrat public. Conserver ordre et pagination serveur.

Implémenté par composition des pages serveur Stock et Product : les filtres de
métadonnées sont transmis à Catalog, l’ordre Stock n’est jamais retrié et les
quantités restent des chaînes décimales exactes jusqu’au formatage d’affichage.

Supports : domain API, routing. Source : spécification F4, section 21.
