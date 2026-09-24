# Epic F4.12 — Ship transfer

**Statut :** Terminé

Confirmer et expédier atomiquement les quantités, représenter insuffisance et
idempotence, puis rafraîchir les stocks source.

Source : spécification F4, section 30.

## Implémentation livrée

- opération API typée avec payload décimal exact, `Idempotency-Key` obligatoire
  et projection défensive sur les deux magasins ;
- formulaire DRAFT sous `STOCK_TRANSFER_SHIP` pour la source et la destination,
  confirmation explicite et quantité expédiée par ligne, zéro inclus ;
- même clé conservée pour le rejeu explicite d’un résultat inconnu, tandis
  qu’un résultat connu ouvre une nouvelle intention ;
- erreurs de stock, état, concurrence et valorisation représentées avec leur
  `correlationId` ;
- détail et listes de transfert, positions, mouvements et valorisations source
  invalidés après succès ou résultat inconnu ;
- contrats d’intégration et composants couvrant exactitude, scope,
  insuffisance et idempotence.
