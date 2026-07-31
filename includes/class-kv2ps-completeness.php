<?php

defined( 'ABSPATH' ) || exit;

final class KV2PS_Completeness {
	public static function init() {
		add_filter( 'manage_' . KV2PS_Post_Types::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . KV2PS_Post_Types::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'add_meta_boxes_' . KV2PS_Post_Types::POST_TYPE, array( __CLASS__, 'add_metabox' ) );
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
		$class  = $report['score'] >= 85 ? 'is-ready' : ( $report['score'] >= 60 ? 'is-almost' : 'is-incomplete' );
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
		<p class="kv2ps-score-summary"><strong><?php echo esc_html( $report['score'] ); ?>%</strong> — <?php echo esc_html( $report['score'] >= 85 ? __( 'prête à publier', 'kv2-portfolio-studio' ) : __( 'à compléter', 'kv2-portfolio-studio' ) ); ?></p>
		<ul class="kv2ps-checklist">
			<?php foreach ( $report['checks'] as $check ) : ?>
				<li class="<?php echo esc_attr( $check['ok'] ? 'is-ok' : 'is-missing' ); ?>"><span aria-hidden="true"><?php echo $check['ok'] ? '✓' : '○'; ?></span> <?php echo esc_html( $check['label'] ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	public static function report( $post_id ) {
		$checks = array(
			array( 'label' => __( 'Titre', 'kv2-portfolio-studio' ), 'ok' => (bool) get_the_title( $post_id ), 'weight' => 8 ),
			array( 'label' => __( 'Extrait', 'kv2-portfolio-studio' ), 'ok' => (bool) get_post_field( 'post_excerpt', $post_id ), 'weight' => 8 ),
			array( 'label' => __( 'Image principale', 'kv2-portfolio-studio' ), 'ok' => (bool) get_post_thumbnail_id( $post_id ), 'weight' => 10 ),
			array( 'label' => __( 'Besoin du client', 'kv2-portfolio-studio' ), 'ok' => (bool) get_post_meta( $post_id, '_kv2ps_problem', true ), 'weight' => 10 ),
			array( 'label' => __( 'Intervention', 'kv2-portfolio-studio' ), 'ok' => (bool) get_post_meta( $post_id, '_kv2ps_intervention', true ), 'weight' => 10 ),
			array( 'label' => __( 'Résultat', 'kv2-portfolio-studio' ), 'ok' => (bool) get_post_meta( $post_id, '_kv2ps_result', true ), 'weight' => 10 ),
			array( 'label' => __( 'Photos avant', 'kv2-portfolio-studio' ), 'ok' => (bool) KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_before_images', true ) ), 'weight' => 8 ),
			array( 'label' => __( 'Photos après', 'kv2-portfolio-studio' ), 'ok' => (bool) KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_after_images', true ) ), 'weight' => 8 ),
			array( 'label' => __( 'Service', 'kv2-portfolio-studio' ), 'ok' => has_term( '', 'kv2_service', $post_id ), 'weight' => 7 ),
			array( 'label' => __( 'Ville ou confidentialité', 'kv2-portfolio-studio' ), 'ok' => has_term( '', 'kv2_ville', $post_id ) || (bool) get_post_meta( $post_id, '_kv2ps_confidential', true ), 'weight' => 7 ),
			array( 'label' => __( 'ALT des images', 'kv2-portfolio-studio' ), 'ok' => self::images_have_alt( $post_id ), 'weight' => 7 ),
			array( 'label' => __( 'Titre SEO Rank Math', 'kv2-portfolio-studio' ), 'ok' => (bool) get_post_meta( $post_id, 'rank_math_title', true ), 'weight' => 4 ),
			array( 'label' => __( 'Description SEO Rank Math', 'kv2-portfolio-studio' ), 'ok' => (bool) get_post_meta( $post_id, 'rank_math_description', true ), 'weight' => 3 ),
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
			'missing' => $missing,
			'checks'  => $checks,
		);
	}

	private static function images_have_alt( $post_id ) {
		$ids = array_unique(
			array_filter(
				array_merge(
					array( get_post_thumbnail_id( $post_id ) ),
					KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_before_images', true ) ),
					KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_after_images', true ) )
				)
			)
		);
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
}
