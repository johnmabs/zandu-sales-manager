# Contrat d’erreurs Inventory & Cash

Les endpoints administratifs renvoient un objet JSON stable contenant
`code`, `message` et `correlationId`.

| HTTP | Code | Cas principaux |
|---:|---|---|
| 400 | `VALIDATION_ERROR` | payload, identifiant ou décimale invalide |
| 401 | `UNAUTHENTICATED` | authentification absente |
| 403 | `FORBIDDEN` | permission ou scope Store insuffisant |
| 404 | `NOT_FOUND` | ressource absente ou appartenant à un autre tenant |
| 409 | `CONFLICT` | conflit d’état ou idempotence incompatible |
| 422 | `DOMAIN_RULE_VIOLATION` | règle métier Inventory/Cash violée |

Les codes métier sont transportés dans le champ `message` lorsque la règle
est levée par le domaine ; les ledgers StockMovement et CashMovement restent
immutables et ne disposent d’aucune opération de modification ou suppression.
