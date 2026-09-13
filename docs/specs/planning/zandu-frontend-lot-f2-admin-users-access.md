# Zandu Sales Manager — Frontend Lot F2 : Admin Users & Access

**Version :** 1.0  
**Statut :** Backlog d’implémentation  
**Surface :** Zandu Admin  
**Prérequis :** Frontend Foundation + Gate F1 validés  
**Langue :** Français — identifiants de code en anglais

---

# 1. Objectif du Lot F2

F2 fournit l’interface complète d’administration des accès à une organisation Zandu : invitations, membres, lifecycle des memberships, rôles, attributions et scopes organisation/magasins.

```text
Admin → Access Management → Members → Invitations → Roles
→ Member details → Role assignments → Store scopes
→ Suspend / Reactivate / Revoke
```

Le frontend administre le modèle dont Symfony reste l’autorité ; il ne devient pas un moteur d’autorisation.

# 2. Position dans la roadmap frontend

```text
Frontend Foundation → F1 Admin Stores → F2 Admin Users & Access
→ F3 Admin Catalog & Pricing
```

# 3. Concepts à distinguer

```text
User ≠ OrganizationMembership ≠ Role ≠ RoleAssignment ≠ AccessScope
```

Le `User` est global ; l’accès à une organisation est porté par `OrganizationMembership`. Les rôles ne sont jamais attachés directement au User global. L’UX parle donc de « Membres de l’organisation » ou « Utilisateurs et accès ».

# 4. Source de vérité

Symfony est la source de vérité. Le frontend peut afficher memberships/rôles/scopes, collecter une configuration, soumettre des assignments et afficher l’état effectif. Il ne calcule pas les permissions autoritatives, l’invariant Owner, la légalité d’une attribution ou `authorizationVersion`.

# 5. OrganizationMembership

Le membership contient identifiants organisation/user, statut, assignments, métadonnées, `authorizationVersion` et version.

```text
INVITED → ACTIVE → SUSPENDED → REVOKED
```

Seul `ACTIVE` accorde des permissions. Suspension réversible ; révocation immédiate, terminale et historiquement traçable.

# 6. Role et RoleAssignment

Un Role expose code, type, statut, nom, description, permissions et version. Un RoleAssignment distinct expose RoleId, AccessScope, auteur/date et expiration optionnelle. Scopes : `ORGANIZATION` ou `SELECTED_STORES`.

# 7. Rôles système

`ORGANIZATION_OWNER`, `STORE_MANAGER`, `CASHIER`, `ACCOUNTANT` sont globaux et immuables. Afficher nom, code, permissions et statut, sans Edit/Delete si aucune API ne le permet.

# 8. Invariant Owner

Owner utilise le scope organisation ; un owner actif doit toujours subsister et seul un owner actif peut attribuer/retirer ce rôle. L’UX confirme fortement, mais le backend tranche et peut retourner `DOMAIN_RULE_VIOLATION`.

# 9. authorizationVersion

Toute mutation membership/role/scope/assignment incrémente la version. Un ancien token devient obsolète ; une modification de ses propres accès ou depuis une autre session doit conduire à un état d’authentification sûr.

# 10. Capacités API Memberships

`GET /api/members`, `GET /api/members/{id}` et transitions Suspend/Reactivate/Revoke. La collection est tenant-scoped ; le détail expose version, rôles et scopes sans informations d’authentification globales.

# 11. API Role Assignments

`GET /api/roles`, `POST /api/members/{id}/role-assignments`, `DELETE /api/members/{id}/role-assignments/{assignmentId}`. Les assignments supportent les deux scopes et `expiresAt?`.

# 12. API Invitations

`POST /api/member-invitations` et `POST /api/member-invitations/{id}/cancel`, plus acceptation existante. La création suit le contrat (email, expiration, rôles/scopes). Le secret brut n’est retourné qu’à la création ; son hash n’est jamais exposé.

# 13. Architecture feature proposée

Créer une feature coordonnée `features/access` avec api, components, invitations, members, roles, schemas, hooks, routes et tests. Ne pas isoler artificiellement trois features qui doivent collaborer.

# 14. Epic F2.1 — Access feature foundation

Créer la frontière Access, réutiliser API/Foundation, éviter tout moteur d’autorisation dupliqué et garder les règles d’architecture vertes.

# 15. Epic F2.2 — Access Management navigation

Administration → Accès → Membres, Invitations, Rôles. Les noms de code restent Members/Invitations/Roles.

# 16. Epic F2.3 — Member list

Créer la liste via `GET /api/members`, avec identité publiée, statut, rôles et résumé de scope. Recherche/filtres statut/rôle seulement si les APIs le permettent sans chargement arbitraire côté client.

# 17. Epic F2.4 — Member details

