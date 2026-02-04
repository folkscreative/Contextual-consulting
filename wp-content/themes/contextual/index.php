<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Contextual
 */

get_header();
$page_slider_html = wms_page_slider();
echo $page_slider_html;
$page_title_locn = get_post_meta(get_the_ID(), '_page_title_locn', true);
if($page_slider_html == '' || $page_title_locn == ''){
	if($page_slider_html == ''){
		$xclass = 'no-hero';
	}else{
		$xclass = '';
	} ?>
	<div class="wms-sect-page-head <?php echo $xclass; ?>">
		<div class="container">
			<div class="row">
				<div class="col-12 text-center">
					<header class="entry-header">
						<?php 
						$postid = get_option('page_for_posts');
						?>
						<h1 class="entry-title mb-5"><?php echo get_the_title($postid); ?></h1>
					</header>
				</div>
			</div>
		</div>
	</div>
<?php } ?>
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
