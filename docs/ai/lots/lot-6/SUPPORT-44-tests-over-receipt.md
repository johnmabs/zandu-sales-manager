# 44. Tests over receipt

```text
ordered = 100
received = 90
receipt = 20
```

Sans permission : rejet.

Avec permission + reason + authorizedBy :

```text
physical received = 20
cumulative = 110
audit present
```

Commit :

```text
test(purchasing): verify over receipt authorization
```

---
