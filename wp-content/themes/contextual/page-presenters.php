<?php
/**
 * Template Name: Presenters Page
 *
 * @package Contextual
 */

global $rpm_theme_options;

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
	    if(has_shortcode($post->post_content, 'section')){
	        the_content();
	    }else{
	        echo do_shortcode('[section xclass="sml-padd-top no-padd-bot"]'.apply_filters('the_content',get_the_content()).'[/section]');
	    }
	    $presenters = cc_presenters_get_all( 'menu_order title', 'ASC' );
	    ?>
	    <div id="" class="wms-section-std wms-team-section wms-team-inlines no-padd-top">
	        <div class="container">
	        	<div class="row">
		            <?php foreach ( $presenters as $presenter ) {
		            	$image_id = cc_presenters_image_id( $presenter->ID, '1' );
		            	?>
	                    <div class="col-12 col-md-4 col-lg-3 d-flex align-items-stretch">
			                <div class="team-member card">
		                        <div class="team-member-photo wms-bg-img" <?php
		                            if( $image_id ){ ?>
		                                style="background-image:url(<?php echo esc_url( wms_section_image_url( $image_id, 'post-thumb' ) ); ?>);"
		                            <?php }
		                        ?>></div>
		                        <div class="card-body">
		                        	<h5 class="card-title"><?php echo $presenter->post_title; ?></h5>
		                        	<p class="card-text presenter-card-text"><?php echo wms_get_excerpt_by_id( $presenter->ID ); ?></p>
		                        	<a href="<?php echo get_permalink( $presenter->ID ); ?>" class="btn btn-primary btn-sm">Read more</a>
		                        </div>
		                    </div>
		                </div>
		            <?php } ?>
		        </div>
	        </div>
	    </div>
	    <?php echo wms_get_the_signature(); ?>
	</article><!-- #post-<?php the_ID(); ?> -->

<?php
endwhile; // End of the loop.
get_footer();
