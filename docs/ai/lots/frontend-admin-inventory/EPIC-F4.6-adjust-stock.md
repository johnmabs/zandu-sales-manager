# Epic F4.6 — Adjust stock

**Statut :** Terminé

Envoyer delta signé non nul et raison obligatoire ; coût client uniquement pour
une entrée autorisée. Représenter insuffisance et verrouillage sans calcul local.

Supports : domain API. Source : spécification F4, section 24.

Implémentation : formulaire intégré au détail initialisé, permission
`INVENTORY_ADJUST`, delta et coût conservés comme décimaux exacts, coût exigé
uniquement pour une entrée, erreurs serveur d’insuffisance/verrouillage visibles,
résultat inconnu non rejouable et invalidations coordonnées.
