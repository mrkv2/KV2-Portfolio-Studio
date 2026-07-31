<?php

defined( 'ABSPATH' ) || exit;

/**
 * SEO coordination for the canonical portfolio page.
 *
 * Rank Math remains authoritative. These filters only supply values that are
 * missing or correct the dedicated shortcode pagination.
 */
final class KV2PS_SEO {
	public static function init() {
		add_filter( 'rank_math/frontend/title', array( __CLASS__, 'filter_title' ), 99 );
		add_filter( 'rank_math/frontend/description', array( __CLASS__, 'filter_description' ), 99 );
		add_filter( 'rank_math/frontend/canonical', array( __CLASS__, 'filter_canonical' ), 99 );
		add_filter( 'rank_math/opengraph/url', array( __CLASS__, 'filter_canonical' ), 99 );
		add_filter( 'rank_math/frontend/robots', array( __CLASS__, 'filter_robots' ), 99 );
	}

	public static function portfolio_page_url() {
		$settings = KV2PS_Plugin::settings();
		$url      = isset( $settings['portfolio_page_url'] ) ? esc_url_raw( $settings['portfolio_page_url'] ) : '';
		if ( ! $url ) {
			return '';
		}

		$target_host = wp_parse_url( $url, PHP_URL_HOST );
		$site_host   = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		if ( ! $target_host || strtolower( (string) $target_host ) !== strtolower( (string) $site_host ) ) {
			return '';
		}

		return $url;
	}

	public static function is_primary_portfolio_page() {
		if ( ! is_singular() ) {
			return false;
		}

		$post_id = get_queried_object_id();
		$post    = $post_id ? get_post( $post_id ) : null;
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		$configured_url = self::portfolio_page_url();
		$current_url    = get_permalink( $post_id );
		return $configured_url && $current_url && self::same_url_path( $configured_url, $current_url );
	}

	/**
	 * Avoid adding a second H1 when the page or its Elementor document already
	 * contains one.
	 */
	public static function page_has_h1( $post_id = 0 ) {
		$post_id = $post_id ?: get_queried_object_id();
		$post    = $post_id ? get_post( $post_id ) : null;
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		if ( preg_match( '/<h1\b/i', (string) $post->post_content ) ) {
			return true;
		}

		$elementor_data = get_post_meta( $post_id, '_elementor_data', true );
		return is_string( $elementor_data ) && (
			preg_match( '/<h1\b/i', $elementor_data )
			|| preg_match( '/"header_size"\s*:\s*"h1"/i', $elementor_data )
		);
	}

	public static function pagination_page() {
		return isset( $_GET['kv2ps_page'] ) ? max( 1, absint( $_GET['kv2ps_page'] ) ) : 1;
	}

	public static function filter_title( $title ) {
		if ( ! self::is_primary_portfolio_page() ) {
			return $title;
		}

		$post_id = get_queried_object_id();
		if ( $post_id && trim( (string) get_post_meta( $post_id, 'rank_math_title', true ) ) ) {
			return $title;
		}

		$settings  = KV2PS_Plugin::settings();
		$seo_title = trim( (string) $settings['portfolio_seo_title'] );
		if ( ! $seo_title ) {
			return $title;
		}

		$page = self::pagination_page();
		return $page > 1 ? sprintf( __( '%1$s — Page %2$d', 'kv2-portfolio-studio' ), $seo_title, $page ) : $seo_title;
	}

	public static function filter_description( $description ) {
		if ( trim( (string) $description ) ) {
			return $description;
		}

		if ( self::is_primary_portfolio_page() ) {
			$settings = KV2PS_Plugin::settings();
			return trim( (string) $settings['portfolio_meta_description'] );
		}

		if ( ! is_singular( KV2PS_Post_Types::POST_TYPE ) ) {
			return $description;
		}

		$post_id = get_queried_object_id();
		$text    = get_the_excerpt( $post_id );
		if ( ! $text ) {
			$parts = array();
			foreach ( array( '_kv2ps_problem', '_kv2ps_intervention', '_kv2ps_result' ) as $meta_key ) {
				$value = trim( (string) get_post_meta( $post_id, $meta_key, true ) );
				if ( $value ) {
					$parts[] = $value;
				}
			}
			$text = implode( ' ', $parts );
		}

		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );
		return $text ? wp_html_excerpt( $text, 158, '…' ) : $description;
	}

	public static function filter_canonical( $canonical ) {
		if ( ! self::is_primary_portfolio_page() || self::has_filter_parameters() ) {
			return $canonical;
		}

		$page = self::pagination_page();
		if ( $page <= 1 ) {
			return $canonical;
		}

		$base_url = self::portfolio_page_url();
		return $base_url ? add_query_arg( 'kv2ps_page', $page, remove_query_arg( array( 'kv2ps_page', 'kv2ps_service', 'kv2ps_search' ), $base_url ) ) : $canonical;
	}

	public static function filter_robots( $robots ) {
		if ( ! self::is_primary_portfolio_page() || ! self::has_filter_parameters() || ! is_array( $robots ) ) {
			return $robots;
		}

		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
		return $robots;
	}

	private static function has_filter_parameters() {
		return ! empty( $_GET['kv2ps_service'] ) || ! empty( $_GET['kv2ps_search'] );
	}

	private static function same_url_path( $first, $second ) {
		$first_host  = strtolower( (string) wp_parse_url( $first, PHP_URL_HOST ) );
		$second_host = strtolower( (string) wp_parse_url( $second, PHP_URL_HOST ) );
		$first_path  = trailingslashit( rawurldecode( (string) wp_parse_url( $first, PHP_URL_PATH ) ) );
		$second_path = trailingslashit( rawurldecode( (string) wp_parse_url( $second, PHP_URL_PATH ) ) );
		return $first_host === $second_host && $first_path === $second_path;
	}
}
