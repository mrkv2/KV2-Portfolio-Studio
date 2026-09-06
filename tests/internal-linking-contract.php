<?php

$root     = dirname( __DIR__ );
$linker   = file_get_contents( $root . '/includes/class-kv2ps-internal-links.php' );
$template = file_get_contents( $root . '/templates/single-kv2_realisation.php' );
$errors   = array();

foreach ( array( 'cannage-de-chaises', 'refection-de-chaises', 'rempaillage-de-chaises', 'refection-de-fauteuils', 'refection-de-canapes', 'tapisserie-ameublement' ) as $service_slug ) {
	if ( false === strpos( $linker, "'" . $service_slug . "'" ) ) {
		$errors[] = 'Missing upholstery service candidate: ' . $service_slug;
	}
}
if ( false === strpos( $linker, 'PROFILE_UPHOLSTERY' ) ) {
	$errors[] = 'Automatic commercial mappings must remain scoped to the upholstery profile.';
}
if ( false === strpos( $linker, "get_post_meta( \$post->ID, 'url', true )" ) || false === strpos( $linker, "get_post_meta( \$post->ID, '_kv2_url', true )" ) ) {
	$errors[] = 'City links must reuse the canonical URL from the technical city registry.';
}
if ( false === strpos( $template, 'KV2PS_Internal_Links::term_url' ) ) {
	$errors[] = 'The single template must use the internal-link resolver.';
}
if ( false === strpos( file_get_contents( $root . '/includes/class-kv2ps-plugin.php' ), 'KV2PS_Internal_Links::term_url' ) ) {
	$errors[] = 'Portfolio cards must use the internal-link resolver.';
}
if ( false !== strpos( $template, "\$links[] = is_wp_error( \$url )" ) ) {
	$errors[] = 'The template can still create empty taxonomy anchors.';
}
if ( false === strpos( $template, "'taxonomy' => 'kv2_service'" ) || false === strpos( $template, "'posts_per_page'      => 3" ) ) {
	$errors[] = 'Three related realizations must remain filtered by service.';
}

if ( $errors ) {
	fwrite( STDERR, implode( PHP_EOL, $errors ) . PHP_EOL );
	exit( 1 );
}

echo "Internal linking checks passed.\n";
