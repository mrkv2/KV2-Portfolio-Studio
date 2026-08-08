<?php

defined( 'ABSPATH' ) || exit;

final class KV2PS_Post_Types {
	const POST_TYPE = 'kv2_realisation';

	public static function taxonomies() {
		return array( 'kv2_service', 'kv2_ville', 'kv2_meuble', 'kv2_style', 'kv2_technique' );
	}

	public static function register() {
		$settings      = KV2PS_Plugin::settings();
		$existing_page = 'existing_page' === ( isset( $settings['routing_mode'] ) ? $settings['routing_mode'] : 'standard' );
		$single_slug   = $existing_page ? sanitize_title( isset( $settings['single_slug'] ) ? $settings['single_slug'] : 'realisation' ) : 'realisations';
		$single_slug   = $single_slug ?: 'realisation';
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'                  => __( 'Réalisations', 'kv2-portfolio-studio' ),
					'singular_name'         => __( 'Réalisation', 'kv2-portfolio-studio' ),
					'add_new'               => __( 'Ajouter', 'kv2-portfolio-studio' ),
					'add_new_item'          => __( 'Ajouter une réalisation', 'kv2-portfolio-studio' ),
					'edit_item'             => __( 'Modifier la réalisation', 'kv2-portfolio-studio' ),
					'new_item'              => __( 'Nouvelle réalisation', 'kv2-portfolio-studio' ),
					'view_item'             => __( 'Voir la réalisation', 'kv2-portfolio-studio' ),
					'search_items'          => __( 'Rechercher des réalisations', 'kv2-portfolio-studio' ),
					'not_found'             => __( 'Aucune réalisation trouvée', 'kv2-portfolio-studio' ),
					'not_found_in_trash'    => __( 'Aucune réalisation dans la corbeille', 'kv2-portfolio-studio' ),
					'all_items'             => __( 'Toutes les réalisations', 'kv2-portfolio-studio' ),
					'archives'              => __( 'Archives des réalisations', 'kv2-portfolio-studio' ),
					'featured_image'        => __( 'Image principale', 'kv2-portfolio-studio' ),
					'set_featured_image'    => __( 'Définir l’image principale', 'kv2-portfolio-studio' ),
					'remove_featured_image' => __( 'Retirer l’image principale', 'kv2-portfolio-studio' ),
				),
				'public'             => true,
				'show_in_rest'       => true,
				'has_archive'        => $existing_page ? false : 'realisations',
				'rewrite'            => array( 'slug' => $single_slug, 'with_front' => false ),
				'menu_icon'          => 'dashicons-format-gallery',
				'menu_position'      => 20,
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields' ),
				'taxonomies'         => self::taxonomies(),
				'show_in_nav_menus'  => true,
				'publicly_queryable' => true,
				'query_var'          => true,
			),
		);

		$taxonomies = array(
			'kv2_service'   => array( 'Services', 'Service', true, 'service-realisation' ),
			'kv2_ville'     => array( 'Villes', 'Ville', true, 'ville-realisation' ),
			'kv2_meuble'    => array( 'Types de meuble', 'Type de meuble', true, 'meuble-realisation' ),
			'kv2_style'     => array( 'Styles', 'Style', false, 'style-realisation' ),
			'kv2_technique' => array( 'Techniques', 'Technique', false, 'technique-realisation' ),
		);

		foreach ( $taxonomies as $taxonomy => $config ) {
			register_taxonomy(
				$taxonomy,
				self::POST_TYPE,
				array(
					'labels' => array(
						'name'          => __( $config[0], 'kv2-portfolio-studio' ),
						'singular_name' => __( $config[1], 'kv2-portfolio-studio' ),
						'search_items'  => sprintf( __( 'Rechercher : %s', 'kv2-portfolio-studio' ), strtolower( $config[0] ) ),
						'all_items'     => sprintf( __( 'Tous les %s', 'kv2-portfolio-studio' ), strtolower( $config[0] ) ),
						'edit_item'     => sprintf( __( 'Modifier : %s', 'kv2-portfolio-studio' ), strtolower( $config[1] ) ),
						'add_new_item'  => sprintf( __( 'Ajouter : %s', 'kv2-portfolio-studio' ), strtolower( $config[1] ) ),
						'menu_name'     => __( $config[0], 'kv2-portfolio-studio' ),
					),
					'public'            => ! $existing_page,
					'publicly_queryable'=> ! $existing_page,
					'hierarchical'      => (bool) $config[2],
					'show_ui'           => true,
					'show_admin_column' => true,
					'show_in_rest'      => true,
					'show_in_nav_menus' => ! $existing_page,
					'query_var'         => ! $existing_page,
					'rewrite'           => $existing_page ? false : array( 'slug' => $config[3], 'with_front' => false ),
				),
			);
		}

		self::register_meta();
	}

	private static function register_meta() {
		$fields = array(
			'_kv2ps_problem'       => 'string',
			'_kv2ps_intervention'  => 'string',
			'_kv2ps_result'        => 'string',
			'_kv2ps_materials'     => 'string',
			'_kv2ps_project_date'  => 'string',
			'_kv2ps_duration'      => 'string',
			'_kv2ps_initial_state' => 'string',
			'_kv2ps_constraints'   => 'string',
			'_kv2ps_work_type'     => 'string',
			'_kv2ps_price_range'   => 'string',
			'_kv2ps_confidential'  => 'boolean',
			'_kv2ps_testimonial'   => 'string',
			'_kv2ps_testimonial_author'     => 'string',
			'_kv2ps_testimonial_source'     => 'string',
			'_kv2ps_testimonial_source_url' => 'string',
			'_kv2ps_testimonial_rating'     => 'string',
			'_kv2ps_testimonial_date'       => 'string',
			'_kv2ps_testimonial_consent'    => 'boolean',
			'_kv2ps_cta_override'           => 'boolean',
			'_kv2ps_cta_title'              => 'string',
			'_kv2ps_cta_text'               => 'string',
			'_kv2ps_cta_primary_action'     => 'string',
			'_kv2ps_cta_primary_label'      => 'string',
			'_kv2ps_cta_secondary_enabled'  => 'boolean',
			'_kv2ps_cta_secondary_label'    => 'string',
			'_kv2ps_cta_form_url'           => 'string',
			'_kv2ps_before_images' => 'array',
			'_kv2ps_after_images'  => 'array',
			'_kv2ps_publication_mode' => 'string',
			'_kv2ps_destination_url'  => 'string',
		);

		foreach ( $fields as $key => $type ) {
			$rest_schema = array(
				'type'    => $type,
				'context' => array( 'edit' ),
			);
			if ( 'array' === $type ) {
				$rest_schema['items'] = array( 'type' => 'integer' );
			}
			$args = array(
				'type'              => $type,
				'single'            => true,
				'show_in_rest'      => array( 'schema' => $rest_schema ),
				'sanitize_callback' => array( __CLASS__, 'sanitize_meta_value' ),
				'auth_callback'     => function( $allowed, $meta_key, $object_id ) {
					unset( $allowed, $meta_key );
					return $object_id && current_user_can( 'edit_post', (int) $object_id );
				},
			);
			register_post_meta( self::POST_TYPE, $key, $args );
		}
	}

	public static function sanitize_meta_value( $value, $meta_key ) {
		if ( in_array( $meta_key, array( '_kv2ps_before_images', '_kv2ps_after_images' ), true ) ) {
			return self::sanitize_ids( $value );
		}
		if ( in_array( $meta_key, array( '_kv2ps_confidential', '_kv2ps_testimonial_consent', '_kv2ps_cta_override', '_kv2ps_cta_secondary_enabled' ), true ) ) {
			return rest_sanitize_boolean( $value );
		}
		if ( in_array( $meta_key, array( '_kv2ps_testimonial_source_url', '_kv2ps_cta_form_url', '_kv2ps_destination_url' ), true ) ) {
			return esc_url_raw( $value );
		}
		if ( '_kv2ps_publication_mode' === $meta_key ) {
			$mode = sanitize_key( $value );
			return in_array( $mode, array( KV2PS_Compatibility::MODE_CASE_STUDY, KV2PS_Compatibility::MODE_GALLERY ), true ) ? $mode : KV2PS_Compatibility::MODE_CASE_STUDY;
		}
		if ( '_kv2ps_testimonial_rating' === $meta_key ) {
			$rating = absint( $value );
			return $rating >= 1 && $rating <= 5 ? (string) $rating : '';
		}
		if ( '_kv2ps_cta_primary_action' === $meta_key ) {
			$action = sanitize_key( $value );
			return in_array( $action, array( 'click_to_chat', 'form' ), true ) ? $action : '';
		}
		if ( in_array( $meta_key, array( '_kv2ps_project_date', '_kv2ps_testimonial_date' ), true ) ) {
			$date = sanitize_text_field( $value );
			if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches ) || ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
				return '';
			}
			return $date;
		}
		if ( in_array( $meta_key, array( '_kv2ps_problem', '_kv2ps_intervention', '_kv2ps_result', '_kv2ps_materials', '_kv2ps_initial_state', '_kv2ps_constraints', '_kv2ps_testimonial', '_kv2ps_cta_text' ), true ) ) {
			return sanitize_textarea_field( $value );
		}

		return sanitize_text_field( $value );
	}

	public static function sanitize_ids( $ids ) {
		if ( ! is_array( $ids ) ) {
			$ids = explode( ',', (string) $ids );
		}

		return array_values( array_filter( array_unique( array_map( 'absint', $ids ) ) ) );
	}
}
