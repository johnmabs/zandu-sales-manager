# 25. Auth state

Modèle frontend minimal :

```text
AuthState
├── status
├── actor?
├── expiresAt?
└── session metadata?
```

Status :

```text
UNKNOWN
AUTHENTICATING
AUTHENTICATED
UNAUTHENTICATED
REFRESHING
```

Éviter un simple :

```ts
const isLoggedIn = true;
```

qui ne représente pas correctement le bootstrap de session.

---
