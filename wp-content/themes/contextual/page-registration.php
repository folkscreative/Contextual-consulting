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
$form_data = json_decode($registration_data->form_data, true);
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
