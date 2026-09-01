# 9 ter. Epic 1.5 ter — Authentication security hardening

## Objectif

Supprimer les faiblesses d'authentification qui influencent la suite du
développement avant d'exposer l'administration opérationnelle.

## Definition of Done — Epic 1.5 ter

- aucun compte bootstrap n'est disponible en production ;
- les secrets de production sont obligatoires et injectables au runtime ;
- les endpoints publics d'authentification sont rate-limited ;
- les erreurs d'invitation invalides ont un contrat public stable ;
- le modèle d'identité globale et d'organisation active est documenté ;
- l'image de production passe le smoke test avec des secrets runtime.

La séparation physique des identités PostgreSQL de migration, d'authentification
et de runtime demeure obligatoire avant la production. Elle sera traitée avec
la configuration réelle de la plateforme de déploiement, sans introduire de
mot de passe runtime statique dans les migrations.

---
