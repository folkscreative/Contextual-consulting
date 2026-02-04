<?php
/**
 * Template Name: Registration Confirmation Page
 *
 * @package Contextual
 */

$payment_id = 0;

$form_data = NULL;
$token = '';
if( isset( $_GET['token'] ) ){
    $token = sanitize_text_field( $_GET['token'] );
    $registration_data = TempRegistration::get( $token );
    if( $registration_data ){
        $form_data = json_decode($registration_data->form_data, true);
    }
}
if( $form_data === NULL ){
    ccpa_write_log('page-reg-confirmation.php token invalid or missing: '.$token);
    wp_redirect( home_url() );
    exit;
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
            							<?php
                                        if( $form_data['student'] == 'yes' && $form_data['student_price'] > 0 ){
                                            $price_to_use = $form_data['student_price'];
                                        }else{
                                            $price_to_use = $form_data['raw_price'] - $form_data['series_discount'];
                                        }
                                        if( $form_data['training_type'] == 's' ){
                                            $disc_amount = $form_data['series_discount'];
                                        }else{
                                            $disc_amount = $form_data['disc_amount'];
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
                                            $disc_amount, 
                                            $form_data['voucher_amount'] ?? 0,
                                            $form_data['vat_exempt'] ?? 'n',
                                            $form_data['upsell_workshop_id'] ?? 0,
                                            'reg-conf',  // $page
                                            count($form_data['attendees']), 
                                            $form_data['group_training'] ?? [],
                                        );
                                        ?>
                                        <input type="hidden" id="user-timezone" value="<?php echo $form_data['user_timezone']; ?>">
            						</div>
            					</div>
                            </div>
                        </div>
    				</div>
    			</div>
    			<div id="reg-pmt-det-col" class="col-12 col-md-8 order-md-1">

                    <?php echo cc_payment_free_panel(); ?>

                    <!-- conversion tracking -->
                    <script>
                        window.addEventListener('load', (event) => {
                            if (typeof fbq === "function"){
                                fbq('track', 'Purchase', {
                                    value: 0,
                                    currency: 'GBP',
                                    contents: [
                                        {
                                            id: '<?php echo $token; ?>'
                                        }
                                    ],
                                });
                            }
                            // Google
                            recordConversion( '0|GBP|0|<?php echo $form_data['email']; ?>' );
                        });
                    </script>

                    <div class="">
                        <?php
                        /*
                        <div class="mb-5 text-center">
                            <a href="/workshop" class="btn btn-primary btn-lg mx-4 mb-3">All Live Training</a><a href="/recording" class="btn btn-primary btn-lg mx-4">On Demand Training</a>
                        </div>
                        */
                        echo cc_custom_training_buttons_core( '', 'center', 'yes', 'yes' );
                        ?>
                    </div>
    			</div>
    		</div>
    	</div>
    </div>

    <div id="payment-terms" class="modal tandcs-modal cc-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms &amp; Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo cc_phrases_tandcs($form_data['training_type'], $form_data['training_id'] ); ?>
                </div>
            </div>
        </div>
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
get_footer();
