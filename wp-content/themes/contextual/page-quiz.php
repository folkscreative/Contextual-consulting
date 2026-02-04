<?php
/**
 * Template Name: Quiz page
 *
 * @package Contextual
 */

// ccpa_write_log('page-quiz.php');

// need to be logged in 
if(!cc_users_is_valid_user_logged_in()){
    wp_redirect( add_query_arg($_GET, '/member-login') );
    exit;
}

// we need the training id
if(isset($_GET['t']) && $_GET['t'] <> ''){
    $training_id = (int) $_GET['t'];
}else{
    // ccpa_write_log('$training_id not set');
    wp_redirect( home_url() );
    exit;
}

// we may also know the question we're to display
if(isset($_GET['q']) && $_GET['q'] <> ''){
    if($_GET['q'] == 'end' || $_GET['q'] == 'submit'){
        // end = final chance to go back and change answers
        // submit = answers submitted
        $qnum = $_GET['q'];
    }else{
        $qnum = (int) $_GET['q'];
    }
}else{
	$qnum = 0;
}

$user_id = get_current_user_id();

// let's just check that this training has a quiz for it and that this user is registered for this training
$quiz_id = cc_quizzes_quiz_id($training_id);
$recording_access = ccrecw_user_can_view($training_id, $user_id);
if( $quiz_id === NULL || ( !$recording_access['access'] && $recording_access['expiry_date'] == '' ) ){
    // ccpa_write_log('quiz_id:'.$quiz_id);
    // ccpa_write_log($recording_access);
    wp_redirect( home_url() );
    exit;    
}

$training_title = get_the_title($training_id);
$presenters = cc_presenters_names($training_id, 'none');
if($presenters <> ''){
    $training_title .= ' with '.$presenters;
}

// the base url
$next_url = add_query_arg( 
    array(
        't' => $training_id,
    ),
    get_permalink()
);

$next_btn_txt = 'Next';

// user quiz id
// this is a temporary code to use until the final submit happens
if($qnum === 0){ // note 'end' == 0 returns true! 'end' === 0 returns false
    $user_quiz_id = uniqid();
}else{
    if(isset($_POST['user-quiz-id'])){
        $user_quiz_id = stripslashes( sanitize_text_field( $_POST['user-quiz-id'] ) );
    }else{
        // something has gone horribly wrong :-( ... bale
        wp_redirect( $next_url );
        exit;
    }
}

