<?php
/**
 * The archive template file
 *
 * For CC, this handles categories and tags
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Contextual
 */

get_header(); ?>

<div class="wms-sect-page-head no-hero">
	<div class="container">
		<div class="row">
			<div class="col-12 text-center">
				<header class="entry-header">
					<?php
					// acquired from function get_the_archive_title() ...

					$title  = __( 'Archives' );
					$prefix = '';

					if ( is_category() ) {
						$title  = single_cat_title( '', false );
						$prefix = _x( 'Topic:', 'category archive title prefix' );
					} elseif ( is_tag() ) {
						$title  = single_tag_title( '', false );
						$prefix = _x( 'Tag:', 'tag archive title prefix' );
					} elseif ( is_author() ) {
						$title  = get_the_author();
						$prefix = _x( 'Author:', 'author archive title prefix' );
					} elseif ( is_year() ) {
						$title  = get_the_date( _x( 'Y', 'yearly archives date format' ) );
						$prefix = _x( 'Year:', 'date archive title prefix' );
					} elseif ( is_month() ) {
						$title  = get_the_date( _x( 'F Y', 'monthly archives date format' ) );
						$prefix = _x( 'Month:', 'date archive title prefix' );
					} elseif ( is_day() ) {
						$title  = get_the_date( _x( 'F j, Y', 'daily archives date format' ) );
						$prefix = _x( 'Day:', 'date archive title prefix' );
					} elseif ( is_tax( 'post_format' ) ) {
						if ( is_tax( 'post_format', 'post-format-aside' ) ) {
							$title = _x( 'Asides', 'post format archive title' );
						} elseif ( is_tax( 'post_format', 'post-format-gallery' ) ) {
							$title = _x( 'Galleries', 'post format archive title' );
						} elseif ( is_tax( 'post_format', 'post-format-image' ) ) {
							$title = _x( 'Images', 'post format archive title' );
						} elseif ( is_tax( 'post_format', 'post-format-video' ) ) {
							$title = _x( 'Videos', 'post format archive title' );
						} elseif ( is_tax( 'post_format', 'post-format-quote' ) ) {
							$title = _x( 'Quotes', 'post format archive title' );
						} elseif ( is_tax( 'post_format', 'post-format-link' ) ) {
							$title = _x( 'Links', 'post format archive title' );
						} elseif ( is_tax( 'post_format', 'post-format-status' ) ) {
							$title = _x( 'Statuses', 'post format archive title' );
						} elseif ( is_tax( 'post_format', 'post-format-audio' ) ) {
							$title = _x( 'Audio', 'post format archive title' );
						} elseif ( is_tax( 'post_format', 'post-format-chat' ) ) {
							$title = _x( 'Chats', 'post format archive title' );
						}
					} elseif ( is_post_type_archive() ) {
						$title  = post_type_archive_title( '', false );
						$prefix = _x( 'Archives:', 'post type archive title prefix' );
					} elseif ( is_tax() ) {
						$queried_object = get_queried_object();
						if ( $queried_object ) {
							$tax    = get_taxonomy( $queried_object->taxonomy );
							$title  = single_term_title( '', false );
							$prefix = sprintf(
								/* translators: %s: Taxonomy singular name. */
								_x( '%s:', 'taxonomy term archive title prefix' ),
								$tax->labels->singular_name
							);
						}
					}

					$original_title = $title;

					/**
					 * Filters the archive title prefix.
					 *
					 * @since 5.5.0
					 *
					 * @param string $prefix Archive title prefix.
					 */
					$prefix = apply_filters( 'get_the_archive_title_prefix', $prefix );
					if ( $prefix ) {
						$title = sprintf(
							/* translators: 1: Title prefix. 2: Title. */
							_x( '%1$s %2$s', 'archive title' ),
							$prefix,
							'<span>' . $title . '</span>'
						);
					}

					/**
					 * Filters the archive title.
					 *
					 * @since 4.1.0
					 * @since 5.5.0 Added the `$prefix` and `$original_title` parameters.
					 *
					 * @param string $title          Archive title to be displayed.
					 * @param string $original_title Archive title without prefix.
					 * @param string $prefix         Archive title prefix.
					 */
					
					$the_archive_title = apply_filters( 'get_the_archive_title', $title, $original_title, $prefix );
					echo '<h1 class="page-title">'.$the_archive_title.'</h1>';

					the_archive_description( '<div class="taxonomy-description">', '</div>' );
					?>
				</header>
			</div>
		</div>
	</div>
</div>

<div class="wms-section-std sml-padd-top">
	<div class="container">
		<?php if ( have_posts() ) :
			$post_count = 0;
			while ( have_posts() ) :
				the_post();
				if( ( $post_count % 3) == 0 ){
					if($post_count > 0){
						echo '</div>';
					}
					echo '<div class="row">';
				}
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class('col-md-4 mb-5'); ?>>
					<div class="card h-100">

						<?php
						// image
						if(isset($rpm_theme_options['blog-fallback-img']['url']) && $rpm_theme_options['blog-fallback-img']['url'] <> ''){
							$default_image = $rpm_theme_options['blog-fallback-img']['url'];
						}else{
							$default_image = false;
						}
						$args = array(
							'scan' => true,
							'size' => 'post-thumb',
							'image_class' => 'card-img-top',
							'default' => $default_image,
							'format' => 'array',
							'echo' => false,
						);
						echo '<a href="'.get_permalink().'" class="image-zoom"><div class="latest-post-img wms-bg-img"';
						if ( function_exists( 'get_the_image' ) ) {
							$image_arr = get_the_image($args);
							if(isset($image_arr['src']) && $image_arr['src'] <> '') {
								echo ' style="background-image:url('.$image_arr['src'].');"';
							}
						}
						echo '></div></a>';
						?>

						<div class="card-body d-flex flex-column">
							<?php the_title( '<h5 class="card-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h5>' ); ?>
							<p class="card-cite">
								<?php contextual_posted_on(); ?>
							</p>
							<?php the_excerpt(); ?>
							<p class="card-btn-wrap text-right mt-auto">
								<a href="<?php the_permalink(); ?>" class="btn btn-primary">Read more</a>
							</p>
						</div>

					</div>
				</article><!-- #post-<?php the_ID(); ?> -->

				<?php
				$post_count ++;

			endwhile;
			if($post_count > 0){
				echo '</div>';
			}

			echo contextual_the_posts_navigation();
		else :
			get_template_part( 'template-parts/content', 'none' );
		endif; ?>
	</div>
</div>
<?php get_footer();
