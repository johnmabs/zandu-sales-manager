# 19. OpenAPI

## DÉCIDÉ

Le backend expose OpenAPI.

## PROPOSÉ

Générer les types et/ou client TypeScript depuis le contrat OpenAPI.

Objectifs :

- réduire duplication DTO ;
- détecter changements de contrat ;
- accélérer intégration ;
- éviter divergence frontend/backend.

Le code généré doit être isolé :

```text
packages/api-client/src/generated/
```

et ne pas être modifié manuellement.

---
