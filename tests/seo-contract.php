<?php

define( 'ABSPATH', __DIR__ . '/' );

class WP_Post {
	public $post_content = '[kv2_portfolio]';
}

function plugin_dir_path( $file ) {
	return dirname( $file ) . '/';
}

function plugin_dir_url( $file = '' ) {
	unset( $file );
	return 'https://example.test/wp-content/plugins/kv2-portfolio-studio/';
}

function plugin_basename( $file ) {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}

function add_action() {}
function add_filter() {}
function add_shortcode() {}
function register_activation_hook() {}
function register_deactivation_hook() {}
function is_admin() {
	return false;
}
function is_singular() {
	return true;
}
function get_queried_object_id() {
	return 42;
}
function get_post( $post_id ) {
	unset( $post_id );
	return $GLOBALS['kv2ps_test_post'];
}
function get_permalink( $post_id = 0 ) {
	unset( $post_id );
	return 'https://example.test/realisation-tapisserie/';
}
function get_post_meta( $post_id, $key, $single = false ) {
	unset( $post_id, $single );
	return isset( $GLOBALS['kv2ps_test_meta'][ $key ] ) ? $GLOBALS['kv2ps_test_meta'][ $key ] : '';
}
function home_url( $path = '/' ) {
	return 'https://example.test' . $path;
}
function get_bloginfo( $key ) {
	return 'name' === $key ? 'Atelier Exemple' : 'fr-FR';
}
function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['kv2ps_test_options'] ) ? $GLOBALS['kv2ps_test_options'][ $key ] : $default;
}
function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}
function esc_url_raw( $url ) {
	return filter_var( $url, FILTER_SANITIZE_URL );
}
function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}
function trailingslashit( $value ) {
	return rtrim( $value, '/' ) . '/';
}
function absint( $value ) {
	return abs( (int) $value );
}
function add_query_arg( $key, $value, $url ) {
	$parts = parse_url( $url );
	$query = array();
	if ( ! empty( $parts['query'] ) ) {
		parse_str( $parts['query'], $query );
	}
	$query[ $key ] = $value;
	return $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . http_build_query( $query );
}
function remove_query_arg( $keys, $url ) {
	$parts = parse_url( $url );
	$query = array();
	if ( ! empty( $parts['query'] ) ) {
		parse_str( $parts['query'], $query );
	}
	foreach ( (array) $keys as $key ) {
		unset( $query[ $key ] );
	}
	return $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . ( $query ? '?' . http_build_query( $query ) : '' );
}
function __( $text ) {
	return $text;
}

$GLOBALS['kv2ps_test_post']    = new WP_Post();
$GLOBALS['kv2ps_test_meta']    = array();
$GLOBALS['kv2ps_test_options'] = array(
	'kv2ps_version'  => '1.1.7',
	'kv2ps_settings' => array(
		'portfolio_page_url'         => 'https://example.test/realisation-tapisserie/',
		'portfolio_seo_title'        => 'Réalisations de tapisserie à Montpellier | Atelier Exemple',
		'portfolio_meta_description' => 'Description de secours.',
	),
);

require dirname( __DIR__ ) . '/kv2-portfolio-studio.php';

if ( ! KV2PS_SEO::is_primary_portfolio_page() || KV2PS_SEO::page_has_h1() ) {
	throw new RuntimeException( 'The configured shortcode page was not recognized correctly.' );
}

$_GET = array( 'kv2ps_page' => '2' );
$canonical = KV2PS_SEO::filter_canonical( 'https://example.test/realisation-tapisserie/' );
if ( 'https://example.test/realisation-tapisserie/?kv2ps_page=2' !== $canonical ) {
	throw new RuntimeException( 'Page 2 did not receive its own canonical URL.' );
}

$_GET = array( 'kv2ps_service' => 'fauteuil' );
$robots = KV2PS_SEO::filter_robots( array( 'index' => 'index', 'follow' => 'follow' ) );
if ( 'noindex' !== $robots['index'] || 'follow' !== $robots['follow'] ) {
	throw new RuntimeException( 'Temporary filters were not marked noindex, follow.' );
}

$GLOBALS['kv2ps_test_meta']['_elementor_data'] = '[{"widgetType":"heading","settings":{"header_size":"h1"}}]';
if ( ! KV2PS_SEO::page_has_h1() ) {
	throw new RuntimeException( 'An Elementor H1 was not detected.' );
}

echo "SEO contract checks passed.\n";
