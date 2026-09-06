<?php

define( 'ABSPATH', __DIR__ . '/' );

function sanitize_title( $value ) {
	$value = strtolower( trim( (string) $value ) );
	$value = preg_replace( '/[^a-z0-9]+/', '-', $value );
	return trim( $value, '-' );
}
function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

require dirname( __DIR__ ) . '/includes/class-kv2ps-post-types.php';
require dirname( __DIR__ ) . '/includes/class-kv2ps-compatibility.php';

$legacy = array(
	array(
		'taxonomy' => 'kv2_service',
		'field'    => 'term_id',
		'terms'    => array( 99 ),
	),
);

$query = KV2PS_Compatibility::shortcode_tax_query(
	array(
		'service'   => 'refection-de-chaises',
		'ville'     => 'Paris 16e (75)',
		'meuble'    => 'chaise',
		'style'     => 'louis-xvi',
		'technique' => 'cannage|rempaillage',
	),
	$legacy
);

if ( 6 !== count( $query ) || $legacy[0] !== $query[0] ) {
	throw new RuntimeException( 'Native filters did not preserve the legacy taxonomy query.' );
}

$expected = array(
	'kv2_service'   => array( 'refection-de-chaises' ),
	'kv2_ville'     => array( 'paris-16e' ),
	'kv2_meuble'    => array( 'chaise' ),
	'kv2_style'     => array( 'louis-xvi' ),
	'kv2_technique' => array( 'cannage', 'rempaillage' ),
);

foreach ( array_slice( $query, 1 ) as $clause ) {
	$taxonomy = isset( $clause['taxonomy'] ) ? $clause['taxonomy'] : '';
	if ( ! isset( $expected[ $taxonomy ] ) || 'slug' !== $clause['field'] || $expected[ $taxonomy ] !== $clause['terms'] ) {
		throw new RuntimeException( 'Unexpected shortcode taxonomy clause for ' . $taxonomy . '.' );
	}
	unset( $expected[ $taxonomy ] );
}

if ( $expected ) {
	throw new RuntimeException( 'One or more shortcode taxonomy filters were not created.' );
}

$roofing_query = KV2PS_Compatibility::shortcode_tax_query(
	array(
		'element_toiture' => 'fenetre-toit-velux',
		'materiau'        => 'zinc-vmzinc',
	)
);
if ( 2 !== count( $roofing_query ) || 'kv2_meuble' !== $roofing_query[0]['taxonomy'] || 'kv2_style' !== $roofing_query[1]['taxonomy'] ) {
	throw new RuntimeException( 'Roofing shortcode aliases were not mapped to the compatible internal taxonomies.' );
}

echo "Shortcode filter contract checks passed.\n";