// has this user_quiz_id already been saved?
if(cc_quizzes_already_saved($user_quiz_id, $user_id, $training_id)){
    // somebody has hit a back button or a reload button
    // send them off to the final results
    $qnum = 'submit';
}else{
    // maybe we need to save an answer to the previous question
    if( isset( $_POST['question-submit'] )){
        if( $_POST['question-submit'] == 'end' ){
            // we need to store the answers now
            $result = cc_quizzes_store_answers($user_quiz_id, $user_id, $training_id);
        }else{
            $question_submit = (int) $_POST['question-submit'];
            if($question_submit > 0){
                if( isset($_POST['answer']) && in_array( $_POST['answer'], array('a', 'b', 'c', 'd', 'e') ) ){
                    cc_quizzes_save_answer($user_quiz_id, $user_id, $training_id, $question_submit, $_POST['answer']);
                }
            }
        }
    }
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
        <div id="" class="wms-section wms-section-std sml-padd-top">
            <div class="wms-sect-bg">
                <div class="container">
                    <div class="row">
                        <div class="col-12 col-md-6 offset-md-3 quiz-wrap">
                            <h4 class="mb-5"><?php echo $training_title; ?></h4>

                            <?php 
                            if($qnum === 0){
                                $question = array(
                                    'html' => apply_filters('the_content',get_the_content()),
                                    'prev' => false,
                                    'next' => 1,
                                );
                                $results = cc_quizzes_previous_results($user_id, $training_id);
                                switch ($results['attempts']) {
                                    case 0:
                                        $question['html'] .= '<p>Click next to start.</p>';
                                        break;
                                    case 1:
                                    case 2:
                                        $question['html'] .= '<p>Your previous result';
                                        if($results['attempts'] == 1){
                                            $question['html'] .= ' was ';
                                        }else{
                                            $question['html'] .= 's were ';
                                        }
                                        $question['html'] .= $results['text'].'.</p>';
                                        if($results['pass_fail'] == 'pass'){
                                            $question['next'] = false;
                                        }else{
                                            $question['html'] .= '<p>Click next to start.</p>';
                                        }
                                        break;
                                    case 3:
                                        $question['html'] .= '<p>Your results were '.$results['text'].'.</p>';
                                        $question['next'] = false;
                                        break;
                                }
                                if($results['pass_fail'] == 'pass'){
                                    $question['html'] .= '<p>You have successfully passed the quiz. Your CE certificate is now available to download from your account.</p>';
                                }
                            }elseif($qnum == 'end'){
                                $question = array(
                                    'html' => '<p>All finished? You can review your answers if you want to double check. Once you hit the submit button, your answers will be finalised and you won\'t be able to review them.</p>',
                                    'prev' => $question_submit,
                                    'next' => 'submit',
                                );
                                $next_btn_txt = 'Submit answers';
                            }elseif($qnum == 'submit'){
                                $question = array(
                                    'html' => cc_quizzes_show_results($user_id, $training_id),
                                    'prev' => false,
                                    'next' => false,
                                );
                            }else{
                                $question = cc_quizzes_question($user_id, $training_id, $qnum, $user_quiz_id);
                            }
                            if($question['next']){
                                $next_url = add_query_arg( 
                                    array(
                                        't' => $training_id,
                                        'q' => $question['next'],
                                    ),
                                    get_permalink()
                                );
                            } ?>

                            <form action="<?php echo esc_url( $next_url ); ?>" method="POST" id="cc-quiz-form" class="needs-validation">
                                <input type="hidden" name="user-quiz-id" value="<?php echo $user_quiz_id; ?>">

                                <div class="qa-wrap dark-bg p-3 mb-5">
                                    <?php echo $question['html']; ?>
                                </div>

                            </form>

                            <div class="row">
                                <div class="col-6">
                                    <?php if($question['prev']){
                                        $prev_url = add_query_arg( 
                                            array(
                                                't' => $training_id,
                                                'q' => $question['prev'],
                                            ),
                                            get_permalink()
                                        ); ?>
                                        <form action="<?php echo esc_url( $prev_url ); ?>" method="post" id="cc-quiz-prev">
                                            <input type="hidden" name="user-quiz-id" value="<?php echo $user_quiz_id; ?>">
                                            <button type="submit" form="cc-quiz-prev" class="btn btn-secondary">Prev.</button>
                                        </form>
                                    <?php } ?>
                                </div>
                                <div class="col-6 text-end">
                                    <?php if($question['next']){ ?>
                                        <button type="submit" form="cc-quiz-form" class="btn btn-primary" name="question-submit" value="<?php echo $qnum; ?>"><?php echo $next_btn_txt; ?></button>
                                    <?php } ?>
                                </div>
                            </div>

                            <?php
                            if($qnum === 'submit'){
                                // results have been shown above
                                $results = cc_quizzes_previous_results($user_id, $training_id);
                                ?>
                                <div class="row">
                                    <div class="col-4">
                                        <?php
                                        $recording_access = ccrecw_user_can_view($training_id, $user_id);
                                        if($recording_access['access']){
                                            $url = add_query_arg( 'id', $training_id, '/watch-recording' );
                                            ?>
                                            <a class="btn btn-sm btn-primary w-100 recording-btn mb-3" href="<?php echo esc_url($url); ?>"><i class="fa-solid fa-video"></i> Recording</a>
                                        <?php } ?>
                                    </div>
                                    <div class="col-4 text-center">
                                        <?php
                                        if($results['pass_fail'] == 'fail' && $results['attempts'] < 3){
                                            $url = add_query_arg( 't', $training_id, '/quiz' );
                                            ?>
                                            <a class="btn btn-primary btn-sm mb-3 w-100 quiz-btn" href="<?php echo esc_url($url); ?>"><i class="fa-solid fa-star fa-fw"></i> Retake Test</a>
                                        <?php } ?>
                                    </div>
                                    <div class="col-4 text-end">
                                        <a class="btn btn-primary btn-sm mb-3 w-100 account-btn" href="/my-account"><i class="fa-solid fa-user"></i> My Account</a>
                                    </div>
                                </div>
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
