# 51. Tests StockCount lifecycle

Couvrir :

```text
create DRAFT
start OPEN
capture expected quantity
product with no Stock => expected 0
record count
record zero count
update count while OPEN
revision increment
begin finalization
reject uncounted lines
freeze lines FINALIZING
reject cancel FINALIZING
complete after all reconciled
cancel OPEN releases scopes
```

Commit :

```text
test(inventory): cover stock count lifecycle
```

---
