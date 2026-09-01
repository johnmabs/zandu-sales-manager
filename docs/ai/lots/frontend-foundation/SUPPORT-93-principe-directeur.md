# 93. Principe directeur

Le Frontend Foundation ne doit pas devenir un deuxième projet d’architecture interminable.

Sa réussite se mesure à une chose :

> permettre à une feature réelle de traverser proprement Login → Authorization → API → UI → Mutation → Error handling → Tests.

Dès que le vertical slice `Stores` prouve cette chaîne, il faut arrêter d’élargir le Foundation et commencer à livrer les interfaces métier.
