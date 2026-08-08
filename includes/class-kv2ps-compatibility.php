<?php

defined( 'ABSPATH' ) || exit;

/**
 * Safe compatibility layer for mature sites migrating from WP Portfolio.
 *
 * Nothing in this class replaces an existing shortcode or URL unless the
 * administrator has explicitly enabled the corresponding setting.
 */
final class KV2PS_Compatibility {
	const MODE_CASE_STUDY = 'case_study';
	const MODE_GALLERY    = 'gallery';
	private static $legacy_alias_registered = false;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_register_legacy_shortcode' ), 99 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_gallery_item' ), -1 );
		add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'filter_core_sitemap_query' ), 10, 2 );
		add_filter( 'rank_math/sitemap/entry', array( __CLASS__, 'filter_rank_math_sitemap_entry' ), 10, 3 );
	}

	public static function publication_mode( $post_id ) {
		$mode = (string) get_post_meta( $post_id, '_kv2ps_publication_mode', true );
		return self::MODE_GALLERY === $mode ? self::MODE_GALLERY : self::MODE_CASE_STUDY;
	}

	public static function is_gallery_item( $post_id ) {
		return self::MODE_GALLERY === self::publication_mode( $post_id );
	}

	public static function maybe_register_legacy_shortcode() {
		$settings = KV2PS_Plugin::settings();
		if ( empty( $settings['legacy_shortcode_alias'] ) || shortcode_exists( 'wp_portfolio' ) ) {
			return;
		}

		add_shortcode( 'wp_portfolio', array( KV2PS_Plugin::instance(), 'portfolio_shortcode' ) );
		self::$legacy_alias_registered = true;
	}

	public static function legacy_alias_registered() {
		return self::$legacy_alias_registered;
	}

	/**
	 * Translate the attributes used by WP Portfolio without mutating page
	 * content. Unknown attributes remain harmless and are ignored later.
	 */
	public static function normalize_legacy_atts( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();
		if ( isset( $atts['per-page'] ) && ! isset( $atts['limit'] ) ) {
			$atts['limit'] = $atts['per-page'];
		} elseif ( isset( $atts['posts_per_page'] ) && ! isset( $atts['limit'] ) ) {
			$atts['limit'] = $atts['posts_per_page'];
		}

		if ( isset( $atts['show-search'] ) && ! isset( $atts['show_search'] ) ) {
			$atts['show_search'] = $atts['show-search'];
		}
		if ( isset( $atts['show-filters'] ) && ! isset( $atts['show_filters'] ) ) {
			$atts['show_filters'] = $atts['show-filters'];
		} elseif ( isset( $atts['show-categories'] ) && ! isset( $atts['show_filters'] ) ) {
			$atts['show_filters'] = $atts['show-categories'];
		}
		if ( isset( $atts['pagination'] ) && ! self::enabled( $atts['pagination'] ) ) {
			$atts['load_mode'] = 'none';
		}

		return $atts;
	}

	/**
	 * Build clauses from source term IDs stored during import. Clauses are
	 * grouped per destination taxonomy so comma-separated values keep OR
	 * semantics while different filters keep AND semantics.
	 */
	public static function legacy_tax_query( $atts ) {
		$source_map = array(
			'categories'       => 'astra-portfolio-categories',
			'tags'             => 'astra-portfolio-tags',
			'other-categories' => 'astra-portfolio-other-categories',
			'other_categories' => 'astra-portfolio-other-categories',
		);
		$grouped = array();

		foreach ( $source_map as $attribute => $source_taxonomy ) {
			if ( empty( $atts[ $attribute ] ) ) {
				continue;
			}
			$source_ids = preg_split( '/[\s,|]+/', (string) $atts[ $attribute ] );
			$source_ids = array_values( array_filter( array_unique( array_map( 'absint', (array) $source_ids ) ) ) );
			foreach ( $source_ids as $source_id ) {
				foreach ( KV2PS_Post_Types::taxonomies() as $taxonomy ) {
					$terms = get_terms(
						array(
							'taxonomy'   => $taxonomy,
							'hide_empty' => false,
							'fields'     => 'ids',
							'meta_query' => array(
								'relation' => 'AND',
								array( 'key' => '_kv2ps_source_taxonomy', 'value' => $source_taxonomy ),
								array( 'key' => '_kv2ps_source_term_id', 'value' => (string) $source_id ),
							),
						)
					);
					if ( ! is_wp_error( $terms ) && $terms ) {
						$grouped[ $taxonomy ] = array_merge( isset( $grouped[ $taxonomy ] ) ? $grouped[ $taxonomy ] : array(), $terms );
					}
				}
			}
		}

		$query = array();
		foreach ( $grouped as $taxonomy => $term_ids ) {
			$query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => array_values( array_unique( array_map( 'absint', $term_ids ) ) ),
			);
		}
		return $query;
	}

	public static function card_destination( $post_id, $image_id = 0 ) {
		$destination = self::internal_url( get_post_meta( $post_id, '_kv2ps_destination_url', true ) );
		if ( $destination ) {
			return array( 'url' => $destination, 'lightbox' => false );
		}

		if ( self::is_gallery_item( $post_id ) && $image_id ) {
			$image_url = wp_get_attachment_image_url( $image_id, 'full' );
			if ( $image_url ) {
				return array( 'url' => $image_url, 'lightbox' => true );
			}
		}

		return array( 'url' => get_permalink( $post_id ), 'lightbox' => false );
	}

	public static function adjacent_case_study( $post_id, $direction = 'previous', $same_service = true ) {
		$current = get_post( $post_id );
		if ( ! $current instanceof WP_Post ) {
			return null;
		}
		$is_previous = 'previous' === $direction;
		$args = array(
			'post_type'      => KV2PS_Post_Types::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => $is_previous ? 'DESC' : 'ASC',
			'post__not_in'   => array( absint( $post_id ) ),
			'meta_query'     => array(
				'relation' => 'OR',
				array( 'key' => '_kv2ps_publication_mode', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_kv2ps_publication_mode', 'value' => self::MODE_GALLERY, 'compare' => '!=' ),
			),
			'date_query'     => array(
				array(
					( $is_previous ? 'before' : 'after' ) => $current->post_date,
					'inclusive' => false,
				),
			),
		);
		if ( $same_service ) {
			$service_ids = wp_get_post_terms( $post_id, 'kv2_service', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $service_ids ) && $service_ids ) {
				$args['tax_query'] = array(
					array( 'taxonomy' => 'kv2_service', 'field' => 'term_id', 'terms' => $service_ids ),
				);
			}
		}
		$posts = get_posts( $args );
		return $posts ? $posts[0] : ( $same_service ? self::adjacent_case_study( $post_id, $direction, false ) : null );
	}

	public static function maybe_redirect_gallery_item() {
		if ( ! is_singular( KV2PS_Post_Types::POST_TYPE ) ) {
			return;
		}
		$post_id = get_queried_object_id();
		if ( ! $post_id || ! self::is_gallery_item( $post_id ) ) {
			return;
		}

		$target = self::internal_url( get_post_meta( $post_id, '_kv2ps_destination_url', true ) );
		if ( $target ) {
			wp_safe_redirect( $target, 302, 'KV2 Portfolio Studio migration' );
			exit;
		}
	}

	public static function filter_core_sitemap_query( $args, $post_type ) {
		if ( KV2PS_Post_Types::POST_TYPE !== $post_type ) {
			return $args;
		}
		$args['meta_query'] = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
		$args['meta_query'][] = array(
			'relation' => 'OR',
			array( 'key' => '_kv2ps_publication_mode', 'compare' => 'NOT EXISTS' ),
			array( 'key' => '_kv2ps_publication_mode', 'value' => self::MODE_GALLERY, 'compare' => '!=' ),
		);
		return $args;
	}

	public static function filter_rank_math_sitemap_entry( $url, $type, $object ) {
		if ( 'post' !== $type || ! is_object( $object ) || empty( $object->ID ) || KV2PS_Post_Types::POST_TYPE !== get_post_type( $object->ID ) ) {
			return $url;
		}
		return self::is_gallery_item( $object->ID ) ? false : $url;
	}

	private static function internal_url( $url ) {
		$url = esc_url_raw( (string) $url );
		if ( ! $url ) {
			return '';
		}
		$target_host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$site_host   = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		return $target_host && $target_host === $site_host ? $url : '';
	}

	private static function enabled( $value ) {
		return ! in_array( strtolower( trim( (string) $value ) ), array( '', '0', 'false', 'no', 'off' ), true );
	}
}
