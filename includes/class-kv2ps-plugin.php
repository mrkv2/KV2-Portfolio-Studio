<?php

defined( 'ABSPATH' ) || exit;

final class KV2PS_Plugin {
	private static $instance;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ), 5 );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( 'KV2PS_Post_Types', 'register' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'pre_get_posts', array( $this, 'configure_archive_query' ) );
		add_filter( 'template_include', array( $this, 'template_include' ) );
		add_shortcode( 'kv2_portfolio', array( $this, 'portfolio_shortcode' ) );

		KV2PS_Image_Metadata::init();
		KV2PS_Schema::init();
		KV2PS_Redirects::init();

		if ( is_admin() ) {
			KV2PS_Admin::init();
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'kv2-portfolio-studio', false, dirname( plugin_basename( KV2PS_FILE ) ) . '/languages' );
	}

	public static function activate() {
		KV2PS_Post_Types::register();
		flush_rewrite_rules();

		if ( false === get_option( 'kv2ps_settings', false ) ) {
			add_option(
				'kv2ps_settings',
				self::default_settings()
			);
		}
		update_option( 'kv2ps_version', KV2PS_VERSION );
	}

	public function maybe_upgrade() {
		$installed_version = (string) get_option( 'kv2ps_version', '1.0.0' );
		if ( version_compare( $installed_version, KV2PS_VERSION, '>=' ) ) {
			return;
		}

		$settings = get_option( 'kv2ps_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();
		$legacy_display = array(
			'archive_layout'         => 'grid',
			'archive_columns'        => '2',
			'archive_image_ratio'    => '3-2',
			'archive_card_style'     => 'elevated',
			'archive_posts_per_page' => '8',
			'archive_load_mode'      => 'paged',
		);
		$uses_legacy_defaults = true;
		foreach ( $legacy_display as $key => $value ) {
			if ( isset( $settings[ $key ] ) && (string) $settings[ $key ] !== $value ) {
				$uses_legacy_defaults = false;
				break;
			}
		}

		if ( $uses_legacy_defaults ) {
			$settings = array_merge(
				$settings,
				array(
					'archive_layout'         => 'masonry',
					'archive_columns'        => '3',
					'archive_image_ratio'    => 'auto',
					'archive_card_style'     => 'classic',
					'archive_posts_per_page' => '12',
					'archive_load_mode'      => 'button',
				)
			);
		}

		foreach ( array( 'archive_show_filters', 'archive_show_search', 'archive_show_cta' ) as $key ) {
			if ( ! isset( $settings[ $key ] ) ) {
				$settings[ $key ] = '1';
			}
		}

		update_option( 'kv2ps_settings', $settings );
		update_option( 'kv2ps_version', KV2PS_VERSION );
	}

	public static function default_settings() {
		return array(
			'archive_title'              => 'Nos réalisations',
			'archive_intro'              => '',
			'contact_url'                => home_url( '/contact/' ),
			'phone'                      => '',
			'whatsapp'                   => '',
			'accent_color'               => '#9b6b43',
			'rank_math_schema'           => '0',
			'image_schema'               => '1',
			'archive_layout'             => 'masonry',
			'archive_columns'            => '3',
			'archive_image_ratio'        => 'auto',
			'archive_card_style'         => 'classic',
			'archive_posts_per_page'     => '12',
			'archive_load_mode'          => 'button',
			'archive_show_filters'       => '1',
			'archive_show_search'        => '1',
			'archive_show_cta'           => '1',
			'before_after_mode'          => 'columns',
			'cta_title'                  => 'Vous avez un projet similaire ?',
			'cta_text'                   => 'Parlons de votre meuble, de vos contraintes et du résultat souhaité.',
			'cta_primary_action'         => 'click_to_chat',
			'cta_primary_label'          => 'Échanger sur WhatsApp',
			'cta_secondary_enabled'      => '1',
			'cta_secondary_label'        => 'Envoyer une demande',
			'form_url'                   => home_url( '/contact/' ),
			'ctc_trigger'                => 'ctc_chat',
			'image_creator_type'         => 'Organization',
			'image_creator_name'         => get_bloginfo( 'name' ),
			'image_creator_url'          => home_url( '/' ),
			'image_credit'               => get_bloginfo( 'name' ),
			'image_copyright'            => get_bloginfo( 'name' ),
			'image_license_url'          => '',
			'image_acquire_license_url'  => '',
			'legacy_redirects'           => '1',
		);
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public function enqueue_frontend_assets() {
		global $post;
		$has_shortcode = is_singular() && $post instanceof WP_Post && has_shortcode( $post->post_content, 'kv2_portfolio' );
		if ( is_singular( KV2PS_Post_Types::POST_TYPE ) || is_post_type_archive( KV2PS_Post_Types::POST_TYPE ) || is_tax( KV2PS_Post_Types::taxonomies() ) || $has_shortcode ) {
			$this->enqueue_styles();
		}
	}

	private function enqueue_styles() {
		wp_enqueue_style( 'kv2ps-frontend', KV2PS_URL . 'assets/frontend.css', array(), KV2PS_VERSION );
		wp_enqueue_script( 'kv2ps-frontend', KV2PS_URL . 'assets/frontend.js', array(), KV2PS_VERSION, true );
		$settings = wp_parse_args( get_option( 'kv2ps_settings', array() ), self::default_settings() );
		$accent   = isset( $settings['accent_color'] ) ? sanitize_hex_color( $settings['accent_color'] ) : '';

		if ( $accent ) {
			wp_add_inline_style( 'kv2ps-frontend', ':root{--kv2ps-accent:' . $accent . ';}' );
		}
	}

	public static function settings() {
		return wp_parse_args( get_option( 'kv2ps_settings', array() ), self::default_settings() );
	}

	public function configure_archive_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! ( $query->is_post_type_archive( KV2PS_Post_Types::POST_TYPE ) || $query->is_tax( KV2PS_Post_Types::taxonomies() ) ) ) {
			return;
		}
		$settings = wp_parse_args( get_option( 'kv2ps_settings', array() ), self::default_settings() );
		$query->set( 'posts_per_page', max( 1, min( 48, absint( $settings['archive_posts_per_page'] ) ) ) );

		$service = isset( $_GET['kv2ps_service'] ) && is_string( $_GET['kv2ps_service'] ) ? sanitize_title( wp_unslash( $_GET['kv2ps_service'] ) ) : '';
		$search  = isset( $_GET['kv2ps_search'] ) && is_string( $_GET['kv2ps_search'] ) ? sanitize_text_field( wp_unslash( $_GET['kv2ps_search'] ) ) : '';

		if ( $service ) {
			$tax_query   = (array) $query->get( 'tax_query' );
			$tax_query[] = array(
				'taxonomy' => 'kv2_service',
				'field'    => 'slug',
				'terms'    => $service,
			);
			$query->set( 'tax_query', $tax_query );
		}
		if ( $search ) {
			$query->set( 's', $search );
		}
	}

	public function template_include( $template ) {
		$plugin_template = '';

		if ( is_singular( KV2PS_Post_Types::POST_TYPE ) ) {
			$plugin_template = 'single-kv2_realisation.php';
		} elseif ( is_post_type_archive( KV2PS_Post_Types::POST_TYPE ) || is_tax( KV2PS_Post_Types::taxonomies() ) ) {
			$plugin_template = 'archive-kv2_realisation.php';
		}

		if ( ! $plugin_template ) {
			return $template;
		}

		$theme_template = locate_template( 'kv2-portfolio-studio/' . $plugin_template );
		return $theme_template ? $theme_template : KV2PS_DIR . 'templates/' . $plugin_template;
	}

	public function portfolio_shortcode( $atts ) {
		$this->enqueue_styles();
		$settings = self::settings();
		$atts     = is_array( $atts ) ? $atts : array();
		$preset   = isset( $atts['preset'] ) ? sanitize_key( $atts['preset'] ) : 'classic';
		$defaults = 'settings' === $preset
			? array(
				'limit'        => $settings['archive_posts_per_page'],
				'columns'      => $settings['archive_columns'],
				'layout'       => $settings['archive_layout'],
				'image_ratio'  => $settings['archive_image_ratio'],
				'card_style'   => $settings['archive_card_style'],
				'load_mode'    => $settings['archive_load_mode'],
				'show_filters' => $settings['archive_show_filters'],
				'show_search'  => $settings['archive_show_search'],
				'show_cta'     => $settings['archive_show_cta'],
			)
			: array(
				'limit'        => '12',
				'columns'      => '3',
				'layout'       => 'masonry',
				'image_ratio'  => 'auto',
				'card_style'   => 'classic',
				'load_mode'    => 'button',
				'show_filters' => '1',
				'show_search'  => '1',
				'show_cta'     => '1',
			);
		$atts = shortcode_atts(
			array_merge(
				$defaults,
				array(
				'preset'       => $preset,
				'service'    => '',
				'ville'      => '',
				)
			),
			$atts,
			'kv2_portfolio'
		);

		$paged          = isset( $_GET['kv2ps_page'] ) ? max( 1, absint( $_GET['kv2ps_page'] ) ) : 1;
		$active_service = isset( $_GET['kv2ps_service'] ) && is_string( $_GET['kv2ps_service'] ) ? sanitize_title( wp_unslash( $_GET['kv2ps_service'] ) ) : sanitize_title( $atts['service'] );
		$search         = isset( $_GET['kv2ps_search'] ) && is_string( $_GET['kv2ps_search'] ) ? sanitize_text_field( wp_unslash( $_GET['kv2ps_search'] ) ) : '';
		$args  = array(
			'post_type'           => KV2PS_Post_Types::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, min( 48, absint( $atts['limit'] ) ) ),
			'paged'               => $paged,
			'ignore_sticky_posts' => true,
		);

		$tax_query = array();
		if ( $active_service ) {
			$tax_query[] = array(
				'taxonomy' => 'kv2_service',
				'field'    => 'slug',
				'terms'    => $active_service,
			);
		}
		if ( $atts['ville'] ) {
			$tax_query[] = array(
				'taxonomy' => 'kv2_ville',
				'field'    => 'slug',
				'terms'    => sanitize_title( $atts['ville'] ),
			);
		}
		if ( $tax_query ) {
			$args['tax_query'] = $tax_query;
		}
		if ( $search ) {
			$args['s'] = $search;
		}

		$query   = new WP_Query( $args );
		$display = self::sanitize_display_settings( $atts );
		$key     = 'shortcode-' . substr( md5( wp_json_encode( array_diff_key( $atts, array( 'kv2ps_page' => '' ) ) ) ), 0, 10 );

		ob_start();
		if ( $query->have_posts() ) {
			$next_url = $paged < (int) $query->max_num_pages ? add_query_arg( 'kv2ps_page', $paged + 1 ) : '';
			echo '<section class="kv2ps-collection" data-collection-key="' . esc_attr( $key ) . '" data-load-mode="' . esc_attr( $display['load_mode'] ) . '">';
			self::render_collection_controls(
				$key,
				$active_service,
				$search,
				get_permalink(),
				self::enabled( $atts['show_filters'] ),
				self::enabled( $atts['show_search'] )
			);
			echo '<div class="' . esc_attr( self::collection_classes( $display ) ) . '">';
			while ( $query->have_posts() ) {
				$query->the_post();
				$this->render_card();
			}
			echo '</div>';
			self::render_collection_navigation(
				$query->max_num_pages,
				$paged,
				$next_url,
				array(
					'base'   => str_replace( '999999999', '%#%', esc_url_raw( add_query_arg( 'kv2ps_page', '999999999' ) ) ),
					'format' => '',
				)
			);
			echo '</section>';
			if ( self::enabled( $atts['show_cta'] ) ) {
				self::render_cta( 0 );
			}
		} else {
			echo '<p>' . esc_html__( 'Aucune réalisation disponible pour le moment.', 'kv2-portfolio-studio' ) . '</p>';
		}
		wp_reset_postdata();

		return ob_get_clean();
	}

	public static function sanitize_display_settings( $values ) {
		$settings = self::settings();
		$layout   = isset( $values['layout'] ) && in_array( $values['layout'], array( 'grid', 'tiles', 'masonry' ), true ) ? $values['layout'] : $settings['archive_layout'];
		$ratio    = isset( $values['image_ratio'] ) && in_array( $values['image_ratio'], array( 'auto', '1-1', '4-3', '3-2', '16-9' ), true ) ? $values['image_ratio'] : $settings['archive_image_ratio'];
		$card     = isset( $values['card_style'] ) && in_array( $values['card_style'], array( 'classic', 'minimal', 'elevated', 'overlay' ), true ) ? $values['card_style'] : $settings['archive_card_style'];
		$load     = isset( $values['load_mode'] ) && in_array( $values['load_mode'], array( 'paged', 'button', 'infinite' ), true ) ? $values['load_mode'] : $settings['archive_load_mode'];
		return array(
			'layout'      => $layout,
			'columns'     => max( 1, min( 4, isset( $values['columns'] ) ? absint( $values['columns'] ) : absint( $settings['archive_columns'] ) ) ),
			'image_ratio' => $ratio,
			'card_style'  => $card,
			'load_mode'   => $load,
		);
	}

	public static function collection_classes( $display ) {
		return implode(
			' ',
			array(
				'kv2ps-grid',
				'kv2ps-layout-' . sanitize_html_class( $display['layout'] ),
				'kv2ps-cols-' . absint( $display['columns'] ),
				'kv2ps-ratio-' . sanitize_html_class( $display['image_ratio'] ),
				'kv2ps-card-style-' . sanitize_html_class( $display['card_style'] ),
			)
		);
	}

	public static function render_collection_navigation( $total_pages, $current_page, $next_url = '', $pagination_args = array() ) {
		if ( $total_pages <= 1 ) {
			return;
		}
		?>
		<nav class="kv2ps-pagination" aria-label="<?php esc_attr_e( 'Pagination des réalisations', 'kv2-portfolio-studio' ); ?>">
			<?php
			echo wp_kses_post(
				paginate_links(
					wp_parse_args(
						$pagination_args,
						array(
						'total'     => (int) $total_pages,
						'current'   => (int) $current_page,
						'mid_size'  => 2,
						'prev_text' => __( 'Précédent', 'kv2-portfolio-studio' ),
						'next_text' => __( 'Suivant', 'kv2-portfolio-studio' ),
						)
					)
				)
			);
			?>
		</nav>
		<?php if ( $next_url ) : ?>
			<div class="kv2ps-progressive"><button class="kv2ps-load-more" data-next-url="<?php echo esc_url( $next_url ); ?>" type="button"><span><?php esc_html_e( 'Voir plus de réalisations', 'kv2-portfolio-studio' ); ?></span></button><p class="kv2ps-load-status" aria-live="polite"></p></div>
		<?php endif; ?>
		<?php
	}

	public static function enabled( $value ) {
		return ! in_array( strtolower( trim( (string) $value ) ), array( '', '0', 'false', 'no', 'off' ), true );
	}

	public static function render_collection_controls( $key, $active_service = '', $search = '', $base_url = '', $show_filters = true, $show_search = true ) {
		if ( ! $show_filters && ! $show_search ) {
			return;
		}

		$terms = $show_filters ? get_terms( array( 'taxonomy' => 'kv2_service', 'hide_empty' => true ) ) : array();
		$terms = is_wp_error( $terms ) ? array() : $terms;
		$base_url = $base_url ? remove_query_arg( array( 'kv2ps_page', 'kv2ps_service', 'kv2ps_search' ), $base_url ) : '';
		?>
		<div class="kv2ps-toolbar" data-kv2ps-toolbar="<?php echo esc_attr( $key ); ?>" data-active-filter="<?php echo esc_attr( $active_service ); ?>">
			<?php if ( $show_filters ) : ?>
				<nav class="kv2ps-filters" aria-label="<?php esc_attr_e( 'Filtrer les réalisations', 'kv2-portfolio-studio' ); ?>">
					<a class="kv2ps-filter<?php echo $active_service ? '' : ' is-active'; ?>" data-kv2ps-filter="" href="<?php echo esc_url( $base_url ); ?>"<?php echo $active_service ? '' : ' aria-current="page"'; ?>><?php esc_html_e( 'Tous', 'kv2-portfolio-studio' ); ?></a>
					<?php foreach ( $terms as $term ) :
						$url = add_query_arg( 'kv2ps_service', $term->slug, $base_url );
						if ( $search ) {
							$url = add_query_arg( 'kv2ps_search', $search, $url );
						}
						$is_active = $active_service === $term->slug;
						?>
						<a class="kv2ps-filter<?php echo $is_active ? ' is-active' : ''; ?>" data-kv2ps-filter="<?php echo esc_attr( $term->slug ); ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $term->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
			<?php if ( $show_search ) : ?>
				<form class="kv2ps-search" action="<?php echo esc_url( $base_url ); ?>" method="get" role="search">
					<label class="kv2ps-sr-only" for="kv2ps-search-<?php echo esc_attr( $key ); ?>"><?php esc_html_e( 'Rechercher dans les réalisations', 'kv2-portfolio-studio' ); ?></label>
					<?php if ( $active_service ) : ?><input name="kv2ps_service" type="hidden" value="<?php echo esc_attr( $active_service ); ?>"><?php endif; ?>
					<input id="kv2ps-search-<?php echo esc_attr( $key ); ?>" name="kv2ps_search" placeholder="<?php esc_attr_e( 'Recherche…', 'kv2-portfolio-studio' ); ?>" type="search" value="<?php echo esc_attr( $search ); ?>">
					<button class="kv2ps-sr-only" type="submit"><?php esc_html_e( 'Rechercher', 'kv2-portfolio-studio' ); ?></button>
				</form>
			<?php endif; ?>
			<p class="kv2ps-filter-status kv2ps-sr-only" aria-live="polite"></p>
		</div>
		<?php
	}

	public static function get_cta_config( $post_id ) {
		$settings = self::settings();
		$config   = array(
			'title'             => $settings['cta_title'],
			'text'              => $settings['cta_text'],
			'primary_action'    => $settings['cta_primary_action'],
			'primary_label'     => $settings['cta_primary_label'],
			'secondary_enabled' => $settings['cta_secondary_enabled'],
			'secondary_label'   => $settings['cta_secondary_label'],
			'form_url'          => $settings['form_url'] ?: $settings['contact_url'],
			'ctc_trigger'       => $settings['ctc_trigger'],
		);

		if ( get_post_meta( $post_id, '_kv2ps_cta_override', true ) ) {
			$map = array(
				'title'             => '_kv2ps_cta_title',
				'text'              => '_kv2ps_cta_text',
				'primary_action'    => '_kv2ps_cta_primary_action',
				'primary_label'     => '_kv2ps_cta_primary_label',
				'secondary_label'   => '_kv2ps_cta_secondary_label',
				'form_url'          => '_kv2ps_cta_form_url',
			);
			foreach ( $map as $key => $meta_key ) {
				$value = get_post_meta( $post_id, $meta_key, true );
				if ( '' !== $value ) {
					$config[ $key ] = $value;
				}
			}
			$config['secondary_enabled'] = get_post_meta( $post_id, '_kv2ps_cta_secondary_enabled', true ) ? '1' : '0';
		}

		return $config;
	}

	public static function render_cta( $post_id ) {
		$config = self::get_cta_config( $post_id );
		if ( ! $config['title'] && ! $config['text'] ) {
			return;
		}
		$primary_is_chat = 'click_to_chat' === $config['primary_action'];
		$chat_class      = 'ctc_greetings' === $config['ctc_trigger'] ? 'ctc_greetings' : 'ctc_chat';
		$chat_href       = 'ctc_greetings' === $chat_class ? '#' : '#ctc_chat';
		$primary_href    = $primary_is_chat ? $chat_href : $config['form_url'];
		$primary_class   = $primary_is_chat ? $chat_class : '';
		$secondary_href  = $primary_is_chat ? $config['form_url'] : $chat_href;
		$secondary_class = $primary_is_chat ? '' : $chat_class;
		?>
		<section class="kv2ps-cta" aria-labelledby="kv2ps-cta-title"><div class="kv2ps-container"><div class="kv2ps-cta__copy"><h2 id="kv2ps-cta-title"><?php echo esc_html( $config['title'] ); ?></h2><?php if ( $config['text'] ) : ?><p><?php echo esc_html( $config['text'] ); ?></p><?php endif; ?></div><div class="kv2ps-cta__actions">
			<a class="kv2ps-button kv2ps-cta-link <?php echo esc_attr( $primary_class ); ?>" data-kv2ps-action="<?php echo esc_attr( $config['primary_action'] ); ?>" href="<?php echo esc_url( $primary_href ); ?>"><?php echo esc_html( $config['primary_label'] ); ?></a>
			<?php if ( '1' === $config['secondary_enabled'] && $secondary_href ) : ?><a class="kv2ps-button kv2ps-button--secondary kv2ps-cta-link <?php echo esc_attr( $secondary_class ); ?>" data-kv2ps-action="<?php echo esc_attr( $primary_is_chat ? 'form' : 'click_to_chat' ); ?>" href="<?php echo esc_url( $secondary_href ); ?>"><?php echo esc_html( $config['secondary_label'] ); ?></a><?php endif; ?>
			<?php if ( $config['form_url'] ) : ?><small class="kv2ps-cta__form-note"><?php esc_html_e( 'Vous pouvez aussi transmettre les détails et les photos de votre projet via le formulaire.', 'kv2-portfolio-studio' ); ?></small><?php endif; ?>
		</div></div></section>
		<?php
	}

	public function render_card() {
		$post_id       = get_the_ID();
		$service_terms = get_the_terms( get_the_ID(), 'kv2_service' );
		$service_terms = is_wp_error( $service_terms ) ? array() : (array) $service_terms;
		$terms         = get_the_term_list( get_the_ID(), 'kv2_service', '', ', ' );
		$service_slugs = implode( ' ', wp_list_pluck( $service_terms, 'slug' ) );
		$search_text   = implode( ' ', array( get_the_title(), get_the_excerpt(), wp_strip_all_tags( $terms ) ) );
		$image_id      = get_post_thumbnail_id( $post_id );
		if ( ! $image_id ) {
			$after_ids  = KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_after_images', true ) );
			$before_ids = KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_before_images', true ) );
			$image_id   = $after_ids ? reset( $after_ids ) : ( $before_ids ? reset( $before_ids ) : 0 );
		}
		if ( ! $image_id ) {
			$source_id = absint( get_post_meta( $post_id, '_kv2ps_source_wp_portfolio_id', true ) );
			$image_id  = $source_id ? KV2PS_Importer::find_source_image_id( $source_id ) : 0;
		}
		?>
		<article <?php post_class( 'kv2ps-card' ); ?> data-kv2ps-services="<?php echo esc_attr( $service_slugs ); ?>" data-kv2ps-search="<?php echo esc_attr( remove_accents( strtolower( $search_text ) ) ); ?>">
			<a class="kv2ps-card__image" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Voir la réalisation : %s', 'kv2-portfolio-studio' ), get_the_title() ) ); ?>">
				<?php
				if ( $image_id ) {
					$image_html = wp_get_attachment_image(
						$image_id,
						'large',
						false,
						array(
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(max-width: 620px) calc(100vw - 36px), (max-width: 900px) calc(50vw - 34px), 372px',
						)
					);
					/* WordPress 6.7+ prefixes lazy images with `auto`, which can
					 * reserve a 3000 x 1500 placeholder and break masonry measures. */
					$image_html = str_replace( 'sizes="auto, ', 'sizes="', $image_html );
					echo wp_kses_post( $image_html );
				} else {
					echo '<span class="kv2ps-card__placeholder" aria-hidden="true"></span>';
				}
				?>
			</a>
			<div class="kv2ps-card__body">
				<?php if ( $terms ) : ?>
					<div class="kv2ps-eyebrow"><?php echo wp_kses_post( $terms ); ?></div>
				<?php endif; ?>
				<h2 class="kv2ps-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<?php if ( has_excerpt() ) : ?>
					<p><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}
}
