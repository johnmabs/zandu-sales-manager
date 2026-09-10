# 83. Questions ouvertes à trancher avant implémentation complète

## OUVERT — Workspace tooling

Choix recommandé :

```text
pnpm workspaces
```

Évaluer besoin réel d’un orchestrateur supplémentaire avant d’introduire Turborepo/Nx.

---

## OUVERT — UI implementation library

Choisir après un petit spike Admin + POS.

Critères :

```text
accessibility
customization
bundle
Next.js
Vite
Tauri
long-term maintenance
```

Ne pas verrouiller le design autour d’un kit trop tôt.

---

## OUVERT — Server-state library

Une solution dédiée est recommandée.

Décision à figer après spike simple.

---

## OUVERT — Form library / schema library

Même logique :

```text
choose one
standardize
```

---

## DÉCIDÉ — Token storage details

Le refresh token utilise un cookie `HttpOnly`, `Secure`, `SameSite=Strict`
same-origin. L'access token reste uniquement en mémoire. Voir ADR-0006.

---

## OUVERT — Admin rendering strategy

Next.js est DÉCIDÉ.

Mais le choix par écran entre :

```text
Server Component
Client Component
SSR
CSR
```

reste une décision d’implémentation.

Comme Symfony reste autorité et que l’Admin est fortement interactif/authentifié, il ne faut pas adopter SSR partout par réflexe.

---
