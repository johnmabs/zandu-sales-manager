# 14. Scénario de démonstration du Lot 1

Le Lot 1 doit pouvoir démontrer le workflow suivant de bout en bout :

```text
1. User A s’authentifie.

2. User A crée Organization Alpha.

3. User A devient ORGANIZATION_OWNER
   de Organization Alpha.

4. User A crée Store Brazzaville Centre.

5. User A crée Store Poto-Poto.

6. User A invite User B.

7. User B accepte l’invitation.

8. User A attribue à User B :
   role = STORE_MANAGER
   scope = SELECTED_STORES
   stores = [Brazzaville Centre]

9. User B modifie Brazzaville Centre.
   → SUCCESS

10. User B tente de modifier Poto-Poto.
    → DENIED

11. User A suspend User B.
    → accès immédiatement refusé.

12. User A réactive User B.
    → accès rétabli.

13. User A tente de retirer son propre rôle
    alors qu’il est le seul owner actif.
    → DENIED

14. Un second owner est ajouté.

15. Le premier owner peut alors être retiré.

16. Tous les domain events, security audits,
    correlation IDs et outbox messages requis existent.

17. Aucun accès cross-tenant n’est possible.
```

---
