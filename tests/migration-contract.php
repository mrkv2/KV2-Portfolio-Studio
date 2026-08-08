<?php

define( 'ABSPATH', __DIR__ . '/' );

class KV2PS_Post_Types {
	const POST_TYPE = 'kv2_realisation';
	public static function taxonomies() {
		return array( 'kv2_service', 'kv2_ville', 'kv2_meuble', 'kv2_style', 'kv2_technique' );
	}
}

class KV2PS_Plugin {
	public static function settings() {
		return array( 'legacy_shortcode_alias' => '1' );
	}
	public static function instance() {
		return new self();
	}
	public function portfolio_shortcode() {}
}

function add_action() {}
function add_filter() {}
function add_shortcode( $tag ) {
	$GLOBALS['kv2ps_registered_shortcodes'][] = $tag;
}
function shortcode_exists( $tag ) {
	return in_array( $tag, $GLOBALS['kv2ps_existing_shortcodes'], true );
}
function get_post_meta( $post_id, $key = '', $single = false ) {
	if ( isset( $GLOBALS['kv2ps_source_meta'][ $post_id ][ $key ] ) ) {
		$value = $GLOBALS['kv2ps_source_meta'][ $post_id ][ $key ];
		return $single ? reset( $value ) : $value;
	}
	unset( $post_id, $single );
	return isset( $GLOBALS['kv2ps_meta'][ $key ] ) ? $GLOBALS['kv2ps_meta'][ $key ] : '';
}
function get_post_type( $post_id = 0 ) {
	if ( $post_id && in_array( (int) $post_id, $GLOBALS['kv2ps_image_ids'], true ) ) {
		return 'attachment';
	}
	return KV2PS_Post_Types::POST_TYPE;
}
function get_post_mime_type( $post_id ) {
	return in_array( (int) $post_id, $GLOBALS['kv2ps_image_ids'], true ) ? 'image/jpeg' : '';
}
function get_post_thumbnail_id( $post_id ) {
	return 77 === (int) $post_id ? 100 : 0;
}
function attachment_url_to_postid( $url ) {
	$map = array(
		'https://example.test/uploads/four.jpg' => 104,
		'https://example.test/uploads/five.jpg' => 105,
	);
	return isset( $map[ $url ] ) ? $map[ $url ] : 0;
}
function esc_url_raw( $url ) {
	return filter_var( $url, FILTER_SANITIZE_URL );
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function home_url( $path = '/' ) {
	return 'https://example.test' . $path;
}
function get_permalink( $post_id ) {
	return 'https://example.test/realisation/' . absint( $post_id ) . '/';
}
function absint( $value ) {
	return abs( (int) $value );
}

$GLOBALS['kv2ps_existing_shortcodes']   = array( 'wp_portfolio' );
$GLOBALS['kv2ps_registered_shortcodes'] = array();
$GLOBALS['kv2ps_meta']                  = array();
$GLOBALS['kv2ps_image_ids']             = array( 100, 101, 102, 103, 104, 105 );
$GLOBALS['kv2ps_source_meta']           = array(
	77 => array(
		'astra-portfolio-image-id' => array(
			array(
				101,
				array( 102, '<img class="wp-image-103" src="https://example.test/uploads/four.jpg">' ),
			),
		),
		'astra-lightbox-image-id' => array( 'https://example.test/uploads/five.jpg?cache=1' ),
	),
);

require dirname( __DIR__ ) . '/includes/class-kv2ps-compatibility.php';
require dirname( __DIR__ ) . '/includes/class-kv2ps-importer.php';

KV2PS_Compatibility::maybe_register_legacy_shortcode();
if ( $GLOBALS['kv2ps_registered_shortcodes'] ) {
	throw new RuntimeException( 'The active WP Portfolio shortcode was overwritten.' );
}

$GLOBALS['kv2ps_existing_shortcodes'] = array();
KV2PS_Compatibility::maybe_register_legacy_shortcode();
if ( array( 'wp_portfolio' ) !== $GLOBALS['kv2ps_registered_shortcodes'] || ! KV2PS_Compatibility::legacy_alias_registered() ) {
	throw new RuntimeException( 'The opt-in alias was not registered after the legacy provider was removed.' );
}

$atts = KV2PS_Compatibility::normalize_legacy_atts(
	array(
		'per-page'       => '9',
		'show-search'    => 'no',
		'show-categories'=> 'no',
		'pagination'     => 'false',
	)
);
if ( '9' !== $atts['limit'] || 'no' !== $atts['show_search'] || 'no' !== $atts['show_filters'] || 'none' !== $atts['load_mode'] ) {
	throw new RuntimeException( 'Legacy shortcode attributes were not normalized.' );
}

$GLOBALS['kv2ps_meta']['_kv2ps_publication_mode'] = 'gallery';
if ( false !== KV2PS_Compatibility::filter_rank_math_sitemap_entry( array( 'loc' => 'x' ), 'post', (object) array( 'ID' => 42 ) ) ) {
	throw new RuntimeException( 'Gallery-only record was not excluded from the Rank Math sitemap.' );
}
$query = KV2PS_Compatibility::filter_core_sitemap_query( array(), KV2PS_Post_Types::POST_TYPE );
if ( empty( $query['meta_query'] ) ) {
	throw new RuntimeException( 'Gallery-only records were not excluded from the core sitemap query.' );
}

$image_ids = KV2PS_Importer::find_source_image_ids( 77 );
if ( array( 100, 101, 102, 103, 104, 105 ) !== $image_ids ) {
	throw new RuntimeException( 'Nested WP Portfolio image values were not imported completely: ' . implode( ',', $image_ids ) );
}

echo "Migration contract checks passed.\n";
