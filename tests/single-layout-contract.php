<?php

$root     = dirname( __DIR__ );
$template = file_get_contents( $root . '/templates/single-kv2_realisation.php' );
$css      = file_get_contents( $root . '/assets/frontend.css' );
$errors   = array();

$contracts = array(
	array( $css, '.single-kv2_realisation #content > .ast-container', 'Astra narrow containers must be neutralized on realization pages.' ),
	array( $css, 'max-width: none !important;', 'The realization shell must be allowed to use the full viewport width.' ),
	array( $css, '.kv2ps-project-layout', 'The editorial content and project details must use a dedicated responsive layout.' ),
	array( $css, '.kv2ps-gallery-group:only-child', 'A single image group must not remain trapped in half of the page.' ),
	array( $template, '$hero_image_id = get_post_thumbnail_id( $post_id );', 'The hero must start with the featured image.' ),
	array( $template, '$hero_image_id = (int) reset( $after_ids );', 'The hero must fall back to the first after image.' ),
	array( $template, "'loading' => 'eager'", 'The hero image must be loaded eagerly.' ),
	array( $template, 'Retour à toutes les réalisations', 'The single template must provide a route back to the configured portfolio.' ),
	array( $template, "get_page_by_path( 'realisations-tapissier', OBJECT, 'page' )", 'The stale default return URL must be resolved only while rendering a single project.' ),
	array( $template, "__( 'Résultat final'", 'A project with only after images must not be labelled before/after.' ),
	array( $css, '.kv2ps-project-layout--content-only', 'A content-only project must use the available width.' ),
	array( $css, '.kv2ps-story--count-2', 'Two story cards must not leave an empty third column.' ),
);

foreach ( $contracts as $contract ) {
	if ( false === strpos( $contract[0], $contract[1] ) ) {
		$errors[] = $contract[2];
	}
}

if ( $errors ) {
	fwrite( STDERR, implode( PHP_EOL, $errors ) . PHP_EOL );
	exit( 1 );
}

echo "Single layout contract checks passed.\n";
