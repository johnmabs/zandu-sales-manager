# Epic F4.14 — Cancel transfer and multi-store scope

**Statut :** Terminé

Annuler un DRAFT avec raison et représenter les règles d’accès source/destination
sans les réimplémenter dans le client.

Support : security costs. Source : spécification F4, section 32.

## Implémentation livrée

- opération API typée d’annulation avec motif normalisé et réponse `CANCELLED`
  complète, projetée défensivement sur le magasin source ;
- formulaire réservé aux brouillons sous `STOCK_TRANSFER_CANCEL` sur la source,
  motif obligatoire de 1 à 500 caractères et confirmation explicite ;
- résultat inconnu non rejoué aveuglément, détail revalidé et corrélation
  conservée ;
- détail et listes source/destination actualisés après annulation ;
- matrice de scope alignée sur le serveur : lecture par l’un des magasins,
  annulation sur la source, réception sur la destination et expédition sur les
  deux ;
- tests d’intégration et composants couvrant contrat, erreurs, caches et refus
  de scope sans réimplémenter les règles métier serveur.
