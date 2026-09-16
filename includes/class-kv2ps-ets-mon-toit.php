<?php

defined( 'ABSPATH' ) || exit;

/**
 * Site-specific extensions for ETS Mon Toit.
 *
 * This class intentionally lives only on the site/ets-mon-toit branch.
 * Generic fixes must continue to be promoted to main independently.
 */
final class KV2PS_ETS_Mon_Toit {
	const META_VIDEOS = '_kv2ps_video_ids';
	const NONCE_ACTION = 'kv2ps_ets_mon_toit_save_videos';
	const NONCE_NAME = 'kv2ps_ets_mon_toit_videos_nonce';

	private static $wrapped_template = '';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_video_meta' ), 30 );
		add_action( 'add_meta_boxes_' . KV2PS_Post_Types::POST_TYPE, array( __CLASS__, 'add_video_meta_box' ), 30 );
		add_action( 'save_post_' . KV2PS_Post_Types::POST_TYPE, array( __CLASS__, 'save_videos' ), 30 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ), 30 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ), 30 );
		add_filter( 'template_include', array( __CLASS__, 'wrap_single_template' ), 999 );
	}

	public static function register_video_meta() {
		register_post_meta(
			KV2PS_Post_Types::POST_TYPE,
			self::META_VIDEOS,
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'show_in_rest'      => array(
					'schema' => array(
						'type'    => 'array',
						'context' => array( 'edit' ),
						'items'   => array( 'type' => 'integer' ),
					),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_video_ids' ),
				'auth_callback'     => function( $allowed, $meta_key, $object_id ) {
					unset( $allowed, $meta_key );
					return $object_id && current_user_can( 'edit_post', (int) $object_id );
				},
			)
		);
	}

	public static function sanitize_video_ids( $value ) {
		$ids = KV2PS_Post_Types::sanitize_ids( $value );
		if ( ! $ids ) {
			return array();
		}

		$allowed_mimes = array(
			'video/mp4',
			'video/webm',
			'video/ogg',
			'video/quicktime',
		);
		$valid = array();
		foreach ( $ids as $attachment_id ) {
			if ( 'attachment' !== get_post_type( $attachment_id ) ) {
				continue;
			}
			$mime = (string) get_post_mime_type( $attachment_id );
			if ( in_array( $mime, $allowed_mimes, true ) ) {
				$valid[] = (int) $attachment_id;
			}
		}
		return array_values( array_unique( $valid ) );
	}

	public static function get_video_ids( $post_id ) {
		return self::sanitize_video_ids( get_post_meta( $post_id, self::META_VIDEOS, true ) );
	}

	public static function add_video_meta_box( $post ) {
		if ( KV2PS_Plugin::PROFILE_ROOFING !== KV2PS_Plugin::business_profile() ) {
			return;
		}

		add_meta_box(
			'kv2ps-ets-mon-toit-videos',
			__( 'Vidéos du chantier', 'kv2-portfolio-studio' ),
			array( __CLASS__, 'render_video_meta_box' ),
			KV2PS_Post_Types::POST_TYPE,
			'normal',
			'default'
		);
	}

	public static function render_video_meta_box( $post ) {
		$ids = self::get_video_ids( $post->ID );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<div class="kv2ps-emt-video-field" data-video-field>
			<p class="description"><?php esc_html_e( 'Ajoutez de courtes vidéos réelles du chantier. MP4 et WebM sont recommandés ; les vidéos restent séparées des photos avant/après et ne remplacent pas l’image principale.', 'kv2-portfolio-studio' ); ?></p>
			<input class="kv2ps-emt-video-ids" name="kv2ps_emt_video_ids" type="hidden" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
			<div class="kv2ps-emt-video-preview" aria-live="polite">
				<?php foreach ( $ids as $video_id ) : ?>
					<?php echo self::admin_video_card( $video_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>
			<p><button class="button button-secondary kv2ps-emt-select-videos" type="button"><?php esc_html_e( 'Choisir des vidéos', 'kv2-portfolio-studio' ); ?></button></p>
			<p class="description"><?php esc_html_e( 'Vous pouvez réordonner les vidéos par glisser-déposer.', 'kv2-portfolio-studio' ); ?></p>
		</div>
		<?php
	}

	private static function admin_video_card( $video_id ) {
		$url = wp_get_attachment_url( $video_id );
		if ( ! $url ) {
			return '';
		}
		$title = get_the_title( $video_id );
		$mime  = get_post_mime_type( $video_id );
		ob_start();
		?>
		<div class="kv2ps-emt-video-item" data-id="<?php echo esc_attr( $video_id ); ?>">
			<video controls playsinline preload="metadata" src="<?php echo esc_url( $url ); ?>"></video>
			<div class="kv2ps-emt-video-meta">
				<strong><?php echo esc_html( $title ?: basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ); ?></strong>
				<span><?php echo esc_html( $mime ); ?></span>
			</div>
			<button class="button-link-delete kv2ps-emt-remove-video" type="button" aria-label="<?php esc_attr_e( 'Retirer la vidéo', 'kv2-portfolio-studio' ); ?>">×</button>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function save_videos( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = isset( $_POST['kv2ps_emt_video_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['kv2ps_emt_video_ids'] ) ) : '';
		$ids = $raw ? explode( ',', $raw ) : array();
		update_post_meta( $post_id, self::META_VIDEOS, self::sanitize_video_ids( $ids ) );
	}

	public static function admin_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || KV2PS_Post_Types::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style( 'kv2ps-ets-mon-toit-admin', KV2PS_URL . 'assets/ets-mon-toit-admin.css', array(), KV2PS_VERSION );
		wp_enqueue_script(
			'kv2ps-ets-mon-toit-admin',
			KV2PS_URL . 'assets/ets-mon-toit-admin.js',
			array( 'jquery', 'media-editor', 'jquery-ui-sortable' ),
			KV2PS_VERSION,
			true
		);
		wp_localize_script(
			'kv2ps-ets-mon-toit-admin',
			'kv2psEtsMonToit',
			array(
				'mediaTitle'   => __( 'Choisir les vidéos du chantier', 'kv2-portfolio-studio' ),
				'mediaButton'  => __( 'Ajouter ces vidéos', 'kv2-portfolio-studio' ),
				'removeLabel'  => __( 'Retirer la vidéo', 'kv2-portfolio-studio' ),
				'unknownTitle' => __( 'Vidéo du chantier', 'kv2-portfolio-studio' ),
			)
		);
	}

	public static function frontend_assets() {
		if ( ! is_singular( KV2PS_Post_Types::POST_TYPE ) ) {
			return;
		}
		$post_id = get_queried_object_id();
		if ( ! $post_id || ! self::get_video_ids( $post_id ) ) {
			return;
		}
		wp_enqueue_style( 'kv2ps-ets-mon-toit-frontend', KV2PS_URL . 'assets/ets-mon-toit-frontend.css', array(), KV2PS_VERSION );
	}

	public static function wrap_single_template( $template ) {
		if ( ! is_singular( KV2PS_Post_Types::POST_TYPE ) ) {
			return $template;
		}
		$post_id = get_queried_object_id();
		if ( ! $post_id || ! self::get_video_ids( $post_id ) ) {
			return $template;
		}

		$wrapper = KV2PS_DIR . 'templates/single-kv2_realisation-ets-mon-toit.php';
		if ( $template === $wrapper || ! is_file( $wrapper ) ) {
			return $template;
		}
		self::$wrapped_template = $template;
		return $wrapper;
	}

	public static function render_wrapped_single_template() {
		$template = self::$wrapped_template;
		if ( ! $template || ! is_file( $template ) ) {
			$template = KV2PS_DIR . 'templates/single-kv2_realisation.php';
		}

		ob_start();
		include $template;
		$html = ob_get_clean();

		$post_id = get_queried_object_id();
		$section = self::render_videos_section( $post_id );
		if ( ! $section ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		$project_start = strpos( $html, 'kv2ps-project' );
		$insert_at     = false !== $project_start ? strpos( $html, '</article>', $project_start ) : false;
		if ( false === $insert_at ) {
			$insert_at = strrpos( $html, '</main>' );
		}
		if ( false === $insert_at ) {
			echo $html . $section; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		echo substr( $html, 0, $insert_at ) . $section . substr( $html, $insert_at ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function render_videos_section( $post_id ) {
		$ids = self::get_video_ids( $post_id );
		if ( ! $ids ) {
			return '';
		}

		ob_start();
		?>
		<section class="kv2ps-site-videos" aria-labelledby="kv2ps-site-videos-title">
			<div class="kv2ps-container">
				<div class="kv2ps-site-videos__heading">
					<p class="kv2ps-site-videos__eyebrow"><?php esc_html_e( 'Sur le chantier', 'kv2-portfolio-studio' ); ?></p>
					<h2 id="kv2ps-site-videos-title"><?php esc_html_e( 'Vidéos du chantier', 'kv2-portfolio-studio' ); ?></h2>
				</div>
				<div class="kv2ps-site-videos__grid">
					<?php foreach ( $ids as $video_id ) : ?>
						<?php
						$url = wp_get_attachment_url( $video_id );
						if ( ! $url ) {
							continue;
						}
						$mime       = (string) get_post_mime_type( $video_id );
						$caption    = wp_get_attachment_caption( $video_id );
						$poster_id  = get_post_thumbnail_id( $video_id );
						$poster_url = $poster_id ? wp_get_attachment_image_url( $poster_id, 'large' ) : '';
						?>
						<figure class="kv2ps-site-video">
							<video controls playsinline preload="metadata"<?php echo $poster_url ? ' poster="' . esc_url( $poster_url ) . '"' : ''; ?>>
								<source src="<?php echo esc_url( $url ); ?>" type="<?php echo esc_attr( $mime ); ?>">
								<?php esc_html_e( 'Votre navigateur ne peut pas lire cette vidéo.', 'kv2-portfolio-studio' ); ?>
								<a href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Ouvrir la vidéo', 'kv2-portfolio-studio' ); ?></a>
							</video>
							<?php if ( $caption ) : ?><figcaption><?php echo esc_html( $caption ); ?></figcaption><?php endif; ?>
						</figure>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}
