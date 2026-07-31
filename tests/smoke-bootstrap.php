<?php

define( 'ABSPATH', __DIR__ . '/' );

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

require dirname( __DIR__ ) . '/kv2-portfolio-studio.php';

if ( ! class_exists( 'KV2PS_Plugin' ) || ! class_exists( 'KV2PS_Project_Package' ) ) {
	throw new RuntimeException( 'Plugin classes did not load.' );
}

KV2PS_Plugin::instance()->maybe_upgrade();
$migrated = $GLOBALS['kv2ps_test_options']['kv2ps_settings'];
if ( 'masonry' !== $migrated['archive_layout'] || '3' !== $migrated['archive_columns'] || 'classic' !== $migrated['archive_card_style'] || 'button' !== $migrated['archive_load_mode'] ) {
	throw new RuntimeException( 'Legacy display defaults were not migrated to the classic portfolio preset.' );
}
if ( '04 11 93 96 29' !== $migrated['phone'] || 'ctc_greetings' !== $migrated['ctc_trigger'] || empty( $migrated['cta_process_steps'] ) || empty( $migrated['portfolio_page_url'] ) ) {
	throw new RuntimeException( 'Enhanced CTA defaults were not migrated.' );
}
if ( empty( $migrated['portfolio_seo_title'] ) || empty( $migrated['portfolio_meta_description'] ) || '1' !== $migrated['redirect_archive_to_portfolio'] || '1' !== $migrated['rank_math_schema'] ) {
	throw new RuntimeException( 'SEO 1.1.7 defaults were not migrated.' );
}
if ( '1.1.7' !== $GLOBALS['kv2ps_test_options']['kv2ps_version'] ) {
	throw new RuntimeException( 'Installed version was not upgraded to 1.1.7.' );
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

echo "Bootstrap smoke test passed.\n";
