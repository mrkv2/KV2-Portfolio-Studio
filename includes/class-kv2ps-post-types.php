<?php

defined( 'ABSPATH' ) || exit;

final class KV2PS_Post_Types {
	const POST_TYPE = 'kv2_realisation';

	public static function taxonomies() {
		return array( 'kv2_service', 'kv2_ville', 'kv2_meuble', 'kv2_style', 'kv2_technique' );
	}

	public static function register() {
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
				'has_archive'        => 'realisations',
				'rewrite'            => array( 'slug' => 'realisations', 'with_front' => false ),
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
					'public'            => true,
					'hierarchical'      => (bool) $config[2],
					'show_ui'           => true,
					'show_admin_column' => true,
					'show_in_rest'      => true,
					'rewrite'           => array( 'slug' => $config[3], 'with_front' => false ),
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
		);

		foreach ( $fields as $key => $type ) {
			$args = array(
				'type'              => $type,
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'array' === $type ? array( __CLASS__, 'sanitize_ids' ) : ( 'boolean' === $type ? 'rest_sanitize_boolean' : 'sanitize_textarea_field' ),
				'auth_callback'     => function() {
					return current_user_can( 'edit_posts' );
				},
			);
			if ( 'array' === $type ) {
				$args['show_in_rest'] = array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				);
			}
			register_post_meta( self::POST_TYPE, $key, $args );
		}
	}

	public static function sanitize_ids( $ids ) {
		if ( ! is_array( $ids ) ) {
			$ids = explode( ',', (string) $ids );
		}

		return array_values( array_filter( array_unique( array_map( 'absint', $ids ) ) ) );
	}
}
