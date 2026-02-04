<?php
/**
 * Template Name: Feedback Page
 *
 * @package Contextual
 */

global $rpm_theme_options;

// we need training id, event id and user id from the URL
$feedback = false;
if(isset($_GET['f']) && $_GET['f'] <> ''){
    $f = $_GET['f'];
    $feedback = cc_feedback_link_elements( sanitize_text_field( $f ) );
}
if($feedback === false){
    wp_redirect( home_url() );
    exit;
}

// we need to know what user we're doing this for.
// it could be in the $feedback but it won't be if it's a generic link for anybody on the training
// if they are logged in then we know it
// so, if not in $feedback and not logged in, we'll get them to login before returning here
if($feedback['user_id'] == 0){
    if(cc_users_is_valid_user_logged_in()){
        $user_id = get_current_user_id();
        $feedback['user_id'] = $user_id;
        $f = cc_feedback_link_code($feedback['training_id'], $feedback['event_id'], $user_id);
    }else{
        wp_redirect( add_query_arg($_GET, '/member-login') );
        exit;
    }
}

$training_title = get_the_title($feedback['training_id']);
if($feedback['event_id'] > 0){
    $event_name = get_post_meta( $feedback['training_id'], 'event_'.$feedback['event_id'].'_name', true );
    if($event_name <> ''){
        $training_title .= ' - '.$event_name;
    }
}
$presenters = cc_presenters_names($feedback['training_id'], 'none');
if($presenters <> ''){
    $training_title .= ' with '.$presenters;
}

$done = cc_feedback_get_by_user_training($feedback['training_id'], $feedback['event_id'], $feedback['user_id']); // NULL if not found

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
        <div id="" class="wms-section wms-section-std sml-padd-top">
            <div class="wms-sect-bg">
                <div class="container">
                    <div class="row">
                        <div class="col-12 col-md-6 offset-md-3">
                            <h2><?php echo $training_title; ?></h2>

                            <?php if($done === NULL){ ?>
                                <form action="/feedback-submit" method="POST" id="cc-feedback-form" class="needs-validation" novalidate data-source="page">
                                    <input type="hidden" name="f" value="<?php echo $f; ?>">
                                    <?php the_content();
                                    echo do_shortcode( get_post_meta($feedback['training_id'], '_feedback_questions', true) );
                                    ?>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            <?php }else{ ?>
                                <p>&nbsp;</p>
                                <p>&nbsp;</p>
                                <h3 class="text-center">Thank you, you have already submitted your feedback for this training.</h3>
                            <?php } ?>

                        </div><!-- .col outer -->
                    </div><!-- .row outer -->
                </div><!-- .container -->
            </div><!-- .wms-sect-bg -->
        </div><!-- .wms-section -->

        <?php echo wms_get_the_signature(); ?>

    </article><!-- #post-<?php the_ID(); ?> -->
<?php endwhile; // End of the loop.
get_footer();
