<?php

defined( 'ABSPATH' ) || exit;

final class KV2PS_Importer {
	const SOURCE_POST_TYPE = 'astra-portfolio';
	private static $existing_source_map = null;

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$result    = null;
		$remaining = 0;
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) && isset( $_POST['kv2ps_import_action'] ) ) {
			check_admin_referer( 'kv2ps_import_wp_portfolio', 'kv2ps_import_nonce' );
			$action   = sanitize_key( wp_unslash( $_POST['kv2ps_import_action'] ) );
			$user_id  = get_current_user_id();
			$queue_key = 'kv2ps_import_queue';
			if ( 'continue' === $action ) {
				$queue       = get_user_meta( $user_id, $queue_key, true );
				$queue       = is_array( $queue ) ? $queue : array();
				$ids         = isset( $queue['ids'] ) ? array_map( 'absint', (array) $queue['ids'] ) : array();
				$queue_mode  = isset( $queue['mode'] ) ? sanitize_key( $queue['mode'] ) : 'draft';
				$import_mode = in_array( $queue_mode, array( 'draft', 'gallery', 'gallery_sync' ), true ) ? $queue_mode : 'draft';
			} else {
				$ids = isset( $_POST['source_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['source_ids'] ) ) : array();
				if ( 'gallery_sync' === $action ) {
					$import_mode = 'gallery_sync';
					$ids = get_posts(
						array(
							'post_type'      => self::SOURCE_POST_TYPE,
							'post_status'    => 'publish',
							'posts_per_page' => 2000,
							'fields'         => 'ids',
						)
					);
				} else {
					$import_mode = 'gallery' === $action ? 'gallery' : 'draft';
				}
				if ( 'gallery' === $import_mode && $ids ) {
					$ids = get_posts(
						array(
							'post_type'      => self::SOURCE_POST_TYPE,
							'post_status'    => 'publish',
							'posts_per_page' => 2000,
							'fields'         => 'ids',
							'post__in'       => array_values( array_unique( $ids ) ),
						)
					);
				}
			}
			$batch = array_splice( $ids, 0, 50 );
			if ( $ids ) {
				update_user_meta( $user_id, $queue_key, array( 'mode' => $import_mode, 'ids' => $ids ) );
			} else {
				delete_user_meta( $user_id, $queue_key );
			}
			$result    = self::import( $batch, array( 'mode' => $import_mode ) );
			$remaining = count( $ids );
		}

		$available = post_type_exists( self::SOURCE_POST_TYPE );
		if ( ! $result ) {
			$saved_queue = get_user_meta( get_current_user_id(), 'kv2ps_import_queue', true );
			$remaining   = is_array( $saved_queue ) && ! empty( $saved_queue['ids'] ) ? count( (array) $saved_queue['ids'] ) : 0;
		}
		$sources   = $available ? get_posts(
			array(
				'post_type'      => self::SOURCE_POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 2000,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		) : array();
		$published_sources = array_values(
			array_filter(
				$sources,
				function( $source ) {
					return 'publish' === $source->post_status;
				}
			)
		);
		$empty_source_count = count(
			array_filter(
				$published_sources,
				function( $source ) {
					return '' === trim( wp_strip_all_tags( (string) $source->post_content ) );
				}
			)
		);
		$single_image_count = 0;
		$multi_image_count  = 0;
		foreach ( $published_sources as $published_source ) {
			$image_count = count( self::find_source_image_ids( $published_source->ID ) );
			if ( $image_count > 1 ) {
				++$multi_image_count;
			} elseif ( 1 === $image_count ) {
				++$single_image_count;
			}
		}
		?>
		<div class="wrap kv2ps-admin">
			<h1><?php esc_html_e( 'Importer depuis WP Portfolio', 'kv2-portfolio-studio' ); ?></h1>
			<p><?php esc_html_e( 'L’import est non destructif : WP Portfolio et ses données restent intacts. Le mode brouillon sert aux futures études de cas ; le mode galerie reprend uniquement les sources déjà publiées, en noindex et hors sitemap.', 'kv2-portfolio-studio' ); ?></p>

			<?php if ( $result ) : ?>
				<div class="notice notice-success inline"><p><?php echo esc_html( sprintf( __( '%1$d importé(s), %2$d réparé(s) ou resynchronisé(s), %3$d ignoré(s), %4$d erreur(s).', 'kv2-portfolio-studio' ), $result['imported'], $result['updated'], $result['skipped'], count( $result['errors'] ) ) ); ?></p></div>
				<?php foreach ( $result['errors'] as $error ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $error ); ?></p></div><?php endforeach; ?>
				<?php if ( $remaining ) : ?><div class="notice notice-info inline"><p><?php echo esc_html( sprintf( __( '%d source(s) restent dans la file sécurisée. Le traitement est limité à 50 éléments par requête pour éviter les expirations serveur.', 'kv2-portfolio-studio' ), $remaining ) ); ?></p><form method="post"><?php wp_nonce_field( 'kv2ps_import_wp_portfolio', 'kv2ps_import_nonce' ); ?><button class="button button-primary" name="kv2ps_import_action" type="submit" value="continue"><?php esc_html_e( 'Continuer l’import', 'kv2-portfolio-studio' ); ?></button></form></div><?php endif; ?>
			<?php endif; ?>
			<?php if ( ! $result && $remaining ) : ?><div class="notice notice-info inline"><p><?php echo esc_html( sprintf( __( 'Un import interrompu contient encore %d source(s).', 'kv2-portfolio-studio' ), $remaining ) ); ?></p><form method="post"><?php wp_nonce_field( 'kv2ps_import_wp_portfolio', 'kv2ps_import_nonce' ); ?><button class="button button-primary" name="kv2ps_import_action" type="submit" value="continue"><?php esc_html_e( 'Reprendre l’import', 'kv2-portfolio-studio' ); ?></button></form></div><?php endif; ?>

			<?php if ( ! $available ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'Le type de contenu astra-portfolio n’est pas actif. Activez temporairement WP Portfolio pour effectuer l’import, puis revenez ici.', 'kv2-portfolio-studio' ); ?></p></div>
			<?php elseif ( ! $sources ) : ?>
				<p><?php esc_html_e( 'Aucun élément WP Portfolio détecté.', 'kv2-portfolio-studio' ); ?></p>
			<?php else : ?>
				<?php $published_count = count( $published_sources ); ?>
				<p><strong><?php echo esc_html( sprintf( __( '%1$d sources détectées, dont %2$d publiées.', 'kv2-portfolio-studio' ), count( $sources ), $published_count ) ); ?></strong> <button class="button button-small" id="kv2ps-select-published" type="button"><?php esc_html_e( 'Sélectionner les publiées', 'kv2-portfolio-studio' ); ?></button></p>
				<div class="notice notice-warning inline"><p><strong><?php esc_html_e( 'Ce que WP Portfolio contient réellement', 'kv2-portfolio-studio' ); ?></strong><br><?php echo esc_html( sprintf( __( '%1$d élément(s) publié(s) sur %2$d ne contiennent aucun texte éditorial. %3$d n’ont qu’une image et %4$d en ont plusieurs.', 'kv2-portfolio-studio' ), $empty_source_count, $published_count, $single_image_count, $multi_image_count ) ); ?><br><?php esc_html_e( 'Ces sources sont des cartes d’images, pas des fiches complètes. KV2 les conserve donc en galerie noindex avec visionneuse. Une étude de cas indexable doit être enrichie avec un contenu réel.', 'kv2-portfolio-studio' ); ?></p></div>
				<form method="post">
					<?php wp_nonce_field( 'kv2ps_import_wp_portfolio', 'kv2ps_import_nonce' ); ?>
					<table class="widefat striped">
						<thead><tr><td class="check-column"><input id="kv2ps-select-all" type="checkbox"></td><th><?php esc_html_e( 'Titre source', 'kv2-portfolio-studio' ); ?></th><th><?php esc_html_e( 'État', 'kv2-portfolio-studio' ); ?></th><th><?php esc_html_e( 'Migration', 'kv2-portfolio-studio' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $sources as $source ) :
							$existing = self::find_existing( $source->ID );
							?>
							<tr>
								<th class="check-column"><input class="kv2ps-source-checkbox" data-source-status="<?php echo esc_attr( $source->post_status ); ?>" name="source_ids[]" type="checkbox" value="<?php echo esc_attr( $source->ID ); ?>" <?php disabled( (bool) $existing ); ?>></th>
								<td><strong><?php echo esc_html( get_the_title( $source ) ?: sprintf( '#%d', $source->ID ) ); ?></strong></td>
								<td><?php echo esc_html( get_post_status_object( $source->post_status )->label ); ?></td>
								<td><?php echo $existing ? wp_kses_post( sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $existing ) ), esc_html__( 'Déjà importé', 'kv2-portfolio-studio' ) ) ) : esc_html__( 'Prêt', 'kv2-portfolio-studio' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<p class="submit"><button class="button" name="kv2ps_import_action" type="submit" value="draft" onclick="return window.confirm('<?php echo esc_js( __( 'Créer les brouillons sélectionnés ?', 'kv2-portfolio-studio' ) ); ?>');"><?php esc_html_e( 'Importer en brouillons', 'kv2-portfolio-studio' ); ?></button> <button class="button button-primary" name="kv2ps_import_action" type="submit" value="gallery" onclick="return window.confirm('<?php echo esc_js( __( 'Importer les sources publiées en galerie noindex ? Les sources WP Portfolio ne seront ni modifiées ni supprimées.', 'kv2-portfolio-studio' ) ); ?>');"><?php esc_html_e( 'Importer comme galerie noindex', 'kv2-portfolio-studio' ); ?></button></p>
				</form>
				<form method="post">
					<?php wp_nonce_field( 'kv2ps_import_wp_portfolio', 'kv2ps_import_nonce' ); ?>
					<p><button class="button button-secondary" name="kv2ps_import_action" type="submit" value="gallery_sync" onclick="return window.confirm('<?php echo esc_js( __( 'Resynchroniser les éléments publiés ? Les fiches enrichies manuellement seront conservées. Seuls les imports vides seront replacés en galerie noindex.', 'kv2-portfolio-studio' ) ); ?>');"><?php esc_html_e( 'Réparer et resynchroniser l’import existant', 'kv2-portfolio-studio' ); ?></button></p>
					<p class="description"><?php esc_html_e( 'À utiliser après une première migration incomplète. Les images, catégories et modes de publication sont remis en cohérence sans toucher aux fiches qui possèdent déjà un vrai contenu.', 'kv2-portfolio-studio' ); ?></p>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function import( $source_ids, $options = array() ) {
		$result = array( 'imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array() );
		$options = wp_parse_args( is_array( $options ) ? $options : array(), array( 'mode' => 'draft' ) );
		$gallery_mode  = in_array( $options['mode'], array( 'gallery', 'gallery_sync' ), true );
		$sync_existing = 'gallery_sync' === $options['mode'];

		foreach ( array_unique( array_filter( $source_ids ) ) as $source_id ) {
			$source = get_post( $source_id );
			if ( ! $source || self::SOURCE_POST_TYPE !== $source->post_type ) {
				$result['errors'][] = sprintf( __( 'Source #%d introuvable.', 'kv2-portfolio-studio' ), $source_id );
				continue;
			}
			$existing_id = self::find_existing( $source_id );
			if ( $existing_id ) {
				if ( $sync_existing && 'publish' === $source->post_status && self::synchronize_existing_gallery( $existing_id, $source ) ) {
					++$result['updated'];
				} else {
					++$result['skipped'];
				}
				continue;
			}
			if ( $gallery_mode && 'publish' !== $source->post_status ) {
				++$result['skipped'];
				continue;
			}

			$content = wp_kses_post( apply_filters( 'kv2ps_import_source_content', trim( $source->post_content ), $source ) );
			if ( ! $content && ! $gallery_mode ) {
				$content = sprintf(
					'<p>%s</p>',
					esc_html__( 'Contenu à compléter après import depuis WP Portfolio.', 'kv2-portfolio-studio' )
				);
			}

			$new_id = wp_insert_post(
				array(
					'post_type'    => KV2PS_Post_Types::POST_TYPE,
					'post_status'  => $gallery_mode && 'publish' === $source->post_status ? 'publish' : 'draft',
					'post_title'   => sanitize_text_field( $source->post_title ),
					'post_excerpt' => sanitize_textarea_field( $source->post_excerpt ),
					'post_content' => wp_slash( $content ),
					'post_author'  => get_current_user_id(),
					'post_date'    => $source->post_date,
					'post_date_gmt'=> $source->post_date_gmt,
					'menu_order'   => (int) $source->menu_order,
				),
				true
			);

			if ( is_wp_error( $new_id ) ) {
				$result['errors'][] = sprintf( __( '%1$s : %2$s', 'kv2-portfolio-studio' ), $source->post_title, $new_id->get_error_message() );
				continue;
			}

			update_post_meta( $new_id, '_kv2ps_source_wp_portfolio_id', $source_id );
			update_post_meta( $new_id, '_kv2ps_source_wp_portfolio_url', get_permalink( $source_id ) );
			update_post_meta( $new_id, '_kv2ps_publication_mode', $gallery_mode ? KV2PS_Compatibility::MODE_GALLERY : KV2PS_Compatibility::MODE_CASE_STUDY );
			self::synchronize_source_data( $new_id, $source );
			if ( 'publish' === get_post_status( $new_id ) && ! $gallery_mode ) {
				KV2PS_Redirects::refresh_post_redirect( $new_id );
			}
			self::$existing_source_map[ $source_id ] = (int) $new_id;

			++$result['imported'];
		}

		return $result;
	}

	private static function synchronize_existing_gallery( $post_id, $source ) {
		if ( ! self::is_empty_import( $post_id ) ) {
			return false;
		}

		$updated = wp_update_post(
			array(
				'ID'           => absint( $post_id ),
				'post_status'  => 'publish',
				'post_title'   => sanitize_text_field( $source->post_title ),
				'post_excerpt' => sanitize_textarea_field( $source->post_excerpt ),
				'post_content' => '',
			),
			true
		);
		if ( is_wp_error( $updated ) ) {
			return false;
		}

		update_post_meta( $post_id, '_kv2ps_source_wp_portfolio_url', get_permalink( $source->ID ) );
		update_post_meta( $post_id, '_kv2ps_publication_mode', KV2PS_Compatibility::MODE_GALLERY );
		self::synchronize_source_data( $post_id, $source );
		return true;
	}

	private static function is_empty_import( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		$content = trim( wp_strip_all_tags( (string) $post->post_content ) );
		$placeholder = trim( wp_strip_all_tags( __( 'Contenu à compléter après import depuis WP Portfolio.', 'kv2-portfolio-studio' ) ) );
		if ( $content && $content !== $placeholder ) {
			return false;
		}
		if ( trim( (string) $post->post_excerpt ) ) {
			return false;
		}

		foreach ( array( 'problem', 'intervention', 'result', 'materials', 'initial_state', 'constraints' ) as $field ) {
			if ( trim( (string) get_post_meta( $post_id, '_kv2ps_' . $field, true ) ) ) {
				return false;
			}
		}
		return true;
	}

	private static function synchronize_source_data( $post_id, $source ) {
		$image_ids = self::find_source_image_ids( $source->ID );
		if ( $image_ids ) {
			$thumbnail_id = (int) apply_filters( 'kv2ps_import_source_thumbnail_id', reset( $image_ids ), $source );
			if ( $thumbnail_id ) {
				set_post_thumbnail( $post_id, $thumbnail_id );
			}
			update_post_meta( $post_id, '_kv2ps_after_images', array_values( array_unique( array_map( 'absint', $image_ids ) ) ) );

			$candidate_url = self::find_editorial_page_candidate( $image_ids );
			if ( $candidate_url ) {
				update_post_meta( $post_id, '_kv2ps_destination_candidate_url', $candidate_url );
			}
		}

		$source_taxonomies = get_object_taxonomies( self::SOURCE_POST_TYPE, 'objects' );
		$term_snapshot      = array();
		foreach ( $source_taxonomies as $taxonomy ) {
			$terms = wp_get_post_terms( $source->ID, $taxonomy->name );
			if ( ! is_wp_error( $terms ) && $terms ) {
				$term_snapshot[ $taxonomy->label ] = wp_list_pluck( $terms, 'name' );
				self::map_terms( $post_id, $taxonomy->name, $terms );
			}
		}
		if ( $term_snapshot ) {
			update_post_meta( $post_id, '_kv2ps_imported_term_snapshot', $term_snapshot );
		}
	}

	private static function find_editorial_page_candidate( $image_ids ) {
		$image_ids  = array_values( array_filter( array_unique( array_map( 'absint', (array) $image_ids ) ) ) );
		$candidates = array();
		foreach ( $image_ids as $image_id ) {
			$page_ids = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'posts_per_page' => 3,
					'fields'         => 'ids',
					'meta_key'       => '_thumbnail_id',
					'meta_value'     => $image_id,
				)
			);
			$candidates = array_merge( $candidates, $page_ids );
		}
		$candidates = array_values( array_unique( array_map( 'absint', $candidates ) ) );
		return 1 === count( $candidates ) ? esc_url_raw( get_permalink( $candidates[0] ) ) : '';
	}

	private static function map_terms( $post_id, $source_taxonomy, $source_terms ) {
		$by_taxonomy = array();
		foreach ( $source_terms as $source_term ) {
			if ( ! is_object( $source_term ) || empty( $source_term->term_id ) || empty( $source_term->name ) ) {
				continue;
			}
			$target_taxonomy = self::target_taxonomy( $source_taxonomy, $source_term->name );
			if ( ! $target_taxonomy ) {
				continue;
			}
			$target_id = self::mapped_term_id( $target_taxonomy, $source_taxonomy, $source_term );
			if ( $target_id ) {
				$by_taxonomy[ $target_taxonomy ][] = $target_id;
			}
		}

		foreach ( $by_taxonomy as $taxonomy => $term_ids ) {
			wp_set_object_terms( $post_id, array_values( array_unique( array_map( 'absint', $term_ids ) ) ), $taxonomy, true );
		}
	}

	private static function mapped_term_id( $target_taxonomy, $source_taxonomy, $source_term ) {
		$existing = get_terms(
			array(
				'taxonomy'   => $target_taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
				'number'     => 1,
				'meta_query' => array(
					'relation' => 'AND',
					array( 'key' => '_kv2ps_source_taxonomy', 'value' => $source_taxonomy ),
					array( 'key' => '_kv2ps_source_term_id', 'value' => (string) absint( $source_term->term_id ) ),
				),
			)
		);
		if ( ! is_wp_error( $existing ) && $existing ) {
			return absint( $existing[0] );
		}

		$term = term_exists( $source_term->name, $target_taxonomy );
		if ( ! $term ) {
			$term = wp_insert_term(
				sanitize_text_field( $source_term->name ),
				$target_taxonomy,
				array( 'slug' => sanitize_title( $source_term->slug ?: $source_term->name ) )
			);
		}
		if ( is_wp_error( $term ) ) {
			return 0;
		}
		$term_id = is_array( $term ) ? absint( $term['term_id'] ) : absint( $term );
		if ( $term_id ) {
			update_term_meta( $term_id, '_kv2ps_source_taxonomy', sanitize_key( $source_taxonomy ) );
			update_term_meta( $term_id, '_kv2ps_source_term_id', (string) absint( $source_term->term_id ) );
		}
		return $term_id;
	}

	private static function target_taxonomy( $source_taxonomy, $name ) {
		if ( 'astra-portfolio-categories' === $source_taxonomy ) {
			return 'kv2_service';
		}
		if ( 'astra-portfolio-tags' === $source_taxonomy ) {
			return 'kv2_ville';
		}
		if ( 'astra-portfolio-other-categories' !== $source_taxonomy ) {
			return '';
		}

		$value = remove_accents( strtolower( (string) $name ) );
		if ( preg_match( '/\b(paris|yvelines|oise|seine|val d|ile de france|78|75|92|93|94|95|60)\b/', $value ) ) {
			return 'kv2_ville';
		}
		if ( preg_match( '/\b(cannage|rempaillage|paillage|garnissage|finition|nettoyage|patine|simili|cuir|tissu)\b/', $value ) ) {
			return 'kv2_technique';
		}
		if ( preg_match( '/\b(chaise|fauteuil|canape|meridienne|banquette|tabouret|tapis|siege|pouf)\b/', $value ) ) {
			return 'kv2_meuble';
		}
		return 'kv2_style';
	}

	public static function find_source_image_id( $source_id ) {
		$image_ids = self::find_source_image_ids( $source_id );
		return $image_ids ? (int) reset( $image_ids ) : 0;
	}

	public static function find_source_image_ids( $source_id ) {
		$image_ids   = array();
		$thumbnail_id = get_post_thumbnail_id( $source_id );
		if ( $thumbnail_id ) {
			$image_ids[] = (int) $thumbnail_id;
		}

		foreach ( array( 'astra-portfolio-image-id', 'astra-lightbox-image-id' ) as $meta_key ) {
			foreach ( (array) get_post_meta( $source_id, $meta_key, false ) as $value ) {
				$image_ids = array_merge( $image_ids, self::find_attachments_in_value( $value ) );
			}
		}

		if ( $image_ids ) {
			return array_values( array_unique( array_map( 'absint', $image_ids ) ) );
		}

		foreach ( get_post_meta( $source_id ) as $values ) {
			foreach ( (array) $values as $value ) {
				// get_post_meta() has already normalized serialized values.
				$image_ids = array_merge( $image_ids, self::find_attachments_in_value( $value ) );
			}
		}

		return array_values( array_unique( array_map( 'absint', $image_ids ) ) );
	}

	public static function maybe_repair_missing_thumbnails() {
		if ( ! current_user_can( 'manage_options' ) || KV2PS_VERSION === get_option( 'kv2ps_thumbnail_repair_version' ) ) {
			return;
		}

		$imported_ids = get_posts(
			array(
				'post_type'      => KV2PS_Post_Types::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 2000,
				'fields'         => 'ids',
				'meta_key'       => '_kv2ps_source_wp_portfolio_id',
			)
		);

		foreach ( $imported_ids as $post_id ) {
			if ( get_post_thumbnail_id( $post_id ) ) {
				continue;
			}
			$source_id = absint( get_post_meta( $post_id, '_kv2ps_source_wp_portfolio_id', true ) );
			$image_id  = $source_id ? self::find_source_image_id( $source_id ) : 0;
			if ( $image_id ) {
				set_post_thumbnail( $post_id, $image_id );
			}
		}

		update_option( 'kv2ps_thumbnail_repair_version', KV2PS_VERSION );
	}

	/**
	 * Recursively collect every image attachment referenced by a source value.
	 *
	 * WP Portfolio versions have stored image fields as scalar IDs, URLs, HTML
	 * fragments or serialized arrays. Returning only the first match silently
	 * dropped the remaining images when a single meta value contained a gallery.
	 */
	private static function find_attachments_in_value( $value ) {
		$image_ids = array();

		if ( is_array( $value ) ) {
			foreach ( $value as $child ) {
				$image_ids = array_merge( $image_ids, self::find_attachments_in_value( $child ) );
			}
			return array_values( array_unique( array_map( 'absint', $image_ids ) ) );
		}

		if ( is_numeric( $value ) ) {
			$attachment_id = absint( $value );
			if ( 'attachment' === get_post_type( $attachment_id ) && 0 === strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
				$image_ids[] = $attachment_id;
			}
		}

		if ( is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
			$attachment_id = attachment_url_to_postid( esc_url_raw( strtok( $value, '?' ) ) );
			if ( $attachment_id && 0 === strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
				$image_ids[] = $attachment_id;
			}
		}

		if ( is_string( $value ) && preg_match_all( '/wp-image-(\d+)/', $value, $matches ) ) {
			foreach ( $matches[1] as $matched_id ) {
				$attachment_id = absint( $matched_id );
				if ( $attachment_id && 0 === strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
					$image_ids[] = $attachment_id;
				}
			}
		}

		if ( is_string( $value ) && preg_match_all( '#https?://[^\s"\'<>]+#i', $value, $matches ) ) {
			foreach ( $matches[0] as $url ) {
				$attachment_id = attachment_url_to_postid( esc_url_raw( strtok( html_entity_decode( $url ), '?' ) ) );
				if ( $attachment_id && 0 === strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
					$image_ids[] = $attachment_id;
				}
			}
		}

		return array_values( array_unique( array_map( 'absint', $image_ids ) ) );
	}

	private static function find_existing( $source_id ) {
		if ( null === self::$existing_source_map ) {
			self::$existing_source_map = array();
			$posts = get_posts(
				array(
					'post_type'      => KV2PS_Post_Types::POST_TYPE,
					'post_status'    => 'any',
					'posts_per_page' => 2000,
					'fields'         => 'ids',
					'meta_key'       => '_kv2ps_source_wp_portfolio_id',
				)
			);
			foreach ( $posts as $post_id ) {
				$mapped_source = absint( get_post_meta( $post_id, '_kv2ps_source_wp_portfolio_id', true ) );
				if ( $mapped_source ) {
					self::$existing_source_map[ $mapped_source ] = absint( $post_id );
				}
			}
		}
		$source_id = absint( $source_id );
		return isset( self::$existing_source_map[ $source_id ] ) ? (int) self::$existing_source_map[ $source_id ] : 0;
	}
}
