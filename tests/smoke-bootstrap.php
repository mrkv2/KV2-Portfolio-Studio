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
function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}
function sanitize_title( $value ) {
	$value = function_exists( 'iconv' ) ? iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $value ) : (string) $value;
	$value = strtolower( $value );
	$value = preg_replace( '/[^a-z0-9]+/', '-', $value );
	return trim( $value, '-' );
}
function absint( $value ) {
	return abs( (int) $value );
}
function __( $value, $domain = null ) {
	unset( $domain );
	return $value;
}
function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}
function taxonomy_exists( $taxonomy ) {
	return in_array( $taxonomy, array( 'kv2_service', 'kv2_ville', 'kv2_meuble', 'kv2_style', 'kv2_technique' ), true );
}
function get_term_by( $field, $value, $taxonomy ) {
	foreach ( $GLOBALS['kv2ps_test_terms'] as $term ) {
		if ( $taxonomy === $term->taxonomy && isset( $term->{$field} ) && $value === $term->{$field} ) {
			return clone $term;
		}
	}
	return false;
}
function wp_insert_term( $name, $taxonomy, $args = array() ) {
	$slug = isset( $args['slug'] ) ? $args['slug'] : sanitize_title( $name );
	foreach ( $GLOBALS['kv2ps_test_terms'] as $term ) {
		if ( $taxonomy === $term->taxonomy && ( $name === $term->name || $slug === $term->slug ) ) {
			return new WP_Error( 'term_exists', 'Term already exists.', $term->term_id );
		}
	}
	$id = count( $GLOBALS['kv2ps_test_terms'] ) + 1;
	$GLOBALS['kv2ps_test_terms'][ $id ] = (object) array( 'term_id' => $id, 'name' => $name, 'slug' => $slug, 'taxonomy' => $taxonomy );
	return array( 'term_id' => $id, 'term_taxonomy_id' => $id );
}
function wp_update_term( $term_id, $taxonomy, $args ) {
	if ( empty( $GLOBALS['kv2ps_test_terms'][ $term_id ] ) || $taxonomy !== $GLOBALS['kv2ps_test_terms'][ $term_id ]->taxonomy ) {
		return new WP_Error( 'missing_term', 'Term does not exist.' );
	}
	foreach ( array( 'name', 'slug' ) as $field ) {
		if ( isset( $args[ $field ] ) ) {
			$GLOBALS['kv2ps_test_terms'][ $term_id ]->{$field} = $args[ $field ];
		}
	}
	return array( 'term_id' => $term_id, 'term_taxonomy_id' => $term_id );
}
function wp_set_object_terms( $post_id, $term_ids, $taxonomy ) {
	$GLOBALS['kv2ps_test_assignments'][ $post_id ][ $taxonomy ] = array_map( 'absint', (array) $term_ids );
	return $GLOBALS['kv2ps_test_assignments'][ $post_id ][ $taxonomy ];
}
function wp_get_post_terms( $post_id, $taxonomy ) {
	$ids = isset( $GLOBALS['kv2ps_test_assignments'][ $post_id ][ $taxonomy ] ) ? $GLOBALS['kv2ps_test_assignments'][ $post_id ][ $taxonomy ] : array();
	return array_values( array_map( function( $id ) { return clone $GLOBALS['kv2ps_test_terms'][ $id ]; }, $ids ) );
}
function get_post_meta( $post_id, $key, $single = true ) {
	unset( $single );
	return isset( $GLOBALS['kv2ps_test_meta'][ $post_id ][ $key ] ) ? $GLOBALS['kv2ps_test_meta'][ $post_id ][ $key ] : '';
}
function update_post_meta( $post_id, $key, $value ) {
	$GLOBALS['kv2ps_test_meta'][ $post_id ][ $key ] = $value;
	return true;
}
function delete_post_meta( $post_id, $key ) {
	unset( $GLOBALS['kv2ps_test_meta'][ $post_id ][ $key ] );
	return true;
}

class WP_Error {
	private $code;
	private $message;
	private $data;

