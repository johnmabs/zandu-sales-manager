# F2 — Support gate et livraison

## Recommended order

Suivre F2.1 à F2.37 dans l’ordre documenté, en livrant chaque capacité avec ses tests directs et sans anticiper d’endpoint absent.

## Gate F2

La démonstration prouve : accès autorisé ; listes/détails membres et rôles ; permissions lisibles ; création/annulation d’invitation ; sélection rôle et scopes organisation/magasins ; payload non ambigu ; attribution avec expiration et retrait ; traitement sensible Owner et refus dernier owner ; suspension/réactivation/révocation ; absence de réactivation après révocation ; cohérence `authorizationVersion`, self-impact et session obsolète ; refus/404 cross-tenant/erreurs corrélées ; loading/empty ; confirmations accessibles et clavier.

La livraison exige tests unitaires/composants, intégration, invalidation de session et E2E au vert, puis lint, typecheck et build Admin.

## Commits

Conserver des commits atomiques `feat(admin): ...` par capacité et `test(admin): ...` pour les preuves consolidées.
