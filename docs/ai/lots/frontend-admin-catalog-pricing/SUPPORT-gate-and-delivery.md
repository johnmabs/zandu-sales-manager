# F3 — Support gate et livraison

## Prérequis et ordre

Frontend Foundation et Gates F1/F2 validés sont requis avant l’implémentation.
Consulter IMPLEMENTATION_STATUS.md ; ajouter le backlog ne valide pas ces Gates.
Suivre F3.1 à F3.43 dans l’ordre recommandé, avec tests directs par capacité.
La roadmap ne justifie pas de charger les Lots précédents en entier.

## Gate F3

1. Catalog navigation works
2. Product list loads
3. Search works server-side
4. Product filters work
5. Product can be created
6. Product can be updated
7. lifecycle transitions work
8. immutable fields are represented correctly
9. Categories load
10. Category can be created
11. Category can be moved
12. invalid hierarchy is handled safely
13. Packaging list loads
14. Packaging can be created
15. commercial fields can be updated
16. conversionFactor immutability is respected
17. packaging can be deactivated/archived
18. barcode can be added
19. leading zeros are preserved
20. barcode can be removed
21. duplicate/conflict is handled
22. Price Lists load
23. Price List can be created
24. Price List can be updated
25. activation/deactivation/archive work
26. Product Prices load
27. Product Price can be created
28. Product + Packaging relationship is valid
29. exact Money handling is preserved
30. Product Price lifecycle works
31. Effective Price resolves from server
32. no-price state is distinct from zero price
33. cache invalidation keeps displayed price fresh
34. Catalog permissions behave correctly
35. Pricing permissions behave correctly
36. cross-tenant resources remain NOT_FOUND
37. business errors preserve correlationId
38. loading states work
39. empty states work
40. accessibility checks pass
41. unit/component tests pass
42. integration tests pass
43. Catalog E2E passes
44. Pricing E2E passes
45. lint passes
46. typecheck passes
47. Admin build passes

## Commits proposés

```text
feat(admin): add catalog feature foundation
feat(admin): add product administration
feat(admin): add category administration
feat(admin): add product packaging management
feat(admin): add barcode management
feat(admin): add pricing feature foundation
feat(admin): add price list administration
feat(admin): add product price administration
feat(admin): display effective product prices
test(admin): cover catalog administration
test(admin): cover pricing administration
test(admin): verify catalog pricing vertical slice
```

F3.40 couvre les primitives/composants ; F3.41 les contrats et mutations ;
F3.42 le parcours Catalog complet ; F3.43 le parcours Pricing complet.
Conserver les preuves d’exécution dans IMPLEMENTATION_STATUS.md, sans confondre découverte et réussite E2E.

Source : `docs/specs/planning/zandu-frontend-lot-f3-admin-catalog-pricing.md`, sections 54–57, 63–65.
