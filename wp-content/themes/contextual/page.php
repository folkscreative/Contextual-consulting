<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Contextual
 */

get_header();
while ( have_posts() ) : the_post();
	$page_slider_html = wms_page_slider();
	echo $page_slider_html;
	if(!is_front_page()){
		$page_title_locn = get_post_meta($post->ID, '_page_title_locn', true);
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
								<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
							</header>
						</div>
					</div>
				</div>
			</div>
		<?php }
	} ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<?php 
		if(is_front_page()){
			$num_metas = absint($rpm_theme_options['home-sections']);
			for ($sect=0; $sect < $num_metas; $sect++){
				echo wms_home_meta_section($sect);
			}
		}else{
			if(has_shortcode($post->post_content, 'section')){
				the_content();
			}else{
				echo do_shortcode('[section xclass="sml-padd-top auto-section"]'.apply_filters('the_content',get_the_content()).'[/section]');
			}
		}
		echo wms_get_the_signature(); ?>
	</article><!-- #post-<?php the_ID(); ?> -->
<?php endwhile; // End of the loop.
get_footer();
