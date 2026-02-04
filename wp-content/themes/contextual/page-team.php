<?php
/**
 * Template Name: Team Page
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
    <?php }

    // page content set up using the required template ...
    // "modal" or "inline"
    get_template_part( 'template-parts/team', $rpm_theme_options['team-page-layout'] );

endwhile; // End of the loop.
get_footer();
