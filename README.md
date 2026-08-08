# KV2 Portfolio Studio 1.1.12

Un portfolio WordPress natif pensé pour remplacer progressivement WP Portfolio sans sacrifier le référencement ni les données existantes.

## Ce que fait la V1.1

- crée de vraies URL indexables pour chaque réalisation et une archive paginée côté serveur ;
- organise les projets par service, ville, type de meuble, style et technique ;
- ajoute une trame éditoriale complète : besoin, état initial, contraintes, intervention, résultat, matières, durée, prix facultatif et témoignage sourcé ;
- gère les photos avant/après en colonnes ou dans un comparateur accessible ;
- propose des CTA globaux ou propres à une réalisation, avec déclenchement Click to Chat et lien vers le formulaire ;
- enrichit ces CTA avec téléphone cliquable, parcours client, points forts et horaires dynamiques We’re Open! lorsque l’extension est active ;
- propose trois affichages — grille, tuiles éditoriales et Masonry — avec colonnes, format d’image, style de carte et quantité configurables ;
- reprend par défaut le rendu WP Portfolio classique : Masonry fluide en trois colonnes, proportions originales, titres sous les images, filtres de services, recherche à droite et bouton « Voir plus de réalisations » ;
- conserve une pagination HTML explorée par les moteurs, même lorsque l’UX utilise « Afficher plus » ou le défilement infini ;
- détecte une page `/realisations/` déjà publiée et active alors un routage non conflictuel : cette page et ses pages enfants restent intactes, sans archive ni taxonomie KV2 publique concurrente ;
- laisse Rank Math gérer les valeurs déjà saisies, fournit seulement un titre et une meta description de secours, donne à chaque page paginée son propre canonical et place les recherches/filtres temporaires en `noindex, follow` ;
- complète tardivement le graphe Rank Math avec `CreativeWork` seulement si cette entité n’existe pas déjà, puis enrichit les `ImageObject` existants avec créateur, crédit, copyright et licence ;
- importe WP Portfolio vers des brouillons ou vers une galerie de transition `noindex`, sans modifier ni supprimer les sources, après nettoyage du HTML importé ;
- traduit les anciens attributs `categories`, `tags`, `other-categories`, `per-page`, filtres, recherche et pagination ; l’alias `[wp_portfolio]` reste désactivé tant que la bascule n’a pas été validée ;
- inspecte les EXIF présents dans les JPEG/TIFF ;
- affiche une checklist de complétude avant publication et prépare les redirections des anciennes URL WP Portfolio ;
- exporte et réimporte un paquet image JSON ;
- exporte aussi un dossier JSON complet qui permet à ChatGPT de préparer toute une réalisation en brouillon, y compris les images, taxonomies, Rank Math et CTA.

## Installation

1. Dans WordPress, ouvrir **Extensions → Ajouter une extension → Téléverser une extension**.
2. Choisir `kv2-portfolio-studio-v1.1.12.zip`, installer et activer. Une mise à jour depuis la V1 conserve les réalisations et réglages.
3. Ouvrir **Réalisations → Réglages** et configurer l’affichage, Click to Chat, l’URL du formulaire et les droits des images.
4. Dans Rank Math, vérifier que les études de cas indexables sont incluses au sitemap. Les éléments « Galerie uniquement » sont automatiquement exclus des sitemaps WordPress et Rank Math.
5. Publier un projet test, puis contrôler l’affichage, le canonical et le JSON-LD avec Rich Snippet Sniper.

Sur un nouveau site sans page portfolio détectée, la page configurée par défaut est `/realisation-tapisserie/`. Si une page publiée `/realisations/` existe déjà, elle devient la page principale et le plugin active automatiquement le mode « Site existant ». Le shortcode `[kv2_portfolio]` produit directement les cartes et leurs liens dans le HTML initial : son utilisation ne pénalise donc pas le SEO. Il ajoute automatiquement le H1 configuré lorsque la page ou Elementor n’en fournit pas. Utilisez `show_heading="0"` pour le désactiver, ou `heading="…"` et `intro="…"` pour le personnaliser.

En mode standard, l’archive technique `/realisations/` peut être redirigée en 301 vers la page principale et les fiches conservent leurs URL `/realisations/nom-du-projet/`. En mode « Site existant », aucune archive KV2 ne prend ce chemin et les futures études de cas utilisent `/realisation/nom-du-projet/`. Utilisez `[kv2_portfolio preset="settings"]` uniquement pour reprendre les choix de l’écran Réglages. Filtres et affichages peuvent être combinés :

