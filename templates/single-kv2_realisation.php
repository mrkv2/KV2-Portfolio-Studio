<?php
/**
 * Default single template. Copy to your-theme/kv2-portfolio-studio/single-kv2_realisation.php to override.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$post_id      = get_the_ID();
	$settings     = KV2PS_Plugin::settings();
	$roofing      = KV2PS_Plugin::PROFILE_ROOFING === KV2PS_Plugin::business_profile( $settings );
	$before_ids   = KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_before_images', true ) );
	$after_ids    = KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_after_images', true ) );
	$project_date = get_post_meta( $post_id, '_kv2ps_project_date', true );
	$confidential = (bool) get_post_meta( $post_id, '_kv2ps_confidential', true );
	$portfolio_url = ! empty( $settings['portfolio_page_url'] ) ? esc_url_raw( $settings['portfolio_page_url'] ) : get_post_type_archive_link( KV2PS_Post_Types::POST_TYPE );
	$service_terms = get_the_terms( $post_id, 'kv2_service' );
	$story_labels  = array(
		'problem'      => __( 'Le besoin', 'kv2-portfolio-studio' ),
		'intervention' => __( 'Notre intervention', 'kv2-portfolio-studio' ),
		'result'       => __( 'Le résultat', 'kv2-portfolio-studio' ),
	);
	$story_items = array();
	foreach ( $story_labels as $story_key => $story_label ) {
		$story_value = get_post_meta( $post_id, '_kv2ps_' . $story_key, true );
		if ( $story_value ) {
			$story_items[] = array( 'label' => $story_label, 'value' => $story_value );
		}
	}
	$hero_image_id = get_post_thumbnail_id( $post_id );
	if ( ! $hero_image_id && $after_ids ) {
		$hero_image_id = (int) reset( $after_ids );
	}
	if ( ! $hero_image_id && $before_ids ) {
		$hero_image_id = (int) reset( $before_ids );
	}
	?>
	<main id="primary" class="kv2ps-main kv2ps-single">
		<article <?php post_class( 'kv2ps-project' ); ?>>
			<header class="kv2ps-hero">
				<div class="kv2ps-container kv2ps-hero__inner <?php echo $hero_image_id ? 'kv2ps-hero__inner--with-media' : 'kv2ps-hero__inner--without-media'; ?>">
					<div class="kv2ps-hero__copy">
						<?php
						if ( $service_terms && ! is_wp_error( $service_terms ) ) {
							echo '<nav class="kv2ps-taxonomy-chips" aria-label="' . esc_attr__( 'Catégories de la réalisation', 'kv2-portfolio-studio' ) . '">';
							foreach ( $service_terms as $service_term ) {
								$service_url = get_term_meta( $service_term->term_id, '_kv2ps_landing_url', true );
								$service_tax = get_taxonomy( 'kv2_service' );
								if ( ! $service_url && $service_tax && $service_tax->publicly_queryable ) {
									$service_url = get_term_link( $service_term );
								}
								if ( $service_url && ! is_wp_error( $service_url ) ) {
									echo '<a href="' . esc_url( $service_url ) . '">' . esc_html( $service_term->name ) . '</a>';
								} else {
									echo '<span>' . esc_html( $service_term->name ) . '</span>';
								}
							}
							echo '</nav>';
						}
						?>
						<h1><?php the_title(); ?></h1>
						<?php if ( has_excerpt() ) : ?><p class="kv2ps-lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
						<?php if ( $project_date ) : ?><p class="kv2ps-project-date"><span><?php esc_html_e( 'Projet réalisé en', 'kv2-portfolio-studio' ); ?></span> <time datetime="<?php echo esc_attr( $project_date ); ?>"><?php echo esc_html( wp_date( 'F Y', strtotime( $project_date ) ) ); ?></time></p><?php endif; ?>
					</div>
					<?php if ( $hero_image_id ) : ?><figure class="kv2ps-hero__media"><?php echo wp_get_attachment_image( $hero_image_id, 'full', false, array( 'fetchpriority' => 'high', 'loading' => 'eager' ) ); ?></figure><?php endif; ?>
					<?php if ( $portfolio_url ) : ?><div class="kv2ps-back-link-wrap"><a class="kv2ps-back-link" href="<?php echo esc_url( $portfolio_url ); ?>"><span aria-hidden="true">←</span> <?php esc_html_e( 'Retour à toutes les réalisations', 'kv2-portfolio-studio' ); ?></a></div><?php endif; ?>
				</div>
			</header>

			<div class="kv2ps-container kv2ps-project__content">
				<?php if ( $story_items ) : ?>
				<div class="kv2ps-story kv2ps-story--count-<?php echo esc_attr( count( $story_items ) ); ?>">
					<?php foreach ( $story_items as $story_item ) : ?>
						<section class="kv2ps-story__item"><h2><?php echo esc_html( $story_item['label'] ); ?></h2><p><?php echo nl2br( esc_html( $story_item['value'] ) ); ?></p></section>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>

				<?php if ( $before_ids || $after_ids ) : ?>
					<section class="kv2ps-comparison" aria-labelledby="kv2ps-comparison-title">
						<h2 id="kv2ps-comparison-title"><?php echo esc_html( $before_ids && $after_ids ? __( 'Avant / après', 'kv2-portfolio-studio' ) : ( $after_ids ? __( 'Résultat final', 'kv2-portfolio-studio' ) : __( 'État initial', 'kv2-portfolio-studio' ) ) ); ?></h2>
						<?php if ( 'slider' === $settings['before_after_mode'] && $before_ids && $after_ids ) : ?>
							<div class="kv2ps-comparison__sliders">
							<?php for ( $pair = 0; $pair < min( count( $before_ids ), count( $after_ids ) ); $pair++ ) : ?>
								<figure class="kv2ps-before-after" style="--kv2ps-position:50%">
									<div class="kv2ps-before-after__image kv2ps-before-after__after"><?php echo wp_get_attachment_image( $after_ids[ $pair ], 'large', false, array( 'loading' => 'lazy' ) ); ?><span><?php esc_html_e( 'Après', 'kv2-portfolio-studio' ); ?></span></div>
									<div class="kv2ps-before-after__image kv2ps-before-after__before"><?php echo wp_get_attachment_image( $before_ids[ $pair ], 'large', false, array( 'loading' => 'lazy' ) ); ?><span><?php esc_html_e( 'Avant', 'kv2-portfolio-studio' ); ?></span></div>
									<label class="screen-reader-text" for="kv2ps-comparison-<?php echo esc_attr( $pair ); ?>"><?php esc_html_e( 'Déplacer pour comparer la photo avant et la photo après', 'kv2-portfolio-studio' ); ?></label>
									<input id="kv2ps-comparison-<?php echo esc_attr( $pair ); ?>" max="100" min="0" type="range" value="50">
								</figure>
							<?php endfor; ?>
							</div>
						<?php else : ?>
						<div class="kv2ps-comparison__columns">
							<?php foreach ( array( 'Avant' => $before_ids, 'Après' => $after_ids ) as $label => $ids ) :
								if ( ! $ids ) {
									continue;
								}
								?>
								<div class="kv2ps-gallery-group"><h3><?php echo esc_html( $label ); ?></h3><div class="kv2ps-gallery">
									<?php foreach ( $ids as $image_id ) : ?><figure><?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'loading' => 'lazy' ) ); ?><?php if ( wp_get_attachment_caption( $image_id ) ) : ?><figcaption><?php echo esc_html( wp_get_attachment_caption( $image_id ) ); ?></figcaption><?php endif; ?></figure><?php endforeach; ?>
								</div></div>
							<?php endforeach; ?>
						</div>
						<?php endif; ?>
					</section>
				<?php endif; ?>

				<?php
				$materials         = get_post_meta( $post_id, '_kv2ps_materials', true );
				$testimonial       = get_post_meta( $post_id, '_kv2ps_testimonial', true );
				$testimonial_ready = $testimonial && get_post_meta( $post_id, '_kv2ps_testimonial_consent', true );
				$editor_content     = trim( (string) get_post_field( 'post_content', $post_id ) );
				$detail_items       = array();
				$detail_fields      = array(
					'_kv2ps_duration'      => __( 'Durée', 'kv2-portfolio-studio' ),
					'_kv2ps_work_type'     => $roofing ? __( 'Type d’intervention', 'kv2-portfolio-studio' ) : __( 'Transformation', 'kv2-portfolio-studio' ),
					'_kv2ps_initial_state' => __( 'État initial', 'kv2-portfolio-studio' ),
					'_kv2ps_constraints'   => __( 'Contraintes', 'kv2-portfolio-studio' ),
					'_kv2ps_price_range'   => __( 'Fourchette indicative', 'kv2-portfolio-studio' ),
				);
				foreach ( $detail_fields as $meta_key => $detail_label ) {
					$detail_value = get_post_meta( $post_id, $meta_key, true );
					if ( $detail_value ) {
						$detail_items[] = array( 'label' => $detail_label, 'value' => esc_html( $detail_value ) );
					}
				}
				foreach ( KV2PS_Post_Types::taxonomies() as $taxonomy ) {
					if ( $confidential && 'kv2_ville' === $taxonomy ) {
						continue;
					}
					$taxonomy_object = get_taxonomy( $taxonomy );
					$terms           = get_the_terms( $post_id, $taxonomy );
					if ( ! $taxonomy_object || ! $terms || is_wp_error( $terms ) ) {
						continue;
					}
					$links = array();
					foreach ( $terms as $term ) {
						$url = get_term_meta( $term->term_id, '_kv2ps_landing_url', true );
						if ( ! $url && $taxonomy_object->publicly_queryable ) {
							$url = get_term_link( $term );
						}
						$links[] = is_wp_error( $url ) ? esc_html( $term->name ) : '<a href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a>';
					}
					$detail_items[] = array( 'label' => $taxonomy_object->labels->singular_name, 'value' => implode( ', ', $links ) );
				}

				$all_image_ids = array_unique( array_filter( array_merge( array( get_post_thumbnail_id( $post_id ) ), $before_ids, $after_ids ) ) );
				$credits       = array();
				$copyrights    = array();
				$licenses      = array();
				foreach ( $all_image_ids as $image_id ) {
					$credits[]    = get_post_meta( $image_id, '_kv2ps_credit', true ) ?: $settings['image_credit'];
					$copyrights[] = get_post_meta( $image_id, '_kv2ps_copyright', true ) ?: $settings['image_copyright'];
					$licenses[]   = get_post_meta( $image_id, '_kv2ps_license_url', true ) ?: $settings['image_license_url'];
				}
				$credits            = array_unique( array_filter( $credits ) );
				$copyrights         = array_unique( array_filter( $copyrights ) );
				$licenses           = array_unique( array_filter( $licenses ) );
				$has_image_rights   = $credits || $copyrights || $licenses;
				$has_primary_column = $editor_content || $testimonial_ready;
				$has_side_column    = $materials || $detail_items || $has_image_rights;
				$layout_modifier    = $has_primary_column && $has_side_column ? '' : ( $has_primary_column ? ' kv2ps-project-layout--content-only' : ' kv2ps-project-layout--sidebar-only' );
				?>
				<?php if ( $has_primary_column || $has_side_column ) : ?>
				<div class="kv2ps-project-layout<?php echo esc_attr( $layout_modifier ); ?>">
					<?php if ( $editor_content ) : ?><div class="kv2ps-editor-content"><?php the_content(); ?></div><?php endif; ?>
					<?php if ( $materials ) : ?><aside class="kv2ps-note"><h2><?php echo esc_html( $roofing ? __( 'Matériaux et fournitures', 'kv2-portfolio-studio' ) : __( 'Matières et finitions', 'kv2-portfolio-studio' ) ); ?></h2><p><?php echo nl2br( esc_html( $materials ) ); ?></p></aside><?php endif; ?>
					<?php if ( $testimonial_ready ) :
						$testimonial_author = $confidential ? __( 'Client', 'kv2-portfolio-studio' ) : get_post_meta( $post_id, '_kv2ps_testimonial_author', true );
						$testimonial_source = get_post_meta( $post_id, '_kv2ps_testimonial_source', true );
						$testimonial_url    = get_post_meta( $post_id, '_kv2ps_testimonial_source_url', true );
						$testimonial_rating = absint( get_post_meta( $post_id, '_kv2ps_testimonial_rating', true ) );
						?><blockquote class="kv2ps-testimonial"><p>“<?php echo esc_html( $testimonial ); ?>”</p><?php if ( $testimonial_author || $testimonial_source ) : ?><footer>— <?php echo esc_html( $testimonial_author ); ?><?php if ( $testimonial_source ) : ?>, <?php if ( $testimonial_url ) : ?><a href="<?php echo esc_url( $testimonial_url ); ?>" rel="noopener noreferrer"><?php echo esc_html( $testimonial_source ); ?></a><?php else : ?><?php echo esc_html( $testimonial_source ); ?><?php endif; ?><?php endif; ?><?php if ( $testimonial_rating ) : ?> · <?php echo esc_html( sprintf( __( '%d/5', 'kv2-portfolio-studio' ), $testimonial_rating ) ); ?><?php endif; ?></footer><?php endif; ?></blockquote><?php endif; ?>
					<?php if ( $detail_items ) : ?><section class="kv2ps-project-meta" aria-labelledby="kv2ps-meta-title"><h2 id="kv2ps-meta-title"><?php esc_html_e( 'Détails de la réalisation', 'kv2-portfolio-studio' ); ?></h2><ul><?php foreach ( $detail_items as $detail_item ) : ?><li><strong><?php echo esc_html( $detail_item['label'] ); ?> :</strong> <?php echo wp_kses_post( $detail_item['value'] ); ?></li><?php endforeach; ?></ul></section><?php endif; ?>
					<?php if ( $has_image_rights ) : ?><aside class="kv2ps-image-rights"><strong><?php esc_html_e( 'Crédits images :', 'kv2-portfolio-studio' ); ?></strong> <?php echo esc_html( implode( ', ', $credits ) ); ?><?php if ( $copyrights ) : ?> · © <?php echo esc_html( implode( ', ', $copyrights ) ); ?><?php endif; ?><?php foreach ( $licenses as $license_index => $license_url ) : ?> · <a href="<?php echo esc_url( $license_url ); ?>"><?php echo esc_html( 0 === $license_index ? __( 'Conditions d’utilisation', 'kv2-portfolio-studio' ) : sprintf( __( 'Licence %d', 'kv2-portfolio-studio' ), $license_index + 1 ) ); ?></a><?php endforeach; ?></aside><?php endif; ?>
				</div>
				<?php endif; ?>
			</div>
		</article>

		<?php
		$previous_project = KV2PS_Compatibility::adjacent_case_study( $post_id, 'previous' );
		$next_project     = KV2PS_Compatibility::adjacent_case_study( $post_id, 'next' );
		if ( $previous_project || $next_project ) :
			?>
			<nav class="kv2ps-project-nav" aria-label="<?php esc_attr_e( 'Naviguer entre les réalisations', 'kv2-portfolio-studio' ); ?>">
				<div class="kv2ps-container kv2ps-project-nav__inner">
					<?php if ( $previous_project ) : ?>
						<a class="kv2ps-project-nav__link kv2ps-project-nav__link--previous" href="<?php echo esc_url( get_permalink( $previous_project ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Réalisation précédente : %s', 'kv2-portfolio-studio' ), get_the_title( $previous_project ) ) ); ?>">
							<span class="kv2ps-project-nav__arrow" aria-hidden="true">←</span><span><small><?php esc_html_e( 'Réalisation précédente', 'kv2-portfolio-studio' ); ?></small><strong><?php echo esc_html( get_the_title( $previous_project ) ); ?></strong></span>
						</a>
					<?php else : ?><span></span><?php endif; ?>
					<?php if ( $next_project ) : ?>
						<a class="kv2ps-project-nav__link kv2ps-project-nav__link--next" href="<?php echo esc_url( get_permalink( $next_project ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Réalisation suivante : %s', 'kv2-portfolio-studio' ), get_the_title( $next_project ) ) ); ?>">
							<span><small><?php esc_html_e( 'Réalisation suivante', 'kv2-portfolio-studio' ); ?></small><strong><?php echo esc_html( get_the_title( $next_project ) ); ?></strong></span><span class="kv2ps-project-nav__arrow" aria-hidden="true">→</span>
						</a>
					<?php endif; ?>
				</div>
			</nav>
			<?php
		endif;
		?>

		<?php
		$service_ids = wp_get_post_terms( $post_id, 'kv2_service', array( 'fields' => 'ids' ) );
		$related_args = array(
			'post_type'           => KV2PS_Post_Types::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => 3,
			'post__not_in'        => array( $post_id ),
			'ignore_sticky_posts' => true,
			'meta_query'          => array(
				'relation' => 'AND',
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
				array(
					'relation' => 'OR',
					array( 'key' => '_kv2ps_publication_mode', 'compare' => 'NOT EXISTS' ),
					array( 'key' => '_kv2ps_publication_mode', 'value' => KV2PS_Compatibility::MODE_GALLERY, 'compare' => '!=' ),
				),
			),
		);
		if ( ! is_wp_error( $service_ids ) && $service_ids ) {
			$related_args['tax_query'] = array(
				array(
					'taxonomy' => 'kv2_service',
					'field'    => 'term_id',
					'terms'    => $service_ids,
				),
			);
		}
		$related = new WP_Query( $related_args );
		if ( $related->have_posts() ) :
			?>
			<section class="kv2ps-related" aria-labelledby="kv2ps-related-title"><div class="kv2ps-container"><div class="kv2ps-related__header"><div><span class="kv2ps-eyebrow"><?php esc_html_e( 'À découvrir ensuite', 'kv2-portfolio-studio' ); ?></span><h2 id="kv2ps-related-title"><?php esc_html_e( 'D’autres réalisations', 'kv2-portfolio-studio' ); ?></h2><?php if ( $service_terms && ! is_wp_error( $service_terms ) ) : ?><p><?php esc_html_e( 'Une sélection dans les mêmes catégories que ce projet.', 'kv2-portfolio-studio' ); ?></p><?php endif; ?></div><?php if ( $portfolio_url ) : ?><a class="kv2ps-related__all" href="<?php echo esc_url( $portfolio_url ); ?>"><?php esc_html_e( 'Toutes les réalisations', 'kv2-portfolio-studio' ); ?> <span aria-hidden="true">→</span></a><?php endif; ?></div><div class="kv2ps-grid kv2ps-layout-masonry kv2ps-cols-3 kv2ps-ratio-auto kv2ps-card-style-classic">
			<?php while ( $related->have_posts() ) : $related->the_post(); KV2PS_Plugin::instance()->render_card(); endwhile; ?>
			</div></div></section>
			<?php
		endif;
		wp_reset_postdata();
		?>
		<?php if ( $portfolio_url ) : ?><nav class="kv2ps-back-bar" aria-label="<?php esc_attr_e( 'Retour au portfolio', 'kv2-portfolio-studio' ); ?>"><div class="kv2ps-container"><a class="kv2ps-back-link" href="<?php echo esc_url( $portfolio_url ); ?>"><span aria-hidden="true">←</span> <?php esc_html_e( 'Retour à toutes les réalisations', 'kv2-portfolio-studio' ); ?></a></div></nav><?php endif; ?>

		<?php KV2PS_Plugin::render_cta( $post_id ); ?>
	</main>
	<?php
endwhile;

get_footer();
