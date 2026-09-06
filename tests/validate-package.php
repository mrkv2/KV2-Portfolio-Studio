<?php

$root    = dirname( __DIR__ );
$example = json_decode( file_get_contents( $root . '/examples/chatgpt-image-metadata.example.json' ), true );
$project_example = json_decode( file_get_contents( $root . '/examples/chatgpt-realisation.example.json' ), true );
$roofing_example = json_decode( file_get_contents( $root . '/examples/ets-mon-toit-chaville-zinc.example.json' ), true );
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
if ( empty( $project_example['project']['location']['city'] ) || empty( $project_example['project']['location']['postal_code'] ) || empty( $project_example['project']['taxonomies']['villes'][0]['slug'] ) ) {
	$errors[] = 'Complete realization example is missing its canonical location fields.';
}
if ( 'roofing' !== $roofing_example['source']['business_profile'] || 'Chaville' !== $roofing_example['project']['location']['city'] || empty( $roofing_example['project']['taxonomies']['elements_toiture'] ) || empty( $roofing_example['project']['taxonomies']['materiaux'] ) || 2 !== count( $roofing_example['project']['images'] ) ) {
	$errors[] = 'ETS Mon Toit roofing example is incomplete.';
}

$required = array(
	'kv2-portfolio-studio.php',
	'includes/class-kv2ps-plugin.php',
	'includes/class-kv2ps-post-types.php',
	'includes/class-kv2ps-admin.php',
	'includes/class-kv2ps-json.php',
	'includes/class-kv2ps-image-metadata.php',
	'includes/class-kv2ps-importer.php',
	'includes/class-kv2ps-compatibility.php',
	'includes/class-kv2ps-seo.php',
	'includes/class-kv2ps-schema.php',
	'includes/class-kv2ps-completeness.php',
	'includes/class-kv2ps-project-package.php',
	'includes/class-kv2ps-redirects.php',
	'assets/frontend.js',
	'schema/chatgpt-realisation.schema.json',
	'examples/chatgpt-realisation.example.json',
	'examples/ets-mon-toit-chaville-zinc.example.json',
	'tests/smoke-bootstrap.php',
	'tests/security-contract.php',
	'tests/seo-contract.php',
	'tests/migration-contract.php',
	'tests/shortcode-filters-contract.php',
	'tests/single-layout-contract.php',
	'tests/editor-experience-contract.php',
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
