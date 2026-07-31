<?php

defined( 'ABSPATH' ) || exit;

final class KV2PS_Project_Package {
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$result = null;
		$export = '';
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) && isset( $_POST['kv2ps_project_action'] ) ) {
			check_admin_referer( 'kv2ps_project_package', 'kv2ps_project_nonce' );
			$action = sanitize_key( wp_unslash( $_POST['kv2ps_project_action'] ) );
			if ( 'export' === $action ) {
				$post_id = isset( $_POST['kv2ps_project_id'] ) ? absint( $_POST['kv2ps_project_id'] ) : 0;
				$ids     = isset( $_POST['kv2ps_project_image_ids'] ) ? self::sanitize_ids( wp_unslash( $_POST['kv2ps_project_image_ids'] ) ) : array();
				$notes   = isset( $_POST['kv2ps_client_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kv2ps_client_notes'] ) ) : '';
				$export  = wp_json_encode( self::export_payload( $post_id, $ids, $notes ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			} elseif ( in_array( $action, array( 'preview', 'apply' ), true ) ) {
				$payload = self::read_submitted_json();
				$result  = is_wp_error( $payload ) ? $payload : self::process_payload( $payload, 'preview' === $action );
			}
		}
		?>
		<div class="wrap kv2ps-admin">
			<h1><?php esc_html_e( 'Assistant ChatGPT — réalisation complète', 'kv2-portfolio-studio' ); ?></h1>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'L’import crée toujours une nouvelle réalisation en brouillon. Une réalisation existante ne peut être enrichie automatiquement que tant qu’elle est encore en brouillon.', 'kv2-portfolio-studio' ); ?></p></div>

			<div class="kv2ps-tools-grid kv2ps-tools-grid--two">
				<section class="kv2ps-panel">
					<h2><?php esc_html_e( '1. Préparer le dossier pour ChatGPT', 'kv2-portfolio-studio' ); ?></h2>
					<p><?php esc_html_e( 'Ajoutez les ID des photos reçues du client. Vous pouvez aussi partir d’un brouillon existant.', 'kv2-portfolio-studio' ); ?></p>
					<form method="post">
						<?php wp_nonce_field( 'kv2ps_project_package', 'kv2ps_project_nonce' ); ?>
						<p><label for="kv2ps-project-id"><strong><?php esc_html_e( 'ID du brouillon facultatif', 'kv2-portfolio-studio' ); ?></strong></label><br><input id="kv2ps-project-id" min="1" name="kv2ps_project_id" type="number"></p>
						<p><label for="kv2ps-project-image-ids"><strong><?php esc_html_e( 'ID des images', 'kv2-portfolio-studio' ); ?></strong></label><br><input class="regular-text" id="kv2ps-project-image-ids" name="kv2ps_project_image_ids" placeholder="123, 124, 125" type="text"></p>
						<p><label for="kv2ps-client-notes"><strong><?php esc_html_e( 'Notes ou message du client', 'kv2-portfolio-studio' ); ?></strong></label><textarea class="large-text" id="kv2ps-client-notes" name="kv2ps_client_notes" rows="6" placeholder="Collez ici le message, les matériaux confirmés, le lieu approximatif et les contraintes connues."></textarea></p>
						<button class="button button-primary" name="kv2ps_project_action" type="submit" value="export"><?php esc_html_e( 'Générer le dossier JSON', 'kv2-portfolio-studio' ); ?></button>
					</form>
				</section>

				<section class="kv2ps-panel">
					<h2><?php esc_html_e( '2. Importer le dossier complété', 'kv2-portfolio-studio' ); ?></h2>
					<form enctype="multipart/form-data" method="post">
						<?php wp_nonce_field( 'kv2ps_project_package', 'kv2ps_project_nonce' ); ?>
						<p><label for="kv2ps-project-json-file"><strong><?php esc_html_e( 'Fichier JSON', 'kv2-portfolio-studio' ); ?></strong></label><br><input id="kv2ps-project-json-file" name="kv2ps_project_json_file" type="file" accept="application/json,.json"></p>
						<p><label for="kv2ps-project-json"><strong><?php esc_html_e( 'ou JSON collé', 'kv2-portfolio-studio' ); ?></strong></label><textarea class="large-text code" id="kv2ps-project-json" name="kv2ps_project_json" rows="13"></textarea></p>
						<p><button class="button" name="kv2ps_project_action" type="submit" value="preview"><?php esc_html_e( 'Simuler', 'kv2-portfolio-studio' ); ?></button> <button class="button button-primary" name="kv2ps_project_action" type="submit" value="apply" onclick="return window.confirm('<?php echo esc_js( __( 'Créer ou compléter ce brouillon ?', 'kv2-portfolio-studio' ) ); ?>');"><?php esc_html_e( 'Importer en brouillon', 'kv2-portfolio-studio' ); ?></button></p>
					</form>
				</section>
			</div>

			<section class="kv2ps-panel kv2ps-prompt-box">
				<h2><?php esc_html_e( 'Prompt à utiliser avec ChatGPT', 'kv2-portfolio-studio' ); ?></h2>
				<textarea class="large-text code" rows="9" readonly><?php echo esc_textarea( self::prompt() ); ?></textarea>
			</section>

			<?php if ( $export ) : ?><section class="kv2ps-panel kv2ps-output"><h2><?php esc_html_e( 'Dossier JSON à envoyer avec les photos', 'kv2-portfolio-studio' ); ?></h2><textarea class="large-text code kv2ps-download-source" rows="28" readonly><?php echo esc_textarea( $export ); ?></textarea><p><button class="button kv2ps-download-json" data-filename="kv2-realisation-chatgpt.json" type="button"><?php esc_html_e( 'Télécharger le fichier JSON', 'kv2-portfolio-studio' ); ?></button></p></section><?php endif; ?>
			<?php self::render_result( $result ); ?>
		</div>
		<?php
	}

	public static function prompt() {
		return "Analyse les photos et les notes du client pour préparer une réalisation de portfolio. Complète uniquement le JSON fourni, sans modifier schema_version ni les valeurs match. N’invente jamais une identité, un avis, une autorisation, un lieu, un prix, une date, un matériau ou un droit. Laisse vide ce qui n’est pas certain.\n\nRédige en français naturel : titre, extrait, besoin, état initial, contraintes, intervention, résultat, matières, durée et type de transformation. Propose les taxonomies utiles, un titre SEO, une meta description et le mot-clé principal. Pour chaque image, indique son rôle featured, before ou after, son ordre et complète title, alt, caption, description, credit, copyright, keywords, location, date_created et licence. L’ALT doit décrire l’information visuelle utile sans bourrage de mots-clés.\n\nPrépare aussi le CTA, mais conserve use_global=true sauf besoin particulier. Ne mets un témoignage que si son texte a été réellement fourni et ne mets consent=true que si l’autorisation est explicitement indiquée. Retourne uniquement un JSON valide conforme à chatgpt-realisation.schema.json.";
	}

	private static function read_submitted_json() {
		$json = isset( $_POST['kv2ps_project_json'] ) ? trim( wp_unslash( $_POST['kv2ps_project_json'] ) ) : '';
		$file = isset( $_FILES['kv2ps_project_json_file'] ) && is_array( $_FILES['kv2ps_project_json_file'] ) ? $_FILES['kv2ps_project_json_file'] : array();
		$data = KV2PS_JSON::read( $json, $file, 2097152 );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( '1.1' !== (string) ( isset( $data['schema_version'] ) ? $data['schema_version'] : '' ) || empty( $data['project'] ) || ! is_array( $data['project'] ) ) {
			return new WP_Error( 'bad_schema', __( 'Le dossier doit utiliser schema_version 1.1 et contenir project.', 'kv2-portfolio-studio' ) );
		}
		return $data;
	}

	public static function process_payload( $payload, $dry_run = true ) {
		$project = $payload['project'];
		$match   = isset( $project['match'] ) && is_array( $project['match'] ) ? $project['match'] : array();
		$post_id = ! empty( $match['realisation_id'] ) ? absint( $match['realisation_id'] ) : 0;

		if ( $post_id && KV2PS_Post_Types::POST_TYPE !== get_post_type( $post_id ) ) {
			return new WP_Error( 'bad_project', __( 'La réalisation ciblée est introuvable.', 'kv2-portfolio-studio' ) );
		}
		if ( $post_id && 'draft' !== get_post_status( $post_id ) && ! $dry_run ) {
			return new WP_Error( 'published_project', __( 'Par sécurité, une réalisation déjà publiée ne peut pas être modifiée par import. Repassez-la en brouillon ou créez une nouvelle fiche.', 'kv2-portfolio-studio' ) );
		}

		$fields = isset( $project['fields'] ) && is_array( $project['fields'] ) ? $project['fields'] : array();
		$title  = isset( $fields['title'] ) ? sanitize_text_field( $fields['title'] ) : '';
		if ( ! $title ) {
			return new WP_Error( 'missing_title', __( 'Le titre de la réalisation est obligatoire.', 'kv2-portfolio-studio' ) );
		}
		$fingerprint = md5(
			wp_json_encode(
				array(
					'title'  => $title,
					'match'  => $match,
					'images' => isset( $project['images'] ) ? array_map(
						function( $image ) {
							return is_array( $image ) && isset( $image['match'] ) ? $image['match'] : array();
						},
						(array) $project['images']
					) : array(),
				)
			)
		);
		if ( ! $post_id && ! $dry_run ) {
			$duplicate = get_posts(
				array(
					'post_type'      => KV2PS_Post_Types::POST_TYPE,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => '_kv2ps_package_fingerprint',
					'meta_value'     => $fingerprint,
				)
			);
			if ( $duplicate ) {
				return new WP_Error( 'duplicate_package', sprintf( __( 'Ce dossier semble déjà avoir créé la réalisation #%d.', 'kv2-portfolio-studio' ), $duplicate[0] ) );
			}
		}

		$report = array(
			'dry_run'    => $dry_run,
			'post_id'    => $post_id,
			'title'      => $title,
			'field_count'=> 0,
			'term_count' => 0,
			'image_count'=> 0,
			'edit_url'   => '',
			'warnings'   => array(),
		);

		if ( ! $dry_run ) {
			$post_data = array(
				'post_type'    => KV2PS_Post_Types::POST_TYPE,
				'post_status'  => 'draft',
				'post_title'   => $title,
				'post_excerpt' => isset( $fields['excerpt'] ) ? sanitize_textarea_field( $fields['excerpt'] ) : '',
				'post_content' => isset( $fields['content'] ) ? wp_kses_post( $fields['content'] ) : '',
			);
			if ( $post_id ) {
				$post_data['ID'] = $post_id;
				$result = wp_update_post( wp_slash( $post_data ), true );
			} else {
				$post_data['post_author'] = get_current_user_id();
				$result = wp_insert_post( wp_slash( $post_data ), true );
			}
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$post_id            = (int) $result;
			$report['post_id']  = $post_id;
			$report['edit_url'] = get_edit_post_link( $post_id, 'raw' );
		}

		$meta_map = array(
			'problem'       => '_kv2ps_problem',
			'intervention'  => '_kv2ps_intervention',
			'result'        => '_kv2ps_result',
			'materials'     => '_kv2ps_materials',
			'project_date'  => '_kv2ps_project_date',
			'duration'      => '_kv2ps_duration',
			'initial_state' => '_kv2ps_initial_state',
			'constraints'   => '_kv2ps_constraints',
			'work_type'     => '_kv2ps_work_type',
			'price_range'   => '_kv2ps_price_range',
		);
		foreach ( $meta_map as $json_key => $meta_key ) {
			if ( ! isset( $fields[ $json_key ] ) || '' === trim( (string) $fields[ $json_key ] ) ) {
				continue;
			}
			++$report['field_count'];
			if ( ! $dry_run ) {
				$value = 'project_date' === $json_key ? self::sanitize_date( $fields[ $json_key ] ) : sanitize_textarea_field( $fields[ $json_key ] );
				if ( $value ) {
					update_post_meta( $post_id, $meta_key, $value );
				}
			}
		}
		if ( ! $dry_run ) {
			if ( ! empty( $fields['confidential'] ) ) {
				update_post_meta( $post_id, '_kv2ps_confidential', '1' );
			} else {
				delete_post_meta( $post_id, '_kv2ps_confidential' );
			}
		}

		self::apply_testimonial( $post_id, isset( $project['testimonial'] ) ? $project['testimonial'] : array(), $dry_run, $report );
		self::apply_cta( $post_id, isset( $project['cta'] ) ? $project['cta'] : array(), $dry_run, $report );
		self::apply_taxonomies( $post_id, isset( $project['taxonomies'] ) ? $project['taxonomies'] : array(), $dry_run, $report );
		self::apply_seo( $post_id, isset( $project['seo'] ) ? $project['seo'] : array(), $dry_run );
		self::apply_images( $post_id, isset( $project['images'] ) ? $project['images'] : array(), $dry_run, $report );

		if ( ! $dry_run ) {
			update_post_meta( $post_id, '_kv2ps_package_fingerprint', $fingerprint );
			update_post_meta( $post_id, '_kv2ps_project_package', self::sanitize_recursive( $payload ) );
		}
		return $report;
	}

	private static function apply_testimonial( $post_id, $testimonial, $dry_run, &$report ) {
		if ( ! is_array( $testimonial ) || empty( $testimonial['quote'] ) ) {
			return;
		}
		$map = array(
			'quote'      => '_kv2ps_testimonial',
			'author'     => '_kv2ps_testimonial_author',
			'source'     => '_kv2ps_testimonial_source',
			'source_url' => '_kv2ps_testimonial_source_url',
			'rating'     => '_kv2ps_testimonial_rating',
			'date'       => '_kv2ps_testimonial_date',
		);
		foreach ( $map as $key => $meta_key ) {
			if ( ! isset( $testimonial[ $key ] ) || '' === trim( (string) $testimonial[ $key ] ) ) {
				continue;
			}
			++$report['field_count'];
			if ( ! $dry_run ) {
				$value = 'source_url' === $key ? esc_url_raw( $testimonial[ $key ] ) : ( 'quote' === $key ? sanitize_textarea_field( $testimonial[ $key ] ) : sanitize_text_field( $testimonial[ $key ] ) );
				if ( 'rating' === $key ) {
					$value = max( 1, min( 5, absint( $value ) ) );
				}
				if ( 'date' === $key ) {
					$value = self::sanitize_date( $value );
				}
				if ( '' !== $value ) {
					update_post_meta( $post_id, $meta_key, $value );
				}
			}
		}
		if ( ! $dry_run ) {
			if ( ! empty( $testimonial['consent'] ) ) {
				update_post_meta( $post_id, '_kv2ps_testimonial_consent', '1' );
			} else {
				delete_post_meta( $post_id, '_kv2ps_testimonial_consent' );
			}
		}
	}

	private static function apply_cta( $post_id, $cta, $dry_run, &$report ) {
		if ( ! is_array( $cta ) ) {
			return;
		}
		if ( ! empty( $cta['use_global'] ) ) {
			if ( ! $dry_run ) {
				delete_post_meta( $post_id, '_kv2ps_cta_override' );
			}
			return;
		}
		if ( ! $dry_run ) {
			update_post_meta( $post_id, '_kv2ps_cta_override', '1' );
		}
		$map = array(
			'title'           => '_kv2ps_cta_title',
			'text'            => '_kv2ps_cta_text',
			'primary_action'  => '_kv2ps_cta_primary_action',
			'primary_label'   => '_kv2ps_cta_primary_label',
			'secondary_label' => '_kv2ps_cta_secondary_label',
			'form_url'        => '_kv2ps_cta_form_url',
		);
		foreach ( $map as $key => $meta_key ) {
			if ( empty( $cta[ $key ] ) ) {
				continue;
			}
			++$report['field_count'];
			if ( ! $dry_run ) {
				$value = 'form_url' === $key ? esc_url_raw( $cta[ $key ] ) : sanitize_text_field( $cta[ $key ] );
				if ( 'primary_action' === $key && ! in_array( $value, array( 'click_to_chat', 'form' ), true ) ) {
					$value = 'click_to_chat';
				}
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
		if ( ! $dry_run ) {
			if ( ! empty( $cta['secondary_enabled'] ) ) {
				update_post_meta( $post_id, '_kv2ps_cta_secondary_enabled', '1' );
			} else {
				delete_post_meta( $post_id, '_kv2ps_cta_secondary_enabled' );
			}
		}
	}

	private static function apply_taxonomies( $post_id, $taxonomies, $dry_run, &$report ) {
		if ( ! is_array( $taxonomies ) ) {
			return;
		}
		$map = array(
			'services'   => 'kv2_service',
			'villes'     => 'kv2_ville',
			'meubles'    => 'kv2_meuble',
			'styles'     => 'kv2_style',
			'techniques' => 'kv2_technique',
		);
		foreach ( $map as $key => $taxonomy ) {
			if ( empty( $taxonomies[ $key ] ) || ! is_array( $taxonomies[ $key ] ) ) {
				continue;
			}
			$term_ids = array();
			foreach ( array_slice( $taxonomies[ $key ], 0, 20 ) as $term_data ) {
				$name = is_array( $term_data ) ? ( isset( $term_data['name'] ) ? sanitize_text_field( $term_data['name'] ) : '' ) : sanitize_text_field( $term_data );
				$slug = is_array( $term_data ) && ! empty( $term_data['slug'] ) ? sanitize_title( $term_data['slug'] ) : sanitize_title( $name );
				if ( ! $name ) {
					continue;
				}
				++$report['term_count'];
				if ( $dry_run ) {
					continue;
				}
				$existing = term_exists( $slug, $taxonomy );
				if ( ! $existing ) {
					$existing = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
				}
				if ( ! is_wp_error( $existing ) ) {
					$term_ids[] = (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
				}
			}
			if ( ! $dry_run && $term_ids ) {
				wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
			}
		}
	}

	private static function apply_seo( $post_id, $seo, $dry_run ) {
		if ( ! is_array( $seo ) ) {
			return;
		}
		$clean = array(
			'title'         => isset( $seo['title'] ) ? sanitize_text_field( $seo['title'] ) : '',
			'description'   => isset( $seo['description'] ) ? sanitize_textarea_field( $seo['description'] ) : '',
			'focus_keyword' => isset( $seo['focus_keyword'] ) ? sanitize_text_field( $seo['focus_keyword'] ) : '',
		);
		if ( $dry_run ) {
			return;
		}
		update_post_meta( $post_id, '_kv2ps_seo_suggestions', $clean );
		if ( $clean['title'] ) {
			update_post_meta( $post_id, 'rank_math_title', $clean['title'] );
		}
		if ( $clean['description'] ) {
			update_post_meta( $post_id, 'rank_math_description', $clean['description'] );
		}
		if ( $clean['focus_keyword'] ) {
			update_post_meta( $post_id, 'rank_math_focus_keyword', $clean['focus_keyword'] );
		}
	}

	private static function apply_images( $post_id, $images, $dry_run, &$report ) {
		if ( ! is_array( $images ) ) {
			return;
		}
		$image_payload = array( 'schema_version' => '1.0', 'images' => array() );
		$roles         = array( 'before' => array(), 'after' => array(), 'featured' => array() );
		foreach ( array_slice( $images, 0, 100 ) as $image ) {
			if ( ! is_array( $image ) || empty( $image['match'] ) ) {
				continue;
			}
			$image_payload['images'][] = array(
				'match'            => $image['match'],
				'fields'           => isset( $image['fields'] ) && is_array( $image['fields'] ) ? $image['fields'] : array(),
				'exif_suggestions' => isset( $image['exif_suggestions'] ) ? $image['exif_suggestions'] : array(),
			);
			$attachment_id = KV2PS_Image_Metadata::match_attachment( $image['match'] );
			$role          = isset( $image['role'] ) && isset( $roles[ $image['role'] ] ) ? $image['role'] : '';
			if ( $attachment_id && $role ) {
				$position = isset( $image['position'] ) ? absint( $image['position'] ) : count( $roles[ $role ] );
				$roles[ $role ][ $position ] = $attachment_id;
				++$report['image_count'];
			}
		}
		if ( $image_payload['images'] ) {
			$image_report = KV2PS_Image_Metadata::process_payload( $image_payload, $dry_run );
			if ( ! empty( $image_report['errors'] ) ) {
				$report['warnings'] = array_merge( $report['warnings'], $image_report['errors'] );
			}
		}
		if ( $dry_run ) {
			return;
		}
		foreach ( $roles as &$ids ) {
			ksort( $ids );
			$ids = array_values( array_unique( $ids ) );
		}
		unset( $ids );
		if ( $roles['featured'] ) {
			set_post_thumbnail( $post_id, $roles['featured'][0] );
		} elseif ( $roles['after'] ) {
			set_post_thumbnail( $post_id, $roles['after'][0] );
		}
		if ( $roles['before'] ) {
			update_post_meta( $post_id, '_kv2ps_before_images', $roles['before'] );
		}
		if ( $roles['after'] ) {
			update_post_meta( $post_id, '_kv2ps_after_images', $roles['after'] );
		}
	}

	public static function export_payload( $post_id = 0, $extra_ids = array(), $client_notes = '' ) {
		$post = $post_id && KV2PS_Post_Types::POST_TYPE === get_post_type( $post_id ) ? get_post( $post_id ) : null;
		$before_ids = $post ? KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_before_images', true ) ) : array();
		$after_ids  = $post ? KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_after_images', true ) ) : array();
		$featured   = $post ? get_post_thumbnail_id( $post_id ) : 0;
		$image_ids  = array_values( array_unique( array_filter( array_merge( array( $featured ), $before_ids, $after_ids, $extra_ids ) ) ) );
		$images     = KV2PS_Image_Metadata::export_payload( $image_ids );
		$settings   = KV2PS_Plugin::settings();
		foreach ( $images['images'] as $index => &$image ) {
			$id = (int) $image['match']['attachment_id'];
			if ( $id === (int) $featured ) {
				$image['role'] = 'featured';
				$image['position'] = 0;
			} elseif ( in_array( $id, $before_ids, true ) ) {
				$image['role'] = 'before';
				$image['position'] = array_search( $id, $before_ids, true );
			} elseif ( in_array( $id, $after_ids, true ) ) {
				$image['role'] = 'after';
				$image['position'] = array_search( $id, $after_ids, true );
			} else {
				$image['role'] = 0 === $index ? 'featured' : 'after';
				$image['position'] = $index;
			}
			$image['fields']['credit']              = $image['fields']['credit'] ?: $settings['image_credit'];
			$image['fields']['copyright']           = $image['fields']['copyright'] ?: $settings['image_copyright'];
			$image['fields']['license_url']         = $image['fields']['license_url'] ?: $settings['image_license_url'];
			$image['fields']['acquire_license_url'] = $image['fields']['acquire_license_url'] ?: $settings['image_acquire_license_url'];
		}
		unset( $image );

		$fields = array(
			'title'         => $post ? $post->post_title : '',
			'excerpt'       => $post ? $post->post_excerpt : '',
			'content'       => $post ? $post->post_content : '',
			'problem'       => $post ? get_post_meta( $post_id, '_kv2ps_problem', true ) : '',
			'initial_state' => $post ? get_post_meta( $post_id, '_kv2ps_initial_state', true ) : '',
			'constraints'   => $post ? get_post_meta( $post_id, '_kv2ps_constraints', true ) : '',
			'intervention'  => $post ? get_post_meta( $post_id, '_kv2ps_intervention', true ) : '',
			'result'        => $post ? get_post_meta( $post_id, '_kv2ps_result', true ) : '',
			'materials'     => $post ? get_post_meta( $post_id, '_kv2ps_materials', true ) : '',
			'duration'      => $post ? get_post_meta( $post_id, '_kv2ps_duration', true ) : '',
			'work_type'     => $post ? get_post_meta( $post_id, '_kv2ps_work_type', true ) : '',
			'price_range'   => $post ? get_post_meta( $post_id, '_kv2ps_price_range', true ) : '',
			'project_date'  => $post ? get_post_meta( $post_id, '_kv2ps_project_date', true ) : '',
			'confidential'  => $post ? (bool) get_post_meta( $post_id, '_kv2ps_confidential', true ) : false,
		);

		return array(
			'schema_version' => '1.1',
			'source' => array( 'generator' => 'KV2 Portfolio Studio', 'generated_at' => gmdate( 'c' ), 'site' => home_url( '/' ), 'status_policy' => 'draft_only', 'client_notes' => sanitize_textarea_field( $client_notes ) ),
			'project' => array(
				'match'       => array( 'realisation_id' => $post ? (int) $post_id : null ),
				'fields'      => $fields,
				'testimonial' => self::export_testimonial( $post_id ),
				'taxonomies'  => self::export_taxonomies( $post_id ),
				'cta'         => array_merge( array( 'use_global' => ! $post || ! get_post_meta( $post_id, '_kv2ps_cta_override', true ) ), KV2PS_Plugin::get_cta_config( $post_id ) ),
				'seo'         => array(
					'title'         => $post ? get_post_meta( $post_id, 'rank_math_title', true ) : '',
					'description'   => $post ? get_post_meta( $post_id, 'rank_math_description', true ) : '',
					'focus_keyword' => $post ? get_post_meta( $post_id, 'rank_math_focus_keyword', true ) : '',
				),
				'images' => $images['images'],
			),
		);
	}

	private static function export_testimonial( $post_id ) {
		return array(
			'quote'      => $post_id ? get_post_meta( $post_id, '_kv2ps_testimonial', true ) : '',
			'author'     => $post_id ? get_post_meta( $post_id, '_kv2ps_testimonial_author', true ) : '',
			'source'     => $post_id ? get_post_meta( $post_id, '_kv2ps_testimonial_source', true ) : '',
			'source_url' => $post_id ? get_post_meta( $post_id, '_kv2ps_testimonial_source_url', true ) : '',
			'rating'     => $post_id && get_post_meta( $post_id, '_kv2ps_testimonial_rating', true ) ? absint( get_post_meta( $post_id, '_kv2ps_testimonial_rating', true ) ) : '',
			'date'       => $post_id ? get_post_meta( $post_id, '_kv2ps_testimonial_date', true ) : '',
			'consent'    => $post_id ? (bool) get_post_meta( $post_id, '_kv2ps_testimonial_consent', true ) : false,
		);
	}

	private static function export_taxonomies( $post_id ) {
		$map = array( 'services' => 'kv2_service', 'villes' => 'kv2_ville', 'meubles' => 'kv2_meuble', 'styles' => 'kv2_style', 'techniques' => 'kv2_technique' );
		$output = array();
		foreach ( $map as $key => $taxonomy ) {
			$terms = $post_id ? wp_get_post_terms( $post_id, $taxonomy ) : array();
			$output[ $key ] = is_wp_error( $terms ) ? array() : array_map( function( $term ) { return array( 'name' => $term->name, 'slug' => $term->slug ); }, $terms );
		}
		return $output;
	}

	private static function render_result( $result ) {
		if ( null === $result ) {
			return;
		}
		if ( is_wp_error( $result ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			return;
		}
		?>
		<section class="kv2ps-panel kv2ps-output"><h2><?php echo esc_html( $result['dry_run'] ? __( 'Simulation réussie', 'kv2-portfolio-studio' ) : __( 'Brouillon créé', 'kv2-portfolio-studio' ) ); ?></h2><p><strong><?php echo esc_html( $result['title'] ); ?></strong></p><ul><li><?php echo esc_html( sprintf( __( '%d champ(s) reconnu(s)', 'kv2-portfolio-studio' ), $result['field_count'] ) ); ?></li><li><?php echo esc_html( sprintf( __( '%d terme(s) proposé(s)', 'kv2-portfolio-studio' ), $result['term_count'] ) ); ?></li><li><?php echo esc_html( sprintf( __( '%d image(s) associée(s)', 'kv2-portfolio-studio' ), $result['image_count'] ) ); ?></li></ul><?php if ( $result['edit_url'] ) : ?><p><a class="button button-primary" href="<?php echo esc_url( $result['edit_url'] ); ?>"><?php esc_html_e( 'Ouvrir et vérifier le brouillon', 'kv2-portfolio-studio' ); ?></a></p><?php endif; ?><?php foreach ( $result['warnings'] as $warning ) : ?><p class="kv2ps-error"><?php echo esc_html( $warning ); ?></p><?php endforeach; ?></section>
		<?php
	}

	private static function sanitize_ids( $value ) {
		return array_values( array_filter( array_unique( array_map( 'absint', preg_split( '/[\s,;]+/', (string) $value ) ) ) ) );
	}

	private static function sanitize_date( $value ) {
		$value = sanitize_text_field( $value );
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches ) || ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
			return '';
		}
		return $value;
	}

	private static function sanitize_recursive( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( __CLASS__, 'sanitize_recursive' ), $value );
		}
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}
}