`[kv2_portfolio service="restauration-fauteuil" ville="montpellier" layout="masonry" columns="2" image_ratio="auto" card_style="minimal" load_mode="button"]`

Valeurs de `layout` : `grid`, `tiles`, `masonry`. Valeurs de `load_mode` : `paged`, `button`, `infinite`.

Le préréglage initial et les installations encore restées sur les anciens réglages par défaut utilisent `masonry`, 3 colonnes, ratio `auto`, cartes `classic`, 12 réalisations et navigation `button`. Les attributs `show_filters="0"`, `show_search="0"` ou `show_cta="0"` permettent de masquer séparément ces éléments. Les réglages déjà personnalisés sont conservés lors de la mise à jour.

## Migration depuis WP Portfolio

Ouvrir **Réalisations → Importer WP Portfolio**, sélectionner quelques éléments et commencer par un test. Deux modes sont proposés :

- **Brouillon** pour préparer une véritable étude de cas indexable ;
- **Galerie noindex** pour reproduire les cartes publiées sans créer de pages SEO pauvres. Ces éléments restent hors sitemap et ouvrent une visionneuse accessible, ou une page éditoriale interne lorsqu’elle est raccordée dans la fiche.

Le lien avec l’ID source est conservé pour empêcher les doublons et les taxonomies WP Portfolio sont mappées vers les taxonomies KV2. Le plugin reprend l’image mise en avant ou, à défaut, la première pièce jointe image reconnue dans les métadonnées de la source. Une étude de cas peut être promue manuellement depuis sa fiche. Si les contrôles de recette sont concluants et WP Portfolio est désactivé, l’alias de compatibilité peut alors être activé dans **Réglages** pour interpréter les 82 anciens shortcodes sans modifier les pages. Il ne remplace jamais un shortcode encore enregistré par WP Portfolio.

La V1.1.9 affiche le nombre réel de sources sans contenu et leur nombre d’images avant la migration. Après une première migration mal configurée, le bouton **Réparer et resynchroniser l’import existant** remet les imports vides en galerie `noindex`, resynchronise images et catégories, et conserve intégralement toute fiche qui contient déjà un vrai texte éditorial. Les filtres rechargent une sélection côté serveur : ils couvrent ainsi les centaines de réalisations, et « Voir plus » continue dans cette sélection au lieu de filtrer uniquement la première série visible.

Depuis la V1.1.10, la visionneuse permet de parcourir les cartes de galerie avec les flèches, les touches gauche/droite ou un geste horizontal. Elle affiche un compteur, le titre et les taxonomies disponibles, et charge automatiquement le lot suivant lorsque le visiteur atteint la dernière image déjà présente dans la page.

La V1.1.11 rend directement saisissables, dans **Histoire et médias du projet**, le besoin du client, l’état initial, les contraintes, l’intervention, le résultat et les matières. Les photos avant/après se trouvent juste sous ces champs. Les principales lignes manquantes de la checklist servent aussi de raccourcis vers la zone à compléter.

La V1.1.12 sécurise les migrations multi-images : lorsqu’une seule métadonnée WP Portfolio contient plusieurs ID, URL ou fragments HTML imbriqués, toutes les images reconnues sont désormais conservées dans leur ordre d’origine. Le contrat de migration automatisé couvre explicitement ce cas et le test de mise à niveau suit automatiquement la version courante du plugin.

## CTA intelligent et Click to Chat

Dans **Réalisations → Réglages**, choisir :

- le titre et le texte par défaut ;
- le téléphone affiché, le parcours en plusieurs étapes et les points forts ;
- l’action principale : Click to Chat ou formulaire ;
- `ctc_chat` pour ouvrir directement WhatsApp, ou `ctc_greetings` pour ouvrir l’encart de bienvenue ;
- l’adresse du formulaire et les libellés des deux boutons.

Lorsque We’re Open! est actif, le CTA peut afficher automatiquement la pastille ouvert/fermé, l’heure de prochaine réponse et une synthèse des horaires. Le contrôle immédiat évite de servir un statut périmé depuis le cache. Le lien « Toutes les réalisations » peut être raccordé à la page principale contenant le shortcode ; l’archive native conserve dans tous les cas le rendu Masonry classique en trois colonnes maximum.

Une réalisation peut remplacer ces réglages dans son bloc « CTA de cette réalisation ». Les classes officielles Click to Chat restent sur les boutons, ce qui permet au plugin Click to Chat de conserver ses réglages et son suivi Analytics.

## Workflow réalisation complète avec ChatGPT

