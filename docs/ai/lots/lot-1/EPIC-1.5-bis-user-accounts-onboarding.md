# 9 bis. Epic 1.5 bis — User accounts & onboarding

## Objectif

Combler le prérequis d'identité absent du cadrage initial : permettre à une
personne sans compte de devenir le premier owner d'une organisation ou de
rejoindre une organisation depuis une invitation.

## Parcours livrés

```text
POST /api/auth/register
POST /api/auth/invitations/{token}/register
POST /api/auth/login
```

- compte `User` global identifié par un email canonique unique ;
- mot de passe haché avec Argon2id et jamais persisté en clair ;
- inscription atomique du premier utilisateur, de son organisation, de son
  membership actif et de son rôle `ORGANIZATION_OWNER` ;
- inscription atomique d'un invité sans compte, avec validation de l'email et
  consommation à usage unique du token ;
- authentification des comptes persistés avec résolution du membership actif ;
- organisation par défaut conservée pour établir le contexte tenant initial ;
- identité `User` globale, hors RLS tenant conformément à l'ADR-0018 ; son accès
  SQL runtime actuel doit être réduit avant la production.

Le choix ou changement d'organisation pour un utilisateur multi-organisation,
la récupération de mot de passe et la vérification d'adresse email restent des
sujets d'authentification ultérieurs. Ils ne sont pas déclarés livrés par cet
Epic correctif.

## Definition of Done — Epic 1.5 bis

- un visiteur peut créer son compte et sa première organisation ;
- une personne invitée sans compte peut s'inscrire avec son token ;
- les deux profils peuvent ensuite obtenir un JWT via `/api/auth/login` ;
- la création du compte et de ses accès initiaux est transactionnelle ;
- les parcours sont couverts par des tests API et PostgreSQL réels.

---
