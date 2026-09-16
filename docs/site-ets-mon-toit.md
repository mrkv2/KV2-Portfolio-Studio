# Variante site — ETS Mon Toit

Dernière mise à jour : 16 septembre 2026.

## Identité de la variante

- Site : `https://ets-mon-toit.fr/`
- Dépôt source commun : `mrkv2/KV2-Portfolio-Studio`
- Branche dédiée : `site/ets-mon-toit`
- Version de départ de la branche : `1.2.3-emt.1`
- Version fonctionnelle courante de la branche : `1.3.0-emt.1`
- Version actuellement signalée sur le site au 16/09/2026 : `1.2.0`
- Profil métier : Couverture et zinguerie

Cette branche est la variante de KV2 Portfolio Studio dédiée à ETS Mon Toit. Elle peut évoluer selon les besoins réels du site sans imposer ces choix aux autres installations du plugin.

## Base technique

La branche `site/ets-mon-toit` a été créée depuis `main` le 16/09/2026. Elle hérite des corrections présentes dans `main` à cette date, notamment le correctif de collision REST de la taxonomie `kv2_ville`.

Le correctif conserve la clé interne `kv2_ville`, mais affecte à la taxonomie la route REST distincte :

```php
$args['rest_base'] = 'kv2_ville_terms';
```

Ce garde-fou est requis lorsqu'un autre plugin ou module du site expose également un Custom Post Type `kv2_ville`. Sans séparation des routes REST, Gutenberg peut recevoir des objets de type post au lieu de termes et provoquer une erreur JavaScript lors de la sélection d'une ville.

## Objectifs propres à ETS Mon Toit

1. conserver le profil Couverture et zinguerie introduit en 1.2.0 ;
2. fiabiliser la saisie des villes et départements dans les réalisations ;
3. gérer de courtes vidéos de chantier dans les études de cas ;
4. conserver les photos avant/après comme médias SEO principaux ;
5. privilégier une interface adaptée aux chantiers de couverture, étanchéité, zinguerie et toiture-terrasse ;
6. ne pas imposer les besoins propres à ETS Mon Toit aux variantes Tapisserie ou aux autres sites.

## Version 1.3.0-emt.1 — vidéos de chantier

La 1.3.0-emt.1 ajoute un module isolé propre à la branche ETS Mon Toit :

- fichier principal : `includes/class-kv2ps-ets-mon-toit.php` ;
- métadonnée dédiée : `_kv2ps_video_ids` ;
- méta-box séparée `Vidéos du chantier` dans l'éditeur d'une réalisation ;
- sélection multiple depuis la médiathèque WordPress limitée aux médias vidéo ;
- réordonnancement par glisser-déposer ;
- suppression d'une vidéo sans toucher aux photos avant/après ;
- formats acceptés : MP4, WebM, Ogg et QuickTime ;
- MP4 et WebM restent les formats recommandés ;
- lecteur HTML5 natif avec `controls`, `playsinline` et `preload="metadata"` ;
- pas d'autoplay sonore ;
- affichage responsive, y compris pour les vidéos verticales WhatsApp ;
- image principale et galeries photo inchangées ;
- les vidéos ne sont pas soumises à la checklist ALT des images ;
- aucun `VideoObject` automatique à ce stade : il ne sera ajouté que si les métadonnées nécessaires sont fiables.

Le rendu public enveloppe le template de réalisation déjà sélectionné par le plugin ou le thème, puis injecte la section vidéo dans l'article. Cela évite de dupliquer l'intégralité du template et limite les divergences avec le noyau.

### Fichiers spécifiques

- `includes/class-kv2ps-ets-mon-toit.php`
- `assets/ets-mon-toit-admin.js`
- `assets/ets-mon-toit-admin.css`
- `assets/ets-mon-toit-frontend.css`
- `templates/single-kv2_realisation-ets-mon-toit.php`
- `tests/ets-mon-toit-video-contract.php`

