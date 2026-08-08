<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'OBJECT', 'OBJECT' );

class WP_Post {
	public $ID = 0;
	public $post_status = 'publish';
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
function home_url( $path = '/' ) {
	return 'https://example.test' . $path;
}
function get_page_by_path( $path ) {
	if ( empty( $GLOBALS['kv2ps_page_path'] ) || $path !== $GLOBALS['kv2ps_page_path'] ) {
		return null;
	}
	$page     = new WP_Post();
	$page->ID = 88;
	return $page;
}
function get_permalink( $post_id = 0 ) {
	unset( $post_id );
	return 'https://example.test/' . $GLOBALS['kv2ps_page_path'] . '/';
}
function get_bloginfo( $key ) {
	return 'name' === $key ? 'Atelier Exemple' : '';
}
function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['kv2ps_test_options'] ) ? $GLOBALS['kv2ps_test_options'][ $key ] : $default;
}
function update_option( $key, $value ) {
	$GLOBALS['kv2ps_test_options'][ $key ] = $value;
	return true;
}

$GLOBALS['kv2ps_test_options'] = array(
	'kv2ps_version'  => '1.0.0',
	'kv2ps_settings' => array(
		'archive_layout'         => 'grid',
		'archive_columns'        => '2',
		'archive_image_ratio'    => '3-2',
		'archive_card_style'     => 'elevated',
		'archive_posts_per_page' => '8',
		'archive_load_mode'      => 'paged',
	),
);
$GLOBALS['kv2ps_page_path'] = '';

require dirname( __DIR__ ) . '/kv2-portfolio-studio.php';

if ( ! class_exists( 'KV2PS_Plugin' ) || ! class_exists( 'KV2PS_Project_Package' ) ) {
	throw new RuntimeException( 'Plugin classes did not load.' );
}

KV2PS_Plugin::instance()->maybe_upgrade();
$migrated = $GLOBALS['kv2ps_test_options']['kv2ps_settings'];
if ( 'masonry' !== $migrated['archive_layout'] || '3' !== $migrated['archive_columns'] || 'classic' !== $migrated['archive_card_style'] || 'button' !== $migrated['archive_load_mode'] ) {
	throw new RuntimeException( 'Legacy display defaults were not migrated to the classic portfolio preset.' );
}
if ( '' !== $migrated['phone'] || 'ctc_greetings' !== $migrated['ctc_trigger'] || empty( $migrated['cta_process_steps'] ) || empty( $migrated['portfolio_page_url'] ) ) {
	throw new RuntimeException( 'Enhanced CTA defaults were not migrated.' );
}
if ( empty( $migrated['portfolio_seo_title'] ) || empty( $migrated['portfolio_meta_description'] ) || '1' !== $migrated['redirect_archive_to_portfolio'] || '1' !== $migrated['rank_math_schema'] ) {
	throw new RuntimeException( 'SEO 1.1.7 defaults were not migrated.' );
}
if ( 'existing_page' === $migrated['routing_mode'] || '0' !== $migrated['legacy_shortcode_alias'] || 'realisation' !== $migrated['single_slug'] ) {
	throw new RuntimeException( 'Safe routing defaults were not migrated correctly.' );
}
if ( KV2PS_VERSION !== $GLOBALS['kv2ps_test_options']['kv2ps_version'] ) {
	throw new RuntimeException( 'Installed version was not upgraded to the current plugin version.' );
}

$GLOBALS['kv2ps_test_options'] = array(
	'kv2ps_version'  => '1.0.0',
	'kv2ps_settings' => array(
		'archive_layout'         => 'tiles',
		'archive_columns'        => '2',
		'archive_image_ratio'    => '3-2',
		'archive_card_style'     => 'elevated',
		'archive_posts_per_page' => '8',
		'archive_load_mode'      => 'paged',
	),
);
KV2PS_Plugin::instance()->maybe_upgrade();
if ( 'tiles' !== $GLOBALS['kv2ps_test_options']['kv2ps_settings']['archive_layout'] ) {
	throw new RuntimeException( 'A customized display setting was overwritten during migration.' );
}

$GLOBALS['kv2ps_page_path'] = 'realisations';
$defaults = KV2PS_Plugin::default_settings();
if ( 'existing_page' !== $defaults['routing_mode'] || 'https://example.test/realisations/' !== $defaults['portfolio_page_url'] || '0' !== $defaults['redirect_archive_to_portfolio'] ) {
	throw new RuntimeException( 'An existing /realisations/ Page was not protected.' );
}
$GLOBALS['kv2ps_page_path'] = 'realisation-tapisserie';
$defaults = KV2PS_Plugin::default_settings();
if ( 'standard' !== $defaults['routing_mode'] || 'https://example.test/realisation-tapisserie/' !== $defaults['portfolio_page_url'] ) {
	throw new RuntimeException( 'A non-conflicting catalog Page changed the standard routing mode.' );
}

echo "Bootstrap smoke test passed.\n";
