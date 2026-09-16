<?php

$root = dirname( __DIR__ );
$errors = array();

$bootstrap = file_get_contents( $root . '/kv2-portfolio-studio.php' );
$module    = file_get_contents( $root . '/includes/class-kv2ps-ets-mon-toit.php' );
$admin_js  = file_get_contents( $root . '/assets/ets-mon-toit-admin.js' );
$front_css = file_get_contents( $root . '/assets/ets-mon-toit-frontend.css' );
$wrapper   = file_get_contents( $root . '/templates/single-kv2_realisation-ets-mon-toit.php' );
$post_types = file_get_contents( $root . '/includes/class-kv2ps-post-types.php' );

$checks = array(
	array( false !== strpos( $bootstrap, "Version: 1.3.0-emt.1" ), 'ETS Mon Toit branch version must be 1.3.0-emt.1.' ),
	array( false !== strpos( $bootstrap, "class-kv2ps-ets-mon-toit.php" ), 'ETS Mon Toit module must be loaded by the bootstrap.' ),
	array( false !== strpos( $module, "_kv2ps_video_ids" ), 'Video attachment IDs must use their own post meta.' ),
	array( false !== strpos( $module, "register_post_meta" ), 'Video post meta must be REST-registered.' ),
	array( false !== strpos( $module, "video/mp4" ) && false !== strpos( $module, "video/webm" ), 'MP4 and WebM must be supported.' ),
	array( false !== strpos( $module, "playsinline" ) && false !== strpos( $module, "preload=\"metadata\"" ), 'Frontend video must be mobile-friendly and metadata-only preload.' ),
	array( false !== strpos( $module, "wp_verify_nonce" ) && false !== strpos( $module, "current_user_can" ), 'Video save flow must keep nonce and capability checks.' ),
	array( false !== strpos( $admin_js, 'library: { type: "video" }' ), 'Media picker must be restricted to videos.' ),
	array( false !== strpos( $admin_js, '.sortable(' ), 'Video order must be sortable.' ),
	array( false !== strpos( $front_css, 'object-fit: contain' ), 'Vertical chantier videos must keep their full frame.' ),
	array( false !== strpos( $wrapper, 'render_wrapped_single_template' ), 'Single realization wrapper must render the dedicated video section.' ),
	array( false !== strpos( $post_types, "'rest_base']   = 'kv2_ville_terms'" ), 'kv2_ville REST collision hotfix must remain present on the site branch.' ),
);

foreach ( $checks as $check ) {
	if ( ! $check[0] ) {
		$errors[] = $check[1];
	}
}

if ( $errors ) {
	fwrite( STDERR, implode( PHP_EOL, $errors ) . PHP_EOL );
	exit( 1 );
}

echo "ETS Mon Toit video contract passed.\n";
