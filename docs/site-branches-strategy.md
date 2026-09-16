# Stratégie de variantes par site

Dernière mise à jour : 16 septembre 2026.

## Principe

KV2 Portfolio Studio conserve un noyau commun sur `main`, mais peut désormais évoluer au moyen de branches dédiées aux sites lorsque les besoins métier ou les contraintes techniques divergent.

Objectif : éviter qu'une adaptation utile à un site introduise une régression sur un autre, tout en conservant la possibilité de réutiliser plus tard les fonctions réellement validées sur le terrain.

## Organisation

### Noyau commun : `main`

Le tronc commun reçoit en priorité :

- corrections génériques ;
- sécurité ;
- compatibilité WordPress et PHP ;
- contrats de données communs ;
- correctifs nécessaires à plusieurs sites ;
- composants réutilisables dont le comportement est stabilisé.

### Variantes : `site/<slug-site>`

Une branche dédiée peut être créée lorsqu'un site a des besoins spécifiques.

Première variante officielle :

| Site | Branche | Première version identifiée | Motif |
|---|---|---:|---|
| ETS Mon Toit | `site/ets-mon-toit` | `1.2.3-emt.1` | Couverture/zinguerie, correction ville, futures vidéos de chantier |

## Règle de promotion

Une fonction développée sur une branche site est classée après validation :

1. **générique** : candidate à une intégration dans `main` ;
2. **métier** : candidate à un profil ou module activable ;
3. **spécifique au site** : reste isolée sur sa branche.

Ne pas fusionner intégralement une branche site dans `main` sans revue fonctionnelle. Préférer des commits ciblés ou une réimplémentation propre du composant validé.

## Hybridation de futurs projets

Pour un nouveau projet, le point de départ reste `main`. Les fonctions déjà validées sur différentes branches peuvent ensuite être reprises de manière sélective.

Exemple : un nouveau site peut reprendre le modèle de localisation d'une branche, le workflow média d'une autre et le profil métier d'une troisième, sans importer leurs personnalisations non pertinentes.

Cette organisation construit progressivement une bibliothèque de solutions testées en production.

## Comparaison des variantes

Les branches par site permettent d'observer les effets de workflows et interfaces différents dans des contextes réels. Cette démarche peut guider les choix futurs, mais elle ne doit pas être présentée comme un A/B test statistique strict sans répartition aléatoire des utilisateurs et métrique commune contrôlée.

## Correctifs transversaux

Un bug générique découvert sur une branche doit être reproduit et corrigé dans `main` lorsque cela est pertinent, puis reporté vers les branches concernées.

Exemple actuel : collision REST autour de `kv2_ville`. Le correctif utilisant `rest_base = kv2_ville_terms` appartient au noyau commun et doit rester présent dans les variantes susceptibles de charger simultanément une taxonomie et un CPT portant cette clé interne.

## Versionnement recommandé

- noyau : `X.Y.Z`
- variante ETS Mon Toit : `X.Y.Z-emt.N`
- autres variantes : suffixe court documenté pour chaque site.

Le numéro du paquet installé doit toujours permettre de retrouver sans ambiguïté la branche et le commit source.

## Checklist avant déploiement d'une variante

- comparer la branche avec le paquet actuellement installé ;
- vérifier les migrations et métadonnées existantes ;
- tester création et modification d'une réalisation ;
- tester localisations et taxonomies ;
- tester galeries et image principale ;
- tester frontend desktop/mobile ;
- tester CTA, Rank Math et données structurées ;
- conserver un paquet de retour arrière ;
- documenter le commit effectivement déployé dans le dépôt du site.
