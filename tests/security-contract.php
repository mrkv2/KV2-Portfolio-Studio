<?php

$root   = dirname( __DIR__ );
$errors = array();
$files  = array(
	'json'       => file_get_contents( $root . '/includes/class-kv2ps-json.php' ),
	'post_types' => file_get_contents( $root . '/includes/class-kv2ps-post-types.php' ),
	'importer'   => file_get_contents( $root . '/includes/class-kv2ps-importer.php' ),
	'seo'        => file_get_contents( $root . '/includes/class-kv2ps-seo.php' ),
	'redirects'  => file_get_contents( $root . '/includes/class-kv2ps-redirects.php' ),
	'schema'     => file_get_contents( $root . '/includes/class-kv2ps-schema.php' ),
	'compatibility' => file_get_contents( $root . '/includes/class-kv2ps-compatibility.php' ),
);

$required_contracts = array(
	array( 'json', 'is_uploaded_file(', 'Uploaded JSON must come from a valid HTTP upload.' ),
	array( 'json', 'wp_check_filetype(', 'Uploaded JSON must have an allowed file type.' ),
	array( 'json', 'json_decode( $json, true, 64 )', 'JSON decoding must have a finite depth.' ),
	array( 'post_types', "'context' => array( 'edit' )", 'Business metadata must be REST edit-only.' ),
	array( 'post_types', "current_user_can( 'edit_post'", 'REST metadata writes must use object-level permissions.' ),
	array( 'importer', 'wp_kses_post( apply_filters(', 'Imported WP Portfolio HTML must be filtered.' ),
	array( 'seo', "add_filter( 'rank_math/frontend/canonical'", 'Pagination must coordinate with Rank Math canonical output.' ),
	array( 'redirects', 'wp_safe_redirect( $target, 301', 'The technical archive must use a safe permanent redirect.' ),
	array( 'schema', '! self::has_type( $data, \'CreativeWork\' )', 'CreativeWork must not be duplicated in the Rank Math graph.' ),
	array( 'compatibility', "shortcode_exists( 'wp_portfolio' )", 'The legacy shortcode alias must never override an active provider.' ),
	array( 'compatibility', "add_filter( 'rank_math/sitemap/entry'", 'Gallery-only records must be removable from the Rank Math sitemap.' ),
	array( 'compatibility', 'wp_safe_redirect( $target, 302', 'Gallery-only records must use a reversible migration redirect.' ),
);

foreach ( $required_contracts as $contract ) {
	list( $file_key, $needle, $message ) = $contract;
	if ( false === strpos( $files[ $file_key ], $needle ) ) {
		$errors[] = $message;
	}
}

$all_php = implode( "\n", array_map( 'file_get_contents', glob( $root . '/includes/*.php' ) ) );
foreach ( array( 'eval(', 'shell_exec(', 'passthru(', 'unserialize(' ) as $dangerous_call ) {
	if ( false !== strpos( $all_php, $dangerous_call ) ) {
		$errors[] = 'Forbidden call found: ' . $dangerous_call;
	}
}

if ( $errors ) {
	fwrite( STDERR, implode( PHP_EOL, $errors ) . PHP_EOL );
	exit( 1 );
}

echo "Security contract checks passed.\n";
