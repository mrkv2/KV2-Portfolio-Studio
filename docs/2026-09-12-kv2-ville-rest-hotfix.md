# Hotfix REST `kv2_ville` — 12 septembre 2026

## Contexte

Sur `tapissier-laurot.fr`, l'ajout ou la sélection d'une ville dans l'éditeur d'une réalisation provoquait une erreur JavaScript WordPress :

```text
TypeError: Cannot read properties of undefined (reading 'normalize')
```

Le problème ne venait pas d'un terme ville vide ou corrompu.

## Cause racine

Deux objets WordPress utilisent la même clé interne `kv2_ville` :

- **KV2 Portfolio Studio** enregistre une **taxonomie** `kv2_ville` pour les réalisations ;
- **KV2 Zones Service** enregistre un **Custom Post Type** `kv2_ville` pour les pages ville.

Les deux objets étant exposés à l'API REST, WordPress utilisait par défaut la même base REST `/wp/v2/kv2_ville`.

Sur le site Tapissier Laurot, cette route renvoyait les objets du CPT ville (posts, révisions, médias, métadonnées) au lieu des termes de taxonomie attendus par le sélecteur de ville. Le composant React/Gutenberg attendait notamment un champ `name` de terme et finissait par appeler `normalize()` sur une valeur indéfinie.

## Correctif

Conserver la clé interne de taxonomie `kv2_ville` afin de ne casser ni les termes existants, ni les relations, ni les hooks, mais lui attribuer une base REST distincte.

Dans `includes/class-kv2ps-post-types.php` :

```php
if ( 'kv2_ville' === $taxonomy ) {
    // La localisation est saisie une seule fois dans un bloc dédié.
    $args['meta_box_cb'] = false;
    $args['rest_base']   = 'kv2_ville_terms';
}
```

Après ce correctif :

- le CPT ville conserve `/wp-json/wp/v2/kv2_ville` ;
- la taxonomie ville utilise `/wp-json/wp/v2/kv2_ville_terms` ;
- la clé de taxonomie reste `kv2_ville` ;
- les termes, relations, imports et hooks `created_kv2_ville` / `edited_kv2_ville` restent inchangés.

## Vérification réalisée en production

Le 12 septembre 2026, le correctif a été appliqué sur `tapissier-laurot.fr`.

Contrôles réussis :

1. `get_taxonomy('kv2_ville')->rest_base` renvoie `kv2_ville_terms` ;
2. `/wp/v2/kv2_ville_terms` renvoie bien des termes avec `id`, `name`, `slug` et `taxonomy => kv2_ville` ;
3. le sélecteur de localisation ne déclenche plus l'erreur `undefined.normalize()` ;
4. ajout réel d'un **département** et d'une **ville précise** dans une réalisation : validé.

## Commandes de diagnostic utiles sur Plesk

Le PHP système n'étant pas présent dans le `PATH`, utiliser le binaire Plesk PHP 8.3. Elementor peut également consommer les 128 Mo par défaut en CLI, d'où l'utilisation de `memory_limit=1G` et `--skip-plugins` pour les diagnostics.

```bash
/opt/plesk/php/8.3/bin/php -d memory_limit=1G /usr/local/bin/wp eval '$t=get_taxonomy("kv2_ville");var_dump($t->rest_base);' --allow-root --skip-plugins=elementor,elementor-pro
```

```bash
/opt/plesk/php/8.3/bin/php -d memory_limit=1G /usr/local/bin/wp eval '$r=new WP_REST_Request("GET","/wp/v2/kv2_ville_terms");$r->set_param("per_page",3);$x=rest_do_request($r);print_r($x->get_data());' --allow-root --skip-plugins=elementor,elementor-pro
```

## Version

- Version du plugin installée au moment de l'incident : **1.2.2**.
- Correctif appliqué en production comme hotfix le **12/09/2026**.
- À intégrer dans le code source et le prochain paquet publié sous **1.2.3** (ou version supérieure) avant toute mise à jour du plugin sur les sites concernés.

## Garde-fou pour les futures versions

Ne pas supprimer ce `rest_base` tant qu'un site peut charger simultanément un CPT `kv2_ville` et la taxonomie `kv2_ville`. Le nom interne peut rester identique ; c'est la séparation des routes REST qui évite la collision.
