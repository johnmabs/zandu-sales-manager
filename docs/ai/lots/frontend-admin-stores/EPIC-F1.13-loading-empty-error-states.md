# Epic F1.13 — Loading, empty and error states

## Objective

Donner à chaque écran un état explicite.

## Requirements

- Loading : skeleton de page/table et actions pending, jamais d’écran blanc.
- Empty : expliquer ce qui manque, son importance et l’action autorisée possible.
- Errors : utiliser `ApiError`/`ErrorMapper` du Foundation pour permission, métier, réseau et inattendu.
- Maintenir le `correlationId` accessible pour le diagnostic sans exposer de secret.
