<?php
/**
 * Template Name: Registration Page
 *
 * Updates:
 * Aug 2025 
 * - Accommodate traingin groups
 */

global $rpm_theme_options;

$current_token = handle_registration_page_load();

$registration_data = TempRegistration::get($current_token);


$course_id = $registration_data->course_id;
$course_status = get_post_meta($course_id, '_course_status', true);

$user_id = get_current_user_id(); // 0 if not logged in
$user_timezone = cc_timezone_get_user_timezone($user_id);
$pretty_timezone = cc_timezone_get_user_timezone_pretty($user_id, $user_timezone);

// Get workshop pricing for display (currency switcher, price display, etc.)
$user_currency = cc_currency_get_user_currency();
$workshop_pricing = cc_workshop_price($course_id, $user_currency);

$from_price = $workshop_pricing['price_text'];
$post_type = get_post_type($course_id);
if($post_type == 'workshop'):
if($from_price <> ''){

}else{
  wp_safe_redirect(home_url());
  exit;
}
endif;

if ($course_status == 'closed') {
    wp_safe_redirect(home_url());
    exit;
} else {
    // your else code here

$form_data = json_decode($registration_data->form_data, true);
$post_type = get_post_type($course_id);
if($post_type == 'workshop'):
//if(course)


$user_currency = $form_data['currency'];
$online_pmt_amt = get_post_meta($course_id, 'online_pmt_amt', true);
$online_pmt_aud = get_post_meta($course_id, 'online_pmt_aud', true);
$online_pmt_usd = get_post_meta($course_id, 'online_pmt_usd', true);
$online_pmt_eur = get_post_meta($course_id, 'online_pmt_eur', true);
$student_discount = get_post_meta($course_id, 'student_discount', true);
$earlybird_discount = get_post_meta($course_id, 'earlybird_discount', true);

$course  =array(
'pricing' => array(
 'price_gbp' => $online_pmt_amt,
            'price_usd' => $online_pmt_usd,
            'price_eur' => $online_pmt_eur,
            'price_aud' => $online_pmt_aud,
            'student_discount' => $student_discount,
            'early_bird_discount' => $earlybird_discount

)

);

//print_r($course);


$student_pricing = course_student_pricing( $course, $user_currency );

// Step 1: Set base price (always)
switch ($form_data['currency']) {
    case 'USD':
        $price = $course['pricing']['price_usd'] ?? 0;
        break;
    case 'GBP':
        $price = $course['pricing']['price_gbp'] ?? 0;
        break;
    case 'EUR':
        $price = $course['pricing']['price_eur'] ?? 0;
        break;
    case 'AUD':
        $price = $course['pricing']['price_aud'] ?? 0;
        break;
    default:
        $price = 0;
}

// Always set raw price (original)
$form_data['raw_price'] = $price;


// Step 2: Apply student pricing separately
if (
    $form_data['student'] === 'yes' &&
    !empty($student_pricing['student_price']) &&
    $student_pricing['student_price'] > 0
) {
    $form_data['student_price'] = $student_pricing['student_price'];
} else {
    $form_data['student_price'] = 0;
}
    else:
$course = course_get_all( $course_id );
$user_currency = $form_data['currency'];

        // Step 1: Set base price (always)
switch ($form_data['currency']) {
    case 'USD':
        $price = $course['pricing']['price_usd'] ?? 0;
        break;
    case 'GBP':
        $price = $course['pricing']['price_gbp'] ?? 0;
        break;
    case 'EUR':
        $price = $course['pricing']['price_eur'] ?? 0;
        break;
    case 'AUD':
        $price = $course['pricing']['price_aud'] ?? 0;
        break;
    default:
        $price = 0;

}
        $student_pricing = course_student_pricing( $course, $user_currency );

 //print_r($student_pricing);


        if (
    $form_data['student'] === 'yes' &&
    !empty($student_pricing['student_price']) &&
    $student_pricing['student_price'] > 0
) {
    $form_data['student_price'] = $student_pricing['student_price'];
} else {
    $form_data['student_price'] = 0;
}

       



// Always set raw price (original)
$form_data['raw_price'] = $price;

    endif;

$current_step = $registration_data->current_step;
$user_id = $registration_data->user_id;

// ccpa_write_log('template page-registration.php');

if( $form_data['training_type'] == ''
    || $form_data['training_id'] == 0
    || $form_data['currency'] == ''
    || ( $form_data['training_type'] == 'w' && $form_data['user_timezone'] == '' )
    ){
    wp_redirect( home_url() );
    exit;
}

$earlybird = false;
$price_to_use = $form_data['raw_price'];
if($form_data['student'] == 'yes' && $form_data['student_price'] > 0){
    $price_to_use = $form_data['student_price'];
}elseif( $form_data['training_type'] == 'r' || $form_data['training_type'] == 'w' ){
    $earlybird = cc_workshop_price_earlybird($form_data['training_id']);
}

get_header();
while ( have_posts() ) : the_post(); ?>
    <div class="wms-sect-page-head">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <header class="entry-header">
                        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                    </header>
                </div>
            </div>
        </div>
    </div>
    <div class="wms-section">
    	<div class="container">
    		<div class="row">
    			<div class="col-12 col-md-4 order-md-2">
    				<div class="animated-card">
                        <div id="reg-train-panel" class="reg-train-panel reg-panel wms-background animated-card-inner closed dark-bg">
        					<div class="row">
        						<div class="col-11">
        							<h3>Your training:</h3>
        						</div>
        						<div id="reg-train-closer" class="col-1 text-end d-md-none reg-train-closer">
        							<span class="closed"><i class="fa-solid fa-angle-right"></i></span>
        							<span class="open"><i class="fa-solid fa-angle-down"></i></span>
        						</div>
        					</div>
                            <div class="reg-train-dets">
            					<div class="row">
            						<div class="col-12">
            							<?php echo cc_registration_training_panel( $form_data['training_type'], $form_data['training_id'], $form_data['event_id'], $form_data['user_timezone'], $price_to_use, $form_data['currency'], $form_data['student'], $earlybird, $form_data['series_discount'], 0, 'n', 0, 'registration', 1, $form_data['group_training'] ); ?>
            						</div>
            					</div>
                            </div>
                        </div>
    				</div>
                    <?php echo cc_registration_tncs_panel(); ?>
    			</div>
    			<div id="reg-who-col" class="col-12 col-md-8 order-md-1 mb-5">
    				<div class="animated-card">
                        <div id="reg-who-panel" class="reg-who-panel reg-panel wms-background animated-card-inner pale-bg">
                            <?php
                            $reg_type = '';
                            if( $price_to_use == 0 ){
                                $reg_type = 'free';
                            }
                            // cc_registration_who_panel( $state='1', $error='', $field_classes=array(), $values=array(), $user_id=0, $reg_type='', $reg_token='' )
                            echo cc_registration_who_panel( '1', '', array(), $form_data, $user_id, $reg_type, $current_token );
                            ?>
                        </div>
                    </div>

                    <form action="/pmt-dets" method="GET" id="reg-next-form" class="reg-next-form needs-validation" novalidate>
                        <input type="hidden" id="token" name="token" value="<?php echo $current_token; ?>">
                        <input type="hidden" id="step" name="step" value="1">
                    </form>

                    <div id="reg-extra-panels">
                        <?php
                        if( cc_registration_user_dets_complete_form_data( $form_data ) ){

                            if(cc_users_is_valid_user_logged_in() ){
                                $blocked_panel = '';
                                if($form_data['portal_user'] == 'cnwl'){
                                    $blocked_panel = cc_registration_blocked_panel( get_current_user_id() );
                                }
                                if($blocked_panel <> ''){
                                    echo $blocked_panel;
                                }else{
                                    echo cc_registration_more_info_panel( get_current_user_id(), $form_data );
                                    $reg_type = '';
                                    if( $price_to_use == 0 ){
                                        $reg_type = 'free';
                                    }
                                    echo cc_registration_attend_next_panels( '', array(), array(), '3', $reg_type, $form_data );
                                }
                            }else{
                                echo cc_registration_more_info_panel( 0, $form_data );
                                $reg_type = '';
                                if( $price_to_use == 0 ){
                                    $reg_type = 'free';
                                }
                                echo cc_registration_attend_next_panels( '', array(), array(), '3', $reg_type, $form_data );
                            }

                        }
                        ?>
                    </div>
    			</div>
    		</div>
    	</div>
    </div>

    <?php // for the timezone changer ... ?>
    <input type="hidden" id="user-timezone" value="<?php echo $form_data['user_timezone']; ?>">
    <input type="hidden" id="user-prettytime" value="<?php echo $form_data['user_prettytime']; ?>">

    <?php // voucher offer t&cs modal ?>
    <div id="voucher-tandcs" class="modal cc-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gift Voucher Terms &amp; Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo cc_phrases_gift_voucher_terms(); ?>
                </div>
            </div>
        </div>
    </div>

    <?php // the workshop times modal ?>
    <div id="workshop-times-modal" class="modal session-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"><div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div></div>
            </div>
        </div>
    </div>

<?php endwhile; // End of the loop.
get_footer();
}
