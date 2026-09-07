<?php

defined( 'ABSPATH' ) || exit;

final class KV2PS_Image_Metadata {
	const META_PAYLOAD = '_kv2ps_metadata_payload';

	public static function init() {
		add_filter( 'attachment_fields_to_edit', array( __CLASS__, 'attachment_fields_to_edit' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( __CLASS__, 'attachment_fields_to_save' ), 10, 2 );
		add_filter( 'manage_media_columns', array( __CLASS__, 'media_columns' ) );
		add_action( 'manage_media_custom_column', array( __CLASS__, 'media_custom_column' ), 10, 2 );
		add_filter( 'media_row_actions', array( __CLASS__, 'media_row_actions' ), 10, 2 );
	}

	public static function media_columns( $columns ) {
		$columns['kv2ps_image_seo'] = __( 'KV2 Image SEO', 'kv2-portfolio-studio' );
		return $columns;
	}

	public static function media_custom_column( $column, $attachment_id ) {
		if ( 'kv2ps_image_seo' !== $column || 0 !== strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
			return;
		}
		$has_alt    = (bool) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$has_rights = (bool) ( get_post_meta( $attachment_id, '_kv2ps_credit', true ) || get_post_meta( $attachment_id, '_kv2ps_copyright', true ) );
		echo '<strong>#' . esc_html( $attachment_id ) . '</strong><br>';
		echo esc_html( $has_alt ? __( 'ALT : oui', 'kv2-portfolio-studio' ) : __( 'ALT : manquant', 'kv2-portfolio-studio' ) );
		echo '<br>' . esc_html( $has_rights ? __( 'Droits : renseignés', 'kv2-portfolio-studio' ) : __( 'Droits : manquants', 'kv2-portfolio-studio' ) );
	}

	public static function media_row_actions( $actions, $post ) {
		if ( 0 === strpos( (string) get_post_mime_type( $post ), 'image/' ) && current_user_can( 'upload_files' ) ) {
			$url = add_query_arg(
				array(
					'post_type'     => KV2PS_Post_Types::POST_TYPE,
					'page'          => 'kv2ps-image-seo',
					'attachment_id' => $post->ID,
				),
				admin_url( 'edit.php' )
			);
			$actions['kv2ps_image_seo'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Contrôler avec KV2', 'kv2-portfolio-studio' ) . '</a>';
		}
		return $actions;
	}

	public static function attachment_fields_to_edit( $fields, $post ) {
		if ( 0 !== strpos( (string) get_post_mime_type( $post ), 'image/' ) ) {
			return $fields;
		}

		$definitions = array(
			'credit'              => array( 'Crédit photo', 'Nom du photographe, de l’agence ou du client.' ),
			'copyright'           => array( 'Copyright', 'Titulaire des droits, sans symbole ajouté automatiquement.' ),
			'keywords'            => array( 'Mots-clés', 'Séparés par des virgules. Usage éditorial interne.' ),
			'location'            => array( 'Lieu', 'Ville, région, pays. Les coordonnées GPS ne sont jamais rendues publiques automatiquement.' ),
			'date_created'        => array( 'Date de création', 'Format AAAA-MM-JJ.' ),
			'license_url'         => array( 'URL de licence', 'Adresse décrivant la licence de l’image.' ),
			'acquire_license_url' => array( 'URL d’acquisition', 'Adresse permettant d’acquérir les droits.' ),
		);

		foreach ( $definitions as $key => $definition ) {
			$value = get_post_meta( $post->ID, '_kv2ps_' . $key, true );
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_filter( $value ) );
			}
			$fields[ 'kv2ps_' . $key ] = array(
				'label' => __( $definition[0], 'kv2-portfolio-studio' ),
				'input' => 'text',
				'value' => $value,
				'helps' => __( $definition[1], 'kv2-portfolio-studio' ),
			);
		}

		return $fields;
	}

