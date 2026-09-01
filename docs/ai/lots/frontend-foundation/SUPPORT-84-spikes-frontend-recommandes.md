# 84. Spikes Frontend recommandés

## Spike F-A — OpenAPI client

Prouver :

```text
Symfony OpenAPI
→ generated TypeScript
→ typed request
→ typed response
→ error decode
```

---

## Spike F-B — JWT refresh

Prouver :

```text
expired access token
→ single refresh
→ concurrent requests resume
→ refresh rotation
→ failed refresh logs out
```

---

## Spike F-C — Permission scopes

Prouver :

```text
Organization scope
Selected Stores scope
navigation filtering
button guard
backend DENIED remains authoritative
```

---

## Spike F-D — Shared UI

Prouver qu’un composant partagé peut être consommé par :

```text
Next.js Admin
+
Vite POS
```

sans dépendance runtime incompatible.

---

## Spike F-E — Tauri boundary

Prouver :

```text
POS React feature
→ platform adapter
→ Tauri implementation
```

et test navigateur sans Tauri.

---
