<?php

$root          = dirname( __DIR__ );
$post_types    = file_get_contents( $root . '/includes/class-kv2ps-post-types.php' );
$admin         = file_get_contents( $root . '/includes/class-kv2ps-admin.php' );
$city_selector = file_get_contents( $root . '/assets/city-selector.js' );
$errors        = array();

$contracts = array(
	array( $post_types, "'kv2_ville'     => array( 'Villes', 'Ville', false", 'Cities must remain a flat multi-value taxonomy.' ),
	array( $admin, "add_action( 'enqueue_block_editor_assets'", 'The custom city selector must load in the block editor.' ),
	array( $admin, "array( 'wp-components', 'wp-core-data'", 'The editor selector must declare its WordPress dependencies.' ),
	array( $admin, "'hide_empty' => false", 'All existing cities must be loaded on the server, including unused terms.' ),
	array( $admin, "'terms'        => \$city_terms", 'Existing cities must be injected when the editor loads.' ),
	array( $admin, "add_action( 'wp_ajax_kv2ps_create_city'", 'New cities must use the authenticated WordPress administration endpoint.' ),
	array( $admin, "check_ajax_referer( 'kv2ps_city_selector', 'nonce' )", 'City creation must be protected by a dedicated nonce.' ),
	array( $admin, "KV2PS_Post_Types::ensure_term", 'City creation must reuse the canonical de-duplication logic.' ),
	array( $city_selector, 'editor.PostTaxonomyType', 'The native flat city field must be replaced without affecting other taxonomies.' ),
	array( $city_selector, 'useState(mergeTerms([], settings.terms))', 'Injected cities must initialize autocomplete without an extra REST request.' ),
	array( $city_selector, 'sanitizeTerms(items)', 'Terms must be sanitized before reaching FormTokenField.' ),
	array( $city_selector, 'typeof value !== "string"', 'Undefined and non-string names must be rejected before normalize can be called.' ),
	array( $city_selector, 'suggestions: suggestions', 'The sanitized city list must feed autocomplete.' ),
	array( $city_selector, 'selection.selectedIds', 'Already assigned cities must be restored as selected tokens.' ),
	array( $city_selector, 'Promise.all(missingNames.map(createTerm))', 'Several new cities must be creatable without losing existing selections.' ),
	array( $city_selector, 'request.append("action", "kv2ps_create_city")', 'New cities must be sent to the dedicated administration action.' ),
	array( $city_selector, 'edit[restBase] = ids', 'The selector must save the complete set of city term IDs through the editor.' ),
	array( $city_selector, '!settings.termsError', 'The field must stay unavailable if cities could not be prepared on the server.' ),
);

foreach ( $contracts as $contract ) {
	if ( false === strpos( $contract[0], $contract[1] ) ) {
		$errors[] = $contract[2];
	}
}

if ( false !== strpos( $post_types, "'kv2_ville'     => array( 'Villes', 'Ville', true" ) ) {
	$errors[] = 'Cities must not fall back to the category-style hierarchical selector.';
}
if ( false !== strpos( $city_selector, 'apiFetch' ) || false !== strpos( $city_selector, '/wp/v2/' ) ) {
	$errors[] = 'The city selector must not depend on the site-specific REST terms route.';
}

if ( $errors ) {
	fwrite( STDERR, implode( PHP_EOL, $errors ) . PHP_EOL );
	exit( 1 );
}

echo "City selector contract checks passed.\n";
