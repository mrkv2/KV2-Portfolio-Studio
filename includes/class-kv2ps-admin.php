<?php

defined( 'ABSPATH' ) || exit;

final class KV2PS_Admin {
	public static function init() {
		KV2PS_Completeness::init();
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_init', array( 'KV2PS_Importer', 'maybe_repair_missing_thumbnails' ), 20 );
		add_action( 'add_meta_boxes_' . KV2PS_Post_Types::POST_TYPE, array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . KV2PS_Post_Types::POST_TYPE, array( __CLASS__, 'save_realisation' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );

		foreach ( KV2PS_Post_Types::taxonomies() as $taxonomy ) {
			add_action( $taxonomy . '_add_form_fields', array( __CLASS__, 'term_add_fields' ) );
			add_action( $taxonomy . '_edit_form_fields', array( __CLASS__, 'term_edit_fields' ) );
			add_action( 'created_' . $taxonomy, array( __CLASS__, 'save_term_fields' ) );
			add_action( 'edited_' . $taxonomy, array( __CLASS__, 'save_term_fields' ) );
		}
	}

	public static function admin_menu() {
		add_submenu_page(
			'edit.php?post_type=' . KV2PS_Post_Types::POST_TYPE,
			__( 'Assistant ChatGPT', 'kv2-portfolio-studio' ),
			__( 'Assistant ChatGPT', 'kv2-portfolio-studio' ),
			'manage_options',
			'kv2ps-chatgpt',
			array( 'KV2PS_Project_Package', 'render_page' )
		);

		add_submenu_page(
			'edit.php?post_type=' . KV2PS_Post_Types::POST_TYPE,
			__( 'Image SEO & ChatGPT', 'kv2-portfolio-studio' ),
			__( 'Image SEO & ChatGPT', 'kv2-portfolio-studio' ),
			'upload_files',
			'kv2ps-image-seo',
			array( 'KV2PS_Image_Metadata', 'render_tools_page' )
		);

		add_submenu_page(
			'edit.php?post_type=' . KV2PS_Post_Types::POST_TYPE,
			__( 'Importer WP Portfolio', 'kv2-portfolio-studio' ),
			__( 'Importer WP Portfolio', 'kv2-portfolio-studio' ),
			'manage_options',
			'kv2ps-import',
			array( 'KV2PS_Importer', 'render_page' )
		);

		add_submenu_page(
			'edit.php?post_type=' . KV2PS_Post_Types::POST_TYPE,
			__( 'Réglages du portfolio', 'kv2-portfolio-studio' ),
			__( 'Réglages', 'kv2-portfolio-studio' ),
			'manage_options',
			'kv2ps-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'kv2ps_settings_group',
			'kv2ps_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	public static function sanitize_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = KV2PS_Plugin::default_settings();
		$layout   = isset( $input['archive_layout'] ) && in_array( $input['archive_layout'], array( 'grid', 'tiles', 'masonry' ), true ) ? $input['archive_layout'] : $defaults['archive_layout'];
		$ratio    = isset( $input['archive_image_ratio'] ) && in_array( $input['archive_image_ratio'], array( 'auto', '1-1', '4-3', '3-2', '16-9' ), true ) ? $input['archive_image_ratio'] : $defaults['archive_image_ratio'];
		$card     = isset( $input['archive_card_style'] ) && in_array( $input['archive_card_style'], array( 'classic', 'minimal', 'elevated', 'overlay' ), true ) ? $input['archive_card_style'] : $defaults['archive_card_style'];
		$load     = isset( $input['archive_load_mode'] ) && in_array( $input['archive_load_mode'], array( 'paged', 'button', 'infinite' ), true ) ? $input['archive_load_mode'] : $defaults['archive_load_mode'];
		$action   = isset( $input['cta_primary_action'] ) && in_array( $input['cta_primary_action'], array( 'click_to_chat', 'form' ), true ) ? $input['cta_primary_action'] : $defaults['cta_primary_action'];
		$trigger  = isset( $input['ctc_trigger'] ) && in_array( $input['ctc_trigger'], array( 'ctc_chat', 'ctc_greetings' ), true ) ? $input['ctc_trigger'] : $defaults['ctc_trigger'];
		$creator  = isset( $input['image_creator_type'] ) && in_array( $input['image_creator_type'], array( 'Organization', 'Person' ), true ) ? $input['image_creator_type'] : $defaults['image_creator_type'];
		return array(
			'archive_title'             => isset( $input['archive_title'] ) ? sanitize_text_field( $input['archive_title'] ) : '',
			'archive_intro'             => isset( $input['archive_intro'] ) ? wp_kses_post( $input['archive_intro'] ) : '',
			'portfolio_page_url'        => isset( $input['portfolio_page_url'] ) ? esc_url_raw( $input['portfolio_page_url'] ) : '',
			'contact_url'               => isset( $input['contact_url'] ) ? esc_url_raw( $input['contact_url'] ) : '',
			'phone'                     => isset( $input['phone'] ) ? sanitize_text_field( $input['phone'] ) : '',
			'whatsapp'                  => isset( $input['whatsapp'] ) ? preg_replace( '/[^0-9+]/', '', $input['whatsapp'] ) : '',
			'accent_color'              => isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : $defaults['accent_color'],
			'rank_math_schema'          => empty( $input['rank_math_schema'] ) ? '0' : '1',
			'image_schema'              => empty( $input['image_schema'] ) ? '0' : '1',
			'archive_layout'            => $layout,
			'archive_columns'           => (string) max( 1, min( 3, isset( $input['archive_columns'] ) ? absint( $input['archive_columns'] ) : 3 ) ),
			'archive_image_ratio'       => $ratio,
			'archive_card_style'        => $card,
			'archive_posts_per_page'    => (string) max( 1, min( 48, isset( $input['archive_posts_per_page'] ) ? absint( $input['archive_posts_per_page'] ) : 8 ) ),
			'archive_load_mode'         => $load,
			'archive_show_filters'      => empty( $input['archive_show_filters'] ) ? '0' : '1',
			'archive_show_search'       => empty( $input['archive_show_search'] ) ? '0' : '1',
			'archive_show_cta'          => empty( $input['archive_show_cta'] ) ? '0' : '1',
			'before_after_mode'         => isset( $input['before_after_mode'] ) && 'slider' === $input['before_after_mode'] ? 'slider' : 'columns',
			'cta_title'                 => isset( $input['cta_title'] ) ? sanitize_text_field( $input['cta_title'] ) : '',
			'cta_text'                  => isset( $input['cta_text'] ) ? sanitize_textarea_field( $input['cta_text'] ) : '',
			'cta_process_title'         => isset( $input['cta_process_title'] ) ? sanitize_text_field( $input['cta_process_title'] ) : '',
			'cta_process_steps'         => isset( $input['cta_process_steps'] ) ? sanitize_textarea_field( $input['cta_process_steps'] ) : '',
			'cta_benefits'              => isset( $input['cta_benefits'] ) ? sanitize_textarea_field( $input['cta_benefits'] ) : '',
			'cta_show_opening_status'   => empty( $input['cta_show_opening_status'] ) ? '0' : '1',
			'cta_primary_action'        => $action,
			'cta_primary_label'         => isset( $input['cta_primary_label'] ) ? sanitize_text_field( $input['cta_primary_label'] ) : '',
			'cta_secondary_enabled'     => empty( $input['cta_secondary_enabled'] ) ? '0' : '1',
			'cta_secondary_label'       => isset( $input['cta_secondary_label'] ) ? sanitize_text_field( $input['cta_secondary_label'] ) : '',
			'form_url'                  => isset( $input['form_url'] ) ? esc_url_raw( $input['form_url'] ) : '',
			'ctc_trigger'               => $trigger,
			'image_creator_type'        => $creator,
			'image_creator_name'        => isset( $input['image_creator_name'] ) ? sanitize_text_field( $input['image_creator_name'] ) : '',
			'image_creator_url'         => isset( $input['image_creator_url'] ) ? esc_url_raw( $input['image_creator_url'] ) : '',
			'image_credit'              => isset( $input['image_credit'] ) ? sanitize_text_field( $input['image_credit'] ) : '',
			'image_copyright'           => isset( $input['image_copyright'] ) ? sanitize_text_field( $input['image_copyright'] ) : '',
			'image_license_url'         => isset( $input['image_license_url'] ) ? esc_url_raw( $input['image_license_url'] ) : '',
			'image_acquire_license_url' => isset( $input['image_acquire_license_url'] ) ? esc_url_raw( $input['image_acquire_license_url'] ) : '',
			'legacy_redirects'          => empty( $input['legacy_redirects'] ) ? '0' : '1',
		);
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = wp_parse_args(
			get_option( 'kv2ps_settings', array() ),
			KV2PS_Plugin::default_settings()
		);
		?>
		<div class="wrap kv2ps-admin">
			<h1><?php esc_html_e( 'Réglages de KV2 Portfolio Studio', 'kv2-portfolio-studio' ); ?></h1>
			<p><?php esc_html_e( 'Rank Math conserve la main sur les titres, descriptions, canonicals et sitemaps. Le complément CreativeWork est facultatif et désactivé par défaut.', 'kv2-portfolio-studio' ); ?></p>
			<form action="options.php" method="post">
				<?php settings_fields( 'kv2ps_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="kv2ps-archive-title"><?php esc_html_e( 'Titre de l’archive', 'kv2-portfolio-studio' ); ?></label></th>
						<td><input class="regular-text" id="kv2ps-archive-title" name="kv2ps_settings[archive_title]" type="text" value="<?php echo esc_attr( $settings['archive_title'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="kv2ps-archive-intro"><?php esc_html_e( 'Introduction', 'kv2-portfolio-studio' ); ?></label></th>
						<td><textarea class="large-text" id="kv2ps-archive-intro" name="kv2ps_settings[archive_intro]" rows="5"><?php echo esc_textarea( $settings['archive_intro'] ); ?></textarea></td>
					</tr>
					<tr><th colspan="2"><h2><?php esc_html_e( 'Affichage des réalisations', 'kv2-portfolio-studio' ); ?></h2></th></tr>
					<tr>
						<th scope="row"><label for="kv2ps-archive-layout"><?php esc_html_e( 'Disposition', 'kv2-portfolio-studio' ); ?></label></th>
						<td><select id="kv2ps-archive-layout" name="kv2ps_settings[archive_layout]"><option value="grid" <?php selected( $settings['archive_layout'], 'grid' ); ?>><?php esc_html_e( 'Grille régulière', 'kv2-portfolio-studio' ); ?></option><option value="tiles" <?php selected( $settings['archive_layout'], 'tiles' ); ?>><?php esc_html_e( 'Tuiles éditoriales', 'kv2-portfolio-studio' ); ?></option><option value="masonry" <?php selected( $settings['archive_layout'], 'masonry' ); ?>><?php esc_html_e( 'Masonry', 'kv2-portfolio-studio' ); ?></option></select></td>
					</tr>
					<tr>
						<th scope="row"><label for="kv2ps-archive-columns"><?php esc_html_e( 'Colonnes sur ordinateur', 'kv2-portfolio-studio' ); ?></label></th>
						<td><select id="kv2ps-archive-columns" name="kv2ps_settings[archive_columns]"><?php for ( $column = 1; $column <= 3; $column++ ) : ?><option value="<?php echo esc_attr( $column ); ?>" <?php selected( min( 3, (int) $settings['archive_columns'] ), $column ); ?>><?php echo esc_html( $column ); ?></option><?php endfor; ?></select><p class="description"><?php esc_html_e( 'Trois colonnes maximum pour conserver des images lisibles.', 'kv2-portfolio-studio' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="kv2ps-image-ratio"><?php esc_html_e( 'Format des images', 'kv2-portfolio-studio' ); ?></label></th>
						<td><select id="kv2ps-image-ratio" name="kv2ps_settings[archive_image_ratio]"><option value="auto" <?php selected( $settings['archive_image_ratio'], 'auto' ); ?>><?php esc_html_e( 'Original – idéal Masonry', 'kv2-portfolio-studio' ); ?></option><option value="1-1" <?php selected( $settings['archive_image_ratio'], '1-1' ); ?>>1:1</option><option value="4-3" <?php selected( $settings['archive_image_ratio'], '4-3' ); ?>>4:3</option><option value="3-2" <?php selected( $settings['archive_image_ratio'], '3-2' ); ?>>3:2</option><option value="16-9" <?php selected( $settings['archive_image_ratio'], '16-9' ); ?>>16:9</option></select></td>
					</tr>
					<tr>
						<th scope="row"><label for="kv2ps-card-style"><?php esc_html_e( 'Style des cartes', 'kv2-portfolio-studio' ); ?></label></th>
						<td><select id="kv2ps-card-style" name="kv2ps_settings[archive_card_style]"><option value="classic" <?php selected( $settings['archive_card_style'], 'classic' ); ?>><?php esc_html_e( 'Portfolio classique – titre sous l’image', 'kv2-portfolio-studio' ); ?></option><option value="minimal" <?php selected( $settings['archive_card_style'], 'minimal' ); ?>><?php esc_html_e( 'Minimal', 'kv2-portfolio-studio' ); ?></option><option value="elevated" <?php selected( $settings['archive_card_style'], 'elevated' ); ?>><?php esc_html_e( 'Carte avec relief', 'kv2-portfolio-studio' ); ?></option><option value="overlay" <?php selected( $settings['archive_card_style'], 'overlay' ); ?>><?php esc_html_e( 'Texte sur image', 'kv2-portfolio-studio' ); ?></option></select></td>
					</tr>
					<tr>
						<th scope="row"><label for="kv2ps-per-page"><?php esc_html_e( 'Réalisations par chargement', 'kv2-portfolio-studio' ); ?></label></th>
						<td><input id="kv2ps-per-page" max="48" min="1" name="kv2ps_settings[archive_posts_per_page]" type="number" value="<?php echo esc_attr( $settings['archive_posts_per_page'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="kv2ps-load-mode"><?php esc_html_e( 'Navigation', 'kv2-portfolio-studio' ); ?></label></th>
						<td><select id="kv2ps-load-mode" name="kv2ps_settings[archive_load_mode]"><option value="paged" <?php selected( $settings['archive_load_mode'], 'paged' ); ?>><?php esc_html_e( 'Pagination classique', 'kv2-portfolio-studio' ); ?></option><option value="button" <?php selected( $settings['archive_load_mode'], 'button' ); ?>><?php esc_html_e( 'Bouton “Afficher plus”', 'kv2-portfolio-studio' ); ?></option><option value="infinite" <?php selected( $settings['archive_load_mode'], 'infinite' ); ?>><?php esc_html_e( 'Défilement infini accessible', 'kv2-portfolio-studio' ); ?></option></select><p class="description"><?php esc_html_e( 'La pagination HTML reste présente pour les moteurs et lorsque JavaScript est désactivé.', 'kv2-portfolio-studio' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="kv2ps-before-after"><?php esc_html_e( 'Avant / après', 'kv2-portfolio-studio' ); ?></label></th>
						<td><select id="kv2ps-before-after" name="kv2ps_settings[before_after_mode]"><option value="columns" <?php selected( $settings['before_after_mode'], 'columns' ); ?>><?php esc_html_e( 'Deux colonnes', 'kv2-portfolio-studio' ); ?></option><option value="slider" <?php selected( $settings['before_after_mode'], 'slider' ); ?>><?php esc_html_e( 'Comparateur coulissant', 'kv2-portfolio-studio' ); ?></option></select></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Barre du portfolio', 'kv2-portfolio-studio' ); ?></th>
						<td><label><input name="kv2ps_settings[archive_show_filters]" type="checkbox" value="1" <?php checked( $settings['archive_show_filters'], '1' ); ?>> <?php esc_html_e( 'Afficher les filtres de services', 'kv2-portfolio-studio' ); ?></label><br><label><input name="kv2ps_settings[archive_show_search]" type="checkbox" value="1" <?php checked( $settings['archive_show_search'], '1' ); ?>> <?php esc_html_e( 'Afficher la recherche à droite', 'kv2-portfolio-studio' ); ?></label><br><label><input name="kv2ps_settings[archive_show_cta]" type="checkbox" value="1" <?php checked( $settings['archive_show_cta'], '1' ); ?>> <?php esc_html_e( 'Afficher le CTA global sous les réalisations', 'kv2-portfolio-studio' ); ?></label></td>
					</tr>
					<tr><th colspan="2"><h2><?php esc_html_e( 'CTA intelligent', 'kv2-portfolio-studio' ); ?></h2><p><?php esc_html_e( 'Ces valeurs deviennent les valeurs par défaut de toutes les réalisations. Elles peuvent être remplacées projet par projet.', 'kv2-portfolio-studio' ); ?></p></th></tr>
					<tr><th scope="row"><label for="kv2ps-portfolio-page-url"><?php esc_html_e( 'Page principale des réalisations', 'kv2-portfolio-studio' ); ?></label></th><td><input class="regular-text code" id="kv2ps-portfolio-page-url" name="kv2ps_settings[portfolio_page_url]" type="url" value="<?php echo esc_attr( $settings['portfolio_page_url'] ); ?>"><p class="description"><?php esc_html_e( 'Destination du lien « Toutes les réalisations » depuis une fiche.', 'kv2-portfolio-studio' ); ?></p></td></tr>
					<tr><th scope="row"><label for="kv2ps-cta-title"><?php esc_html_e( 'Titre du CTA', 'kv2-portfolio-studio' ); ?></label></th><td><input class="regular-text" id="kv2ps-cta-title" name="kv2ps_settings[cta_title]" type="text" value="<?php echo esc_attr( $settings['cta_title'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="kv2ps-cta-text"><?php esc_html_e( 'Texte du CTA', 'kv2-portfolio-studio' ); ?></label></th><td><textarea class="large-text" id="kv2ps-cta-text" name="kv2ps_settings[cta_text]" rows="3"><?php echo esc_textarea( $settings['cta_text'] ); ?></textarea></td></tr>
					<tr><th scope="row"><label for="kv2ps-cta-process-title"><?php esc_html_e( 'Titre du parcours', 'kv2-portfolio-studio' ); ?></label></th><td><input class="regular-text" id="kv2ps-cta-process-title" name="kv2ps_settings[cta_process_title]" type="text" value="<?php echo esc_attr( $settings['cta_process_title'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="kv2ps-cta-process-steps"><?php esc_html_e( 'Étapes du parcours', 'kv2-portfolio-studio' ); ?></label></th><td><textarea class="large-text" id="kv2ps-cta-process-steps" name="kv2ps_settings[cta_process_steps]" rows="5"><?php echo esc_textarea( $settings['cta_process_steps'] ); ?></textarea><p class="description"><?php esc_html_e( 'Une étape par ligne.', 'kv2-portfolio-studio' ); ?></p></td></tr>
					<tr><th scope="row"><label for="kv2ps-cta-benefits"><?php esc_html_e( 'Points forts', 'kv2-portfolio-studio' ); ?></label></th><td><textarea class="large-text" id="kv2ps-cta-benefits" name="kv2ps_settings[cta_benefits]" rows="4"><?php echo esc_textarea( $settings['cta_benefits'] ); ?></textarea><p class="description"><?php esc_html_e( 'Un avantage par ligne.', 'kv2-portfolio-studio' ); ?></p></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Horaires dynamiques', 'kv2-portfolio-studio' ); ?></th><td><label><input name="kv2ps_settings[cta_show_opening_status]" type="checkbox" value="1" <?php checked( $settings['cta_show_opening_status'], '1' ); ?>> <?php esc_html_e( 'Afficher le statut et la synthèse du plugin We’re Open! dans le CTA', 'kv2-portfolio-studio' ); ?></label></td></tr>
					<tr><th scope="row"><label for="kv2ps-cta-action"><?php esc_html_e( 'Action principale', 'kv2-portfolio-studio' ); ?></label></th><td><select id="kv2ps-cta-action" name="kv2ps_settings[cta_primary_action]"><option value="click_to_chat" <?php selected( $settings['cta_primary_action'], 'click_to_chat' ); ?>><?php esc_html_e( 'Ouvrir Click to Chat', 'kv2-portfolio-studio' ); ?></option><option value="form" <?php selected( $settings['cta_primary_action'], 'form' ); ?>><?php esc_html_e( 'Ouvrir le formulaire', 'kv2-portfolio-studio' ); ?></option></select></td></tr>
					<tr><th scope="row"><label for="kv2ps-cta-primary-label"><?php esc_html_e( 'Libellé principal', 'kv2-portfolio-studio' ); ?></label></th><td><input class="regular-text" id="kv2ps-cta-primary-label" name="kv2ps_settings[cta_primary_label]" type="text" value="<?php echo esc_attr( $settings['cta_primary_label'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="kv2ps-form-url"><?php esc_html_e( 'Adresse du formulaire', 'kv2-portfolio-studio' ); ?></label></th><td><input class="regular-text code" id="kv2ps-form-url" name="kv2ps_settings[form_url]" type="url" value="<?php echo esc_attr( $settings['form_url'] ); ?>"><p class="description"><?php esc_html_e( 'Page de contact ou ancre, par exemple https://site.fr/contact/#devis.', 'kv2-portfolio-studio' ); ?></p></td></tr>
					<tr><th scope="row"><label for="kv2ps-ctc-trigger"><?php esc_html_e( 'Déclencheur Click to Chat', 'kv2-portfolio-studio' ); ?></label></th><td><select id="kv2ps-ctc-trigger" name="kv2ps_settings[ctc_trigger]"><option value="ctc_chat" <?php selected( $settings['ctc_trigger'], 'ctc_chat' ); ?>>ctc_chat — <?php esc_html_e( 'ouvrir WhatsApp', 'kv2-portfolio-studio' ); ?></option><option value="ctc_greetings" <?php selected( $settings['ctc_trigger'], 'ctc_greetings' ); ?>>ctc_greetings — <?php esc_html_e( 'ouvrir l’encart de bienvenue', 'kv2-portfolio-studio' ); ?></option></select></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Bouton secondaire', 'kv2-portfolio-studio' ); ?></th><td><label><input name="kv2ps_settings[cta_secondary_enabled]" type="checkbox" value="1" <?php checked( $settings['cta_secondary_enabled'], '1' ); ?>> <?php esc_html_e( 'Afficher aussi l’autre action', 'kv2-portfolio-studio' ); ?></label><br><input class="regular-text" name="kv2ps_settings[cta_secondary_label]" type="text" value="<?php echo esc_attr( $settings['cta_secondary_label'] ); ?>"></td></tr>
					<tr>
						<th scope="row"><label for="kv2ps-contact-url"><?php esc_html_e( 'URL de contact', 'kv2-portfolio-studio' ); ?></label></th>
						<td><input class="regular-text code" id="kv2ps-contact-url" name="kv2ps_settings[contact_url]" type="url" value="<?php echo esc_attr( $settings['contact_url'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="kv2ps-phone"><?php esc_html_e( 'Téléphone affiché dans le CTA', 'kv2-portfolio-studio' ); ?></label></th>
						<td><input class="regular-text" id="kv2ps-phone" name="kv2ps_settings[phone]" type="text" value="<?php echo esc_attr( $settings['phone'] ); ?>"><p class="description"><?php esc_html_e( 'Le numéro est automatiquement rendu cliquable.', 'kv2-portfolio-studio' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="kv2ps-whatsapp"><?php esc_html_e( 'Numéro WhatsApp', 'kv2-portfolio-studio' ); ?></label></th>
						<td><input class="regular-text" id="kv2ps-whatsapp" name="kv2ps_settings[whatsapp]" type="text" value="<?php echo esc_attr( $settings['whatsapp'] ); ?>"><p class="description"><?php esc_html_e( 'Format international, par exemple +33612345678.', 'kv2-portfolio-studio' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="kv2ps-accent"><?php esc_html_e( 'Couleur d’accent', 'kv2-portfolio-studio' ); ?></label></th>
						<td><input id="kv2ps-accent" name="kv2ps_settings[accent_color]" type="color" value="<?php echo esc_attr( $settings['accent_color'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Schema CreativeWork', 'kv2-portfolio-studio' ); ?></th>
						<td><label><input name="kv2ps_settings[rank_math_schema]" type="checkbox" value="1" <?php checked( $settings['rank_math_schema'], '1' ); ?>> <?php esc_html_e( 'Ajouter uniquement s’il n’existe pas déjà dans le JSON-LD de Rank Math', 'kv2-portfolio-studio' ); ?></label></td>
					</tr>
					<tr><th colspan="2"><h2><?php esc_html_e( 'Créateur, droits et licence des images', 'kv2-portfolio-studio' ); ?></h2><p><?php esc_html_e( 'Valeurs globales utilisées lorsque l’image ne possède pas ses propres informations.', 'kv2-portfolio-studio' ); ?></p></th></tr>
					<tr><th scope="row"><label for="kv2ps-creator-type"><?php esc_html_e( 'Type de créateur', 'kv2-portfolio-studio' ); ?></label></th><td><select id="kv2ps-creator-type" name="kv2ps_settings[image_creator_type]"><option value="Organization" <?php selected( $settings['image_creator_type'], 'Organization' ); ?>><?php esc_html_e( 'Entreprise', 'kv2-portfolio-studio' ); ?></option><option value="Person" <?php selected( $settings['image_creator_type'], 'Person' ); ?>><?php esc_html_e( 'Personne', 'kv2-portfolio-studio' ); ?></option></select></td></tr>
					<tr><th scope="row"><label for="kv2ps-creator-name"><?php esc_html_e( 'Nom du créateur', 'kv2-portfolio-studio' ); ?></label></th><td><input class="regular-text" id="kv2ps-creator-name" name="kv2ps_settings[image_creator_name]" type="text" value="<?php echo esc_attr( $settings['image_creator_name'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="kv2ps-creator-url"><?php esc_html_e( 'URL du créateur', 'kv2-portfolio-studio' ); ?></label></th><td><input class="regular-text code" id="kv2ps-creator-url" name="kv2ps_settings[image_creator_url]" type="url" value="<?php echo esc_attr( $settings['image_creator_url'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="kv2ps-image-credit"><?php esc_html_e( 'Crédit par défaut', 'kv2-portfolio-studio' ); ?></label></th><td><input class="regular-text" id="kv2ps-image-credit" name="kv2ps_settings[image_credit]" type="text" value="<?php echo esc_attr( $settings['image_credit'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="kv2ps-image-copyright"><?php esc_html_e( 'Copyright par défaut', 'kv2-portfolio-studio' ); ?></label></th><td><input class="regular-text" id="kv2ps-image-copyright" name="kv2ps_settings[image_copyright]" type="text" value="<?php echo esc_attr( $settings['image_copyright'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="kv2ps-license-url"><?php esc_html_e( 'Page de licence', 'kv2-portfolio-studio' ); ?></label></th><td><input class="regular-text code" id="kv2ps-license-url" name="kv2ps_settings[image_license_url]" type="url" value="<?php echo esc_attr( $settings['image_license_url'] ); ?>"></td></tr>
					<tr><th scope="row"><label for="kv2ps-acquire-license-url"><?php esc_html_e( 'Page pour demander les droits', 'kv2-portfolio-studio' ); ?></label></th><td><input class="regular-text code" id="kv2ps-acquire-license-url" name="kv2ps_settings[image_acquire_license_url]" type="url" value="<?php echo esc_attr( $settings['image_acquire_license_url'] ); ?>"></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'ImageObject', 'kv2-portfolio-studio' ); ?></th><td><label><input name="kv2ps_settings[image_schema]" type="checkbox" value="1" <?php checked( $settings['image_schema'], '1' ); ?>> <?php esc_html_e( 'Compléter le graphe Rank Math avec les crédits et licences manquants', 'kv2-portfolio-studio' ); ?></label></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Redirections WP Portfolio', 'kv2-portfolio-studio' ); ?></th><td><label><input name="kv2ps_settings[legacy_redirects]" type="checkbox" value="1" <?php checked( $settings['legacy_redirects'], '1' ); ?>> <?php esc_html_e( 'Rediriger les anciennes URL devenues 404 vers les réalisations publiées', 'kv2-portfolio-studio' ); ?></label></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function add_meta_boxes() {
		add_meta_box(
			'kv2ps-project-story',
			__( 'Histoire et médias du projet', 'kv2-portfolio-studio' ),
			array( __CLASS__, 'render_project_metabox' ),
			KV2PS_Post_Types::POST_TYPE,
			'normal',
			'high'
		);
	}

	public static function render_project_metabox( $post ) {
		wp_nonce_field( 'kv2ps_save_realisation', 'kv2ps_nonce' );
		$fields = array(
			'problem'      => array( 'Le besoin / problème', 'Ce que le client souhaitait résoudre.' ),
			'intervention' => array( 'Notre intervention', 'Diagnostic, étapes, savoir-faire et choix techniques.' ),
			'result'       => array( 'Le résultat', 'Bénéfice visible, usage retrouvé et finition.' ),
			'materials'    => array( 'Matières et finitions', 'Tissus, bois, mousses, peintures, références utiles.' ),
			'initial_state' => array( 'État initial', 'Usure, défauts ou état de la pièce avant intervention.' ),
			'constraints'   => array( 'Contraintes particulières', 'Délais, conservation, usage, dimensions ou contraintes techniques.' ),
		);
		?>
		<div class="kv2ps-metabox-grid">
			<?php foreach ( $fields as $key => $labels ) : ?>
				<p class="kv2ps-field">
					<label for="kv2ps-<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $labels[0] ); ?></strong></label>
					<span class="description"><?php echo esc_html( $labels[1] ); ?></span>
				</p>
			<?php endforeach; ?>
			<p class="kv2ps-field">
				<label for="kv2ps-project-date"><strong><?php esc_html_e( 'Date du projet', 'kv2-portfolio-studio' ); ?></strong></label>
				<input id="kv2ps-project-date" name="kv2ps_project_date" type="date" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_project_date', true ) ); ?>">
			</p>
			<p class="kv2ps-field"><label for="kv2ps-duration"><strong><?php esc_html_e( 'Durée de réalisation', 'kv2-portfolio-studio' ); ?></strong></label><input id="kv2ps-duration" name="kv2ps_duration" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_duration', true ) ); ?>" placeholder="Ex. 3 semaines"></p>
			<p class="kv2ps-field"><label for="kv2ps-work-type"><strong><?php esc_html_e( 'Type de transformation', 'kv2-portfolio-studio' ); ?></strong></label><input id="kv2ps-work-type" name="kv2ps_work_type" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_work_type', true ) ); ?>" placeholder="Restauration, création sur mesure…"></p>
			<p class="kv2ps-field"><label for="kv2ps-price-range"><strong><?php esc_html_e( 'Fourchette tarifaire facultative', 'kv2-portfolio-studio' ); ?></strong></label><input id="kv2ps-price-range" name="kv2ps_price_range" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_price_range', true ) ); ?>" placeholder="Ex. 800 à 1 200 €"></p>
			<p class="kv2ps-field"><label><input name="kv2ps_confidential" type="checkbox" value="1" <?php checked( get_post_meta( $post->ID, '_kv2ps_confidential', true ), '1' ); ?>> <strong><?php esc_html_e( 'Projet confidentiel : ne pas afficher d’identité ou de localisation précise', 'kv2-portfolio-studio' ); ?></strong></label></p>
		</div>

		<div class="kv2ps-subpanel">
			<h3><?php esc_html_e( 'Témoignage client', 'kv2-portfolio-studio' ); ?></h3>
			<p class="kv2ps-field"><label for="kv2ps-testimonial"><strong><?php esc_html_e( 'Citation', 'kv2-portfolio-studio' ); ?></strong></label><textarea id="kv2ps-testimonial" name="kv2ps_testimonial" rows="3"><?php echo esc_textarea( get_post_meta( $post->ID, '_kv2ps_testimonial', true ) ); ?></textarea></p>
			<div class="kv2ps-inline-fields">
				<p><label for="kv2ps-testimonial-author"><?php esc_html_e( 'Nom affiché', 'kv2-portfolio-studio' ); ?></label><input id="kv2ps-testimonial-author" name="kv2ps_testimonial_author" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_testimonial_author', true ) ); ?>" placeholder="Cliente à Montpellier"></p>
				<p><label for="kv2ps-testimonial-source"><?php esc_html_e( 'Source', 'kv2-portfolio-studio' ); ?></label><input id="kv2ps-testimonial-source" name="kv2ps_testimonial_source" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_testimonial_source', true ) ); ?>" placeholder="Google, e-mail…"></p>
				<p><label for="kv2ps-testimonial-source-url"><?php esc_html_e( 'Lien de l’avis', 'kv2-portfolio-studio' ); ?></label><input id="kv2ps-testimonial-source-url" name="kv2ps_testimonial_source_url" type="url" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_testimonial_source_url', true ) ); ?>"></p>
				<p><label for="kv2ps-testimonial-rating"><?php esc_html_e( 'Note sur 5', 'kv2-portfolio-studio' ); ?></label><input id="kv2ps-testimonial-rating" max="5" min="1" name="kv2ps_testimonial_rating" type="number" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_testimonial_rating', true ) ); ?>"></p>
				<p><label for="kv2ps-testimonial-date"><?php esc_html_e( 'Date', 'kv2-portfolio-studio' ); ?></label><input id="kv2ps-testimonial-date" name="kv2ps_testimonial_date" type="date" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_testimonial_date', true ) ); ?>"></p>
			</div>
			<label><input name="kv2ps_testimonial_consent" type="checkbox" value="1" <?php checked( get_post_meta( $post->ID, '_kv2ps_testimonial_consent', true ), '1' ); ?>> <?php esc_html_e( 'Autorisation de publication obtenue', 'kv2-portfolio-studio' ); ?></label>
		</div>

		<div class="kv2ps-subpanel">
			<h3><?php esc_html_e( 'CTA de cette réalisation', 'kv2-portfolio-studio' ); ?></h3>
			<label><input name="kv2ps_cta_override" type="checkbox" value="1" <?php checked( get_post_meta( $post->ID, '_kv2ps_cta_override', true ), '1' ); ?>> <?php esc_html_e( 'Remplacer les réglages globaux', 'kv2-portfolio-studio' ); ?></label>
			<div class="kv2ps-inline-fields">
				<p><label for="kv2ps-cta-title-override"><?php esc_html_e( 'Titre', 'kv2-portfolio-studio' ); ?></label><input id="kv2ps-cta-title-override" name="kv2ps_cta_title" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_cta_title', true ) ); ?>"></p>
				<p><label for="kv2ps-cta-action-override"><?php esc_html_e( 'Action principale', 'kv2-portfolio-studio' ); ?></label><select id="kv2ps-cta-action-override" name="kv2ps_cta_primary_action"><option value="click_to_chat" <?php selected( get_post_meta( $post->ID, '_kv2ps_cta_primary_action', true ), 'click_to_chat' ); ?>>Click to Chat</option><option value="form" <?php selected( get_post_meta( $post->ID, '_kv2ps_cta_primary_action', true ), 'form' ); ?>><?php esc_html_e( 'Formulaire', 'kv2-portfolio-studio' ); ?></option></select></p>
				<p><label for="kv2ps-cta-primary-label-override"><?php esc_html_e( 'Libellé principal', 'kv2-portfolio-studio' ); ?></label><input id="kv2ps-cta-primary-label-override" name="kv2ps_cta_primary_label" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_cta_primary_label', true ) ); ?>"></p>
				<p><label for="kv2ps-cta-form-url-override"><?php esc_html_e( 'URL du formulaire', 'kv2-portfolio-studio' ); ?></label><input id="kv2ps-cta-form-url-override" name="kv2ps_cta_form_url" type="url" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_cta_form_url', true ) ); ?>"></p>
			</div>
			<p class="kv2ps-field"><label for="kv2ps-cta-text-override"><?php esc_html_e( 'Texte', 'kv2-portfolio-studio' ); ?></label><textarea id="kv2ps-cta-text-override" name="kv2ps_cta_text" rows="2"><?php echo esc_textarea( get_post_meta( $post->ID, '_kv2ps_cta_text', true ) ); ?></textarea></p>
			<label><input name="kv2ps_cta_secondary_enabled" type="checkbox" value="1" <?php checked( get_post_meta( $post->ID, '_kv2ps_cta_secondary_enabled', true ), '1' ); ?>> <?php esc_html_e( 'Afficher le bouton secondaire', 'kv2-portfolio-studio' ); ?></label>
			<p><label for="kv2ps-cta-secondary-label-override"><?php esc_html_e( 'Libellé secondaire', 'kv2-portfolio-studio' ); ?></label><input id="kv2ps-cta-secondary-label-override" name="kv2ps_cta_secondary_label" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_kv2ps_cta_secondary_label', true ) ); ?>"></p>
		</div>

		<?php
		self::render_gallery_field( $post->ID, 'before', __( 'Photos avant', 'kv2-portfolio-studio' ) );
		self::render_gallery_field( $post->ID, 'after', __( 'Photos après', 'kv2-portfolio-studio' ) );
	}

	private static function render_gallery_field( $post_id, $key, $label ) {
		$ids = KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_' . $key . '_images', true ) );
		?>
		<div class="kv2ps-gallery-field" data-gallery="<?php echo esc_attr( $key ); ?>">
			<h3><?php echo esc_html( $label ); ?></h3>
			<input class="kv2ps-gallery-ids" name="kv2ps_<?php echo esc_attr( $key ); ?>_images" type="hidden" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
			<div class="kv2ps-gallery-preview">
				<?php foreach ( $ids as $id ) : ?>
					<div class="kv2ps-gallery-item" data-id="<?php echo esc_attr( $id ); ?>"><?php echo wp_get_attachment_image( $id, 'thumbnail' ); ?><button class="button-link-delete kv2ps-remove-image" type="button" aria-label="<?php esc_attr_e( 'Retirer l’image', 'kv2-portfolio-studio' ); ?>">×</button></div>
				<?php endforeach; ?>
			</div>
			<p><button class="button kv2ps-select-images" type="button"><?php esc_html_e( 'Choisir des images', 'kv2-portfolio-studio' ); ?></button></p>
		</div>
		<?php
	}

	public static function save_realisation( $post_id ) {
		if ( ! isset( $_POST['kv2ps_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kv2ps_nonce'] ) ), 'kv2ps_save_realisation' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( array( 'problem', 'intervention', 'result', 'materials', 'initial_state', 'constraints', 'testimonial', 'cta_text' ) as $field ) {
			$value = isset( $_POST[ 'kv2ps_' . $field ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ 'kv2ps_' . $field ] ) ) : '';
			self::update_or_delete_meta( $post_id, '_kv2ps_' . $field, $value );
		}

		foreach ( array( 'duration', 'work_type', 'price_range', 'testimonial_author', 'testimonial_source', 'cta_title', 'cta_primary_label', 'cta_secondary_label' ) as $field ) {
			$value = isset( $_POST[ 'kv2ps_' . $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'kv2ps_' . $field ] ) ) : '';
			self::update_or_delete_meta( $post_id, '_kv2ps_' . $field, $value );
		}

		foreach ( array( 'testimonial_source_url', 'cta_form_url' ) as $field ) {
			$value = isset( $_POST[ 'kv2ps_' . $field ] ) ? esc_url_raw( wp_unslash( $_POST[ 'kv2ps_' . $field ] ) ) : '';
			self::update_or_delete_meta( $post_id, '_kv2ps_' . $field, $value );
		}

		$rating = isset( $_POST['kv2ps_testimonial_rating'] ) ? absint( $_POST['kv2ps_testimonial_rating'] ) : 0;
		self::update_or_delete_meta( $post_id, '_kv2ps_testimonial_rating', $rating >= 1 && $rating <= 5 ? (string) $rating : '' );

		$cta_action = isset( $_POST['kv2ps_cta_primary_action'] ) ? sanitize_key( wp_unslash( $_POST['kv2ps_cta_primary_action'] ) ) : '';
		self::update_or_delete_meta( $post_id, '_kv2ps_cta_primary_action', in_array( $cta_action, array( 'click_to_chat', 'form' ), true ) ? $cta_action : '' );

		foreach ( array( 'confidential', 'testimonial_consent', 'cta_override', 'cta_secondary_enabled' ) as $field ) {
			if ( ! empty( $_POST[ 'kv2ps_' . $field ] ) ) {
				update_post_meta( $post_id, '_kv2ps_' . $field, '1' );
			} else {
				delete_post_meta( $post_id, '_kv2ps_' . $field );
			}
		}

		$date = isset( $_POST['kv2ps_project_date'] ) ? sanitize_text_field( wp_unslash( $_POST['kv2ps_project_date'] ) ) : '';
		if ( $date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$date = '';
		}
		self::update_or_delete_meta( $post_id, '_kv2ps_project_date', $date );

		$testimonial_date = isset( $_POST['kv2ps_testimonial_date'] ) ? sanitize_text_field( wp_unslash( $_POST['kv2ps_testimonial_date'] ) ) : '';
		if ( $testimonial_date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $testimonial_date ) ) {
			$testimonial_date = '';
		}
		self::update_or_delete_meta( $post_id, '_kv2ps_testimonial_date', $testimonial_date );

		foreach ( array( 'before', 'after' ) as $gallery ) {
			$value = isset( $_POST[ 'kv2ps_' . $gallery . '_images' ] ) ? wp_unslash( $_POST[ 'kv2ps_' . $gallery . '_images' ] ) : '';
			$ids   = KV2PS_Post_Types::sanitize_ids( $value );
			self::update_or_delete_meta( $post_id, '_kv2ps_' . $gallery . '_images', $ids );
		}
	}

	private static function update_or_delete_meta( $object_id, $key, $value ) {
		if ( '' === $value || array() === $value ) {
			delete_post_meta( $object_id, $key );
		} else {
			update_post_meta( $object_id, $key, $value );
		}
	}

	public static function admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$is_plugin_page = false !== strpos( (string) $screen->id, 'kv2ps' );
		$is_editor      = KV2PS_Post_Types::POST_TYPE === $screen->post_type && in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		$is_post_list   = KV2PS_Post_Types::POST_TYPE === $screen->post_type && 'edit.php' === $hook;
		if ( ! $is_plugin_page && ! $is_editor && ! $is_post_list ) {
			return;
		}

		wp_enqueue_style( 'kv2ps-admin', KV2PS_URL . 'assets/admin.css', array(), KV2PS_VERSION );
		wp_enqueue_media();
		wp_enqueue_script( 'kv2ps-admin', KV2PS_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), KV2PS_VERSION, true );
		wp_localize_script(
			'kv2ps-admin',
			'kv2psAdmin',
			array(
				'mediaTitle'  => __( 'Choisir les photos du projet', 'kv2-portfolio-studio' ),
				'mediaButton' => __( 'Utiliser ces images', 'kv2-portfolio-studio' ),
			)
		);
	}

	public static function term_add_fields() {
		wp_nonce_field( 'kv2ps_term_fields', 'kv2ps_term_nonce' );
		?>
		<div class="form-field">
			<label for="kv2ps-landing-url"><?php esc_html_e( 'Page SEO principale', 'kv2-portfolio-studio' ); ?></label>
			<input id="kv2ps-landing-url" name="kv2ps_landing_url" type="url" value="">
			<p><?php esc_html_e( 'URL facultative d’une page service ou ville existante. Elle sera proposée comme lien contextuel sans remplacer l’archive.', 'kv2-portfolio-studio' ); ?></p>
		</div>
		<?php
	}

	public static function term_edit_fields( $term ) {
		wp_nonce_field( 'kv2ps_term_fields', 'kv2ps_term_nonce' );
		?>
		<tr class="form-field">
			<th scope="row"><label for="kv2ps-landing-url"><?php esc_html_e( 'Page SEO principale', 'kv2-portfolio-studio' ); ?></label></th>
			<td><input class="regular-text" id="kv2ps-landing-url" name="kv2ps_landing_url" type="url" value="<?php echo esc_attr( get_term_meta( $term->term_id, '_kv2ps_landing_url', true ) ); ?>"><p class="description"><?php esc_html_e( 'Lien contextuel vers une page service ou ville existante.', 'kv2-portfolio-studio' ); ?></p></td>
		</tr>
		<?php
	}

	public static function save_term_fields( $term_id ) {
		if ( ! isset( $_POST['kv2ps_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kv2ps_term_nonce'] ) ), 'kv2ps_term_fields' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		$url = isset( $_POST['kv2ps_landing_url'] ) ? esc_url_raw( wp_unslash( $_POST['kv2ps_landing_url'] ) ) : '';
		if ( $url ) {
			update_term_meta( $term_id, '_kv2ps_landing_url', $url );
		} else {
			delete_term_meta( $term_id, '_kv2ps_landing_url' );
		}
	}
}
