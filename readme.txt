=== KV2 Portfolio Studio ===
Contributors: kv2
Tags: portfolio, seo, image metadata, exif, rank math
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 1.1.17
License: Proprietary

Portfolio de réalisations SEO-first, import WP Portfolio et flux de métadonnées d’images avec ChatGPT.

== Installation ==

1. Téléverser le ZIP dans Extensions > Ajouter une extension > Téléverser.
2. Activer KV2 Portfolio Studio.
3. Ouvrir Réalisations > Réglages.
4. Enregistrer une fois Réglages > Permaliens si le site utilise un cache de routes persistant.

== Important ==

Le plugin ne supprime aucune donnée à la désinstallation. Le module EXIF lit le fichier original mais ne le réécrit pas dans la version 1.1.

== Changelog ==

= 1.1.17 =
* Adapte le nombre de colonnes à la largeur réelle du bloc portfolio, y compris dans Elementor.
* Empêche le Masonry de compresser trois cartes minuscules dans un conteneur étroit.
* Conserve trois colonnes sur les galeries suffisamment larges, deux en largeur intermédiaire et une sur les petits blocs.

= 1.1.16 =
* Remplace la saisie séparée des termes de ville par un bloc unique avec ville, département et code postal.
* Réutilise les villes existantes, évite les doublons et distingue les homonymes de départements différents.
* Corrige l’import JSON des localisations et affiche les erreurs d’association au lieu de les ignorer.
* Étend la recherche publique aux services, villes, départements, codes postaux et autres taxonomies.
* N’expose plus la localisation dans les données de recherche ou la visionneuse lorsqu’un projet est confidentiel.
* Corrige les schémas JSON pour les pièces jointes sans ID et les dates d’image laissées vides.

= 1.1.15 =
* Recalcule automatiquement la checklist après l’enregistrement dans l’éditeur de blocs et permet son actualisation manuelle.
* Sépare les éléments indispensables des enrichissements facultatifs afin qu’une photo avant, un extrait ou un témoignage absent ne bloque pas la publication.
* Rend la saisie des villes recherchable avec les suggestions natives de WordPress.
* Adapte automatiquement les cartes éditoriales et les colonnes de la fiche au contenu réellement disponible, sans zone vide artificielle.
* Affiche « Résultat final » ou « État initial » lorsqu’un seul groupe de photos existe.
* Ajoute un retour vers la page portfolio configurée sous le hero et en bas de chaque fiche.

= 1.1.14 =
* Neutralise le conteneur étroit de 750 px imposé par Astra sur les fiches individuelles et restitue une mise en page pleine largeur.
* Réorganise la fiche en hero éditorial, cartes de synthèse et grille contenu/détails plus lisible sur ordinateur.
* Utilise la première photo après ou avant comme image de hero lorsque l’image mise en avant est absente.
* Agrandit les galeries ne contenant qu’un seul groupe ou qu’une seule image, sans recadrage destructeur.

= 1.1.13 =
* Ajoute les filtres natifs de shortcode `meuble`, `style` et `technique` en complément de `service` et `ville`.
* Permet de combiner plusieurs taxonomies sans casser les anciens attributs WP Portfolio ni la pagination HTML.
* Ajoute un contrat automatisé couvrant les pages dédiées aux chaises, fauteuils, styles et techniques.

= 1.1.12 =
* Récupère toutes les images présentes dans une même métadonnée WP Portfolio, y compris les tableaux imbriqués, fragments HTML et listes d’URL.
* Ajoute un contrat de migration qui protège les imports multi-images contre les régressions.
* Aligne le test de mise à niveau sur la version réelle du plugin afin que les futures releases restent vérifiables par la CI.

= 1.1.11 =
* Rétablit les zones de saisie manquantes pour le besoin client, l’état initial, les contraintes, l’intervention, le résultat et les matières.
* Place les sélecteurs Photos avant/après immédiatement sous le contenu éditorial.
* Rend les principaux éléments manquants de la checklist cliquables pour accéder directement au champ concerné.
* Clarifie la différence entre une carte de galerie et une étude de cas complète indexable.

= 1.1.10 =
* Transforme la visionneuse en galerie navigable avec flèches, clavier, geste tactile et compteur.
* Charge automatiquement la série suivante lorsqu’on atteint la dernière image déjà présente dans la page.
* Affiche sous l’image le titre et les catégories réellement disponibles : service, ville, meuble, style et technique.
* Améliore l’accessibilité, les états de chargement et le rendu mobile de la visionneuse.

