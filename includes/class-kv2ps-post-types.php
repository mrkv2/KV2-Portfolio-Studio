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
			'kv2_ville'     => array( 'Villes', 'Ville', false, 'ville-realisation' ),
			'kv2_meuble'    => array( 'Types de meuble', 'Type de meuble', true, 'meuble-realisation' ),
			'kv2_style'     => array( 'Styles', 'Style', false, 'style-realisation' ),
			'kv2_technique' => array( 'Techniques', 'Technique', false, 'technique-realisation' ),
		);

		foreach ( $taxonomies as $taxonomy => $config ) {
			$args = array(
				'labels' => array(
					'name'          => __( $config[0], 'kv2-portfolio-studio' ),
					'singular_name' => __( $config[1], 'kv2-portfolio-studio' ),
					'search_items'  => sprintf( __( 'Rechercher : %s', 'kv2-portfolio-studio' ), strtolower( $config[0] ) ),
					'all_items'     => sprintf( __( 'Tous les %s', 'kv2-portfolio-studio' ), strtolower( $config[0] ) ),
					'edit_item'     => sprintf( __( 'Modifier : %s', 'kv2-portfolio-studio' ), strtolower( $config[1] ) ),
					'add_new_item'  => sprintf( __( 'Ajouter : %s', 'kv2-portfolio-studio' ), strtolower( $config[1] ) ),
					'separate_items_with_commas' => sprintf( __( 'Séparez les %s par des virgules', 'kv2-portfolio-studio' ), strtolower( $config[0] ) ),
					'add_or_remove_items' => sprintf( __( 'Ajouter ou retirer des %s', 'kv2-portfolio-studio' ), strtolower( $config[0] ) ),
					'choose_from_most_used' => sprintf( __( 'Choisir parmi les %s les plus utilisés', 'kv2-portfolio-studio' ), strtolower( $config[0] ) ),
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
			);

			register_taxonomy( $taxonomy, self::POST_TYPE, $args );
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
			'_kv2ps_city'          => 'string',
			'_kv2ps_department'    => 'string',
			'_kv2ps_postal_code'   => 'string',
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
		if ( '_kv2ps_department' === $meta_key ) {
			return self::sanitize_department( $value );
		}
		if ( '_kv2ps_postal_code' === $meta_key ) {
			return self::sanitize_postal_code( $value );
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

	public static function get_location( $post_id ) {
		$location = array(
			'city'        => sanitize_text_field( get_post_meta( $post_id, '_kv2ps_city', true ) ),
			'department'  => self::sanitize_department( get_post_meta( $post_id, '_kv2ps_department', true ) ),
			'postal_code' => self::sanitize_postal_code( get_post_meta( $post_id, '_kv2ps_postal_code', true ) ),
		);

		$terms = wp_get_post_terms( $post_id, 'kv2_ville' );
		if ( ! is_wp_error( $terms ) && $terms ) {
			$parsed                 = self::parse_city_label( $terms[0]->name );
			$location['city']       = $parsed['city'];
			$location['department'] = $location['department'] ?: $parsed['department'];
		}

		return $location;
	}

	public static function set_location( $post_id, $location ) {
		$location    = is_array( $location ) ? $location : array();
		$raw_city    = isset( $location['city'] ) ? sanitize_text_field( $location['city'] ) : '';
		$parsed      = self::parse_city_label( $raw_city );
		$city        = $parsed['city'];
		$department  = isset( $location['department'] ) ? self::sanitize_department( $location['department'] ) : '';
		$department  = $department ?: $parsed['department'];
		$postal_code = isset( $location['postal_code'] ) ? self::sanitize_postal_code( $location['postal_code'] ) : '';

		if ( ! $city ) {
			$cleared = wp_set_object_terms( $post_id, array(), 'kv2_ville', false );
			if ( is_wp_error( $cleared ) ) {
				return $cleared;
			}
			delete_post_meta( $post_id, '_kv2ps_city' );
			delete_post_meta( $post_id, '_kv2ps_department' );
			delete_post_meta( $post_id, '_kv2ps_postal_code' );
			return 0;
		}

		$label   = self::city_label( $city, $department );
		$term_id = self::ensure_term( $label, 'kv2_ville', self::city_slug( $city ) );
		if ( is_wp_error( $term_id ) ) {
			return $term_id;
		}

		$assigned = wp_set_object_terms( $post_id, array( $term_id ), 'kv2_ville', true );
		if ( is_wp_error( $assigned ) ) {
			return $assigned;
		}

		update_post_meta( $post_id, '_kv2ps_city', $city );
		self::update_or_delete_meta( $post_id, '_kv2ps_department', $department );
		self::update_or_delete_meta( $post_id, '_kv2ps_postal_code', $postal_code );

		return $term_id;
	}

	public static function set_location_details( $post_id, $location ) {
		$location    = is_array( $location ) ? $location : array();
		$department  = isset( $location['department'] ) ? self::sanitize_department( $location['department'] ) : '';
		$postal_code = isset( $location['postal_code'] ) ? self::sanitize_postal_code( $location['postal_code'] ) : '';

		self::update_or_delete_meta( $post_id, '_kv2ps_department', $department );
		self::update_or_delete_meta( $post_id, '_kv2ps_postal_code', $postal_code );

		return true;
	}

	public static function ensure_term( $name, $taxonomy, $slug = '' ) {
		$name = trim( sanitize_text_field( $name ) );
		$slug = sanitize_title( $slug ?: $name );
		if ( ! $name || ! taxonomy_exists( $taxonomy ) ) {
			return new WP_Error( 'kv2ps_invalid_term', __( 'Taxonomie ou terme invalide.', 'kv2-portfolio-studio' ) );
		}

		$by_name = get_term_by( 'name', $name, $taxonomy );
		if ( $by_name && ! is_wp_error( $by_name ) ) {
			return (int) $by_name->term_id;
		}

		$by_slug = get_term_by( 'slug', $slug, $taxonomy );
		if ( $by_slug && ! is_wp_error( $by_slug ) ) {
			if ( 'kv2_ville' !== $taxonomy || self::same_city( $name, $by_slug->name ) ) {
				if ( 'kv2_ville' === $taxonomy ) {
					$requested = self::parse_city_label( $name );
					$current   = self::parse_city_label( $by_slug->name );
					if ( $requested['department'] && ! $current['department'] ) {
						$updated = wp_update_term( $by_slug->term_id, $taxonomy, array( 'name' => $name ) );
						if ( is_wp_error( $updated ) ) {
							return $updated;
						}
					}
				}
				return (int) $by_slug->term_id;
			}

			$parsed = self::parse_city_label( $name );
			$suffix = $parsed['department'] ?: 'localite';
			$slug  .= '-' . sanitize_title( $suffix );
		}

		$created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
		if ( is_wp_error( $created ) ) {
			if ( 'term_exists' === $created->get_error_code() ) {
				$existing_id = absint( $created->get_error_data( 'term_exists' ) );
				if ( $existing_id ) {
					return $existing_id;
				}
			}
			return $created;
		}

		return (int) $created['term_id'];
	}

	public static function city_slug( $city ) {
		$parsed = self::parse_city_label( $city );
		return sanitize_title( $parsed['city'] );
	}

	public static function city_label( $city, $department = '' ) {
		$parsed     = self::parse_city_label( $city );
		$city       = $parsed['city'];
		$department = self::sanitize_department( $department ) ?: $parsed['department'];
		return $department ? sprintf( '%1$s (%2$s)', $city, $department ) : $city;
	}

	private static function parse_city_label( $label ) {
		$label      = trim( sanitize_text_field( $label ) );
		$department = '';
		if ( preg_match( '/\s*\(((?:2[AB])|\d{2,3})\)\s*$/iu', $label, $matches ) ) {
			$department = self::sanitize_department( $matches[1] );
			$label      = trim( substr( $label, 0, -strlen( $matches[0] ) ) );
		}

		return array( 'city' => $label, 'department' => $department );
	}

	private static function same_city( $first, $second ) {
		$first  = self::parse_city_label( $first );
		$second = self::parse_city_label( $second );
		if ( self::city_slug( $first['city'] ) !== self::city_slug( $second['city'] ) ) {
			return false;
		}

		return ! $first['department'] || ! $second['department'] || $first['department'] === $second['department'];
	}

	private static function sanitize_department( $value ) {
		$value = strtoupper( trim( sanitize_text_field( $value ) ) );
		if ( preg_match( '/(?:^|[^0-9A-Z])(2[AB]|\d{2,3})(?:[^0-9A-Z]|$)/i', $value, $matches ) ) {
			return strtoupper( $matches[1] );
		}
		return '';
	}

	private static function sanitize_postal_code( $value ) {
		$value = strtoupper( trim( sanitize_text_field( $value ) ) );
		$compact = preg_replace( '/[\s-]+/', '', $value );
		if ( preg_match( '/^\d{5}$/', $compact ) ) {
			return $compact;
		}
		return preg_match( '/^[A-Z0-9][A-Z0-9 -]{1,11}$/', $value ) ? $value : '';
	}

	private static function update_or_delete_meta( $post_id, $key, $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}
