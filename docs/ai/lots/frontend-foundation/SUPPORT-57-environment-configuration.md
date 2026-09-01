# 57. Environment configuration

Variables publiques minimales :

```text
API_BASE_URL
APP_ENV
observability configuration if required
```

Ne jamais exposer :

```text
JWT signing secret
database credentials
provider secrets
```

dans les bundles frontend.

Validation des variables au démarrage/build.

---
