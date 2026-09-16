# Variante site — ETS Mon Toit

Dernière mise à jour : 16 septembre 2026.

## Identité de la variante

- Site : `https://ets-mon-toit.fr/`
- Dépôt source commun : `mrkv2/KV2-Portfolio-Studio`
- Branche dédiée : `site/ets-mon-toit`
- Version de départ de la branche : `1.2.3-emt.1`
- Version actuellement signalée sur le site au 16/09/2026 : `1.2.0`
- Profil métier : Couverture et zinguerie

Cette branche est la variante de KV2 Portfolio Studio dédiée à ETS Mon Toit. Elle doit pouvoir évoluer selon les besoins réels du site sans imposer ces choix aux autres installations du plugin.

## Base technique

La branche `site/ets-mon-toit` a été créée depuis `main` le 16/09/2026. Elle hérite donc des corrections présentes dans `main` à cette date, notamment le correctif de collision REST de la taxonomie `kv2_ville`.

Le correctif conserve la clé interne `kv2_ville`, mais affecte à la taxonomie la route REST distincte :

```php
$args['rest_base'] = 'kv2_ville_terms';
```

Ce garde-fou est requis lorsqu'un autre plugin ou module du site expose également un Custom Post Type `kv2_ville`. Sans séparation des routes REST, Gutenberg peut recevoir des objets de type post au lieu de termes et provoquer une erreur JavaScript lors de la sélection d'une ville.

## Objectifs propres à ETS Mon Toit

Priorités fonctionnelles de cette variante :

1. conserver le profil Couverture et zinguerie introduit en 1.2.0 ;
2. fiabiliser la saisie des villes et départements dans les réalisations ;
3. permettre l'ajout de courtes vidéos de chantier dans les études de cas ;
4. conserver les photos avant/après comme médias SEO principaux ;
5. privilégier une interface adaptée aux chantiers de couverture, étanchéité, zinguerie et toiture-terrasse ;
6. ne pas imposer les besoins propres à ETS Mon Toit aux variantes Tapisserie ou aux autres sites.

## Vidéos de chantier — cible fonctionnelle

La version 1.2.x ne permet de sélectionner que des images dans les galeries d'administration. La future évolution ETS Mon Toit doit ajouter un bloc distinct `Vidéos du chantier` au lieu de mélanger les vidéos dans les métadonnées historiques des photos.

Principes retenus :

- médias vidéo WordPress natifs ;
- formats prioritaires : MP4 et WebM ;
- plusieurs vidéos courtes par réalisation ;
- lecteur HTML5 responsive avec `controls`, `playsinline` et `preload="metadata"` ;
- pas d'autoplay sonore ;
- image mise en avant et photos conservées pour les cartes du portfolio, Open Graph et les besoins SEO image ;
- les vidéos ne doivent pas être contrôlées par la règle ALT des images ;
- ajout éventuel de `VideoObject` uniquement lorsque les métadonnées nécessaires sont réellement disponibles ;
- compatibilité mobile prioritaire pour les vidéos verticales issues de WhatsApp.

## Stratégie de branches par site

À partir de cette variante, KV2 Portfolio Studio est géré comme un noyau commun avec des branches terrain par site.

### `main`

`main` reste le tronc commun :

- corrections génériques ;
- sécurité ;
- compatibilité WordPress/PHP ;
- contrats de données communs ;
- améliorations utiles à tous les métiers.

### `site/<site>`

Chaque site peut disposer d'une branche dédiée, par exemple :

- `site/ets-mon-toit`
- futures variantes pour d'autres projets si leurs besoins divergent réellement.

Une branche site peut expérimenter une interface, un workflow ou une fonction métier sans créer de régression sur les autres installations.

### Réintégration dans le noyau

Une fonctionnalité validée sur un site n'est pas fusionnée automatiquement dans `main`.

Elle doit d'abord être classée :

- **générique** : candidate à une réintégration dans `main` ;
- **métier** : peut devenir un module ou une option de profil ;
- **spécifique au site** : reste sur la branche du site.

Les futures installations peuvent ensuite être construites par hybridation de fonctions déjà validées sur plusieurs branches, en reprenant les composants utiles plutôt qu'en fusionnant aveuglément des branches complètes.

## Comparaison terrain

Les variantes permettent de comparer les choix fonctionnels entre sites en production et d'identifier les solutions qui fonctionnent le mieux dans leur contexte réel. Il s'agit d'un test comparatif terrain ; ce n'est pas un A/B test statistique strict tant que les utilisateurs ne sont pas répartis aléatoirement entre deux variantes sur un même objectif.

## Politique de version

Pour identifier clairement les paquets spécifiques :

- noyau commun : version normale, par exemple `1.2.2` ;
- ETS Mon Toit : suffixe `-emt.N`, par exemple `1.2.3-emt.1`.

Le suffixe indique qu'un paquet n'est pas interchangeable aveuglément avec celui d'un autre site.

## Règles de maintenance

1. Toute correction de sécurité ou bug générique doit d'abord être vérifiée sur `main`.
2. Les correctifs communs nécessaires sont ensuite reportés sur les branches site.
3. Toute modification spécifique doit être documentée dans ce fichier avec son objectif, ses fichiers touchés et son statut de recette.
4. Ne jamais écraser une branche site avec `main` sans comparaison préalable.
5. Avant déploiement, tester au minimum : création d'une réalisation, ville, taxonomies, galerie, image principale, CTA, Rank Math, affichage mobile et page publique.
6. Conserver une possibilité de retour au paquet précédemment validé sur le site.

## État au 16/09/2026

- branche `site/ets-mon-toit` créée ;
- version identifiée `1.2.3-emt.1` ;
- correctif REST `kv2_ville` hérité de `main` ;
- support vidéo : spécifié, pas encore déclaré comme validé en production ;
- déploiement sur `ets-mon-toit.fr` : à effectuer après construction du paquet et recette.