= 1.1.9 =
* Applique les filtres et la recherche à l’ensemble du portfolio côté serveur, pas seulement aux cartes déjà chargées.
* Conserve les paramètres de filtre lors du chargement « Voir plus » afin que chaque clic affiche réellement de nouveaux résultats pertinents.
* Ajoute un diagnostic explicite des contenus et images réellement disponibles dans WP Portfolio.
* Ajoute une resynchronisation sûre qui replace les imports vides en galerie noindex sans modifier les études de cas déjà enrichies.
* Préserve toutes les images WP Portfolio reconnues dans les métadonnées d’une source.

= 1.1.8 =
* Ajoute un mode site existant qui protège /realisations/ et ses pages enfants.
* Sépare les études de cas indexables des éléments de galerie noindex exclus des sitemaps.
* Importe jusqu’à 2 000 sources et mappe les taxonomies WP Portfolio vers KV2.
* Traduit les attributs des anciens shortcodes et fournit un alias opt-in qui ne remplace jamais WP Portfolio lorsqu’il est actif.
* Ajoute une destination éditoriale interne et une visionneuse accessible pour les cartes de galerie.
* Retire les valeurs Montpellier et téléphone propres à un site des réglages de nouvelle installation.

= 1.1.7 =
* Consacre la page avec shortcode comme catalogue SEO principal et redirige l’archive technique en 301.
* Ajoute un H1 automatique uniquement lorsqu’aucun H1 n’existe déjà dans la page ou Elementor.
* Ajoute les titres et descriptions de secours Rank Math, les canonicals paginés et le noindex des filtres temporaires.
* Active CreativeWork sans dupliquer une entité déjà présente dans le graphe Rank Math.
* Durcit les imports JSON, les permissions REST et le nettoyage des contenus importés depuis WP Portfolio.
* Ajoute des contrôles automatisés et une intégration continue PHP 7.4/8.3.

= 1.1.6 =
* Enrichit le CTA global et les fiches avec téléphone cliquable, parcours client et points forts.
* Ajoute un bouton WhatsApp vert piloté par Click to Chat, préréglé sur l’encart de bienvenue.
* Intègre le statut et la synthèse des horaires du plugin We’re Open! lorsqu’il est actif.
* Uniformise l’archive « Toutes les réalisations » en Masonry classique, trois colonnes maximum.
* Permet de choisir la page principale ciblée par le lien « Toutes les réalisations ».

= 1.1.5 =
* Ajoute un espace de respiration de 30 px sous le CTA des fiches de réalisation.

= 1.1.4 =
* Refonte éditoriale pleine largeur des fiches de réalisation.
* Neutralise les marges et paddings Astra sur l’article et les cartes associées.
* Ajoute les catégories en tête de fiche et une navigation précédente/suivante accessible.
* Aligne « D’autres réalisations » sur le Masonry classique validé du portfolio.
* Masque de cette sélection les projets incomplets sans image à la une.

= 1.1.3 =
* Neutralise à la source le préfixe `sizes="auto"` ajouté par WordPress aux images différées.
* Rétablit un vrai Masonry en rangées visuelles compactes après chargement des ratios fiables.
* Reproduit le style WP Portfolio : ombre sur l’image seule et titre directement sur le fond de page.

= 1.1.2 =
* Corrige les images étirées par le placeholder natif des images différées de WordPress.
* Stabilise le Masonry avec les colonnes CSS natives.
* Neutralise les tailles de liens imposées par Astra dans les titres de cartes.
* Rééquilibre le titre et l’image principale des fiches de réalisation.

= 1.1.1 =
* Le shortcode simple applique toujours le préréglage Portfolio classique, indépendamment des anciens réglages enregistrés.
* Réparation automatique des images importées manquantes et meilleure reconnaissance des URL WP Portfolio.

= 1.1.0 =
* CTA intelligent avec Click to Chat et formulaire.
* Assistant JSON ChatGPT pour créer une réalisation complète en brouillon.
* Grille, tuiles, Masonry, formats d’image et chargement progressif.
* Préréglage WP Portfolio classique : Masonry 3 colonnes, ratios originaux, titres sous les images, filtres, recherche et bouton « Voir plus ».
* Checklist de publication, témoignages structurés et redirections WP Portfolio.
* Crédits, copyright, licences et enrichissement ImageObject Rank Math.