Répondre à : qui, statut, rôles, scopes, Stores, expirations et actions disponibles.

# 18. Epic F2.5 — Role catalog

Afficher rôle, description, permissions, statut et type via `GET /api/roles`, sans inventer de CRUD custom.

# 19. Epic F2.6 — Permission visualization

Maintenir un mapping de présentation PermissionCode → libellé/description. Un code inconnu reste affichable ; ce mapping ne décide jamais d’un accès.

# 20. Epic F2.7 — Invite member

Workflow Members → Invite avec champs strictement issus du contrat : email, rôles, scope, Stores et expiration éventuelle.

# 21. Epic F2.8 — Role selection during invitation

Choisir un ou plusieurs rôles et les portées réellement acceptées par l’API.

# 22. Epic F2.9 — Store scope selector

Réutiliser la récupération Stores de F1. `StoreScopeSelector` construit le payload mais n’accorde aucun droit.

# 23. Epic F2.10 — Invitation success state

Afficher explicitement le succès. Un lien/secret retourné une fois peut être copié avec avertissement uniquement si présent dans la réponse.

# 24. Epic F2.11 — Invitation lifecycle

Lister les invitations seulement si une API de lecture existe. Ne pas inventer Resend, Extend ou Edit.

# 25. Epic F2.12 — Cancel invitation

Confirmation contextualisée, transition API, invalidation des vues et feedback de succès.

# 26. Epic F2.13 — Assign Role

Depuis le membre : Role, Scope, Selected Stores, Expiration, puis POST assignment.

# 27. Epic F2.14 — Scope rules UX

`ORGANIZATION` ne transporte aucun store ; `SELECTED_STORES` requiert les sélections prévues. Le backend valide toujours organisation et ambiguïtés.

# 28. Epic F2.15 — Expiring assignment

Distinguer Sans expiration et Expire le…, conformément aux conventions Foundation et sans confondre timestamp et BusinessDate.

# 29. Epic F2.16 — Remove Role Assignment

Confirmer rôle, scope et Stores affectés avant DELETE.

# 30. Epic F2.17 — Owner assignment protection

Marquer Owner « rôle sensible » et renforcer la confirmation, sans calcul local autoritatif.

# 31. Epic F2.18 — Suspend Membership

Expliquer l’arrêt d’accès, confirmer, rafraîchir membre/liste et réévaluer la session courante si affectée.

# 32. Epic F2.19 — Reactivate Membership

Disponible pour un membership suspendu lorsque le serveur autorise la transition ; rafraîchir les vues.

# 33. Epic F2.20 — Revoke Membership

Action terminale et sensible conservant l’historique. Ne jamais présenter Reactivate après révocation.

# 34. Epic F2.21 — Immediate access invalidation UX

Gérer requête 401 → refresh refusé → état authentifié local vidé → login requis.

# 35. Epic F2.22 — Current-user self-impact

Après auto-modification, recharger l’accès effectif ou suivre l’invalidation Foundation ; ne pas supposer la session valide.

# 36. Epic F2.23 — Permission-aware UI

Utiliser les permissions MEMBER/ROLE pour navigation, actions, disabled states et routes, jamais comme sécurité définitive.

# 37. Epic F2.24 — Scope-aware navigation

Ne pas suggérer un accès organisation-wide à un membre limité ; afficher nombre et détail des Stores.

# 38. Epic F2.25 — RoleAssignment summary

Primitive feature-local : rôle, toute l’organisation ou N magasins, expiration ou sans expiration.

# 39. Epic F2.26 — Status and access badges

MembershipStatusBadge, RoleStatusBadge, ScopeBadge et ExpirationBadge ; aucun état uniquement par couleur.

# 40. Epic F2.27 — Error handling

Traiter VALIDATION_ERROR, UNAUTHENTICATED, FORBIDDEN, NOT_FOUND, CONFLICT, DOMAIN_RULE_VIOLATION et préserver `correlationId`. Une ressource cross-tenant reste 404.

# 41. Epic F2.28 — Last Owner error

Expliquer le refus de suspendre/révoquer/retirer le dernier owner : l’organisation doit garder un propriétaire actif.

# 42. Epic F2.29 — Stale authorization state

Après mutation, invalider détail, liste, rôles si nécessaire et accès effectif courant si concerné.

# 43. Epic F2.30 — Loading / empty states

États explicites pour membres/invitations ; un catalogue système vide est une anomalie.

# 44. Epic F2.31 — Sensitive action confirmations

Suspend, Revoke, Cancel invitation, Remove role et Owner exigent une confirmation nommant action, cible et conséquence.

# 45. Epic F2.32 — Accessibility

Clavier, tables sémantiques, dialogues avec restitution du focus, champs/erreurs associés, annonces lecteur d’écran et états non fondés sur la couleur.