La CI exécute le contrat `ETS Mon Toit video contract` sous PHP 7.4 et PHP 8.3. Une exécution complète de la branche a été validée avec succès le 16/09/2026 après l'ajout du module vidéo.

## Paquet WordPress

La CI de la branche construit désormais, sur PHP 8.3, un ZIP installable nommé :

`kv2-portfolio-studio-1.3.0-emt.1.zip`

Le ZIP contient un unique dossier racine `kv2-portfolio-studio`, afin que WordPress remplace correctement l'extension existante au lieu d'installer une seconde copie sous un autre nom de dossier.

Le paquet exclut les dossiers de développement `.github`, `tests`, `docs` et `dist`.

## Premier cas de recette

Premier chantier prévu pour la recette réelle : rénovation complète d'un toit-terrasse à Viroflay (78), avec une courte vidéo verticale WhatsApp montrant les travaux d'étanchéité.

Points à valider avant publication :

1. le champ `Ville ou arrondissement` accepte Viroflay sans erreur JavaScript ;
2. l'association ville / département / code postal est conservée après enregistrement ;
3. la vidéo MP4 peut être téléversée puis sélectionnée dans `Vidéos du chantier` ;
4. l'ordre des vidéos est conservé ;
5. lecture correcte sur ordinateur et mobile ;
6. aucune régression sur les photos avant/après, image principale, CTA, Rank Math et galeries ;
7. cache front purgé après mise à jour du plugin.

## Stratégie de branches par site

### `main`

`main` reste le tronc commun : corrections génériques, sécurité, compatibilité WordPress/PHP, contrats de données communs et composants réutilisables stabilisés.

### `site/<site>`

Chaque site peut disposer d'une branche dédiée lorsque les besoins divergent réellement. Une branche site peut expérimenter une interface, un workflow ou une fonction métier sans créer de régression sur les autres installations.

### Réintégration dans le noyau

Une fonctionnalité validée sur un site n'est pas fusionnée automatiquement dans `main`. Elle est d'abord classée :

- **générique** : candidate à une réintégration dans `main` ;
- **métier** : candidate à un profil ou module activable ;
- **spécifique au site** : reste isolée sur sa branche.

Les futurs projets pourront être construits par hybridation sélective des composants validés sur plusieurs branches.

## Comparaison terrain

Les variantes permettent de comparer les choix fonctionnels entre sites en production et d'identifier les solutions qui fonctionnent le mieux dans leur contexte réel. Il s'agit d'un test comparatif terrain et non d'un A/B test statistique strict sans répartition aléatoire et métrique commune contrôlée.

## Politique de version

- noyau commun : `X.Y.Z` ;
- variante ETS Mon Toit : `X.Y.Z-emt.N`.

Exemple courant : `1.3.0-emt.1`.

Le numéro du paquet installé doit permettre de retrouver sans ambiguïté la branche et le commit source.

## Règles de maintenance

1. Toute correction de sécurité ou bug générique doit être vérifiée sur `main`.
2. Les correctifs communs nécessaires sont ensuite reportés sur les branches site.
3. Toute modification spécifique est documentée ici avec son objectif, ses fichiers touchés et son statut de recette.
4. Ne jamais écraser une branche site avec `main` sans comparaison préalable.
5. Avant déploiement, tester au minimum : création d'une réalisation, ville, taxonomies, galerie, image principale, vidéos, CTA, Rank Math, affichage mobile et page publique.
6. Conserver une possibilité de retour au paquet précédemment validé sur le site.

## État au 16/09/2026

- branche `site/ets-mon-toit` active ;
- version source : `1.3.0-emt.1` ;
- correctif REST `kv2_ville` présent ;
- support des vidéos de chantier : implémenté sur la branche ;
- tests CI du module : validés ;
- construction automatique du ZIP : ajoutée à la CI ;
- déploiement sur `ets-mon-toit.fr` : non effectué tant que le paquet final n'a pas été récupéré et testé en production/recette.
