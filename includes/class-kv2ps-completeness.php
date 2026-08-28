<?php

defined( 'ABSPATH' ) || exit;

final class KV2PS_Completeness {
	public static function init() {
		add_filter( 'manage_' . KV2PS_Post_Types::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . KV2PS_Post_Types::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'add_meta_boxes_' . KV2PS_Post_Types::POST_TYPE, array( __CLASS__, 'add_metabox' ) );
		add_action( 'wp_ajax_kv2ps_completeness_report', array( __CLASS__, 'ajax_report' ) );
	}

	public static function columns( $columns ) {
		$columns['kv2ps_completeness'] = __( 'Prêt à publier', 'kv2-portfolio-studio' );
		return $columns;
	}

	public static function column_content( $column, $post_id ) {
		if ( 'kv2ps_completeness' !== $column ) {
			return;
		}
		$report = self::report( $post_id );
		$class  = $report['ready'] ? 'is-ready' : ( $report['score'] >= 60 ? 'is-almost' : 'is-incomplete' );
		echo '<span class="kv2ps-score ' . esc_attr( $class ) . '"><strong>' . esc_html( $report['score'] ) . '%</strong></span>';
		if ( $report['missing'] ) {
			echo '<br><small>' . esc_html( implode( ', ', array_slice( $report['missing'], 0, 3 ) ) ) . ( count( $report['missing'] ) > 3 ? '…' : '' ) . '</small>';
		}
	}

	public static function add_metabox() {
		add_meta_box(
			'kv2ps-completeness',
			__( 'Checklist de publication', 'kv2-portfolio-studio' ),
			array( __CLASS__, 'render_metabox' ),
			KV2PS_Post_Types::POST_TYPE,
			'side',
			'high'
		);
	}

	public static function render_metabox( $post ) {
		$report = self::report( $post->ID );
		?>
		<div id="kv2ps-completeness-body" data-post-id="<?php echo esc_attr( $post->ID ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'kv2ps_completeness_' . $post->ID ) ); ?>">
			<?php self::render_report( $report ); ?>
		</div>
		<?php
	}

	private static function render_report( $report ) {
		?>
		<p class="kv2ps-score-summary"><strong><?php echo esc_html( $report['score'] ); ?>%</strong> — <?php echo esc_html( $report['ready'] ? __( 'prête à publier', 'kv2-portfolio-studio' ) : __( 'éléments essentiels à compléter', 'kv2-portfolio-studio' ) ); ?></p>
		<p class="kv2ps-checklist-heading"><strong><?php esc_html_e( 'Essentiels', 'kv2-portfolio-studio' ); ?></strong></p>
		<?php self::render_checks( $report['checks'] ); ?>
		<p class="kv2ps-checklist-heading"><strong><?php esc_html_e( 'Enrichissements facultatifs', 'kv2-portfolio-studio' ); ?></strong></p>
		<?php self::render_checks( $report['optional'] ); ?>
		<p class="description" id="kv2ps-checklist-note"><?php esc_html_e( 'La checklist se recalcule après chaque enregistrement.', 'kv2-portfolio-studio' ); ?></p>
		<p><button class="button button-small" id="kv2ps-refresh-checklist" type="button"><?php esc_html_e( 'Actualiser', 'kv2-portfolio-studio' ); ?></button></p>
		<?php
	}