# 46. Epic F2.33 — Responsive Admin

Desktop puis laptop et tablette utilisable ; progressive disclosure pour les listes trop larges.

# 47. Epic F2.34 — Unit/component tests

Couvrir badges, résumés, labels, disponibilité d’actions, Owner sensible, sélecteur de Stores et formulaires.

# 48. Epic F2.35 — Integration tests

Couvrir chargements, invitation valide/invalide, assignments scopes organisation/magasins, retrait, lifecycle membership, refus et violations métier.

# 49. Epic F2.36 — Session invalidation test

Prouver utilisateur authentifié → version changée → ancien token rejeté → refresh échoue → état sûr.

# 50. Epic F2.37 — E2E administration flow

Owner login → invite avec rôle/scope → acceptation par fixture → détail → ajout/retrait rôle → suspend/restart. Scénario séparé : dernier owner refusé et UI cohérente.

# 51. Routing proposé

```text
/admin/access
/admin/access/members
/admin/access/members/{memberId}
/admin/access/invitations
/admin/access/invite
/admin/access/roles
```

`/admin/access` peut rediriger vers Members ; les conventions réelles du routeur prévalent.

# 52. Écrans minimaux

MemberListPage, MemberDetailsPage, InvitationListPage, InviteMemberPage, RoleCatalogPage ; dialogues Assign/Remove Role, Suspend/Reactivate/Revoke Member, Cancel Invitation et StoreScopeSelector.

# 53. Hors scope F2

Administration globale, password/reset/email verification, service accounts, custom-role editor sans API, permission creation, negative permissions, security policy editor, audit viewer, Catalog/Pricing/Inventory/Purchasing/Cash/POS.

# 54. Custom Roles

La DDD prévoit `CUSTOM`, mais Create/Edit/Archive restent hors scope tant que les endpoints ne sont pas explicitement disponibles.

# 55. Audit

Le backend audite les opérations sensibles. Le frontend propage seulement correlation/request context ; il ne produit aucun audit métier parallèle.

# 56. Observability

Tracer feature, opération, route, catégorie de résultat, correlationId et clientVersion. Ne jamais tracer password, JWT, refresh token, secret d’invitation, authorization headers ou credentials.

# 57. Sécurité invitation

Un secret utilisable une fois n’est jamais loggé, analysé ou persisté inutilement. Une copie volontaire peut être proposée si le workflow le requiert.

# 58. Dépendances F1

Réutiliser Store list/query, identifiants/libellés, présentation de scope, OrganizationContext et StoreContext ; ne pas dupliquer la récupération des Stores.

# 59. Ordre d’implémentation recommandé

Implémenter F2.1 à F2.37 dans l’ordre, du socle/navigation aux écrans, mutations, cohérence session, qualité et E2E.

# 60. Stratégie de commits

Commits atomiques `feat(admin): ...` par capacité ; validations consolidées `test(admin): cover access management flows` et `test(admin): verify last owner protection`.

# 61. Gate F2

Le Lot est terminé lorsque la démonstration prouve :

1. accès autorisé à Access Management ;
2. liste des membres ;
3. détail membre ;
4. catalogue des rôles ;
5. permissions lisibles ;
6. création d’invitation ;
7. rôle sélectionnable ;
8. scope ORGANIZATION ;
9. scope SELECTED_STORES ;
10. Stores issus de l’organisation active ;
11. payload ambigu non soumis normalement ;
12. annulation si API disponible ;
13. attribution de rôle ;
14. expiration ;
15. retrait ;
16. UX Owner sensible ;
17. refus dernier owner ;
18. suspension ;
19. réactivation ;
20. révocation ;
21. aucune réactivation après révocation ;
22. aucune UI obsolète après changement de version ;
23. self-impact sûr ;
24. session obsolète ramenée à l’authentification ;
25. action interdite traitée ;
26. ressource cross-tenant introuvable ;
27. erreurs métier corrélées ;
28. loading ;
29. empty ;
30. confirmations accessibles ;
31. navigation clavier ;
32. tests unitaires/composants ;
33. tests intégration ;
34. test invalidation session ;
35. E2E Access ;
36. lint ;
37. typecheck ;
38. build Admin.

# 62. Résultat attendu

Un owner/admin autorisé peut administrer `Invite → Member → Roles → Scopes → Suspend → Reactivate → Revoke`, sans second moteur de sécurité. La chaîne relie organisation, membres, assignments, scopes, autorisation backend, version et cohérence session.

# 63. Étape suivante

F3 Admin Catalog & Pricing couvrira produits, recherche, détails, création/mise à jour/lifecycle, configuration stock, pricing courant/nouveau/historique et visibilité cost/value selon permissions.