1. Importer les photos du client dans la médiathèque.
2. Ouvrir **Réalisations → Assistant ChatGPT**.
3. Saisir les ID des images et coller les notes ou le message du client.
4. Télécharger le dossier `kv2-realisation-chatgpt.json`.
5. Envoyer à ChatGPT le JSON et les photos en utilisant le prompt affiché dans l’interface.
6. Récupérer le fichier JSON complété.
7. L’importer avec **Simuler**, puis **Importer en brouillon**.
8. Ouvrir la fiche, suivre la checklist et publier manuellement.

Une fiche déjà publiée ne peut pas être modifiée par ce flux : cette protection évite qu’un import IA ne change directement une page en ligne. Le contrat se trouve dans `schema/chatgpt-realisation.schema.json` et l’exemple dans `examples/chatgpt-realisation.example.json`.

## Workflow image avec ChatGPT

### À partir de WordPress

1. Relever les ID des images dans la médiathèque.
2. Aller dans **Réalisations → Image SEO & ChatGPT**.
3. Exporter le JSON des ID concernés.
4. Envoyer à ChatGPT les images, le contexte du projet et le JSON.
5. Réimporter le fichier retourné en utilisant d’abord **Simuler l’import**.
6. Vérifier les correspondances, puis cliquer sur **Appliquer**.

### Prompt conseillé

> Analyse ces photos d’une même réalisation. Complète le JSON fourni sans changer `schema_version`, `match.attachment_id`, `match.filename` ni `match.source_url`. Rédige en français naturel : un titre média concis, un ALT qui décrit uniquement ce qui est visuellement utile, une légende éditoriale, une description, le crédit et le copyright si je les fournis, 3 à 8 mots-clés, le lieu et la date. N’invente jamais une personne, un droit, un matériau, une ville ou une date. Laisse vide ce qui n’est pas certain. Retourne uniquement un fichier JSON valide conforme à `chatgpt-image-metadata.schema.json`.

Le schéma se trouve dans `schema/chatgpt-image-metadata.schema.json` et un exemple dans `examples/chatgpt-image-metadata.example.json`.

## EXIF, IPTC et fiabilité

La V1.1 lit les EXIF du fichier original et signale notamment la présence de GPS, d’auteur et de copyright. Elle n’écrit pas dans le binaire : une recompression, Imagify, un CDN ou une conversion WebP/AVIF peut retirer ces données. Le paquet JSON canonique et les champs WordPress restent donc la référence. Les valeurs globales de créateur, crédit, copyright et licence servent de repli et peuvent enrichir le graphe `ImageObject` de Rank Math sans dupliquer une image déjà déclarée.

Les coordonnées GPS détectées ne sont jamais publiées automatiquement.

## Personnalisation

Les modèles peuvent être surchargés dans le thème :

- `votre-theme/kv2-portfolio-studio/single-kv2_realisation.php`
- `votre-theme/kv2-portfolio-studio/archive-kv2_realisation.php`

La couleur d’accent, les tailles d’images, le nombre de colonnes, les cartes, la navigation et le comparateur sont configurables. Le CSS et le JavaScript ne sont chargés que sur les pages du portfolio ou sur une page contenant le shortcode KV2.

## V2 envisagée

- génération de variantes sociales et aperçu des recadrages ;
- file d’attente éditoriale avec validation manuelle ;
- publication via les API officielles Facebook/Instagram, Google Business Profile et Pinterest ;
- modèles de légendes par réseau, paramètres UTM et journal d’envoi ;
- écriture IPTC/EXIF optionnelle avec sauvegarde de l’original et test de persistance.

## Sécurité et réversibilité

Les imports exigent les droits WordPress adaptés et des nonces. Les fichiers JSON image sont limités à 1 Mo, les dossiers complets à 2 Mo, et chaque import à 100 images. Le lecteur contrôle la provenance HTTP, l’extension, le type MIME, la taille réelle, la profondeur JSON et le schéma attendu. Les métadonnées métier ne sont exposées par l’API REST qu’en contexte d’édition et nécessitent le droit de modifier la fiche concernée. Le HTML repris de WP Portfolio est filtré avant insertion.

Les témoignages ne sont affichés que lorsque l’autorisation de publication est cochée. La désinstallation conserve volontairement les réalisations, taxonomies, réglages et métadonnées : aucune donnée métier n’est supprimée automatiquement. Les contrôles de migration, paquet, sécurité et contrat SEO du dossier `tests/` sont exécutés par l’intégration continue GitHub sur PHP 7.4 et 8.3.