	private static function render_checks( $checks ) {
		?>
		<ul class="kv2ps-checklist">
			<?php foreach ( $checks as $check ) :
				$class = $check['ok'] ? 'is-ok' : ( ! empty( $check['optional'] ) ? 'is-optional' : 'is-missing' );
				$icon  = $check['ok'] ? '✓' : ( ! empty( $check['optional'] ) ? '–' : '○' );
				?>
				<li class="<?php echo esc_attr( $class ); ?>" data-check-key="<?php echo esc_attr( $check['key'] ); ?>"><span aria-hidden="true"><?php echo esc_html( $icon ); ?></span> <?php if ( ! $check['ok'] && ! empty( $check['target'] ) ) : ?><a href="<?php echo esc_attr( $check['target'] ); ?>"><?php echo esc_html( $check['label'] ); ?></a><?php else : ?><?php echo esc_html( $check['label'] ); ?><?php endif; ?></li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	public static function report( $post_id ) {
		$image_ids = self::image_ids( $post_id );
		$story     = array_filter(
			array(
				get_post_meta( $post_id, '_kv2ps_problem', true ),
				get_post_meta( $post_id, '_kv2ps_intervention', true ),
				get_post_meta( $post_id, '_kv2ps_result', true ),
			)
		);
		$content   = trim( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) );
		$excerpt   = trim( (string) get_post_field( 'post_excerpt', $post_id ) );
		$classified = false;
		foreach ( KV2PS_Post_Types::taxonomies() as $taxonomy ) {
			if ( has_term( '', $taxonomy, $post_id ) ) {
				$classified = true;
				break;
			}
		}

		$checks = array(
			array( 'key' => 'title', 'label' => __( 'Titre', 'kv2-portfolio-studio' ), 'ok' => (bool) get_the_title( $post_id ), 'weight' => 20 ),
			array( 'key' => 'visual', 'label' => __( 'Image principale ou photo du projet', 'kv2-portfolio-studio' ), 'ok' => (bool) $image_ids, 'weight' => 20, 'target' => '#kv2ps-gallery-after' ),
			array( 'key' => 'content', 'label' => __( 'Contenu utile', 'kv2-portfolio-studio' ), 'ok' => (bool) ( $excerpt || $content || $story ), 'weight' => 20, 'target' => '#kv2ps-problem' ),
			array( 'key' => 'classification', 'label' => __( 'Au moins un classement', 'kv2-portfolio-studio' ), 'ok' => $classified, 'weight' => 15, 'target' => '#taxonomy-kv2_service' ),
			array( 'key' => 'location', 'label' => __( 'Ville ou confidentialité', 'kv2-portfolio-studio' ), 'ok' => has_term( '', 'kv2_ville', $post_id ) || (bool) get_post_meta( $post_id, '_kv2ps_confidential', true ), 'weight' => 10, 'target' => '#kv2ps-project-location' ),
			array( 'key' => 'alt', 'label' => __( 'ALT des images', 'kv2-portfolio-studio' ), 'ok' => (bool) $image_ids && self::images_have_alt( $image_ids ), 'weight' => 15, 'target' => '#kv2ps-gallery-after' ),
		);
		$optional = array(
			array( 'key' => 'excerpt', 'label' => __( 'Extrait éditorial', 'kv2-portfolio-studio' ), 'ok' => (bool) $excerpt, 'optional' => true ),
			array( 'key' => 'problem', 'label' => __( 'Besoin du client', 'kv2-portfolio-studio' ), 'ok' => (bool) get_post_meta( $post_id, '_kv2ps_problem', true ), 'optional' => true, 'target' => '#kv2ps-problem' ),
			array( 'key' => 'intervention', 'label' => __( 'Intervention', 'kv2-portfolio-studio' ), 'ok' => (bool) get_post_meta( $post_id, '_kv2ps_intervention', true ), 'optional' => true, 'target' => '#kv2ps-intervention' ),
			array( 'key' => 'result', 'label' => __( 'Résultat', 'kv2-portfolio-studio' ), 'ok' => (bool) get_post_meta( $post_id, '_kv2ps_result', true ), 'optional' => true, 'target' => '#kv2ps-result' ),
			array( 'key' => 'before', 'label' => __( 'Photos avant', 'kv2-portfolio-studio' ), 'ok' => (bool) KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_before_images', true ) ), 'optional' => true, 'target' => '#kv2ps-gallery-before' ),
			array( 'key' => 'after', 'label' => __( 'Photos après', 'kv2-portfolio-studio' ), 'ok' => (bool) KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_after_images', true ) ), 'optional' => true, 'target' => '#kv2ps-gallery-after' ),
			array( 'key' => 'service', 'label' => __( 'Service', 'kv2-portfolio-studio' ), 'ok' => has_term( '', 'kv2_service', $post_id ), 'optional' => true, 'target' => '#taxonomy-kv2_service' ),
			array( 'key' => 'furniture', 'label' => __( 'Type de meuble', 'kv2-portfolio-studio' ), 'ok' => has_term( '', 'kv2_meuble', $post_id ), 'optional' => true, 'target' => '#taxonomy-kv2_meuble' ),
			array( 'key' => 'style', 'label' => __( 'Style', 'kv2-portfolio-studio' ), 'ok' => has_term( '', 'kv2_style', $post_id ), 'optional' => true, 'target' => '#tagsdiv-kv2_style' ),
			array( 'key' => 'technique', 'label' => __( 'Technique', 'kv2-portfolio-studio' ), 'ok' => has_term( '', 'kv2_technique', $post_id ), 'optional' => true, 'target' => '#tagsdiv-kv2_technique' ),
		);

		$earned  = 0;
		$total   = 0;
		$missing = array();
		foreach ( $checks as $check ) {
			$total += $check['weight'];
			if ( $check['ok'] ) {
				$earned += $check['weight'];
			} else {
				$missing[] = $check['label'];
			}
		}

		return array(
			'score'   => $total ? (int) round( ( $earned / $total ) * 100 ) : 0,
			'ready'   => ! $missing,
			'missing' => $missing,
			'checks'  => $checks,
			'optional'=> $optional,
		);
	}

	private static function image_ids( $post_id ) {
		return array_unique(
			array_filter(
				array_merge(
					array( get_post_thumbnail_id( $post_id ) ),
					KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_before_images', true ) ),
					KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_after_images', true ) )
				)
			)
		);
	}

	private static function images_have_alt( $ids ) {
		if ( ! $ids ) {
			return false;
		}
		foreach ( $ids as $id ) {
			if ( '' === trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ) {
				return false;
			}
		}
		return true;
	}

	public static function ajax_report() {
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		check_ajax_referer( 'kv2ps_completeness_' . $post_id, 'nonce' );
		if ( ! $post_id || KV2PS_Post_Types::POST_TYPE !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Action non autorisée.', 'kv2-portfolio-studio' ) ), 403 );
		}

		ob_start();
		self::render_report( self::report( $post_id ) );
		$html = ob_get_clean();
		wp_send_json_success( array( 'html' => $html ) );
	}
}
