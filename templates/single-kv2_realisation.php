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
	$before_ids   = KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_before_images', true ) );
	$after_ids    = KV2PS_Post_Types::sanitize_ids( get_post_meta( $post_id, '_kv2ps_after_images', true ) );
	$project_date = get_post_meta( $post_id, '_kv2ps_project_date', true );
	$confidential = (bool) get_post_meta( $post_id, '_kv2ps_confidential', true );
	?>
	<main id="primary" class="kv2ps-main kv2ps-single">
		<article <?php post_class( 'kv2ps-project' ); ?>>
			<header class="kv2ps-hero">
				<div class="kv2ps-container kv2ps-hero__inner">
					<div class="kv2ps-hero__copy">
						<?php
						$services = get_the_term_list( $post_id, 'kv2_service', '', ' · ' );
						if ( $services ) {
							echo '<div class="kv2ps-eyebrow">' . wp_kses_post( $services ) . '</div>';
						}
						?>
						<h1><?php the_title(); ?></h1>
						<?php if ( has_excerpt() ) : ?><p class="kv2ps-lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
						<?php if ( $project_date ) : ?><p class="kv2ps-project-date"><span><?php esc_html_e( 'Projet réalisé en', 'kv2-portfolio-studio' ); ?></span> <time datetime="<?php echo esc_attr( $project_date ); ?>"><?php echo esc_html( wp_date( 'F Y', strtotime( $project_date ) ) ); ?></time></p><?php endif; ?>
					</div>
					<?php if ( has_post_thumbnail() ) : ?><figure class="kv2ps-hero__media"><?php the_post_thumbnail( 'full', array( 'fetchpriority' => 'high' ) ); ?></figure><?php endif; ?>
				</div>
			</header>

			<div class="kv2ps-container kv2ps-project__content">
				<?php
				$story_fields = array(
					'problem'      => __( 'Le besoin', 'kv2-portfolio-studio' ),
					'intervention' => __( 'Notre intervention', 'kv2-portfolio-studio' ),
					'result'       => __( 'Le résultat', 'kv2-portfolio-studio' ),
				);
				?>
				<div class="kv2ps-story">
					<?php foreach ( $story_fields as $key => $label ) :
						$value = get_post_meta( $post_id, '_kv2ps_' . $key, true );
						if ( ! $value ) {
							continue;
						}
						?>
						<section class="kv2ps-story__item"><h2><?php echo esc_html( $label ); ?></h2><p><?php echo nl2br( esc_html( $value ) ); ?></p></section>
					<?php endforeach; ?>
				</div>

				<?php if ( $before_ids || $after_ids ) : ?>
					<section class="kv2ps-comparison" aria-labelledby="kv2ps-comparison-title">
						<h2 id="kv2ps-comparison-title"><?php esc_html_e( 'Avant / après', 'kv2-portfolio-studio' ); ?></h2>
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

				<div class="kv2ps-editor-content">
					<?php the_content(); ?>
				</div>

				<?php
				$materials   = get_post_meta( $post_id, '_kv2ps_materials', true );
				$testimonial = get_post_meta( $post_id, '_kv2ps_testimonial', true );
				?>
				<?php if ( $materials ) : ?><aside class="kv2ps-note"><h2><?php esc_html_e( 'Matières et finitions', 'kv2-portfolio-studio' ); ?></h2><p><?php echo nl2br( esc_html( $materials ) ); ?></p></aside><?php endif; ?>
				<?php if ( $testimonial && get_post_meta( $post_id, '_kv2ps_testimonial_consent', true ) ) :
					$testimonial_author = $confidential ? __( 'Client', 'kv2-portfolio-studio' ) : get_post_meta( $post_id, '_kv2ps_testimonial_author', true );
					$testimonial_source = get_post_meta( $post_id, '_kv2ps_testimonial_source', true );
					$testimonial_url    = get_post_meta( $post_id, '_kv2ps_testimonial_source_url', true );
					$testimonial_rating = absint( get_post_meta( $post_id, '_kv2ps_testimonial_rating', true ) );
					?>
					<blockquote class="kv2ps-testimonial"><p>“<?php echo esc_html( $testimonial ); ?>”</p><?php if ( $testimonial_author || $testimonial_source ) : ?><footer>— <?php echo esc_html( $testimonial_author ); ?><?php if ( $testimonial_source ) : ?>, <?php if ( $testimonial_url ) : ?><a href="<?php echo esc_url( $testimonial_url ); ?>" rel="noopener noreferrer"><?php echo esc_html( $testimonial_source ); ?></a><?php else : ?><?php echo esc_html( $testimonial_source ); ?><?php endif; ?><?php endif; ?><?php if ( $testimonial_rating ) : ?> · <?php echo esc_html( sprintf( __( '%d/5', 'kv2-portfolio-studio' ), $testimonial_rating ) ); ?><?php endif; ?></footer><?php endif; ?></blockquote>
				<?php endif; ?>

				<section class="kv2ps-project-meta" aria-labelledby="kv2ps-meta-title">
					<h2 id="kv2ps-meta-title"><?php esc_html_e( 'Détails de la réalisation', 'kv2-portfolio-studio' ); ?></h2>
					<ul>
					<?php
					$detail_fields = array(
						'_kv2ps_duration'      => __( 'Durée', 'kv2-portfolio-studio' ),
						'_kv2ps_work_type'     => __( 'Transformation', 'kv2-portfolio-studio' ),
						'_kv2ps_initial_state' => __( 'État initial', 'kv2-portfolio-studio' ),
						'_kv2ps_constraints'   => __( 'Contraintes', 'kv2-portfolio-studio' ),
						'_kv2ps_price_range'   => __( 'Fourchette indicative', 'kv2-portfolio-studio' ),
					);
					foreach ( $detail_fields as $meta_key => $detail_label ) :
						$detail_value = get_post_meta( $post_id, $meta_key, true );
						if ( ! $detail_value ) {
							continue;
						}
						?>
						<li><strong><?php echo esc_html( $detail_label ); ?> :</strong> <?php echo esc_html( $detail_value ); ?></li>
					<?php endforeach; ?>
					<?php foreach ( KV2PS_Post_Types::taxonomies() as $taxonomy ) :
						if ( $confidential && 'kv2_ville' === $taxonomy ) {
							continue;
						}
						$taxonomy_object = get_taxonomy( $taxonomy );
						$terms           = get_the_terms( $post_id, $taxonomy );
						if ( ! $taxonomy_object || ! $terms || is_wp_error( $terms ) ) {
							continue;
						}
						?>
						<li><strong><?php echo esc_html( $taxonomy_object->labels->singular_name ); ?> :</strong>
						<?php
						$links = array();
						foreach ( $terms as $term ) {
							$url     = get_term_meta( $term->term_id, '_kv2ps_landing_url', true );
							$url     = $url ?: get_term_link( $term );
							$links[] = is_wp_error( $url ) ? esc_html( $term->name ) : '<a href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a>';
						}
						echo wp_kses_post( implode( ', ', $links ) );
						?>
						</li>
					<?php endforeach; ?>
					</ul>
				</section>

				<?php
				$all_image_ids = array_unique( array_filter( array_merge( array( get_post_thumbnail_id( $post_id ) ), $before_ids, $after_ids ) ) );
				$credits       = array();
				$copyrights    = array();
				$licenses      = array();
				foreach ( $all_image_ids as $image_id ) {
					$credits[]    = get_post_meta( $image_id, '_kv2ps_credit', true ) ?: $settings['image_credit'];
					$copyrights[] = get_post_meta( $image_id, '_kv2ps_copyright', true ) ?: $settings['image_copyright'];
					$licenses[]   = get_post_meta( $image_id, '_kv2ps_license_url', true ) ?: $settings['image_license_url'];
				}
				$credits    = array_unique( array_filter( $credits ) );
				$copyrights = array_unique( array_filter( $copyrights ) );
				$licenses   = array_unique( array_filter( $licenses ) );
				?>
				<?php if ( $credits || $copyrights || $licenses ) : ?><aside class="kv2ps-image-rights"><strong><?php esc_html_e( 'Crédits images :', 'kv2-portfolio-studio' ); ?></strong> <?php echo esc_html( implode( ', ', $credits ) ); ?><?php if ( $copyrights ) : ?> · © <?php echo esc_html( implode( ', ', $copyrights ) ); ?><?php endif; ?><?php foreach ( $licenses as $license_index => $license_url ) : ?> · <a href="<?php echo esc_url( $license_url ); ?>"><?php echo esc_html( 0 === $license_index ? __( 'Conditions d’utilisation', 'kv2-portfolio-studio' ) : sprintf( __( 'Licence %d', 'kv2-portfolio-studio' ), $license_index + 1 ) ); ?></a><?php endforeach; ?></aside><?php endif; ?>
			</div>
		</article>

		<?php
		$service_ids = wp_get_post_terms( $post_id, 'kv2_service', array( 'fields' => 'ids' ) );
		$related_args = array(
			'post_type'           => KV2PS_Post_Types::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => 3,
			'post__not_in'        => array( $post_id ),
			'ignore_sticky_posts' => true,
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
			<section class="kv2ps-related" aria-labelledby="kv2ps-related-title"><div class="kv2ps-container"><h2 id="kv2ps-related-title"><?php esc_html_e( 'D’autres réalisations', 'kv2-portfolio-studio' ); ?></h2><div class="kv2ps-grid kv2ps-cols-3">
			<?php while ( $related->have_posts() ) : $related->the_post(); KV2PS_Plugin::instance()->render_card(); endwhile; ?>
			</div></div></section>
			<?php
		endif;
		wp_reset_postdata();
		?>

		<?php KV2PS_Plugin::render_cta( $post_id ); ?>
	</main>
	<?php
endwhile;

get_footer();
