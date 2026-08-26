# Inventory bounded context

Ce module porte les stocks et mouvements d’inventaire du Lot 3.

Les dépendances entrantes du domaine Catalog passent exclusivement par des
contrats applicatifs ; le domaine Inventory ne dépend ni de Doctrine/Symfony ni
des agrégats externes.

Les sous-répertoires `Domain`, `Application/Contract`, `Infrastructure` et
`Presentation/Api` sont réservés aux incréments prévus par le planning Lot 3.
