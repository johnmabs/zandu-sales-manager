# 24. Epic F0.11 — Authentication

Le Foundation gère :

```text
login
access token lifecycle
refresh token rotation
logout
expired session
invalidated authorizationVersion
```

Le refresh token est transporté par un cookie `HttpOnly`, `Secure`,
`SameSite=Strict` géré par Symfony. Il n’est jamais exposé au JavaScript.
L'access token reste uniquement en mémoire et le bootstrap restaure la session
en appelant l'endpoint de refresh.

---