	public static function attachment_fields_to_save( $post, $attachment ) {
		if ( ! current_user_can( 'edit_post', $post['ID'] ) ) {
			return $post;
		}

		foreach ( array( 'credit', 'copyright', 'keywords', 'location', 'date_created', 'license_url', 'acquire_license_url' ) as $key ) {
			if ( ! isset( $attachment[ 'kv2ps_' . $key ] ) ) {
				continue;
			}
			$value = $attachment[ 'kv2ps_' . $key ];
			if ( in_array( $key, array( 'license_url', 'acquire_license_url' ), true ) ) {
				$value = esc_url_raw( $value );
			} elseif ( 'keywords' === $key ) {
				$value = self::sanitize_keywords( $value );
			} else {
				$value = sanitize_text_field( $value );
			}

			if ( '' === $value || array() === $value ) {
				delete_post_meta( $post['ID'], '_kv2ps_' . $key );
			} else {
				update_post_meta( $post['ID'], '_kv2ps_' . $key, $value );
			}
		}

		return $post;
	}

	public static function render_tools_page() {
		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}

		$report = null;
		$export = '';
		$inspect = null;

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) && isset( $_POST['kv2ps_image_action'] ) ) {
			check_admin_referer( 'kv2ps_image_tools', 'kv2ps_image_nonce' );
			$action = sanitize_key( wp_unslash( $_POST['kv2ps_image_action'] ) );

			if ( in_array( $action, array( 'preview', 'apply' ), true ) ) {
				$payload = self::read_submitted_json();
				$report  = is_wp_error( $payload ) ? $payload : self::process_payload( $payload, 'preview' === $action );
			} elseif ( 'inspect' === $action ) {
				$attachment_id = isset( $_POST['kv2ps_attachment_id'] ) ? absint( $_POST['kv2ps_attachment_id'] ) : 0;
				$inspect       = self::inspect_attachment( $attachment_id );
			} elseif ( 'export' === $action ) {
				$ids    = isset( $_POST['kv2ps_attachment_ids'] ) ? self::sanitize_ids( wp_unslash( $_POST['kv2ps_attachment_ids'] ) ) : array();
				$export = wp_json_encode( self::export_payload( $ids ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			}
		}
		$prefill_attachment_id = isset( $_GET['attachment_id'] ) ? absint( $_GET['attachment_id'] ) : 0;
		?>
		<div class="wrap kv2ps-admin">
			<h1><?php esc_html_e( 'Image SEO & ChatGPT', 'kv2-portfolio-studio' ); ?></h1>
			<div class="notice notice-info inline"><p><strong><?php esc_html_e( 'Principe fiable :', 'kv2-portfolio-studio' ); ?></strong> <?php esc_html_e( 'le plugin conserve les textes dans WordPress et le paquet JSON complet. Il lit les EXIF du fichier original, mais ne modifie pas le binaire de l’image dans cette version.', 'kv2-portfolio-studio' ); ?></p></div>

			<div class="kv2ps-tools-grid">
				<section class="kv2ps-panel">
					<h2><?php esc_html_e( '1. Importer le paquet fourni par ChatGPT', 'kv2-portfolio-studio' ); ?></h2>
					<p><?php esc_html_e( 'Faites d’abord une simulation. L’application remplit le titre, l’ALT, la légende, la description, les droits, les mots-clés et le lieu des images reconnues.', 'kv2-portfolio-studio' ); ?></p>
					<form method="post" enctype="multipart/form-data">
						<?php wp_nonce_field( 'kv2ps_image_tools', 'kv2ps_image_nonce' ); ?>
						<p><label for="kv2ps-json-file"><strong><?php esc_html_e( 'Fichier JSON', 'kv2-portfolio-studio' ); ?></strong></label><br><input id="kv2ps-json-file" name="kv2ps_json_file" type="file" accept="application/json,.json"></p>
						<p><label for="kv2ps-json-payload"><strong><?php esc_html_e( 'ou coller le JSON', 'kv2-portfolio-studio' ); ?></strong></label><br><textarea class="large-text code" id="kv2ps-json-payload" name="kv2ps_json_payload" rows="12" spellcheck="false"></textarea></p>
						<p>
							<button class="button button-secondary" name="kv2ps_image_action" type="submit" value="preview"><?php esc_html_e( 'Simuler l’import', 'kv2-portfolio-studio' ); ?></button>
							<button class="button button-primary" name="kv2ps_image_action" type="submit" value="apply" onclick="return window.confirm('<?php echo esc_js( __( 'Appliquer ces métadonnées aux images reconnues ?', 'kv2-portfolio-studio' ) ); ?>');"><?php esc_html_e( 'Appliquer', 'kv2-portfolio-studio' ); ?></button>
						</p>
					</form>
				</section>

				<section class="kv2ps-panel">
					<h2><?php esc_html_e( '2. Contrôler les EXIF d’une image', 'kv2-portfolio-studio' ); ?></h2>
					<p><?php esc_html_e( 'Indiquez l’identifiant visible dans la médiathèque. Le rapport ne publie aucune coordonnée GPS.', 'kv2-portfolio-studio' ); ?></p>
					<form method="post">
						<?php wp_nonce_field( 'kv2ps_image_tools', 'kv2ps_image_nonce' ); ?>
						<p><label for="kv2ps-attachment-id"><strong><?php esc_html_e( 'ID de l’image', 'kv2-portfolio-studio' ); ?></strong></label><br><input id="kv2ps-attachment-id" min="1" name="kv2ps_attachment_id" type="number" value="<?php echo esc_attr( $prefill_attachment_id ); ?>" required></p>
						<button class="button" name="kv2ps_image_action" type="submit" value="inspect"><?php esc_html_e( 'Analyser', 'kv2-portfolio-studio' ); ?></button>
					</form>
				</section>

				<section class="kv2ps-panel">
					<h2><?php esc_html_e( '3. Exporter un brief pour ChatGPT', 'kv2-portfolio-studio' ); ?></h2>
					<p><?php esc_html_e( 'Saisissez un ou plusieurs ID séparés par des virgules. ChatGPT pourra compléter ce JSON puis vous le rendre prêt à réimporter.', 'kv2-portfolio-studio' ); ?></p>
					<form method="post">
						<?php wp_nonce_field( 'kv2ps_image_tools', 'kv2ps_image_nonce' ); ?>
						<p><label for="kv2ps-attachment-ids"><strong><?php esc_html_e( 'ID des images', 'kv2-portfolio-studio' ); ?></strong></label><br><input class="regular-text" id="kv2ps-attachment-ids" name="kv2ps_attachment_ids" type="text" placeholder="123, 124, 125" required></p>
						<button class="button" name="kv2ps_image_action" type="submit" value="export"><?php esc_html_e( 'Générer le JSON', 'kv2-portfolio-studio' ); ?></button>
					</form>
				</section>
			</div>

			<?php self::render_import_report( $report ); ?>
			<?php self::render_inspection( $inspect ); ?>
			<?php if ( $export ) : ?>
				<section class="kv2ps-panel kv2ps-output">
					<h2><?php esc_html_e( 'JSON à transmettre à ChatGPT', 'kv2-portfolio-studio' ); ?></h2>
					<textarea class="large-text code kv2ps-download-source" rows="22" readonly><?php echo esc_textarea( $export ); ?></textarea>
					<p><button class="button kv2ps-download-json" data-filename="kv2-images-chatgpt.json" type="button"><?php esc_html_e( 'Télécharger le fichier JSON', 'kv2-portfolio-studio' ); ?></button></p>
				</section>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function read_submitted_json() {
		$json = isset( $_POST['kv2ps_json_payload'] ) ? trim( wp_unslash( $_POST['kv2ps_json_payload'] ) ) : '';
		$file = isset( $_FILES['kv2ps_json_file'] ) && is_array( $_FILES['kv2ps_json_file'] ) ? $_FILES['kv2ps_json_file'] : array();
		$data = KV2PS_JSON::read( $json, $file, 1048576 );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( empty( $data['schema_version'] ) || empty( $data['images'] ) || ! is_array( $data['images'] ) ) {
			return new WP_Error( 'bad_schema', __( 'Le fichier doit contenir schema_version et un tableau images.', 'kv2-portfolio-studio' ) );
		}

		if ( count( $data['images'] ) > 100 ) {
			return new WP_Error( 'too_many_images', __( 'Un import est limité à 100 images.', 'kv2-portfolio-studio' ) );
		}

		return $data;
	}

	public static function process_payload( $payload, $dry_run = true ) {
		$report = array(
			'dry_run' => $dry_run,
			'updated' => 0,
			'errors'  => array(),
			'items'   => array(),
		);

		foreach ( $payload['images'] as $index => $image ) {
			if ( ! is_array( $image ) || empty( $image['match'] ) || empty( $image['fields'] ) || ! is_array( $image['fields'] ) ) {
				$report['errors'][] = sprintf( __( 'Image %d : match ou fields manquant.', 'kv2-portfolio-studio' ), $index + 1 );
				continue;
			}

			$attachment_id = self::match_attachment( $image['match'] );
			if ( ! $attachment_id ) {
				$report['items'][] = array( 'status' => 'not_found', 'label' => self::match_label( $image['match'] ), 'fields' => array() );
				continue;
			}

			$fields = self::sanitize_fields( $image['fields'] );
			if ( ! $fields ) {
				$report['items'][] = array( 'status' => 'skipped', 'label' => get_the_title( $attachment_id ), 'fields' => array() );
				continue;
			}

			if ( ! $dry_run ) {
				if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
					$report['errors'][] = sprintf( __( 'Vous ne pouvez pas modifier l’image #%d.', 'kv2-portfolio-studio' ), $attachment_id );
					continue;
				}
				self::apply_fields( $attachment_id, $fields, $image );
				++$report['updated'];
			}

			$report['items'][] = array(
				'status' => $dry_run ? 'preview' : 'updated',
				'label'  => sprintf( '#%d — %s', $attachment_id, get_the_title( $attachment_id ) ),
				'fields' => array_keys( $fields ),
			);
		}

		return $report;
	}

	private static function sanitize_fields( $fields ) {
		$clean = array();
		foreach ( array( 'title', 'alt', 'caption', 'description', 'credit', 'copyright' ) as $key ) {
			if ( isset( $fields[ $key ] ) && '' !== trim( (string) $fields[ $key ] ) ) {
				$clean[ $key ] = in_array( $key, array( 'description', 'caption' ), true ) ? wp_kses_post( $fields[ $key ] ) : sanitize_text_field( $fields[ $key ] );
			}
		}
		if ( ! empty( $fields['date_created'] ) ) {
			$date_parts = array_map( 'absint', explode( '-', (string) $fields['date_created'] ) );
			if ( 3 === count( $date_parts ) && checkdate( $date_parts[1], $date_parts[2], $date_parts[0] ) ) {
				$clean['date_created'] = sprintf( '%04d-%02d-%02d', $date_parts[0], $date_parts[1], $date_parts[2] );
			}
		}

		foreach ( array( 'license_url', 'acquire_license_url' ) as $key ) {
			if ( ! empty( $fields[ $key ] ) ) {
				$clean[ $key ] = esc_url_raw( $fields[ $key ] );
			}
		}

		if ( ! empty( $fields['keywords'] ) ) {
			$clean['keywords'] = self::sanitize_keywords( $fields['keywords'] );
		}

		if ( ! empty( $fields['location'] ) && is_array( $fields['location'] ) ) {
			$location = array();
			foreach ( array( 'city', 'region', 'country' ) as $key ) {
				if ( isset( $fields['location'][ $key ] ) ) {
					$location[ $key ] = sanitize_text_field( $fields['location'][ $key ] );
				}
			}
			if ( isset( $fields['location']['latitude'] ) && is_numeric( $fields['location']['latitude'] ) && (float) $fields['location']['latitude'] >= -90 && (float) $fields['location']['latitude'] <= 90 ) {
				$location['latitude'] = (float) $fields['location']['latitude'];
			}
			if ( isset( $fields['location']['longitude'] ) && is_numeric( $fields['location']['longitude'] ) && (float) $fields['location']['longitude'] >= -180 && (float) $fields['location']['longitude'] <= 180 ) {
				$location['longitude'] = (float) $fields['location']['longitude'];
			}
			if ( $location ) {
				$clean['location'] = $location;
			}
		}

		return array_filter(
			$clean,
			function( $value ) {
				return '' !== $value && array() !== $value;
			}
		);
	}

	private static function apply_fields( $attachment_id, $fields, $original ) {
		$post_update = array( 'ID' => $attachment_id );
		if ( isset( $fields['title'] ) ) {
			$post_update['post_title'] = $fields['title'];
		}
		if ( isset( $fields['caption'] ) ) {
			$post_update['post_excerpt'] = $fields['caption'];
		}
		if ( isset( $fields['description'] ) ) {
			$post_update['post_content'] = $fields['description'];
		}
		if ( count( $post_update ) > 1 ) {
			wp_update_post( wp_slash( $post_update ) );
		}

		if ( isset( $fields['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $fields['alt'] );
		}

		foreach ( array( 'credit', 'copyright', 'keywords', 'location', 'date_created', 'license_url', 'acquire_license_url' ) as $key ) {
			if ( isset( $fields[ $key ] ) ) {
				update_post_meta( $attachment_id, '_kv2ps_' . $key, $fields[ $key ] );
			}
		}

		$canonical = array(
			'schema_version'   => '1.0',
			'imported_at'      => current_time( 'mysql', true ),
			'fields'           => $fields,
			'exif_suggestions' => isset( $original['exif_suggestions'] ) && is_array( $original['exif_suggestions'] ) ? self::sanitize_recursive( $original['exif_suggestions'] ) : array(),
		);
		update_post_meta( $attachment_id, self::META_PAYLOAD, $canonical );
	}

	public static function match_attachment( $match ) {
		if ( ! is_array( $match ) ) {
			return 0;
		}
		if ( ! empty( $match['attachment_id'] ) ) {
			$id = absint( $match['attachment_id'] );
			return 'attachment' === get_post_type( $id ) && 0 === strpos( (string) get_post_mime_type( $id ), 'image/' ) ? $id : 0;
		}
		if ( ! empty( $match['source_url'] ) ) {
			$id = attachment_url_to_postid( esc_url_raw( $match['source_url'] ) );
			if ( $id ) {
				return $id;
			}
		}
		if ( ! empty( $match['filename'] ) ) {
			$filename = sanitize_file_name( basename( $match['filename'] ) );
			$candidates = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'posts_per_page' => 20,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'     => '_wp_attached_file',
							'value'   => $filename,
							'compare' => 'LIKE',
						),
					),
				)
			);
			foreach ( $candidates as $candidate ) {
				if ( $filename === basename( (string) get_post_meta( $candidate, '_wp_attached_file', true ) ) ) {
					return (int) $candidate;
				}
			}
		}
		return 0;
	}

	private static function match_label( $match ) {
		foreach ( array( 'attachment_id', 'filename', 'source_url' ) as $key ) {
			if ( isset( $match[ $key ] ) ) {
				return sanitize_text_field( $key . ': ' . $match[ $key ] );
			}
		}
		return __( 'Correspondance inconnue', 'kv2-portfolio-studio' );
	}

	public static function inspect_attachment( $attachment_id ) {
		if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) || 0 !== strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
			return new WP_Error( 'bad_attachment', __( 'Cette image est introuvable dans la médiathèque.', 'kv2-portfolio-studio' ) );
		}

		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! is_readable( $file ) ) {
			return new WP_Error( 'missing_file', __( 'Le fichier original n’est pas lisible sur le serveur.', 'kv2-portfolio-studio' ) );
		}

		if ( ! function_exists( 'wp_read_image_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$wp_meta = wp_read_image_metadata( $file );
		$raw     = array();
		if ( function_exists( 'exif_read_data' ) && in_array( get_post_mime_type( $attachment_id ), array( 'image/jpeg', 'image/tiff' ), true ) ) {
			$raw = @exif_read_data( $file, 'ANY_TAG', true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$has_gps = false;
		if ( is_array( $raw ) ) {
			$has_gps = ! empty( $raw['GPS'] ) || isset( $raw['GPSLatitude'] ) || isset( $raw['GPSLongitude'] );
		}

		return array(
			'attachment_id' => $attachment_id,
			'filename'      => basename( $file ),
			'mime_type'     => get_post_mime_type( $attachment_id ),
			'file_size'     => size_format( (int) filesize( $file ) ),
			'wordpress'     => array(
				'alt'         => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'title'       => get_the_title( $attachment_id ),
				'caption'     => wp_get_attachment_caption( $attachment_id ),
				'description' => get_post_field( 'post_content', $attachment_id ),
				'credit'      => get_post_meta( $attachment_id, '_kv2ps_credit', true ),
				'copyright'   => get_post_meta( $attachment_id, '_kv2ps_copyright', true ),
			),
			'exif'          => array(
				'available'      => (bool) $raw,
				'has_gps'        => $has_gps,
				'camera'         => self::find_exif_value( $raw, 'Model' ),
				'artist'         => self::find_exif_value( $raw, 'Artist' ),
				'copyright'      => self::find_exif_value( $raw, 'Copyright' ),
				'description'    => self::find_exif_value( $raw, 'ImageDescription' ),
				'creation_date'  => ! empty( $wp_meta['created_timestamp'] ) ? gmdate( 'Y-m-d H:i:s', $wp_meta['created_timestamp'] ) : '',
			),
		);
	}

	private static function find_exif_value( $data, $key ) {
		if ( ! is_array( $data ) ) {
			return '';
		}
		if ( isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) ) {
			return sanitize_text_field( (string) $data[ $key ] );
		}
		foreach ( $data as $value ) {
			if ( is_array( $value ) ) {
				$found = self::find_exif_value( $value, $key );
				if ( '' !== $found ) {
					return $found;
				}
			}
		}
		return '';
	}

	public static function export_payload( $ids ) {
		$images = array();
		foreach ( $ids as $id ) {
			if ( 'attachment' !== get_post_type( $id ) || 0 !== strpos( (string) get_post_mime_type( $id ), 'image/' ) ) {
				continue;
			}
			$location = get_post_meta( $id, '_kv2ps_location', true );
			if ( is_string( $location ) && $location ) {
				$location = array( 'city' => $location );
			}
			$images[] = array(
				'match' => array(
					'attachment_id' => (int) $id,
					'filename'      => basename( (string) get_attached_file( $id ) ),
					'source_url'    => wp_get_attachment_url( $id ),
				),
				'fields' => array(
					'title'               => get_the_title( $id ),
					'alt'                 => get_post_meta( $id, '_wp_attachment_image_alt', true ),
					'caption'             => wp_get_attachment_caption( $id ),
					'description'         => get_post_field( 'post_content', $id ),
					'credit'              => get_post_meta( $id, '_kv2ps_credit', true ),
					'copyright'           => get_post_meta( $id, '_kv2ps_copyright', true ),
					'keywords'            => get_post_meta( $id, '_kv2ps_keywords', true ),
					'location'            => $location ? $location : array(),
					'date_created'        => get_post_meta( $id, '_kv2ps_date_created', true ),
					'license_url'         => get_post_meta( $id, '_kv2ps_license_url', true ),
					'acquire_license_url' => get_post_meta( $id, '_kv2ps_acquire_license_url', true ),
				),
				'exif_suggestions' => array(),
			);
		}

		return array(
			'schema_version' => '1.0',
			'source'         => array(
				'generator'    => 'KV2 Portfolio Studio',
				'generated_at' => gmdate( 'c' ),
				'site'         => home_url( '/' ),
			),
			'images'         => $images,
		);
	}

	private static function render_import_report( $report ) {
		if ( null === $report ) {
			return;
		}
		if ( is_wp_error( $report ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( $report->get_error_message() ) . '</p></div>';
			return;
		}
		?>
		<section class="kv2ps-panel kv2ps-output">
			<h2><?php echo esc_html( $report['dry_run'] ? __( 'Résultat de la simulation', 'kv2-portfolio-studio' ) : __( 'Import terminé', 'kv2-portfolio-studio' ) ); ?></h2>
			<?php if ( ! $report['dry_run'] ) : ?><p><strong><?php echo esc_html( sprintf( __( '%d image(s) mise(s) à jour.', 'kv2-portfolio-studio' ), $report['updated'] ) ); ?></strong></p><?php endif; ?>
			<?php foreach ( $report['errors'] as $error ) : ?><p class="kv2ps-error"><?php echo esc_html( $error ); ?></p><?php endforeach; ?>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Image', 'kv2-portfolio-studio' ); ?></th><th><?php esc_html_e( 'État', 'kv2-portfolio-studio' ); ?></th><th><?php esc_html_e( 'Champs', 'kv2-portfolio-studio' ); ?></th></tr></thead><tbody>
			<?php foreach ( $report['items'] as $item ) : ?><tr><td><?php echo esc_html( $item['label'] ); ?></td><td><?php echo esc_html( $item['status'] ); ?></td><td><?php echo esc_html( implode( ', ', $item['fields'] ) ); ?></td></tr><?php endforeach; ?>
			</tbody></table>
		</section>
		<?php
	}

	private static function render_inspection( $inspect ) {
		if ( null === $inspect ) {
			return;
		}
		if ( is_wp_error( $inspect ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( $inspect->get_error_message() ) . '</p></div>';
			return;
		}
		?>
		<section class="kv2ps-panel kv2ps-output">
			<h2><?php echo esc_html( sprintf( __( 'Rapport EXIF — #%1$d %2$s', 'kv2-portfolio-studio' ), $inspect['attachment_id'], $inspect['filename'] ) ); ?></h2>
			<p><?php echo esc_html( $inspect['mime_type'] . ' · ' . $inspect['file_size'] ); ?></p>
			<table class="widefat striped"><tbody>
				<tr><th><?php esc_html_e( 'EXIF détectés', 'kv2-portfolio-studio' ); ?></th><td><?php echo esc_html( $inspect['exif']['available'] ? 'Oui' : 'Non' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Coordonnées GPS détectées', 'kv2-portfolio-studio' ); ?></th><td><?php echo esc_html( $inspect['exif']['has_gps'] ? 'Oui — données sensibles, non publiées' : 'Non' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Appareil', 'kv2-portfolio-studio' ); ?></th><td><?php echo esc_html( $inspect['exif']['camera'] ?: '—' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Auteur EXIF', 'kv2-portfolio-studio' ); ?></th><td><?php echo esc_html( $inspect['exif']['artist'] ?: '—' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Copyright EXIF', 'kv2-portfolio-studio' ); ?></th><td><?php echo esc_html( $inspect['exif']['copyright'] ?: '—' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'ALT WordPress', 'kv2-portfolio-studio' ); ?></th><td><?php echo esc_html( $inspect['wordpress']['alt'] ?: 'Manquant' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Légende WordPress', 'kv2-portfolio-studio' ); ?></th><td><?php echo esc_html( $inspect['wordpress']['caption'] ?: 'Manquante' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Crédit KV2', 'kv2-portfolio-studio' ); ?></th><td><?php echo esc_html( $inspect['wordpress']['credit'] ?: 'Manquant' ); ?></td></tr>
			</tbody></table>
		</section>
		<?php
	}

	private static function sanitize_ids( $value ) {
		return array_values( array_filter( array_unique( array_map( 'absint', preg_split( '/[\s,;]+/', (string) $value ) ) ) ) );
	}

	private static function sanitize_keywords( $keywords ) {
		if ( ! is_array( $keywords ) ) {
			$keywords = preg_split( '/[,;]+/', (string) $keywords );
		}
		return array_values( array_filter( array_unique( array_map( 'sanitize_text_field', $keywords ) ) ) );
	}

	private static function sanitize_recursive( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( __CLASS__, 'sanitize_recursive' ), $value );
		}
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}
}
