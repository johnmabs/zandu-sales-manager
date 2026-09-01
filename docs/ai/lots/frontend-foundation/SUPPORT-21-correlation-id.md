# 21. Correlation ID

Lorsque pertinent, le client doit pouvoir :

- recevoir le correlation id serveur ;
- l’inclure dans les écrans d’erreur/support ;
- le transmettre dans les logs frontend sans exposer de secret.

Exemple UI :

```text
Une erreur est survenue.
Référence : 01J...
```

utile pour diagnostic.

---
