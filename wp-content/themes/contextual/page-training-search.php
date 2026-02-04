<?php
/**
 * Template Name: Training Search
 */

get_header();
while ( have_posts() ) : the_post();
	$page_slider_html = wms_page_slider();
	echo $page_slider_html;
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
	<?php } ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<?php
		if( $post->post_content <> '' ){
			if(has_shortcode($post->post_content, 'section')){
				the_content();
			}else{
				echo do_shortcode('[section xclass="sml-padd-top no-padd-bot auto-section"]'.apply_filters('the_content',get_the_content()).'[/section]');
			}
		}

		$params = cc_training_search_params();
		?>

		<div class="wms-section wms-section-std training-search-section sml-padd-top">
			<div class="wms-sect-bg">
				<div class="container">
					<div class="row">
						<div id="training-search-panel" class="training-search-panel main col-md-4 col-lg-3 bg-light mb-3">
							<div class="p-5 p-md-2">
								<?php echo cc_training_search_sidebar( $params, 'no' ); ?>
							</div>
						</div>
						<div class="col-md-8 col-lg-9">
							<button class="btn btn-primary btn-sm d-md-none mb-3" id="tsf-toggle-sidebar">Training filter &amp; search</button>
							<div id="training-search-wrap" class="training-search-wrap">
								<?php // base64_encode to solve the quotations problem! ?>
								<input type="hidden" id="training-search-loader" value='<?php echo base64_encode( maybe_serialize( $params ) ); ?>'>
								<div class="text-center m-5">
									<i class="fa-solid fa-spinner fa-spin-pulse"></i> Loading, please wait ...
								</div>
							</div>
							<?php // echo cc_training_search_get( $params ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>

	</article><!-- #post-<?php the_ID(); ?> -->
<?php endwhile; // End of the loop.

get_footer();
