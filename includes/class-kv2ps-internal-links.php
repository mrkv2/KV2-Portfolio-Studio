<?php

defined( 'ABSPATH' ) || exit;

/**
 * Connects realization terms to existing commercial service and city pages.
 * Manual term URLs always take precedence over automatic discovery.
 */
final class KV2PS_Internal_Links {
	const SYNC_VERSION = '1.2.1';
	const SYNC_OPTION  = 'kv2ps_internal_links_upholstery_version';
	const URL_META     = '_kv2ps_landing_url';
	const AUTO_META    = '_kv2ps_landing_url_auto';

	private static $city_index = null;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_sync_all' ), 30 );
		add_action( 'created_kv2_service', array( __CLASS__, 'sync_service_term' ), 20 );
		add_action( 'edited_kv2_service', array( __CLASS__, 'sync_service_term' ), 20 );
		add_action( 'created_kv2_ville', array( __CLASS__, 'sync_city_term' ), 20 );
		add_action( 'edited_kv2_ville', array( __CLASS__, 'sync_city_term' ), 20 );
		add_action( 'save_post_kv2_ville', array( __CLASS__, 'sync_city_post' ), 20, 3 );
	}

	private static function is_upholstery() {
		return KV2PS_Plugin::PROFILE_UPHOLSTERY === KV2PS_Plugin::business_profile();
	}

	public static function maybe_sync_all() {
		if ( ! self::is_upholstery() || self::SYNC_VERSION === (string) get_option( self::SYNC_OPTION, '' ) ) {
			return;
		}

		self::sync_service_terms();
		self::sync_city_terms();
		update_option( self::SYNC_OPTION, self::SYNC_VERSION, false );
	}

	public static function term_url( $term, $taxonomy = '' ) {
		if ( ! is_object( $term ) || empty( $term->term_id ) ) {
			return '';
		}

		$stored = self::internal_url( get_term_meta( $term->term_id, self::URL_META, true ) );
		if ( $stored ) {
			return $stored;
		}

		$taxonomy = $taxonomy ?: ( isset( $term->taxonomy ) ? $term->taxonomy : '' );
		if ( self::is_upholstery() && 'kv2_service' === $taxonomy ) {
			return self::discover_service_url( $term );
		}
		if ( self::is_upholstery() && 'kv2_ville' === $taxonomy ) {
			return self::discover_city_url( $term );
		}

		$taxonomy_object = $taxonomy ? get_taxonomy( $taxonomy ) : false;
		if ( $taxonomy_object && $taxonomy_object->publicly_queryable ) {
			$url = get_term_link( $term );
			return is_wp_error( $url ) ? '' : self::internal_url( $url );
		}

		return '';
	}

	public static function sync_service_terms() {
		if ( ! self::is_upholstery() ) {
			return;
		}
		$terms = get_terms( array( 'taxonomy' => 'kv2_service', 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			return;
		}
		foreach ( $terms as $term ) {
			self::sync_service_term( $term->term_id );
		}
	}

	public static function sync_service_term( $term_id ) {
		if ( ! self::is_upholstery() || self::has_manual_url( $term_id ) ) {
			return;
		}
		$term = get_term( $term_id, 'kv2_service' );
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}
		$url = self::discover_service_url( $term );
		self::store_auto_url( $term_id, $url );
	}

	private static function service_page_candidates() {
		return apply_filters(
			'kv2ps_service_page_candidates',
			array(
				'cannage-de-chaises'     => array( 'cannage' ),
				'refection-de-chaises'   => array( 'restauration-chaises', 'refection-chaises' ),
				'rempaillage-de-chaises' => array( 'rempaillage' ),
				'refection-de-fauteuils' => array( 'refection-fauteuil', 'restauration-fauteuils' ),
				'refection-de-canapes'   => array( 'refection-canape', 'restauration-canapes' ),
				'tapisserie-ameublement' => array( 'tapissier', 'autres-prestations-tapissier-yvelines/tapissier-dameublement-yvelines', 'tapissier-dameublement' ),
			)
		);
	}

	private static function discover_service_url( $term ) {
		$candidates = self::service_page_candidates();
		$paths      = isset( $candidates[ $term->slug ] ) ? (array) $candidates[ $term->slug ] : array( $term->slug );
		foreach ( $paths as $path ) {
			$parts = array_filter( array_map( 'sanitize_title', explode( '/', trim( (string) $path, '/' ) ) ) );
			$path  = implode( '/', $parts );
			$page  = $path ? get_page_by_path( $path, OBJECT, 'page' ) : false;
			if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
				$url = self::internal_url( get_permalink( $page->ID ) );
				if ( $url ) {
					return $url;
				}
			}
		}
		return '';
	}

	public static function sync_city_terms() {
		if ( ! self::is_upholstery() ) {
			return;
		}
		$terms = get_terms( array( 'taxonomy' => 'kv2_ville', 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			return;
		}
		foreach ( $terms as $term ) {
			self::sync_city_term( $term->term_id );
		}
	}

	public static function sync_city_term( $term_id ) {
		if ( ! self::is_upholstery() || self::has_manual_url( $term_id ) ) {
			return;
		}
		$term = get_term( $term_id, 'kv2_ville' );
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}
		$url = self::discover_city_url( $term );
		self::store_auto_url( $term_id, $url );
	}

	public static function sync_city_post( $post_id, $post, $update ) {
		unset( $post_id, $post, $update );
		if ( self::is_upholstery() ) {
			self::$city_index = null;
			self::sync_city_terms();
		}
	}

	private static function discover_city_url( $term ) {
		if ( ! post_type_exists( 'kv2_ville' ) ) {
			return '';
		}

		$slugs = array_unique( array_filter( array( $term->slug, preg_replace( '/-\d{5}$/', '', $term->slug ) ) ) );
		foreach ( $slugs as $slug ) {
			$url = self::city_post_url( get_page_by_path( $slug, OBJECT, 'kv2_ville' ) );
			if ( $url ) {
				return $url;
			}
		}

		$wanted = self::normalized_city_name( $term->name );
		if ( ! $wanted || 0 === strpos( $wanted, 'realisation-tapisserie-' ) ) {
			return '';
		}
		foreach ( self::city_index() as $city ) {
			if ( $wanted === $city['name'] ) {
				return $city['url'];
			}
		}
		return '';
	}

	private static function city_index() {
		if ( null !== self::$city_index ) {
			return self::$city_index;
		}
		self::$city_index = array();
		$ids   = get_posts(
			array(
				'post_type'              => 'kv2_ville',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);
		foreach ( $ids as $id ) {
			$url = self::city_post_url( get_post( $id ) );
			if ( $url ) {
				self::$city_index[] = array( 'name' => self::normalized_city_name( get_the_title( $id ) ), 'url' => $url );
			}
		}
		return self::$city_index;
	}

	private static function city_post_url( $post ) {
		if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
			return '';
		}
		$url = get_post_meta( $post->ID, 'url', true );
		$url = $url ?: get_post_meta( $post->ID, '_kv2_url', true );
		return self::internal_url( $url );
	}

	private static function normalized_city_name( $name ) {
		$name = preg_replace( '/\s*[\(\[]?\d{2,5}[\)\]]?\s*$/u', '', (string) $name );
		return sanitize_title( $name );
	}

	private static function internal_url( $url ) {
		$url = esc_url_raw( (string) $url );
		if ( ! $url ) {
			return '';
		}
		if ( '/' === substr( $url, 0, 1 ) && '/' !== substr( $url, 1, 1 ) ) {
			$url = home_url( $url );
		}
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$url_host  = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return $home_host && $home_host === $url_host ? $url : '';
	}

	private static function has_manual_url( $term_id ) {
		return (bool) get_term_meta( $term_id, self::URL_META, true ) && ! get_term_meta( $term_id, self::AUTO_META, true );
	}

	private static function store_auto_url( $term_id, $url ) {
		if ( $url ) {
			update_term_meta( $term_id, self::URL_META, $url );
			update_term_meta( $term_id, self::AUTO_META, '1' );
			return;
		}
		if ( get_term_meta( $term_id, self::AUTO_META, true ) ) {
			delete_term_meta( $term_id, self::URL_META );
			delete_term_meta( $term_id, self::AUTO_META );
		}
	}
}
