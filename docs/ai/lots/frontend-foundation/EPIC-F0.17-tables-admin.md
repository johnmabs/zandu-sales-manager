# 43. Epic F0.17 — Tables Admin

Créer une abstraction adaptée aux besoins récurrents :

```text
pagination
sorting
filters
loading
empty
error
row actions
bulk actions later
```

Ne pas imposer une DataTable géante universelle à toutes les features.

Convention :

```text
URL
```

peut porter :

```text
page
sort
filters
search
```

pour permettre refresh/share/back.

---