	public function __construct( $code, $message, $data = null ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}

	public function get_error_code() {
		return $this->code;
	}

	public function get_error_message() {
		return $this->message;
	}

	public function get_error_data( $code = '' ) {
		unset( $code );
		return $this->data;
	}
}

class KV2PS_Test_Query {
	private $values;

	public function __construct( $values ) {
		$this->values = $values;
	}

	public function get( $key ) {
		return isset( $this->values[ $key ] ) ? $this->values[ $key ] : '';
	}

	public function is_post_type_archive() {
		return false;
	}

	public function is_tax() {
		return false;
	}
}

class KV2PS_Test_WPDB {
	public $posts              = 'wp_posts';
	public $postmeta           = 'wp_postmeta';
	public $terms              = 'wp_terms';
	public $term_taxonomy      = 'wp_term_taxonomy';
	public $term_relationships = 'wp_term_relationships';

	public function esc_like( $value ) {
		return addcslashes( $value, '_%\\' );
	}

	public function prepare( $query, ...$values ) {
		if ( substr_count( $query, '%s' ) !== count( $values ) ) {
			throw new RuntimeException( 'Unexpected number of SQL placeholders.' );
		}
		return vsprintf( str_replace( '%s', "'%s'", $query ), array_map( 'addslashes', $values ) );
	}
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
$GLOBALS['kv2ps_test_terms']       = array();
$GLOBALS['kv2ps_test_assignments'] = array();
$GLOBALS['kv2ps_test_meta']        = array();
$GLOBALS['wpdb']                   = new KV2PS_Test_WPDB();

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

$location_result = KV2PS_Post_Types::set_location(
	42,
	array( 'city' => 'Ville Exemple', 'department' => '92', 'postal_code' => '92000' )
);
if ( 1 !== $location_result || 'Ville Exemple (92)' !== $GLOBALS['kv2ps_test_terms'][1]->name || 'ville-exemple' !== $GLOBALS['kv2ps_test_terms'][1]->slug ) {
	throw new RuntimeException( 'The canonical city term was not created.' );
}
$second_location_result = KV2PS_Post_Types::set_location(
	43,
	array( 'city' => 'Ville Exemple (92)', 'department' => '', 'postal_code' => '92000' )
);
if ( 1 !== $second_location_result || 1 !== count( $GLOBALS['kv2ps_test_terms'] ) ) {
	throw new RuntimeException( 'An existing city term was duplicated.' );
}
$stored_location = KV2PS_Post_Types::get_location( 42 );
if ( 'Ville Exemple' !== $stored_location['city'] || '92' !== $stored_location['department'] || '92000' !== $stored_location['postal_code'] ) {
	throw new RuntimeException( 'Location metadata was not stored consistently.' );
}
$first_homonym = KV2PS_Post_Types::set_location(
	44,
	array( 'city' => 'Saint-Denis', 'department' => '93', 'postal_code' => '93200' )
);
$second_homonym = KV2PS_Post_Types::set_location(
	45,
	array( 'city' => 'Saint-Denis', 'department' => '974', 'postal_code' => '97400' )
);
if ( is_wp_error( $first_homonym ) || is_wp_error( $second_homonym ) || $first_homonym === $second_homonym || 'saint-denis-974' !== $GLOBALS['kv2ps_test_terms'][ $second_homonym ]->slug ) {
	throw new RuntimeException( 'Homonymous cities in different departments were not separated.' );
}

$search_where = KV2PS_Plugin::instance()->extend_portfolio_search(
	'WHERE 1=1',
	new KV2PS_Test_Query( array( 'post_type' => KV2PS_Post_Types::POST_TYPE, 'kv2ps_search_term' => '92000' ) )
);
if ( false === strpos( $search_where, 'kv2_ville' ) || false === strpos( $search_where, '_kv2ps_postal_code' ) || false === strpos( $search_where, '_kv2ps_confidential' ) ) {
	throw new RuntimeException( 'The public search does not include the safe location fields.' );
}

echo "Bootstrap smoke test passed.\n";
