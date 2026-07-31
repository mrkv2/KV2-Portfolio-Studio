<?php

defined( 'ABSPATH' ) || exit;

final class KV2PS_Importer {
	const SOURCE_POST_TYPE = 'astra-portfolio';

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$result = null;
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) && isset( $_POST['kv2ps_import_action'] ) ) {
			check_admin_referer( 'kv2ps_import_wp_portfolio', 'kv2ps_import_nonce' );
			$ids    = isset( $_POST['source_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['source_ids'] ) ) : array();
			$result = self::import( $ids );
		}

		$available = post_type_exists( self::SOURCE_POST_TYPE );
		$sources   = $available ? get_posts(
			array(
				'post_type'      => self::SOURCE_POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		) : array();
		?>
		<div class="wrap kv2ps-admin">
			<h1><?php esc_html_e( 'Importer depuis WP Portfolio', 'kv2-portfolio-studio' ); ?></h1>
			<p><?php esc_html_e( 'L’import est non destructif : chaque élément choisi devient un brouillon KV2. La source WP Portfolio reste intacte et peut servir de filet de sécurité pendant la migration.', 'kv2-portfolio-studio' ); ?></p>

			<?php if ( $result ) : ?>
				<div class="notice notice-success inline"><p><?php echo esc_html( sprintf( __( '%1$d importé(s), %2$d déjà importé(s), %3$d erreur(s).', 'kv2-portfolio-studio' ), $result['imported'], $result['skipped'], count( $result['errors'] ) ) ); ?></p></div>
				<?php foreach ( $result['errors'] as $error ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $error ); ?></p></div><?php endforeach; ?>
			<?php endif; ?>

			<?php if ( ! $available ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'Le type de contenu astra-portfolio n’est pas actif. Activez temporairement WP Portfolio pour effectuer l’import, puis revenez ici.', 'kv2-portfolio-studio' ); ?></p></div>
			<?php elseif ( ! $sources ) : ?>
				<p><?php esc_html_e( 'Aucun élément WP Portfolio détecté.', 'kv2-portfolio-studio' ); ?></p>
			<?php else : ?>
				<form method="post">
					<?php wp_nonce_field( 'kv2ps_import_wp_portfolio', 'kv2ps_import_nonce' ); ?>
					<table class="widefat striped">
						<thead><tr><td class="check-column"><input id="kv2ps-select-all" type="checkbox"></td><th><?php esc_html_e( 'Titre source', 'kv2-portfolio-studio' ); ?></th><th><?php esc_html_e( 'État', 'kv2-portfolio-studio' ); ?></th><th><?php esc_html_e( 'Migration', 'kv2-portfolio-studio' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $sources as $source ) :
							$existing = self::find_existing( $source->ID );
							?>
							<tr>
								<th class="check-column"><input class="kv2ps-source-checkbox" name="source_ids[]" type="checkbox" value="<?php echo esc_attr( $source->ID ); ?>" <?php disabled( (bool) $existing ); ?>></th>
								<td><strong><?php echo esc_html( get_the_title( $source ) ?: sprintf( '#%d', $source->ID ) ); ?></strong></td>
								<td><?php echo esc_html( get_post_status_object( $source->post_status )->label ); ?></td>
								<td><?php echo $existing ? wp_kses_post( sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $existing ) ), esc_html__( 'Déjà importé', 'kv2-portfolio-studio' ) ) ) : esc_html__( 'Prêt', 'kv2-portfolio-studio' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<p><button class="button button-primary" name="kv2ps_import_action" type="submit" value="import" onclick="return window.confirm('<?php echo esc_js( __( 'Créer les brouillons sélectionnés ?', 'kv2-portfolio-studio' ) ); ?>');"><?php esc_html_e( 'Importer la sélection en brouillons', 'kv2-portfolio-studio' ); ?></button></p>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function import( $source_ids ) {
		$result = array( 'imported' => 0, 'skipped' => 0, 'errors' => array() );

		foreach ( array_unique( array_filter( $source_ids ) ) as $source_id ) {
			$source = get_post( $source_id );
			if ( ! $source || self::SOURCE_POST_TYPE !== $source->post_type ) {
				$result['errors'][] = sprintf( __( 'Source #%d introuvable.', 'kv2-portfolio-studio' ), $source_id );
				continue;
			}
			if ( self::find_existing( $source_id ) ) {
				++$result['skipped'];
				continue;
			}

			$content = wp_kses_post( apply_filters( 'kv2ps_import_source_content', trim( $source->post_content ), $source ) );
			if ( ! $content ) {
				$content = sprintf(
					'<p>%s</p>',
					esc_html__( 'Contenu à compléter après import depuis WP Portfolio.', 'kv2-portfolio-studio' )
				);
			}

			$new_id = wp_insert_post(
				array(
					'post_type'    => KV2PS_Post_Types::POST_TYPE,
					'post_status'  => 'draft',
					'post_title'   => sanitize_text_field( $source->post_title ),
					'post_excerpt' => sanitize_textarea_field( $source->post_excerpt ),
					'post_content' => wp_slash( $content ),
					'post_author'  => get_current_user_id(),
				),
				true
			);

			if ( is_wp_error( $new_id ) ) {
				$result['errors'][] = sprintf( __( '%1$s : %2$s', 'kv2-portfolio-studio' ), $source->post_title, $new_id->get_error_message() );
				continue;
			}

			update_post_meta( $new_id, '_kv2ps_source_wp_portfolio_id', $source_id );
			update_post_meta( $new_id, '_kv2ps_source_wp_portfolio_url', get_permalink( $source_id ) );

			$thumbnail_id = (int) apply_filters( 'kv2ps_import_source_thumbnail_id', self::find_source_image_id( $source_id ), $source );
			if ( $thumbnail_id ) {
				set_post_thumbnail( $new_id, $thumbnail_id );
			}

			$source_taxonomies = get_object_taxonomies( self::SOURCE_POST_TYPE, 'objects' );
			$term_snapshot      = array();
			foreach ( $source_taxonomies as $taxonomy ) {
				$terms = wp_get_post_terms( $source_id, $taxonomy->name, array( 'fields' => 'names' ) );
				if ( ! is_wp_error( $terms ) && $terms ) {
					$term_snapshot[ $taxonomy->label ] = $terms;
				}
			}
			if ( $term_snapshot ) {
				update_post_meta( $new_id, '_kv2ps_imported_term_snapshot', $term_snapshot );
			}

			++$result['imported'];
		}

		return $result;
	}

	public static function find_source_image_id( $source_id ) {
		$thumbnail_id = get_post_thumbnail_id( $source_id );
		if ( $thumbnail_id ) {
			return (int) $thumbnail_id;
		}

		foreach ( get_post_meta( $source_id ) as $values ) {
			foreach ( (array) $values as $value ) {
				// get_post_meta() has already normalized serialized values.
				$attachment_id = self::find_attachment_in_value( $value );
				if ( $attachment_id ) {
					return $attachment_id;
				}
			}
		}

		return 0;
	}

	public static function maybe_repair_missing_thumbnails() {
		if ( ! current_user_can( 'manage_options' ) || KV2PS_VERSION === get_option( 'kv2ps_thumbnail_repair_version' ) ) {
			return;
		}

		$imported_ids = get_posts(
			array(
				'post_type'      => KV2PS_Post_Types::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 500,
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

	private static function find_attachment_in_value( $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $child ) {
				$attachment_id = self::find_attachment_in_value( $child );
				if ( $attachment_id ) {
					return $attachment_id;
				}
			}
			return 0;
		}

		if ( is_numeric( $value ) ) {
			$attachment_id = absint( $value );
			if ( 'attachment' === get_post_type( $attachment_id ) && 0 === strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
				return $attachment_id;
			}
		}

		if ( is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
			$attachment_id = attachment_url_to_postid( esc_url_raw( strtok( $value, '?' ) ) );
			if ( $attachment_id && 0 === strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
				return $attachment_id;
			}
		}

		if ( is_string( $value ) && preg_match( '/wp-image-(\d+)/', $value, $matches ) ) {
			$attachment_id = absint( $matches[1] );
			if ( $attachment_id && 0 === strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
				return $attachment_id;
			}
		}

		if ( is_string( $value ) && preg_match_all( '#https?://[^\s"\'<>]+#i', $value, $matches ) ) {
			foreach ( $matches[0] as $url ) {
				$attachment_id = attachment_url_to_postid( esc_url_raw( strtok( html_entity_decode( $url ), '?' ) ) );
				if ( $attachment_id && 0 === strpos( (string) get_post_mime_type( $attachment_id ), 'image/' ) ) {
					return $attachment_id;
				}
			}
		}

		return 0;
	}

	private static function find_existing( $source_id ) {
		$posts = get_posts(
			array(
				'post_type'      => KV2PS_Post_Types::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_kv2ps_source_wp_portfolio_id',
				'meta_value'     => absint( $source_id ),
			)
		);
		return $posts ? (int) $posts[0] : 0;
	}
}
