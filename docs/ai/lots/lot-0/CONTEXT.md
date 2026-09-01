# Zandu Sales Manager — Lot 0 : Architecture exécutable

**Version :** 1.0  
**Statut :** Backlog d’implémentation  
**Langue :** Français — identifiants de code en anglais

---
# 1. Objectif du Lot 0

Le Lot 0 construit le socle technique exécutable de Zandu Sales Manager.

Il ne vise pas encore à implémenter l’ensemble des modules métier. Son rôle est de :

- matérialiser le modular monolith dans le code ;
- rendre les frontières DDD vérifiables automatiquement ;
- mettre en place PostgreSQL, Doctrine et les migrations ;
- construire les primitives techniques partagées nécessaires ;
- rendre l’API et l’authentication opérationnelles ;
- valider les choix sensibles par des spikes reproductibles ;
- mettre en place un socle minimal d’exploitation et d’observabilité ;
- produire les ADR ou mises à jour d’ADR découlant des résultats.

Le Lot 0 est terminé uniquement lorsque son gate de sortie est satisfait.

---
# 2. Règle de commits

Le développement du Lot 0 suit une logique de **commits atomiques**.

Un commit doit :

- représenter une seule intention cohérente ;
- laisser le repository dans un état valide ;
- ne pas mélanger refactoring, feature et configuration sans nécessité ;
- inclure les tests directement liés à la modification ;
- éviter les commits du type `misc`, `changes`, `fix stuff` ou `wip` dans l’historique final.

## 2.1 Convention de message

Format recommandé :

```text
<type>(<scope>): <description>
```

Types principaux :

```text
feat      nouvelle capacité
fix       correction
refactor  restructuration sans changement fonctionnel
test      ajout ou adaptation de tests
build     système de build / dépendances
ci        pipeline CI
chore     maintenance technique
docs      documentation
perf      optimisation
```

Exemples :

```text
build(backend): initialize Symfony application
chore(docker): add local PostgreSQL service
test(architecture): enforce Domain dependency rules
feat(identity): add UUID v7 generator abstraction
feat(api): add correlation id middleware
```

Les messages restent en anglais, comme les identifiants de code.

---
# 3. Vue d’ensemble

```text
Epic 0.1 — Repository foundation
       ↓
Epic 0.2 — Architecture fitness tests
       ↓
Epic 0.3 — Persistence foundation
       ↓
Epic 0.4 — SharedKernel foundation
       ↓
Epic 0.5 — API foundation
       ↓
Epic 0.6 — Authentication foundation
       ↓
Epic 0.7 — Architectural spikes
       ↓
Epic 0.8 — Operations & observability
       ↓
Lot 0 Gate
```

---


## Dependencies

Dependencies are capability-based. Follow dependencies explicitly named by the current Epic or support file; do not infer dependencies from Lot numbering.


## Source of truth

`docs/specs/planning/zandu-lot-0-architecture-executable.md`

Open this complete specification only when the targeted AI files do not answer a required business question.
