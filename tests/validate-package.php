<?php

$root    = dirname( __DIR__ );
$example = json_decode( file_get_contents( $root . '/examples/chatgpt-image-metadata.example.json' ), true );
$project_example = json_decode( file_get_contents( $root . '/examples/chatgpt-realisation.example.json' ), true );
$errors  = array();

if ( JSON_ERROR_NONE !== json_last_error() ) {
	$errors[] = 'Example JSON is invalid: ' . json_last_error_msg();
}
if ( ! isset( $example['schema_version'] ) || '1.0' !== $example['schema_version'] ) {
	$errors[] = 'schema_version must be 1.0.';
}
if ( empty( $example['images'] ) || ! is_array( $example['images'] ) ) {
	$errors[] = 'images must be a non-empty array.';
}
foreach ( isset( $example['images'] ) && is_array( $example['images'] ) ? $example['images'] : array() as $index => $image ) {
	if ( empty( $image['match'] ) || empty( $image['fields'] ) ) {
		$errors[] = 'Image ' . $index . ' is missing match or fields.';
	}
}
if ( ! isset( $project_example['schema_version'] ) || '1.1' !== $project_example['schema_version'] || empty( $project_example['project']['fields']['title'] ) ) {
	$errors[] = 'Complete realization example is invalid.';
}

$required = array(
	'kv2-portfolio-studio.php',
	'includes/class-kv2ps-plugin.php',
	'includes/class-kv2ps-post-types.php',
	'includes/class-kv2ps-admin.php',
	'includes/class-kv2ps-image-metadata.php',
	'includes/class-kv2ps-importer.php',
	'includes/class-kv2ps-schema.php',
	'includes/class-kv2ps-completeness.php',
	'includes/class-kv2ps-project-package.php',
	'includes/class-kv2ps-redirects.php',
	'assets/frontend.js',
	'schema/chatgpt-realisation.schema.json',
	'examples/chatgpt-realisation.example.json',
	'tests/smoke-bootstrap.php',
	'templates/single-kv2_realisation.php',
	'templates/archive-kv2_realisation.php',
);

foreach ( $required as $file ) {
	if ( ! is_file( $root . '/' . $file ) ) {
		$errors[] = 'Missing file: ' . $file;
	}
}

$public_examples = file_get_contents( $root . '/examples/chatgpt-image-metadata.example.json' ) . file_get_contents( $root . '/examples/chatgpt-realisation.example.json' );
if ( false === strpos( $public_examples, 'https://example.com/' ) || false === strpos( $public_examples, 'Atelier Exemple' ) ) {
	$errors[] = 'Public examples must use the generic example identity.';
}
if ( preg_match_all( '#https?://([a-z0-9.-]+)#i', $public_examples, $matches ) ) {
	foreach ( array_unique( $matches[1] ) as $host ) {
		if ( 'example.com' !== strtolower( $host ) ) {
			$errors[] = 'Public examples contain a non-generic host: ' . $host;
		}
	}
}

if ( $errors ) {
	fwrite( STDERR, implode( PHP_EOL, $errors ) . PHP_EOL );
	exit( 1 );
}

echo "Package checks passed.\n";
