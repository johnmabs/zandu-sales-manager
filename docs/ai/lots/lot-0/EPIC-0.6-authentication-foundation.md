# 9. Epic 0.6 — Authentication foundation

## Objectif

Fournir une authentication MVP compatible avec le futur modèle d’authorization.

## Étape 0.6.1 — Symfony Security

### Commit proposé

```text
build(auth): configure Symfony Security
```

---

## Étape 0.6.2 — JWT access token

Installer et configurer Lexik JWT.

### Commit proposé

```text
feat(auth): add JWT access token authentication
```

---

## Étape 0.6.3 — Refresh token session

Créer une session de refresh stateful avec persistance serveur.

### Commit proposé

```text
feat(auth): add stateful refresh token sessions
```

---

## Étape 0.6.4 — Rotation des refresh tokens

Un refresh token utilisé est remplacé. La réutilisation d’un token obsolète doit être détectable.

### Commit proposé

```text
feat(auth): rotate refresh tokens on use
```

---

## Étape 0.6.5 — Révocation

Supporter logout/révocation de session.

### Commit proposé

```text
feat(auth): add refresh session revocation
```

---

## Étape 0.6.6 — ActorContext

Construire `ActorContext` depuis l’identité authentifiée ; ne jamais faire confiance à un `actorId` fourni arbitrairement dans le payload métier.

### Commit proposé

```text
feat(auth): resolve ActorContext from authenticated identity
```

---

## Definition of Done — Epic 0.6

- login valide/invalide testé ;
- access token fonctionnel ;
- refresh fonctionnel ;
- rotation prouvée ;
- réutilisation interdite selon la stratégie retenue ;
- révocation fonctionnelle ;
- `ActorContext` dérivé côté serveur.

---
