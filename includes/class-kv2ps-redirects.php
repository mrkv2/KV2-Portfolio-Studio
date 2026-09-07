<?php

defined( 'ABSPATH' ) || exit;

final class KV2PS_Redirects {
	const OPTION = 'kv2ps_legacy_redirect_map';

	public static function init() {
		add_action( 'save_post_' . KV2PS_Post_Types::POST_TYPE, array( __CLASS__, 'refresh_post_redirect' ), 40 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_archive' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ), 1 );
	}

	public static function maybe_redirect_archive() {
		if ( ! is_post_type_archive( KV2PS_Post_Types::POST_TYPE ) || is_feed() ) {
			return;
		}

		$settings = KV2PS_Plugin::settings();
		if ( empty( $settings['redirect_archive_to_portfolio'] ) ) {
			return;
		}

		$target  = KV2PS_SEO::portfolio_page_url();
		$archive = get_post_type_archive_link( KV2PS_Post_Types::POST_TYPE );
		if ( ! $target || ! $archive ) {
			return;
		}

		$target_path  = trailingslashit( (string) wp_parse_url( $target, PHP_URL_PATH ) );
		$archive_path = trailingslashit( (string) wp_parse_url( $archive, PHP_URL_PATH ) );
		if ( $target_path === $archive_path ) {
			return;
		}

		wp_safe_redirect( $target, 301, 'KV2 Portfolio Studio' );
		exit;
	}

	public static function refresh_post_redirect( $post_id ) {
		if ( wp_is_post_revision( $post_id ) || 'publish' !== get_post_status( $post_id ) || KV2PS_Compatibility::is_gallery_item( $post_id ) ) {
			return;
		}
		$source_url = get_post_meta( $post_id, '_kv2ps_source_wp_portfolio_url', true );
		if ( ! $source_url ) {
			return;
		}
		$source_host = wp_parse_url( $source_url, PHP_URL_HOST );
		$site_host   = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$source_path = wp_parse_url( $source_url, PHP_URL_PATH );
		if ( ! $source_path || strtolower( (string) $source_host ) !== strtolower( (string) $site_host ) ) {
			return;
		}
		$map = get_option( self::OPTION, array() );
		$map[ trailingslashit( $source_path ) ] = (int) $post_id;
		update_option( self::OPTION, $map, false );
	}

	public static function maybe_redirect() {
		if ( ! is_404() ) {
			return;
		}
		$settings = wp_parse_args( get_option( 'kv2ps_settings', array() ), KV2PS_Plugin::default_settings() );
		if ( empty( $settings['legacy_redirects'] ) ) {
			return;
		}
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path        = wp_parse_url( esc_url_raw( home_url( $request_uri ) ), PHP_URL_PATH );
		$map         = get_option( self::OPTION, array() );
		$key         = trailingslashit( (string) $path );
		if ( empty( $map[ $key ] ) || 'publish' !== get_post_status( $map[ $key ] ) ) {
			return;
		}
		$target = get_permalink( $map[ $key ] );
		if ( $target ) {
			wp_safe_redirect( $target, 301, 'KV2 Portfolio Studio' );
			exit;
		}
	}
}
