<?php
/**
 * Template Name: Payment Details Page
 *
 * @package Contextual
 */

// ccpa_write_log('Template Name: Payment Details Page');

global $rpm_theme_options;

// ccpa_write_log( 'page-payment-details.php' );
// ccpa_write_log($_REQUEST);

$current_token = handle_registration_page_load();
$registration_data = TempRegistration::get($current_token);
$form_data = json_decode($registration_data->form_data, true);
$current_step = $registration_data->current_step;

// ccpa_write_log('current token:'.$current_token);
// ccpa_write_log('form_data:');
// ccpa_write_log($form_data);
// ccpa_write_log('current step:'.$current_step);

$user_id = $registration_data->user_id;

/*
// total_payable is not being set in the form data
// this is a quick hack to get around that until a real fix is put in place
$vatExempt = 'n';
$promo = '';
$voucher = '';
$upsell_workshop_id = 0;
$price_data = cc_registration_reg_update_pricing_core( $form_data['training_type'], $form_data['training_id'], $form_data['event_id'], $form_data['currency'], $vatExempt, $promo, $voucher, $form_data['student'], $upsell_workshop_id, $group_training, $current_token, $form_data['attendees'] );
/*  $response = array(
        'status' => 'ok',
        'currency' => '',
        'icon' => '',
        'price_html' => '',
        'raw_price' => 0,
        'disc_amount' => 0,
        'voucher_amount' => 0,
        'vat' => 0,
        'tot_pay' => 0,
        'upsell_html' => '',
        'total_payable' => 0,
    );
*/

