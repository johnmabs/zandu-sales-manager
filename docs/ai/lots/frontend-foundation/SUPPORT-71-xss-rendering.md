# 71. XSS / rendering

Par défaut :

```text
React escaping
```

Aucun HTML métier arbitraire ne doit être rendu via :

```ts
dangerouslySetInnerHTML
```

sans justification et sanitization explicite.

---
