<?php
/**
 * Template Name: Site FAQs
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

    if(has_shortcode($post->post_content, 'section')){
        the_content();
    }else{
        echo do_shortcode('[section xclass="sml-padd-top no-padd-bot"]'.apply_filters('the_content',get_the_content()).'[/section]');
    }

    // get all site FAQ categories (that are used)
    $args = array(
        'taxonomy' => 'sitefaqcat',
    );
    $categories = get_categories($args);
    $sfaq_cats = array();
    foreach ($categories as $category) {
        $sfaq_cats[] = array(
            'order' => get_term_meta( $category->term_id, 'sitefaqcat_order', true ),
            'id' => $category->term_id,
            'name' => $category->name,
            'description' => $category->description,
        );
    }
    // let's put them in order
    usort($sfaq_cats, fn($a, $b) => $a['order'] <=> $b['order']); // php 7.4+
    ?>

    <div id="" class="wms-section wms-section-std wms-faq-section no-padd-top">
        <div class="wms-sect-bg">
            <div class="container">
                <div class="row site-faqs">
                    <div class="col-12">
                        <?php foreach ($sfaq_cats as $category) {
                            if( $category['description'] <> '' ){ ?>
                                <h4 class="mt-5 mb-2"><?php echo $category['name']; ?></h4>
                                <p><?php echo $category['description']; ?></p>
                            <?php }else{ ?>
                                <h4 class="mt-5 mb-3"><?php echo $category['name']; ?></h4>
                            <?php } ?>
                            <div class="accordion" id="accordion-<?php echo $category['id']; ?>">
                                <?php
                                $args = array(
                                    'numberposts' => -1,
                                    'post_type' => 'sitefaq',
                                    'tax_query' => array(
                                        array(
                                            'taxonomy' => 'sitefaqcat',
                                            'field'    => 'term_id',
                                            'terms'    => $category['id'],
                                        ),
                                    ),
                                    'orderby' => 'menu_order',
                                    'order' => 'ASC',
                                );
                                $faqs = get_posts($args);
                                foreach ($faqs as $faq) { ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading-<?php echo $category['id'].'-'.$faq->ID; ?>">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $category['id'].'-'.$faq->ID; ?>"><?php echo $faq->post_title; ?></button>
                                        </h2>
                                        <div id="collapse-<?php echo $category['id'].'-'.$faq->ID; ?>" class="accordion-collapse collapse">
                                            <div class="accordion-body">
                                                <?php echo apply_filters( 'the_content', $faq->post_content ); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php echo wms_get_the_signature();
endwhile; // End of the loop.
get_footer();
