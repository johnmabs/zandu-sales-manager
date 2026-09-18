# Epic F4.9 — Cost confidentiality

**Statut :** Terminé

Protéger coûts, valeurs et attributions dans vues, erreurs, exports et skeletons
selon le contrat serveur réel : `INVENTORY_READ` pour la consultation et les
permissions d’opération dédiées pour initialiser ou attribuer un coût. Toute
future séparation de la visibilité exige d’abord une décision backend.

Support : security costs. Source : spécification F4, section 27.

Implémentation : politique centrale fail-closed alignée sur les permissions
serveur, vues de valorisation masquées sans `INVENTORY_READ`, permissions
d’opération distinctes, révocation immédiate dans le rendu, erreurs et états de
chargement sans montant confidentiel, sans export de coût exposé.
