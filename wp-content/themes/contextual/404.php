<?php
/**
 * 404
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">

			<div id="home-slideshow" class="slide-container full-screen">
				<div class="slide">
					<div class="wp-post-image">
						<style>
							<?php
							$frontpage_id = get_option( 'page_on_front' );
							// wms_page_slider_slide_css($img_id, $selector, $bg_pos='', $bg_height='', $bg_wash_opacity='')
							echo wms_page_slider_slide_css( get_post_thumbnail_id( $frontpage_id), '.feat-img-resp.visible', get_post_meta( $frontpage_id, '_featured_bg_position', true, 1080) );
							?>
						</style>
						<div class="carousel-item active feat-img-resp wms-bg-img parallax visible">
							<div class="item-overlay">
								<div class="caption-container">
									<div class="carousel-caption">
										<h2 class="headline"><strong>Oops - that page has moved!</strong></h2>
										<p class="subhead">Embrace the present moment and try one of these links instead:</p>
										<p class="featured-btns">
											<?php echo cc_custom_training_buttons_core( '', 'start'); ?>
										</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_footer();
