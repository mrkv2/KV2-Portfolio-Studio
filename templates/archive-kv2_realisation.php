<?php
/**
 * Default archive/taxonomy template. Copy to your-theme/kv2-portfolio-studio/archive-kv2_realisation.php to override.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$settings = KV2PS_Plugin::settings();
$is_term  = is_tax();
$title    = $is_term ? single_term_title( '', false ) : ( ! empty( $settings['archive_title'] ) ? $settings['archive_title'] : __( 'Nos réalisations', 'kv2-portfolio-studio' ) );
$intro    = $is_term ? term_description() : ( ! empty( $settings['archive_intro'] ) ? $settings['archive_intro'] : '' );
?>
<main id="primary" class="kv2ps-main kv2ps-archive">
	<header class="kv2ps-archive-header"><div class="kv2ps-container"><div class="kv2ps-eyebrow"><?php esc_html_e( 'Portfolio', 'kv2-portfolio-studio' ); ?></div><h1><?php echo esc_html( $title ); ?></h1><?php if ( $intro ) : ?><div class="kv2ps-lead"><?php echo wp_kses_post( wpautop( $intro ) ); ?></div><?php endif; ?></div></header>
	<div class="kv2ps-container kv2ps-archive-content">
		<?php if ( have_posts() ) : ?>
			<?php
			$display = KV2PS_Plugin::sanitize_display_settings(
				array(
					'layout'      => 'masonry',
					'columns'     => '3',
					'image_ratio' => 'auto',
					'card_style'  => 'classic',
					'load_mode'   => $settings['archive_load_mode'],
				)
			);
			global $wp_query;
			$next_url = get_next_posts_page_link( $wp_query->max_num_pages );
			?>
			<section class="kv2ps-collection" data-collection-key="archive" data-load-mode="<?php echo esc_attr( $display['load_mode'] ); ?>">
			<?php
			$active_service = isset( $_GET['kv2ps_service'] ) && is_string( $_GET['kv2ps_service'] ) ? sanitize_title( wp_unslash( $_GET['kv2ps_service'] ) ) : '';
			$search         = isset( $_GET['kv2ps_search'] ) && is_string( $_GET['kv2ps_search'] ) ? sanitize_text_field( wp_unslash( $_GET['kv2ps_search'] ) ) : '';
			KV2PS_Plugin::render_collection_controls(
				'archive',
				$active_service,
				$search,
				get_post_type_archive_link( KV2PS_Post_Types::POST_TYPE ),
				'1' === $settings['archive_show_filters'],
				'1' === $settings['archive_show_search']
			);
			?>
			<div class="<?php echo esc_attr( KV2PS_Plugin::collection_classes( $display ) ); ?>">
				<?php while ( have_posts() ) : the_post(); KV2PS_Plugin::instance()->render_card(); endwhile; ?>
			</div>
			<?php KV2PS_Plugin::render_collection_navigation( $wp_query->max_num_pages, max( 1, get_query_var( 'paged' ) ), $next_url ); ?>
			</section>
		<?php else : ?>
			<p><?php esc_html_e( 'Aucune réalisation publiée pour le moment.', 'kv2-portfolio-studio' ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( '1' === $settings['archive_show_cta'] ) : KV2PS_Plugin::render_cta( 0 ); endif; ?>
</main>
<?php
get_footer();
