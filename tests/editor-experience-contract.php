<?php

$root         = dirname( __DIR__ );
$completeness = file_get_contents( $root . '/includes/class-kv2ps-completeness.php' );
$post_types   = file_get_contents( $root . '/includes/class-kv2ps-post-types.php' );
$admin        = file_get_contents( $root . '/includes/class-kv2ps-admin.php' );
$admin_js     = file_get_contents( $root . '/assets/admin.js' );
$frontend_js  = file_get_contents( $root . '/assets/frontend.js' );
$template     = file_get_contents( $root . '/templates/single-kv2_realisation.php' );
$css          = file_get_contents( $root . '/assets/frontend.css' );
$errors       = array();

$contracts = array(
	array( $completeness, 'wp_ajax_kv2ps_completeness_report', 'The publication checklist must expose a secured refresh endpoint.' ),
	array( $completeness, "'optional'=> \$optional", 'Optional enrichments must be separated from publication essentials.' ),
	array( $completeness, "'classification'", 'A realization must accept any relevant taxonomy as a useful classification.' ),
	array( $post_types, "'kv2_ville'     => array( 'Villes', 'Ville'", 'The native multi-value city taxonomy must remain available in the editor.' ),
	array( $admin, 'Villes multiples', 'The location panel must explain how to assign several cities.' ),
	array( $admin, "wp_nonce_field( 'kv2ps_save_location', 'kv2ps_location_nonce' )", 'The location panel must own an independent save nonce.' ),
	array( $admin, 'self::save_location( $post_id )', 'Location saving must not depend on the editorial metabox nonce.' ),
	array( $post_types, 'set_location_details', 'Department and postal code must save without replacing city terms.' ),
	array( $admin, 'realisation_columns', 'The realization overview must expose a controlled location column.' ),
	array( $admin, 'kv2ps_location', 'The realization overview must display the saved city.' ),
	array( $completeness, "'target' => '#kv2ps-project-location'", 'The checklist must point to the dedicated location panel.' ),
	array( $admin_js, 'didPostSaveRequestSucceed', 'The checklist must refresh after a successful block-editor save.' ),
	array( $admin_js, 'kv2ps_completeness_report', 'The editor script must call the checklist refresh endpoint.' ),
	array( $template, 'Retour à toutes les réalisations', 'Single projects must link back to the configured portfolio.' ),
	array( $template, 'kv2ps-story--count-', 'Story cards must expose their real item count to the responsive layout.' ),
	array( $template, 'kv2ps-project-layout--content-only', 'Content-only projects must not reserve an empty sidebar.' ),
	array( $template, 'kv2ps-project-layout--sidebar-only', 'Metadata-only projects must not reserve an empty content column.' ),
	array( $css, '.kv2ps-back-bar', 'The lower portfolio return link must have a dedicated accessible region.' ),
	array( $css, 'container-type: inline-size', 'Portfolio columns must react to their actual container width.' ),
	array( $frontend_js, 'grid.getBoundingClientRect().width', 'Masonry must measure its own container instead of the viewport.' ),
	array( $frontend_js, 'maxColumnsByWidth', 'Masonry must cap columns before cards become too narrow.' ),
	array( $css, '.ast-separate-container .kv2ps-card.ast-article-single', 'Astra card padding must not shrink portfolio images.' ),
);

foreach ( $contracts as $contract ) {
	if ( false === strpos( $contract[0], $contract[1] ) ) {
		$errors[] = $contract[2];
	}
}

if ( false !== strpos( $admin, 'name="kv2ps_location_city"' ) ) {
	$errors[] = 'The obsolete single-city input must not duplicate the native multi-value city taxonomy.';
}
if ( false !== strpos( $post_types, "\$args['meta_box_cb'] = false" ) ) {
	$errors[] = 'The native multi-value city taxonomy must not be hidden.';
}

if ( $errors ) {
	fwrite( STDERR, implode( PHP_EOL, $errors ) . PHP_EOL );
	exit( 1 );
}

echo "Editor experience contract checks passed.\n";
