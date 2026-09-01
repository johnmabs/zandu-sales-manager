# 60. Tauri boundary

Aucune feature React ne doit dépendre directement partout des APIs Tauri.

Prévoir une façade :

```text
DesktopPlatform
```

ou services ciblés :

```text
PrinterService
ScannerService
LocalStorageService
WindowService
```

selon les besoins futurs.

Cela garde le POS testable en navigateur.

---
