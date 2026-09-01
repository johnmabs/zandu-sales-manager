# 10. Epic 2.5 — Product packaging & barcode

## Objectif

Permettre de vendre ou acheter un même produit selon différents conditionnements tout en conservant une base quantity cohérente.

---

## Étape 2.5.1 — Ajouter `ProductPackaging`

Modèle :

```text
ProductPackaging
├── ProductPackagingId
├── ProductId
├── code
├── name
├── unitId
├── conversionFactor
├── precision
├── minimumQuantity
├── quantityIncrement
├── allowedForSale
├── allowedForPurchase
├── status
└── Version
```

Statuts :

```text
ACTIVE
INACTIVE
ARCHIVED
```

### Invariants

- appartient à un Product ;
- `conversionFactor > 0` ;
- décimal exact ;
- quantité convertie compatible avec précision métier ;
- `minimumQuantity > 0` ;
- `quantityIncrement > 0` ;
- un packaging utilisé historiquement n’est jamais réinterprété ;
- un changement de facteur significatif conduit à créer un nouveau packaging plutôt qu’à réécrire l’ancien.

### Commit proposé

```text
feat(catalog): add product packaging
```

---

## Étape 2.5.2 — Base packaging

Chaque produit activable doit posséder exactement un packaging de base cohérent avec :

```text
baseUnitId
```

et :

```text
conversionFactor = 1
```

Le packaging de base doit être explicite.

### Validation

```text
Product.baseUnitId
=
BasePackaging.unitId
```

```text
BasePackaging.conversionFactor
=
1
```

### Commit proposé

```text
feat(catalog): enforce base product packaging
```

---

## Étape 2.5.3 — Conversion de quantité

Introduire un service/value object de conversion pure :

```text
baseQuantity
=
enteredQuantity × conversionFactor
```

Aucun arrondi silencieux.

Exemple :

```text
enteredQuantity = 2
packaging = CARTON_24
factor = 24

baseQuantity = 48
```

Tester :

- entiers ;
- décimaux ;
- précision maximum ;
- facteurs non entiers autorisés selon unité ;
- quantités incompatibles ;
- résidus interdits selon précision.

### Commit proposé

```text
feat(catalog): add exact packaging quantity conversion
```

---

## Étape 2.5.4 — Persistence ProductPackaging

Ajouter :

- mapping ;
- migration ;
- contraintes d’unicité ;
- repository si aggregate indépendant techniquement ;
- index ;
- tests PostgreSQL ;
- RLS indirect ou explicite selon mapping choisi.

Éviter une collection Doctrine non bornée si le modèle de persistence devient problématique.

Le design doit rester compatible avec l’utilisation future en snapshots transactionnels.

### Commit proposé

```text
feat(catalog): persist product packaging
```

---

## Étape 2.5.5 — Ajouter les barcodes

Modèle minimal :

```text
ProductBarcode
├── ProductBarcodeId
├── OrganizationId
├── ProductId
├── ProductPackagingId
├── rawBarcode
├── normalizedBarcode
├── status
└── Version
```

Le choix exact aggregate/entity doit respecter les décisions de la baseline et rester cohérent avec l’unicité globale au tenant.

### Invariants

```text
unique(
    organizationId,
    normalizedBarcode
)
```

Le barcode :

- est une string ;
- conserve les zéros initiaux ;
- appartient à un packaging du même Product ;
- appartient au même tenant ;
- n’est jamais interprété comme un nombre.

### Domain events

```text
ProductBarcodeAdded
ProductBarcodeRemoved
```

Si un barcode historique ne doit pas être physiquement supprimé, utiliser un statut/inactivation selon la décision retenue.

### Commit proposé

```text
feat(catalog): add product barcodes
```

---

## Étape 2.5.6 — Barcode resolver

Exposer un contrat applicatif :

```text
BarcodeResolver
```

Entrée :

```text
OrganizationId
Barcode
```

Sortie :

```text
BarcodeResolution
├── ProductId
└── ProductPackagingId
```

Aucune fuite cross-tenant.

Barcode d’un autre tenant :

```text
NOT_FOUND
```

### Commit proposé

```text
feat(catalog): add barcode resolution
```

---

## Definition of Done — Epic 2.5

- ProductPackaging opérationnel ;
- base packaging explicite ;
- conversionFactor exact ;
- aucune quantité arrondie silencieusement ;
- facteur historique protégé ;
- barcode string ;
- zéros initiaux préservés ;
- barcode unique par tenant ;
- résolution barcode tenant-safe ;
- persistence PostgreSQL testée ;
- architecture tests verts.

---