// ccpa_write_log([ 'price_data' => $price_data ]);

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
                                        <?php
                                        // cc_registration_training_panel($training_type, $training_id, $eventID, $timezone, $raw_price, $currency, $student, $earlybird, $discount, $voucher, $vat_exempt, $upsell_workshop_id, $page, $num_attends, $group_training=array())

                                        if( $form_data['student'] == 'yes' && $form_data['student_price'] > 0 ){
                                            $price_to_use = $form_data['student_price'];
                                        }else{
                                            $price_to_use = $form_data['raw_price'] - $form_data['series_discount'];
                                        }

                                        echo cc_registration_training_panel(
                                            $form_data['training_type'], 
                                            $form_data['training_id'], 
                                            $form_data['event_id'] ?? '', 
                                            $form_data['user_timezone'], 
                                            $price_to_use, 
                                            $form_data['currency'], 
                                            $form_data['student'] ?? 'no', 
                                            cc_workshop_price_earlybird( $form_data['training_id'] ),
                                            $form_data['series_discount'], 
                                            $form_data['voucher_amount'] ?? 0, // voucher amount
                                            $form_data['vat_exempt'] ?? 'n',
                                            $form_data['upsell_workshop_id'] ?? 0, // 0
                                            'details',  // $page
                                            count($form_data['attendees']), 
                                            $form_data['group_training'] ?? [],
                                        );

                                        ?>
            						</div>
            					</div>
                            </div>
                        </div>
    				</div>
                    <?php echo cc_registration_tncs_panel(); ?>
    			</div>

    			<div id="reg-pmt-det-col" class="col-12 col-md-8 order-md-1">
                    <form action="/payment" method="GET" id="reg-pay-dets-form" class="reg-pay-dets-form" novalidate>
                        <input type="hidden" id="token" name="token" value="<?php echo $current_token; ?>">
                        <input type="hidden" id="step" name="step" value="2">
                    </form>

                    <form id="reg-pay-dets-more-form" class="needs-validation" novalidate>

                        <?php 
                        $user_country = cc_currencies_get_ip_country();
                        // ccpa_write_log('About to cc_registration_curr_panel');
                        echo cc_registration_curr_panel($form_data['currency'], $user_country);
                        // ccpa_write_log('Done cc_registration_curr_panel');

                        $next_panel_num = 2;

                        // ccpa_write_log('About to cc_registration_vat_panel');
                        // if(get_user_meta( $user_id, 'address_country', true) <> 'GB'){
                        if( $user_country <> 'GB' ){
                            echo cc_registration_vat_panel($user_country);
                            $next_panel_num ++;
                        }else{ ?>
                            <input type="hidden" name="vat-uk" value="y">
                            <input type="hidden" name="vat-employ" value="">
                            <input type="hidden" name="vat-employer" value="">
                        <?php }
                        // ccpa_write_log('done cc_registration_vat_panel');

                        // ccpa_write_log('About to cc_registration_upsell_panel');
                        $upsell_panel = '';
                        if($form_data['student'] <> 'yes'){
                            $upsell_panel = cc_registration_upsell_panel($next_panel_num, $form_data['training_id'], $form_data['currency'], $form_data['user_timezone']);
                            if($upsell_panel <> ''){
                                echo $upsell_panel;
                                $next_panel_num ++;
                            }
                        }
                        // ccpa_write_log('DOne cc_registration_upsell_panel');

                        if( $form_data['training_type'] <> 's' ){
                            echo cc_registration_voucher_panel( $next_panel_num, $form_data['training_type'], $form_data['training_id'] );
                        }

                        ?>

                        <div class="row">
                            <div class="col-6">
                                <button form="reg-form" id="reg-pay-dets-cancel" class="btn btn-secondary btn-sm reg-pay-dets-cancel">Return to registration</button>
                            </div>
                            <div class="col-6 reg-pay-dets-btn-wrap text-end mb-5">
                                <button type="submit" id="reg-pay-dets-submit" class="btn btn-primary reg-pay-dets-submit">Next: Payment method</button>
                            </div>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

    <div class="d-none">

        <?php // fields used to update pricing .... ?>
        <input type="hidden" id="training-type" name="training-type" value="<?php echo $form_data['training_type']; ?>">
        <input type="hidden" id="training-id" name="training-id" value="<?php echo $form_data['training_id']; ?>">
        <input type="hidden" id="event-id" name="event-id" value="<?php echo $form_data['event_id']; ?>">
        <input type="hidden" id="currency" name="currency" value="<?php echo $form_data['currency']; ?>">
        <input type="hidden" id="vat_exempt" name="vat_exempt" value="n">
        <input type="hidden" id="student" name="student" value="<?php echo $form_data['student']; ?>">
        <input type="hidden" id="upsell_workshop_id" name="upsell_workshop_id" value="0">
        <input type="hidden" id="disc_amount" name="disc_amount" value="<?php echo $form_data['disc_amount']; ?>">
        <input type="hidden" id="voucher_amount" name="voucher_amount" value="<?php echo $form_data['voucher_amount']; ?>">


        <form action="/payment" method="post" id="reg-pay-dets-data" class="reg-pay-dets-form" novalidate>

            <!-- Hidden fields for token system -->
            <input type="hidden" id="token" name="token" value="<?php echo esc_attr($current_token); ?>">
            <input type="hidden" id="step" name="step" value="2">

            <?php /*

            <?php // note different IDs! One of these needs to go!! ?>
            <input type="hidden" id="raw-price" name="raw-price" value="<?php echo $price_data['raw_price']; ?>">
            <input type="hidden" id="raw_price" name="raw-price" value="<?php echo $price_data['raw_price']; ?>">


            <input type="hidden" id="vat_amount" name="vat_amount" value="<?php echo $price_data['vat']; ?>">
            <input type="hidden" id="tot_pay" name="tot_pay" value="<?php echo $price_data['tot_pay']; ?>">
            <input type="hidden" id="total_payable" name="total_payable" value="<?php echo $price_data['total_payable']; ?>">
            <?php /*
            <input type="hidden" id="ccreg" name="ccreg" value="<?php echo $ccreg; ?>">
            <input type="hidden" id="disc_code" name="disc_code" value="<?php echo $disc_code; ?>">
            <input type="hidden" id="user-prettytime" name="user-prettytime" value="<?php echo $prettytime; ?>">
            <input type="hidden" name="attend_first" value="<?php echo $attend_first; ?>">
            <input type="hidden" name="attend_last" value="<?php echo $attend_last; ?>">
            <input type="hidden" name="attend_email" value="<?php echo $attend_email; ?>">
            <input type="hidden" name="conf_email" value="<?php echo $conf_email; ?>">
            <input type="hidden" name="source" value="<?php echo $source; ?>">
            <input type="hidden" name="mailing_list" value="<?php echo $mailing_list; ?>">
            *//* ?>
            <input type="hidden" id="voucher_code" name="voucher_code" value="">
            <input type="hidden" id="user-timezone" name="user-timezone" value="<?php echo $form_data['user_timezone']; ?>">


            <input type="hidden" id="series_discount" name="series_discount" value="<?php echo $form_data['series_discount']; ?>">
            <?php foreach ($group_training as $group_training_id): ?>
                <input type="hidden" name="training_id[]" value="<?php echo htmlspecialchars($group_training_id); ?>">
            <?php endforeach; ?>
            */ ?>
        </form>
    </div>


    <?php // the registration form - for going backwards ?>
    <div class="d-none">
        <form action="/registration" id="reg-form" method="GET">
            <input type="hidden" id="token" name="token" value="<?php echo $current_token; ?>">
            <input type="hidden" id="step" name="step" value="1">
        </form>
    </div>

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
// echo '#####'. date('d/m/y H:i:s') .'#####';
// ccpa_write_log('About to get footer');

get_footer();
// ccpa_write_log('Done footer');






