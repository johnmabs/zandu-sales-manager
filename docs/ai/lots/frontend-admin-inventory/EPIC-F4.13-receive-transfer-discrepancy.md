# Epic F4.13 — Receive transfer and discrepancy

**Statut :** Terminé

Saisir la réception finale, afficher les écarts et ne jamais créer localement la
quantité manquante.

Source : spécification F4, section 31.

## Implémentation livrée

- opération API typée avec quantités décimales exactes, `Idempotency-Key`
  obligatoire et projection défensive sur le magasin destination ;
- formulaire réservé aux transferts `SHIPPED` sous
  `STOCK_TRANSFER_RECEIVE` sur la destination, avec confirmation explicite ;
- quantité reçue positive ou nulle par ligne, initialisée à l’expédié sans
  calculer ni ajouter localement une quantité manquante ;
- même clé conservée pour le rejeu explicite d’un résultat inconnu ;
- quantités reçues, indicateur et écarts de transit affichés exclusivement
  depuis la réponse serveur ;
- transfert, positions, mouvements et valorisations destination invalidés
  après succès ou résultat inconnu ;
- contrats d’intégration et composants couvrant scope, exactitude, écart,
  erreurs corrélées et idempotence.
