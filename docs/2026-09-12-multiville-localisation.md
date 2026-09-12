# Hotfix multi-rattachements géographiques — 12 septembre 2026

## Contexte

Sur `tapissier-laurot.fr`, la localisation d'une réalisation est gérée dans le bloc latéral **Localisation de la réalisation** de KV2 Portfolio Studio.

Le champ principal fonctionne correctement pour une ville unique : par exemple, la réalisation `14798` enregistrée avec `Gisors` est bien rattachée au terme `Gisors (27)` et remonte sur les pages / shortcodes associés.

Le besoin métier est cependant plus large : une même réalisation doit pouvoir être rattachée manuellement à plusieurs termes géographiques de la taxonomie `kv2_ville`, par exemple :

- `Gisors (27)` ;
- `Tapissier Eure 27 : Réparation & restaurations chaises` ;
- ou, sur d'autres fiches, `Versailles 78000` + `Réalisation Tapisserie dans les Yvelines 78`.

Il ne faut **aucune déduction automatique** du département ou de la zone. L'utilisateur choisit lui-même les rattachements supplémentaires.

## Cause du blocage

La méthode `KV2PS_Post_Types::set_location()` remplaçait systématiquement la taxonomie `kv2_ville` par un seul terme :

```php
$assigned = wp_set_object_terms( $post_id, array( $term_id ), 'kv2_ville', false );
```

Cela empêchait de conserver plusieurs rattachements géographiques en parallèle.

## Correctif appliqué en production

### 1. Stockage des rattachements supplémentaires

Dans `includes/class-kv2ps-admin.php`, les IDs choisis manuellement dans le bloc de localisation sont enregistrés dans la méta :

```text
_kv2ps_location_terms
```

La valeur est une liste d'IDs de termes `kv2_ville`.

### 2. Conservation des termes supplémentaires dans `set_location()`

Dans `includes/class-kv2ps-post-types.php`, la sauvegarde de la ville principale a été modifiée afin de fusionner :

- le terme principal calculé par `set_location()` ;
- les IDs stockés dans `_kv2ps_location_terms`.

Principe appliqué :

```php
$extra_terms    = self::sanitize_ids( get_post_meta( $post_id, '_kv2ps_location_terms', true ) );
$location_terms = array_values( array_unique( array_merge( array( $term_id ), $extra_terms ) ) );
$assigned       = wp_set_object_terms( $post_id, $location_terms, 'kv2_ville', false );
```

La ville principale reste donc gérée par `set_location()`, mais les rattachements supplémentaires ne sont plus supprimés.

### 3. Interface de sélection manuelle

Dans le bloc **Localisation de la réalisation**, un sélecteur multiple a été ajouté pour les termes `kv2_ville` existants.

Un champ de recherche à la frappe a ensuite été ajouté au-dessus du sélecteur pour filtrer rapidement les termes, par exemple avec :

- `eur` ;
- `yvel` ;
- `vers` ;
- `val`.

Le choix reste entièrement manuel.

## Validation réalisée en production

Test effectué sur la réalisation `14798` :

```text
Gisors (27) | Tapissier Eure 27 : Réparation & restaurations chaises
```

La méta persistante contient :

```text
_kv2ps_location_terms = [309]
```

Le terme `309` correspond à :

```text
Tapissier Eure 27 : Réparation & restaurations chaises
```

Le test confirme donc que :

1. la ville principale reste `Gisors (27)` ;
2. le rattachement Eure est conservé simultanément ;
3. les rattachements supplémentaires persistent en méta ;
4. `wp_set_object_terms()` reçoit bien la liste complète des termes ;
5. le PHP reste syntaxiquement valide après patch.

## Données historiques confirmant le besoin multi-termes

Le site contient déjà de nombreuses réalisations avec plusieurs termes `kv2_ville`, notamment :

```text
16315 | Réalisation Tapisserie dans les Yvelines 78 | Versailles 78000
16068 | Chatou-78400 | Réalisation Tapisserie dans les Yvelines 78
16063 | Eaubonne 95 | Réalisation Val D'Oise 95
15328 | chaville | réalisation Hauts de Seine 92
15216 | Paris 6 | Réalisation Tapisserie Paris
```

Le multi-rattachement est donc un comportement métier existant à préserver.

## Relation avec le hotfix REST `kv2_ville`

Ce correctif est distinct du hotfix REST appliqué le même jour.

Le correctif REST conserve :

```php
$args['rest_base'] = 'kv2_ville_terms';
```

afin d'éviter la collision entre :

- la taxonomie `kv2_ville` de KV2 Portfolio Studio ;
- le CPT `kv2_ville` de KV2 Zones Service.

Ne pas supprimer ce `rest_base`.

## Fichiers locaux hérités à ne pas considérer comme référence

Sur le serveur, deux fichiers datés du 7 septembre sont présents :

```text
assets/city-selector.js
tests/city-selector-contract.php
```

Ils ne sont pas câblés par le PHP actif et ne correspondent pas au flux actuellement utilisé en production. Ils doivent être considérés comme des reliquats d'un essai précédent tant qu'ils ne sont pas réintégrés explicitement dans une release.

## État de version

- Version du plugin en production au moment du hotfix : **1.2.2**.
- Correctif multi-rattachements appliqué directement sur `tapissier-laurot.fr` le **12/09/2026**.
- Le code de production modifié n'est pas encore synchronisé dans une release officielle du plugin.
- Intégration recommandée dans la prochaine version **1.2.3 ou supérieure**.

## À intégrer dans la prochaine release

Lors de la prochaine synchronisation du code source :

1. intégrer proprement la méta `_kv2ps_location_terms` ;
2. intégrer la fusion des termes dans `KV2PS_Post_Types::set_location()` ;
3. remplacer l'interface provisoire `<select multiple>` par un vrai composant d'autocomplétion / tags si souhaité ;
4. ajouter un test automatisé vérifiant qu'une ville principale + plusieurs rattachements supplémentaires restent attachés après sauvegarde ;
5. conserver le hotfix REST `kv2_ville_terms` ;
6. nettoyer ou réintégrer explicitement `assets/city-selector.js` et `tests/city-selector-contract.php` afin d'éviter toute ambiguïté future.

## Commandes de contrôle utiles

Contrôle de la taxonomie sur une réalisation :

```bash
/opt/plesk/php/8.3/bin/php -d memory_limit=1G /usr/local/bin/wp eval '$id=14798;$t=wp_get_post_terms($id,"kv2_ville",["fields"=>"names"]);echo implode(" | ",$t).PHP_EOL;' --allow-root
```

Contrôle de la méta des rattachements supplémentaires :

```bash
/opt/plesk/php/8.3/bin/php -d memory_limit=1G /usr/local/bin/wp eval 'print_r(get_post_meta(14798,"_kv2ps_location_terms",true));' --allow-root
```

Contrôle syntaxique :

```bash
/opt/plesk/php/8.3/bin/php -l wp-content/plugins/kv2-portfolio-studio/includes/class-kv2ps-admin.php
/opt/plesk/php/8.3/bin/php -l wp-content/plugins/kv2-portfolio-studio/includes/class-kv2ps-post-types.php
```
