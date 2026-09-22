# Variante site — Tapissier Laurot

Dernière mise à jour : 22 septembre 2026.

## Identité

- Site : `https://tapissier-laurot.fr/`
- Dépôt source : `mrkv2/KV2-Portfolio-Studio`
- Branche dédiée : `site/tapissier-laurot`
- Version actuellement constatée sur le site avant déploiement : `1.2.2`
- Base exacte de la variante : `main` au commit `5fb670b746c90d25749e1a14c892bad5f26fa475`, version `1.2.2`
- Première version de la variante : `1.2.3-laurot.1`
- Profil métier : tapisserie d’ameublement

## Objectif

Conserver le comportement validé de KV2 Portfolio Studio 1.2.2 sur Tapissier Laurot et ajouter uniquement le workflow de courtes vidéos déjà expérimenté sur la variante ETS Mon Toit.

La branche ne reprend pas les adaptations couverture/zinguerie d’ETS Mon Toit.

## Vidéos de réalisation

La variante ajoute :

- un bloc d’administration `Vidéos de la réalisation` ;
- la métadonnée séparée `_kv2ps_video_ids` ;
- la sélection multiple depuis la médiathèque WordPress, limitée aux vidéos ;
- le réordonnancement par glisser-déposer ;
- la suppression indépendante de chaque vidéo ;
- MP4, WebM, Ogg et QuickTime ;
- un lecteur HTML5 responsive avec `controls`, `playsinline` et `preload="metadata"` ;
- aucun autoplay ;
- un affichage adapté aux vidéos verticales de téléphone ;
- conservation intégrale des photos avant/après et de l’image principale ;
- aucune obligation ALT appliquée aux vidéos ;
- aucun `VideoObject` automatique tant que titre, miniature, durée et autres métadonnées ne sont pas suffisamment fiables.

Le module est limité au profil Tapisserie d’ameublement.

## Fichiers ajoutés

- `includes/class-kv2ps-tapissier-laurot-videos.php`
- `assets/tapissier-laurot-videos-admin.js`
- `assets/tapissier-laurot-videos-admin.css`
- `assets/tapissier-laurot-videos-frontend.css`
- `templates/single-kv2_realisation-tapissier-laurot-videos.php`
- `tests/tapissier-laurot-video-contract.php`

## Garde-fous conservés

La branche part du noyau 1.2.2 actuellement documenté pour le site. Elle conserve notamment le correctif de collision REST de `kv2_ville` avec `rest_base = kv2_ville_terms`.

Les galeries, filtres, taxonomies, import JSON, CTA, Rank Math, données structurées, image principale et logique indexable/noindex ne sont pas modifiés par le module vidéo.

## Recette avant production

1. sauvegarder le dossier `kv2-portfolio-studio` actuellement installé ;
2. installer le ZIP construit depuis `site/tapissier-laurot` ;
3. vérifier que WordPress affiche `1.2.3-laurot.1` ;
4. ouvrir une réalisation existante sans vidéo et confirmer qu’elle reste identique ;
5. ajouter une vidéo MP4 verticale dans une réalisation brouillon ;
6. ajouter une deuxième vidéo, modifier l’ordre puis enregistrer ;
7. recharger l’éditeur et vérifier la persistance de l’ordre ;
8. contrôler photos avant/après, image principale, taxonomies, CTA et Rank Math ;
9. tester la lecture sur ordinateur et mobile ;
10. purger les caches puis contrôler le rendu public.

## Retour arrière

Le paquet 1.2.2 actuellement validé doit être conservé avant remplacement. Les vidéos utilisent une métadonnée séparée ; un retour à 1.2.2 n’altère donc pas les galeries photo existantes, même si les vidéos ne seront plus affichées tant que la variante n’est pas active.
