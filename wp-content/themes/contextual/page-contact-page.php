<?php
/**
 * Template Name: Contact Us Page
 *
 * Modified layout & content for CC
 *
 * @package Contextual
 */

global $rpm_theme_options;

$location_num = 1;
$contact_page_control_location = get_post_meta($post->ID, '_contact_page_control_location', true);
if($contact_page_control_location == 2){
	$location_num = 2;
}

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
		if(trim($post->post_content) <> ''){
			if(has_shortcode($post->post_content, 'section')){
				the_content();
			}else{
				echo do_shortcode('[section]'.apply_filters('the_content',get_the_content()).'[/section]');
			}
		}

		// now the specific contact us content
		?>
		<div class="wms-section-std">
			<div class="container">
				<div class="row contact-row">
					<div class="col-12 col-lg-6">
						<div class="row">
							<div class="col-3 col-xl-2 contact-icon-col">
								<?php
								echo wms_font_awesome_circle_html('map-marker-alt');
								?>
							</div>
							<div class="col-9 col-xl-10 contact-text-col">
								<h3><?php
									if($location_num == 2){
										echo $rpm_theme_options['business-name-2'];
									}else{
										echo $rpm_theme_options['business-name'];
									}
								?></h3>
								<h6><?php echo wms_address_formatted($location_num, '<br>'); ?></h6>
								<?php /*
								// accurate but does not show the right business name or address
								// $google_url = 'https://maps.google.com/maps?daddr='.$rpm_theme_options['latitude'].','.$rpm_theme_options['longitude'].'&hl=en&mra=ltm&t=m&z=17';
								// fairly accurate and shows the right address
								$google_url = esc_url('https://www.google.com/maps/dir/Current+Location/'.wms_address_formatted($location_num, ' '));
								?>
								<a class="btn btn-default" href="<?php echo $google_url; ?>" target="_blank">Get Directions</a>
								*/ ?>
							</div>
						</div>
						<div class="row">
							<div class="col-3 col-xl-2 contact-icon-col">
								<?php
								echo wms_font_awesome_circle_html('phone');
								?>
							</div>
							<div class="col-9 col-xl-10 contact-text-col">
								<h3>Telephone</h3>
								<h6><?php
									if($location_num == 2){
										echo $rpm_theme_options['phone-number-2'];
									}else{
										echo $rpm_theme_options['phone-number'];
									}
								?></h6>
							</div>
						</div>
					</div>
					<div class="col-12 col-lg-6">
						<div class="row">
							<div class="col-3 col-xl-2 contact-icon-col">
								<?php
								if(wms_social_required()){
									$icon_name = 'comments';
									$col_text = 'Email and Social';
								}else{
									$icon_name = 'envelope';
									$col_text = 'Email';
								}
								echo wms_font_awesome_circle_html($icon_name);
								?>
							</div>
							<div class="col-9 col-xl-10 contact-text-col">
								<h3><?php echo $col_text; ?></h3>
								<h6>
									<?php
									if($location_num == 2){
										$biz_email = antispambot($rpm_theme_options['business-email-2']);
									}else{
										$biz_email = antispambot($rpm_theme_options['business-email']);
									}
									?>
									<a href="mailto:<?php echo $biz_email; ?>"><?php echo $biz_email; ?></a>
								</h6>
								<p class="lead">
									<?php echo wms_social_list(); ?>
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php echo wms_get_the_signature(); ?>
	</article><!-- #post-<?php the_ID(); ?> -->
<?php endwhile; // End of the loop.
get_footer();
