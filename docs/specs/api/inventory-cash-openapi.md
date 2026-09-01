# OpenAPI Inventory & Cash

Les ressources Inventory et Cash sont découvertes par API Platform depuis
leurs namespaces `Modules/*/Presentation/Api`. Les contrats exposent les
quantités et montants décimaux comme chaînes afin d’éviter toute perte de
précision JSON.

Les opérations sont limitées aux actions métier prévues : Stock (lecture,
initialisation, ajustement et mouvements en lecture seule), CashRegister
(gestion et transitions), CashSession (ouverture, lecture, fermeture) et
CashMovement (lecture par session, cash-in, cash-out, retrait).

Toutes les ressources sont tenant-aware, appliquent le scope du store et
utilisent le contrat d’erreurs standard. Les ledgers ne proposent ni PATCH ni
DELETE et les commandes rejouables conservent leur sémantique idempotente.
