<?php
/**
 * Xero interface
 */

/**
 * Setting up Oauth2 for Xero is a pain. Try this (note you might find the ChatGPT conversation helpful too):
 * 1. Use composer to install the Xero PHP SDK: Run the following command in the theme directory: composer require xeroapi/xero-php-oauth2. If you get the error "The Process class relies on proc_open, which is not available on your PHP installation." then go to the PHP options and remove proc_open from the list of disabled functions (also remove proc_close and proc_get_status!).
 * 2. Go to Xero's Developer Portal (https://developer.xero.com/) and create a new app. Obtain the following details: Client ID, Client Secret, Redirect URI (set it to something like https://your-site.com/xero-callback). Add these credentials to your WordPress configuration.
 * 3. Authorise the app by going to https://login.xero.com/identity/connect/authorize?response_type=code&client_id=YOURCLIENTID&redirect_uri=YOURREDIRECTURI&scope=openid offline_access profile email accounting.transactions accounting.settings&state=123. see https://developer.xero.com/documentation/guides/oauth2/auth-flow for more info. This will only authorise the app for 30 minutes. If you do not complete everything else within 30 mins, come back to this point and do it again! Note: if you do not request the offline_access scope, you will not get a refresh token and access will be effectively closed in 30 mins!
 * 4. Now get the tenant ID. This may be the same as the one on the dev site but, to be sure, run the get_xero_tenants shortcode. Save that in the CC configs.
 * 5. If we're lucky, the cron job will now refresh the token every 25 mins. However, maybe manually refresh it to kick the process off - shortcode trigger_xero_token_refresh
 * 6. Now you can run the shortcode force_sync_stripe_to_xero to send the last batch of payments across to Xero. It will default to sending the last day's across but, if you want to delete the last_xero_sync_run_time option and change the default option return timeframe, you can do a bigger batch if you want.
 **/

require_once get_stylesheet_directory().'/vendor/autoload.php';

// Add or adjust the cron event based on xero_run_mode
function add_daily_cron_event() {
    if ( ! defined('LIVE_SITE') || ! LIVE_SITE ) {
        return;
    }

    $run_mode = get_option('xero_run_mode', 'auto');
    $hook = 'sync_stripe_to_xero_hook';
    $existing_timestamp = wp_next_scheduled($hook);

    if ($run_mode === 'catchup') {
        // Ensure it's scheduled every 5 minutes
        if ($existing_timestamp) {
            $args = wp_get_scheduled_event($hook);
            if ($args && $args->interval !== 900) { // fifteen_mins = 900 seconds
                wp_unschedule_event($existing_timestamp, $hook);
                wp_schedule_event(time(), 'fifteen_mins', $hook);
            }
        } else {
            wp_schedule_event(time(), 'fifteen_mins', $hook);
        }
    } else {
        // auto or any other mode → schedule daily at 01:15
        $daily_timestamp = strtotime('01:15:00');

        if ($existing_timestamp) {
            $args = wp_get_scheduled_event($hook);
            if ($args && $args->interval !== DAY_IN_SECONDS) {
                wp_unschedule_event($existing_timestamp, $hook);
                wp_schedule_event($daily_timestamp, 'daily', $hook);
            }
        } else {
            wp_schedule_event($daily_timestamp, 'daily', $hook);
        }
    }
}
add_action('wp', 'add_daily_cron_event');

/*
// Add a daily cron event if it doesn't exist
function add_daily_cron_event() {
    // ccpa_write_log('scheduling stripe sync');
    if (wp_next_scheduled('sync_stripe_to_xero_hook')) {
        // $timestamp = wp_next_scheduled('sync_stripe_to_xero_hook');
        // ccpa_write_log('next run: '.date('d/m/Y H:i:s', $timestamp));
        if( ! LIVE_SITE ){
            $timestamp = wp_next_scheduled('sync_stripe_to_xero_hook');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'sync_stripe_to_xero_hook');
            }
        }
    }else{
        // ccpa_write_log('not scheduled');
        if( LIVE_SITE ){
            wp_schedule_event( strtotime('01:15:00'), 'daily', 'sync_stripe_to_xero_hook');
        }
    }
}
add_action('wp', 'add_daily_cron_event');
*/

// Remove the daily cron event on theme deactivation
function remove_daily_cron_event() {
    $timestamp = wp_next_scheduled('sync_stripe_to_xero_hook');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'sync_stripe_to_xero_hook');
    }
}
register_deactivation_hook(__FILE__, 'remove_daily_cron_event');

add_action('sync_stripe_to_xero_hook', 'sync_stripe_to_xero');

// for testing ...
add_shortcode( 'force_sync_stripe_to_xero', 'sync_stripe_to_xero' );

// Define the main function triggered by the cron job
// find all the relevant stripe charges
// if there are any, group them into batches of 10: 10 because Xero has a rate limit of a max of 60 API calls per minute
// store those batches 
// trigger a cron job to run every 2 mins to look for one of these batches to be processed
function sync_stripe_to_xero() {
    // find all the relevant Stripe charges
    $html = 'sync_stripe_to_xero';
    global $wpdb, $rpm_theme_options;
    $payments_table = $wpdb->prefix.'ccpa_payments';

    // Load necessary Stripe and Xero libraries
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
    \Stripe\Stripe::setApiKey(cc_get_stripe_key('secret'));

    // $xero = get_xero_client();

    // Get the last time the process ran (stored in an option)
    $last_run_time = get_option('last_xero_sync_run_time', strtotime('-1 day'));
    $html .= '<br>last_run_time: '.$last_run_time.' '.date( 'd/m/Y H:i:s', $last_run_time );

    /**
     * possible xero_run_modes:
     * - auto = catchup since last run
     * - stop = do nothing
     * - catchup = do 24 hrs more since the last run
     * - manual dd/mm/yyyy dd/mm/yyyy = do those days' payments
     */
    $xero_run_mode = get_option('xero_run_mode', 'stop');
    $html .= '<br>Run mode: '.$xero_run_mode;
    if( $xero_run_mode == 'stop' ){
        $html .= 'Option set to prevent this from running';
        ccpa_write_log( 'sync_stripe_to_xero not running as option is set to STOP' );
    }else{
        $abort = false;
        if( $xero_run_mode == 'auto' ){
            // everything since we last ran
            $end_time = time();
            $stripe_date_args = ['gte' => $last_run_time, 'lte' => $end_time];
        }elseif( $xero_run_mode == 'catchup' ){
            // 24 hours of charges since the last run
            $dateTime = DateTime::createFromFormat( 'U', $last_run_time ); // Unix format === a timestamp
            $dateTime->modify('+1 day'); // Add one day
            $end_time = $dateTime->getTimestamp(); 
            if( $end_time > time() ){
                // we've caught up!
                // switch back to auto and stop
                ccpa_write_log('reverting back to auto mode');
                update_option( 'xero_run_mode', 'auto' );
                $abort = true;
            }else{
                ccpa_write_log('catchup from '.date( 'd/m/Y H:i:s', $last_run_time ).' to '.date( 'd/m/Y H:i:s', $end_time ) );
                $stripe_date_args = ['gte' => $last_run_time, 'lte' => $end_time];
            }
        }else{
            // manual with to and from dates
            // should look EXACTLY like this ... manual dd/mm/yyyy dd/mm/yyyy
            $dateTime = DateTime::createFromFormat( 'd/m/Y H:i:s', substr( $xero_run_mode, 7, 10 ).' 00:00:00' );
            if( $dateTime ){
                $start_time = $dateTime->getTimestamp();
                $dateTime = DateTime::createFromFormat( 'd/m/Y H:i:s', substr( $xero_run_mode, 18, 10 ).' 23:59:59' );
                if( $dateTime ){
                    $end_time = $dateTime->getTimestamp();
                    $stripe_date_args = ['gte' => $start_time, 'lte' => $end_time];
                }else{
                    $html .= 'Invalid end date ... bailing';
                    ccpa_write_log( 'Invalid end date: '.$xero_run_mode.' ... bailing' );
                    $abort = true;
                }
            }else{
                $html .= 'Invalid start date ... bailing';
                ccpa_write_log( 'Invalid start date: '.$xero_run_mode.' ... bailing' );
                $abort = true;
            }
        }

        if( ! $abort ){

            try {
                // Fetch Stripe charges since the last run
                $charges = \Stripe\Charge::all([
                    'created' => $stripe_date_args,
                    'status' => 'succeeded',
                    'limit' => 1000                 // ############# NOTE STRIPE ONLY SENDS A MAX OF 100, not 1000 !!!!!!! ###############
                ]);
                $html .= '<br>'.count( $charges ).' charges found';

                if( count( $charges ) > 99 ){
                    $html .= '<br>Too many! Aborting!';
                    ccpa_write_log('Error: function sync_stripe_to_xero returned too many Stripe charges. NO ACTION TAKEN!');
                    $message = 'sync_stripe_to_xero last_run_time:'.$last_run_time.' run_mode:' .$xero_run_mode.' number charges:'.count($charges).' Error: function sync_stripe_to_xero returned too many Stripe charges. NO ACTION TAKEN!';
                    wp_mail( get_bloginfo('admin_email'), '### CC error sending invoices to Xero ###', $message );
                }elseif( count( $charges ) == 0 ){
                    $html .= '<br>No charges to process';
                    ccpa_write_log('No charges to process');
                    update_option( 'last_xero_sync_run_time', $end_time );
                    if( substr( $xero_run_mode, 0, 6 ) == 'manual' ){
                        update_option( 'xero_run_mode', 'stop' );
                    }
                }else{
                    $html .= '<br>'.count( $charges ).' charges found';
                    ccpa_write_log( count( $charges ).' charges found');
                    // Iterate over each charge and batch them up
                    $chargesArray = [];  // This will hold the batches of charge IDs
                    $batch = [];         // Temporary array to collect charge IDs

                    foreach ($charges->data as $charge) {
                        $batch[] = $charge->id;
                        
                        if (count($batch) === 10) {
                            $chargesArray[] = $batch;
                            $batch = [];  // Reset batch
                        }
                    }

                    // Add any remaining charge IDs that didn't make a full batch
                    if (!empty($batch)) {
                        $chargesArray[] = $batch;
                    }

                    // now save the batches for later processing
                    update_option('charges_to_sync_to_xero', $chargesArray);
                    // and trigger the cron jobs to start processing the batches every 2 mins
                    ccpa_write_log('Starting to process the charges in 2 minute batches');
                    wp_schedule_event( time(), 'two_mins', 'send_a_batch_to_xero_hook');

                    // and set ourselves up ready for the next run
                    // Update the last run time
                    update_option( 'last_xero_sync_run_time', $end_time );
                    if( substr( $xero_run_mode, 0, 6 ) == 'manual' ){
                        update_option( 'xero_run_mode', 'stop' );
                    }
                
                $html .= '<br>done';
                }
            } catch (Exception $e) {
                error_log('Error collecting charges from Stripe: ' . $e->getMessage());
            }
        }
    }
    return $html;
}

// runs every 2 mins once triggered by the function above
add_action( 'send_a_batch_to_xero_hook', 'send_a_batch_to_xero' );
function send_a_batch_to_xero(){
    global $wpdb;
    ccpa_write_log('function send_a_batch_to_xero');
    $payments_table = $wpdb->prefix.'ccpa_payments';
    $batches = get_option( 'charges_to_sync_to_xero', array() );
    if( ! is_array( $batches ) || empty( $batches ) ){
        // stop running this function
        $timestamp = wp_next_scheduled( 'send_a_batch_to_xero_hook' );
        if ($timestamp) {
            wp_unschedule_event( $timestamp, 'send_a_batch_to_xero_hook' );
            ccpa_write_log('batch processing ended');
        }
    }else{
        ccpa_write_log( count( $batches ).' batches to process' );
        $this_batch = array_shift( $batches );

        foreach ($this_batch as $charge_id) {
            ccpa_write_log('charge_id: '.$charge_id);

            // Lookup payment record in WordPress database
            $payment = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $payments_table WHERE charge_id = %s LIMIT 1",
                $charge_id
            ), ARRAY_A );

            if ($payment) {
                ccpa_write_log('payment '.$payment['id'].' matches');
                if( $payment['invoice_no'] == '' ){
                    xero_create_invoice( $payment );
                }else{
                    ccpa_write_log('Invoice not created as apparently it has already been set up: '.$payment['invoice_no']);
                }
            }else{
                ccpa_write_log('ERROR: payment not found for charge '.$charge_id.' - no invoice created in Xero (maybe a value cards purchase?)');
            }
        }
        // then save the remaining batches
        update_option('charges_to_sync_to_xero', $batches);
    }
}

// create an invoice from the payment and record the number in the payment record
function xero_create_invoice( $payment ){
    global $rpm_theme_options;

    // load xero api
    $xero = get_xero_client();

    // Extract details from the payment record
    $course = $payment['workshop_id'];
    $upsell = $payment['upsell_workshop_id'];

    // get attendees
    $quantity = count( cc_attendees_for_payment( $payment['id'] ) );
    if( $quantity < 1 ){
        $quantity = 1;
    }

    // Calculate the net amounts
    $workshop_amount = ( $payment['payment_amount'] - $payment['upsell_payment_amount'] ) / $quantity;
    if( $payment['vat_included'] > 0 ){
        $taxType = 'OUTPUT2';
        $workshop_amount = round( $workshop_amount / 1.2, 2 );
    }else{
        $taxType = 'NONE';
    }

    $xero_tracking_code = get_post_meta( $course, '_xero_tracking_code', true );

    // invoice date comes from payment date
    $dateTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $payment['last_update'] );
    $invoice_date = $dateTime->format( 'Y-m-d' );
    $dateTime->modify( '+30 days' );
    $invoice_due_date = $dateTime->format( 'Y-m-d' );

    // we need a name for the invoice ... From Stripe ($charge->billing_details->name) would be better but that would mean re-extracting the Stripe charge data so ...
    $user = get_user_by( 'ID', $payment['reg_userid'] );
    $user_name = cc_users_user_name( $user );

    if( ensure_xero_tracking_exists( $xero_tracking_code ) ) {

        // Prepare data for Xero
        $xero_invoice_data = [
            'Type' => 'ACCREC',
            'Contact' => [
                'Name' => $user_name
            ],
            'LineItems' => [
                [
                    'Description' => $course.' '.get_the_title( $course ),
                    'Quantity' => $quantity,
                    'UnitAmount' => $workshop_amount,
                    'AccountCode' => '002.00',
                    'TaxType' => $taxType,
                    'Tracking' => [
                        [
                            'Name' => 'Training',
                            'Option' => $xero_tracking_code
                        ]
                    ]
                ]
            ],
            'Date' => $invoice_date,
            'DueDate' => $invoice_due_date,
            'CurrencyCode' => $payment['currency'],
            'Status' => 'AUTHORISED',
            'Reference' => $payment['charge_id'],
        ];

        if( $upsell > 0 ){
            $upsell_amount = $payment['upsell_payment_amount'] / $quantity;
            $xero_tracking_code = get_post_meta( $upsell, '_xero_tracking_code', true );
            if( ensure_xero_tracking_exists( $xero_tracking_code ) ){
                if( $payment['vat_included'] > 0 ){
                    $upsell_amount = round( $upsell_amount / 1.2, 2 );
                }
                $xero_invoice_data['LineItems'][] = [
                    'Description' => $upsell.' '.get_the_title( $upsell ),
                    'Quantity' => $quantity,
                    'UnitAmount' => $upsell_amount,
                    'AccountCode' => '002.00',
                    'TaxType' => $taxType,
                    'Tracking' => [
                        [
                            'Name' => 'Training',
                            'Option' => $xero_tracking_code
                        ]
                    ]
                ];
                $upsell_set = true;
            }else{
                error_log("Failed to ensure tracking category for upsell: $xero_tracking_code.");
                $upsell_set = false;
            }
        }

        if( $upsell == 0 || $upsell_set ){
            // Send data to Xero
            try {
                $response = $xero->createInvoices( $rpm_theme_options['xero_tenant_id'], [
                    'Invoices' => [$xero_invoice_data]
                ] );
                // $response from $xero->createInvoices() is not a raw HTTP response (which would have a getBody() method). Instead, it returns a model object of type XeroAPI\XeroPHP\Models\Accounting\Invoices.
                // and save the results in the payment record
                $body = $response->getInvoices(); // This returns an array of Invoice objects
                $invoice = $body[0]; // Get the first invoice  
                $payment['invoice_no'] = $invoice->getInvoiceNumber();
                $payment['invoice_id'] = $invoice->getInvoiceID();
                cc_paymentdb_update_payment( $payment );
                ccpa_write_log( 'invoice '.$invoice->getInvoiceNumber().' created for payment '.$payment['id'] );
            } catch ( Exception $e ){
                error_log('Error creating Xero invoice: ' . $e->getMessage());
                return false;
            }
        }else{
            error_log('Invoice not created for payment '.$payment['id'] );
            return false;
        }
    } else {
        error_log("Failed to ensure tracking category for $xero_tracking_code.");
        return false;
    }

    return true;
}

/* the old function, replaced by the above
function sync_stripe_to_xero() {
    $html = 'sync_stripe_to_xero';
    global $wpdb, $rpm_theme_options;
    $payments_table = $wpdb->prefix.'ccpa_payments';

    // Load necessary Stripe and Xero libraries
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
	\Stripe\Stripe::setApiKey(cc_get_stripe_key('secret'));

    $xero = get_xero_client();

    // Get the last time the process ran (stored in an option)
    $last_run_time = get_option('last_xero_sync_run_time', strtotime('-1 day'));
    $html .= '<br>last_run_time: '.$last_run_time.' '.date( 'd/m/Y H:i:s', $last_run_time );

    /**
     * possible xero_run_modes:
     * - auto = catchup since last run
     * - stop = do nothing
     * - catchup = do 24 hrs more since the last run
     * - manual dd/mm/yyyy = do that day's payments
     *//*
    $xero_run_mode = get_option('xero_run_mode', 'stop');
    $html .= '<br>Run mode: '.$xero_run_mode;
    if( $xero_run_mode == 'stop' ){
        $html .= 'Option set to prevent this from running';
        ccpa_write_log( 'sync_stripe_to_xero not running as option is set to STOP' );
    }else{
        $abort = false;
        if( $xero_run_mode == 'auto' ){
            // everything since we last ran
            $stripe_date_args = ['gte' => $last_run_time];
        }elseif( $xero_run_mode == 'catchup' ){
            // 24 hours of charges since the last run
            $dateTime = DateTime::createFromFormat( 'U', $last_run_time ); // Unix format === a timestamp
            $dateTime->modify('+1 day'); // Add one day
            $end_time = $dateTime->getTimestamp(); 
            $stripe_date_args = ['gte' => $last_run_time, 'lte' => $end_time];
        }else{
            // manual
            // should look EXACTLY like this ... manual dd/mm/yyyy
            $dateTime = DateTime::createFromFormat( 'd/m/Y H:i:s', substr( $xero_run_mode, 7 ).' 00:00:00' );
            if( $dateTime ){
                $start_time = $dateTime->getTimestamp();
                $dateTime = DateTime::createFromFormat( 'd/m/Y H:i:s', substr( $xero_run_mode, 7 ).' 23:59:59' );
                $end_time = $dateTime->getTimestamp();
                $stripe_date_args = ['gte' => $start_time, 'lte' => $end_time];
            }else{
                $html .= 'Invalid date ... bailing';
                ccpa_write_log( 'Invalid date: '.$xero_run_mode.' ... bailing' );
                $abort = true;
            }
        }

        if( ! $abort ){

            try {
                // Fetch Stripe charges since the last run
                $charges = \Stripe\Charge::all([
                    'created' => $stripe_date_args,
                    'limit' => 1000
                ]);
                $html .= '<br>'.count( $charges ).' charges found';

                // Iterate over each charge
                foreach ($charges->data as $charge) {
                    $charge_id = $charge->id;

                    $html .= '<br><br>charge_id: '.$charge_id;
                    // $html .= '<br>charge ... <br>';
                    // $html .= print_r( $charge, true );

                    // Lookup payment record in WordPress database
                    $payment_record = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM $payments_table WHERE charge_id = %s LIMIT 1",
                        $charge_id
                    ));

                    if ($payment_record) {
                        // Extract details from the payment record
                        $course = $payment_record->workshop_id;
                        $upsell = $payment_record->upsell_workshop_id;
                        $html .= '<br>Payment: '.$payment_record->id.' Trainings: '.$course.' '.$upsell;
                        // $discount = $payment_record->disc_amount; // in currency
                        // $total = $charge->amount / 100; // Convert from pence to pounds

                        // get attendees
                        $quantity = count( cc_attendees_for_payment( $payment_record->id ) );
                        if( $quantity < 1 ){
                            $quantity = 1;
                        }

                        // Calculate the net amounts
                        $workshop_amount = ( $payment_record->payment_amount - $payment_record->upsell_payment_amount ) / $quantity;
                        if( $payment_record->vat_included > 0 ){
                            $taxType = 'OUTPUT2';
                            $workshop_amount = round( $workshop_amount / 1.2, 2 );
                        }else{
                            $taxType = 'NONE';
                        }

                        $xero_tracking_code = get_post_meta( $course, '_xero_tracking_code', true );

                        // invoice date comes from payment date
                        $dateTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $payment_record->last_update );
                        $invoice_date = $dateTime->format( 'Y-m-d' );
                        $dateTime->modify( '+30 days' );
                        $invoice_due_date = $dateTime->format( 'Y-m-d' );

                        if (ensure_xero_tracking_exists($xero_tracking_code)) {

                            // Prepare data for Xero
                            $xero_invoice_data = [
                                'Type' => 'ACCREC',
                                'Contact' => [
                                    'Name' => $charge->billing_details->name
                                ],
                                'LineItems' => [
                                    [
                                        'Description' => $course.' '.get_the_title( $course ),
                                        'Quantity' => $quantity,
                                        'UnitAmount' => $workshop_amount,
                                        'AccountCode' => '002.00',
                                        'TaxType' => $taxType,
                                        'Tracking' => [
                                            [
                                                'Name' => 'Training',
                                                'Option' => $xero_tracking_code
                                            ]
                                        ]
                                    ]
                                ],
                                'Date' => $invoice_date,
                                'DueDate' => $invoice_due_date,
                                'CurrencyCode' => $payment_record->currency,
                                'Status' => 'AUTHORISED',
                            ];

                            if( $upsell > 0 ){
                                $upsell_amount = $payment_record->upsell_payment_amount / $quantity;
                                $xero_tracking_code = get_post_meta( $upsell, '_xero_tracking_code', true );
                                if( $payment_record->vat_included > 0 ){
                                    $upsell_amount = round( $upsell_amount / 1.2, 2 );
                                }
                                $xero_invoice_data['LineItems'][] = [
                                    'Description' => $upsell.' '.get_the_title( $upsell ),
                                    'Quantity' => $quantity,
                                    'UnitAmount' => $upsell_amount,
                                    'AccountCode' => '002.00',
                                    'TaxType' => $taxType,
                                    'Tracking' => [
                                        [
                                            'Name' => 'Training',
                                            'Option' => $xero_tracking_code
                                        ]
                                    ]
                                ];
                            }

                            // $html .= '<br>Invoice ...<br>';
                            // $html .= print_r( $xero_invoice_data, true );

                            // Send data to Xero
                            try {
                                $response = $xero->createInvoices( $rpm_theme_options['xero_tenant_id'], [
                                    'Invoices' => [$xero_invoice_data]
                                ] );
                                $html .= '<br>Invoice created';
                                // $html .= '<br>Invoice creation response... <br>';
                                // $html .= print_r($response, true);
                            } catch ( Exception $e ){
                                $html .= '<br>Error creating Xero invoice: ' . $e->getMessage();
                                error_log('Error creating Xero invoice: ' . $e->getMessage());
                            }
                            /*
                            $xero = new \XeroAPI\XeroPHP\Api\AccountingApi();
                            $api_instance = $xero->createInvoices($rpm_theme_options['xero_tenant_id'], [
                                'Invoices' => [$xero_invoice_data]
                            ]);

                            if ($api_instance) {
                                error_log("Invoice created in Xero for charge ID: $charge_id");
                            }
                            *//*

                        } else {
                            error_log("Failed to ensure tracking category for $xero_tracking_code.");
                            $html .= '<br>Failed to ensure tracking category for '.$xero_tracking_code;
                        }
                    }
                }

                // Update the last run time
                if( $xero_run_mode == 'auto' ){
                    update_option( 'last_xero_sync_run_time', time() );
                }else{
                    update_option( 'last_xero_sync_run_time', $end_time );
                    if( substr( $xero_run_mode, 0, 6 ) == 'manual' ){
                        update_option( 'xero_run_mode', 'stop' );
                    }
                }
                
                $html .= '<br>done';

            } catch (Exception $e) {
                error_log('Error syncing Stripe to Xero: ' . $e->getMessage());
            }
        }
    }
    return $html;
}
*/

/**
 * Searches for the invoice using the Stripe charge ID or date & amount.
 * Retrieves the invoice number, ID, and tracking category.
 * If missing, it updates the invoice with the provided tracking category option.
 * Returns the invoice details for storage.
 */
add_shortcode( 'findAndUpdateXeroInvoice', function() {
    global $wpdb, $rpm_theme_options;
    $html = 'function findAndUpdateXeroInvoice';
    $payments_table = $wpdb->prefix.'ccpa_payments';

    $xero = get_xero_client();
    $tenant_id = $rpm_theme_options['xero_tenant_id'];
    $tracking_category_id = get_xero_training_tracking_category_id(); // the id for "Training"

    // get all the relevant payment records
    $payment_records = $wpdb->get_results(
        "SELECT * FROM $payments_table WHERE charge_id <> '' AND invoice_no = '' AND last_update > '2024-10-27 00:00:00' and last_update < '2024-12-02 00:00:00' ORDER BY id LIMIT 10",
        ARRAY_A
    );

    $html .= '<br>'.count( $payment_records ).' payment records found';

    // now get all the Xero invoices
    $invoicesResponse = $xero->getInvoices($tenant_id);
    $invoices = $invoicesResponse->getInvoices(); // Get the array of Invoice objects

    $html .= '<br>'.count( $invoices ).' invoices collected';

    foreach ($payment_records as $paymentRecord) {

        $html .= '<br><br>';

        $stripeChargeId = $paymentRecord['charge_id'];
        $payment_date = date( 'Y-m-d', strtotime( $paymentRecord['last_update'] ) );
        $amount = $paymentRecord['payment_amount'] * 100; // do all the comparisons in pence

        $user = get_user_by( 'ID', $paymentRecord['reg_userid'] );
        $userName = cc_users_user_name( $user );

        $html .= $stripeChargeId.' '.$payment_date.' '.$amount.' '.$userName;

        // search for the invoice
        $invoices_found = 0;
        foreach ( $invoices as $invoice) {
            /*
            if( $invoice->getInvoiceNumber() == 'INV-3539' ){
                $html .= '<br>INV-3539 is for #'.$invoice->getContact()->getName().'#';
                $html .= '<br>currency: '.$invoice->getCurrencyCode();
                $html .= '<br>amount: '.$invoice->getTotal();
                $html .= '<br>user name: '.$userName;
                if( $userName == $invoice->getContact()->getName() ){
                    $html .= ' names match';
                }else{
                    $html .= ' names do not match';
                }
            }
            */
            // looking for a match on date, name and amount
            // start with name
            $invoice_name = $invoice->getContact()->getName();
            if( $invoice_name == $userName ){
                // $html .= '<br>'.$invoice_name;
                // ok now try amount
                // Xero stores things in pence/cents/...
                $invoice_amount = $invoice->getTotal() * 100;
                // $html .= ' '.$invoice_amount;
                if( $invoice_amount == $amount ){
                    // now see if it also matches on date
                    $xeroDate = $invoice->getDate();
                    preg_match('/\/Date\((\d+)(?:[+-]\d+)?\)\//', $xeroDate, $matches);
                    if (isset($matches[1])) {
                        $unixTimestamp = intval($matches[1]) / 1000; // Convert milliseconds to seconds
                        $invoice_date = date("Y-m-d", $unixTimestamp);
                        // $html .= ' '.$invoice_date;
                        if( $invoice_date == $payment_date ){
                            $invoices_found ++;
                            // we may have a good match here
                            $invoiceId = $invoice->getInvoiceID();
                            $invoiceNumber = $invoice->getInvoiceNumber();

                            /* line items are not returned when you request all invoices!!!!
                            $line_1_tracking = '';
                            $existingTracking = $invoice['LineItems'][0]['Tracking'] ?? [];
                            if (!empty($existingTracking)) {
                                $line_1_tracking = $existingTracking[0]['Option'];
                            }
                            */

                            $html .= '<br>Matches: '.$invoiceNumber.' '.$invoiceId;
                        }
                    }
                }
            }
        }

        if( $invoices_found == 1 ){
            $html .= '<br>Good to go!';
            // party on!
            // we want to update the payment record and the invoice
            // payment needs to have invoice_no and invoice_id
            // invoice may need to have a tracking thingy and a reference
            $update_xero = false;
            $tracking_ok = false;
            // get a fresh copy of the invoice
            $invoice_array = $xero->getInvoice( $tenant_id, $invoiceId ); // this returns an array of one invoice!
            $invoice = $invoice_array->getInvoices()[0]; // to get the actual invoice
            $lineItems = $invoice->getLineItems(); // The response contains an array inside "Invoices"
            $line_num = 0;
            foreach ($lineItems as $lineItem) {
                // $html .= ' 1:'.$line_num;
                if ($lineItem->getTracking()) {
                    // $html .= ' 2 ';
                    foreach ($lineItem->getTracking() as $line_tracking) {
                        // $html .= ' 3 ';
                        if (empty($line_tracking->getName())) {
                            // i don;t think we ever get here ....
                            $html .= '<br>##### found line item with blank tracking name somehow ...';
                            /*
                            // not set
                            if( $line_num == 0 ){
                                $html .= ' 4 ';
                                $xero_tracking_code = get_post_meta( $paymentRecord['workshop_id'], '_xero_tracking_code', true );
                            }elseif( $paymentRecord['upsell_workshop_id'] > 0 ){
                                $html .= ' 5 ';
                                $xero_tracking_code = get_post_meta( $paymentRecord['upsell_workshop_id'], '_xero_tracking_code', true );
                            }
                            $html .= 'Line '.$line_num.' tracking: '.$xero_tracking_code;
                            if( $xero_tracking_code <> '' ){
                                $html .= ' 6 ';
                                $tracking_option_id = ensure_xero_tracking_exists( $xero_tracking_code );
                                if( $tracking_option_id ) {
                                    $html .= ' 7 ';
                                    $trackingCategory = new \XeroAPI\XeroPHP\Models\Accounting\TrackingCategory();
                                    $trackingCategory->setName("Training");
                                    $trackingCategory->setOption($xero_tracking_code);

                                    $lineItems[$line_num]->setTracking([$trackingCategory]);
                                    $invoice->setLineItems($lineItems);
                                    $update_xero = true;
                                }
                            }
                            */
                        }else{
                            $html .= '<br>tracking already set to<br>';
                            $html .= print_r( $line_tracking, true );
                            $tracking_ok = true;
                        }
                    }
                }else{
                    // no tracking set for this line item
                    // $html .= '<br>Tracking Category ID: '.$tracking_category_id;
                    if( $line_num == 0 ){
                        // $html .= ' 10 ';
                        $xero_tracking_code = get_post_meta( $paymentRecord['workshop_id'], '_xero_tracking_code', true );
                    }elseif( $paymentRecord['upsell_workshop_id'] > 0 ){
                        $html .= '<br>UPSELL!';
                        $xero_tracking_code = get_post_meta( $paymentRecord['upsell_workshop_id'], '_xero_tracking_code', true );
                    }
                    // $html .= 'Line '.$line_num.' tracking: '.$xero_tracking_code;
                    if( $xero_tracking_code <> '' ){
                        // $html .= ' 12 ';
                        $tracking_option_id = ensure_xero_tracking_exists( $xero_tracking_code );
                        // $html .= '<br>tracking option id: '.$tracking_option_id;
                        if(strlen($tracking_option_id) == 36){
                            // $html .= ' 13 ';
                            $trackingCategory = new \XeroAPI\XeroPHP\Models\Accounting\TrackingCategory();
                            $trackingCategory->setTrackingCategoryID($tracking_category_id);
                            $trackingCategory->setName("Training");
                            $trackingCategory->setTrackingOptionID($tracking_option_id);
                            $trackingCategory->setOption($xero_tracking_code);

                            $lineItems[$line_num]->setTracking([$trackingCategory]);
                            $invoice->setLineItems($lineItems);

                            $html .= '<br>Tracking set to '.$xero_tracking_code;
                            $update_xero = true;
                            $tracking_ok = true;
                        }else{
                            $html .= '<br>##### length of tracking option id is '.strlen($tracking_option_id).' ... ignored!';
                        }
                    }else{
                        $html .= '<br>no tracking code for this training!';
                    }
                }
                $line_num ++;
            }

            // If reference is missing, set it to charge_id
            $reference = $invoice->getReference();
            // $html .= '<br>Ref was: '.$reference;
            if ( ! $reference || $reference == '' ) {
                // $html . ' 8 ';
                $invoice->setReference($stripeChargeId);
                // $html .= '<br>Ref becoming: '.$stripeChargeId;
                $update_xero = true;
            }

            // Save updates to Xero
            if ( $update_xero && $tracking_ok ) {
                // $html .= ' 9 ';
                $invoice_array2 = new \XeroAPI\XeroPHP\Models\Accounting\Invoices();
                $invoice_array2->setInvoices([$invoice]); // Explicitly setting invoices array
                $updatedInvoice = $xero->updateInvoice($tenant_id, $invoiceId, $invoice_array2);
                // the following does the same as the 3 lines above, bundled into one line
                // $xero->updateInvoice($tenant_id, $invoice->getInvoiceID(), new \XeroAPI\XeroPHP\Models\Accounting\Invoices(['Invoices' => [$invoice]]));
                $html .= '<br>Xero updated';
            }

            // save updates to the payment record
            $paymentRecord['invoice_no'] = $invoiceNumber;
            $paymentRecord['invoice_id'] = $invoiceId;
            // $html .= '<br>payment update: '.$invoiceNumber.' '.$invoiceId;
            if( $tracking_ok ){
                cc_paymentdb_update_payment( $paymentRecord );
                $html .= '<br>payment updated';
            }
        }elseif( $invoices_found == 0 || $stripeChargeId == 'ch_3QRKxkCbNJKjn0bn0vC38vTq' ){
            // create it then .......
            $html .= '<br>creating the invoice ...';
            xero_create_invoice( $paymentRecord );
            $html .= ' done';
        }else{
            $html .= '<br>Invoices found: '.$invoices_found.' no further action being taken';
        }

    }
    return $html;
});




// when a training is saved we need to check to see if a Xero tracking code has been set up
// if not, we'll set it up
add_action( 'save_post', function( $post_id ){
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if( isset( $_POST['post_type'] ) 
        && ( $_POST['post_type'] == 'workshop' || $_POST['post_type'] == 'course' ) 
        && current_user_can( 'edit_post', $post_id ) ){
        $new_tracking_code = '';
        $xero_tracking_code = get_post_meta( $post_id, '_xero_tracking_code', true );
        // tracking code is made up of the first bit of the title followed by Liv or Pub, the date, then the ID
        // eg Introduction to ACT Liv 06 NOV 2025 12345
        // or Introduction to ACT Pub 06 NOV 2025 54321
        // Xero allows a max of 50 chars therefore we can only take 50-22=28 chars from the title
        $title_bit = substr( isset( $_POST['post_title'] ) ? $_POST['post_title'] : '', 0, 28 );
        if( $title_bit <> '' ){
            if( $_POST['post_type'] == 'workshop' ){
                // start date held in meta_a
                $start_date = isset( $_POST['meta_a'] ) ? $_POST['meta_a'] : '';
                if( $start_date <> '' ){
                    $dateTime = DateTime::createFromFormat('d/m/Y', $start_date);
                    $formattedDate = $dateTime->format('d M Y');
                    $new_tracking_code = $title_bit.' Liv '.$formattedDate.' '.$post_id;
                }
            }else{
                // there may already be a published date
                $pub_date = get_the_date( 'd M Y', $post_id );
                if( ! $pub_date ){
                    $pub_date = date( 'd M Y' );
                }
                $new_tracking_code = $title_bit.' Pub '.$pub_date.' '.$post_id;
            }
        }
        // Replace multiple spaces with a single space and remove any leading/trailing spaces
        $new_tracking_code = preg_replace('/\s+/', ' ', trim($new_tracking_code));

        if( $new_tracking_code <> '' && $xero_tracking_code <> $new_tracking_code ){
            update_post_meta( $post_id, '_xero_tracking_code', $new_tracking_code );
        }
    }
});

// one off exercise to setup tracking codes
add_shortcode( 'setup_xero_tracking_codes', function(){
    $html = 'setup_xero_tracking_codes';
    $args = array(
        'post_type' => array( 'workshop', 'course' ),
        'numberposts' => -1,
    );
    $trainings = get_posts( $args );
    $html .= '<br>'.count( $trainings ).' trainings';
    foreach ($trainings as $training) {
        $new_tracking_code = '';
        $xero_tracking_code = get_post_meta( $training->ID, '_xero_tracking_code', true );
        // tracking code is made up of the first bit of the title followed by Liv or Pub, the date, then the ID
        // eg Introduction to ACT Liv 06 Nov 2025 12345
        // or Introduction to ACT Pub 06 Nov 2025 54321
        // Xero allows a max of 50 chars therefore we can only take 50-22=28 chars from the title
        $title_bit = substr( get_the_title( $training->ID ), 0, 28 );
        if( $title_bit <> '' ){
            if( $training->post_type == 'workshop' ){
                // start date held in meta_a
                $start_date = get_post_meta( $training->ID, 'meta_a', true );
                if( $start_date <> '' ){
                    $dateTime = DateTime::createFromFormat('d/m/Y', $start_date);
                    $formattedDate = $dateTime->format('d M Y');
                    $new_tracking_code = $title_bit.' Liv '.$formattedDate.' '.$training->ID;
                }
            }else{
                $pub_date = get_the_date( 'd M Y', $training->ID );
                if( $pub_date ){
                    $new_tracking_code = $title_bit.' Pub '.$pub_date.' '.$training->ID;
                }
            }
        }
        if( $new_tracking_code <> '' && $xero_tracking_code <> $new_tracking_code ){
            update_post_meta( $training->ID, '_xero_tracking_code', $new_tracking_code );
            $html .= '<br>code for '.$training->ID.' set to '.$new_tracking_code;
        }
    }
    $html .= '<br>done';
    return $html;
} );


/**
 * Create Authentication Functions
 */

use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\ApiClient;
use XeroAPI\XeroPHP\OAuth2\OAuth2Provider;

function get_xero_access_token() {
    global $rpm_theme_options;

    // Check if we already have a valid token
    $access_token = get_option('xero_access_token');
    $token_expires = get_option('xero_token_expires');
    $refresh_token = get_option('xero_refresh_token');

    if ($access_token && $token_expires && time() < $token_expires) {
        return $access_token; // Use the cached token if still valid
    }

    // If the access token is expired, use the refresh token to get a new one
    if ($refresh_token) {
        try {
            $client = new GuzzleHttp\Client();
            $response = $client->post('https://identity.xero.com/connect/token', [
                'form_params' => [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refresh_token,
                    'client_id'     => $rpm_theme_options['xero_client_id'],
                    'client_secret' => $rpm_theme_options['xero_client_secret'],
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $body = json_decode($response->getBody(), true);

            if (isset($body['access_token']) && isset($body['refresh_token'])) {
                // Store the new tokens
                update_option('xero_access_token', $body['access_token']);
                update_option('xero_refresh_token', $body['refresh_token']);
                update_option('xero_token_expires', time() + $body['expires_in']); // Typically 1800s (30 minutes)

                return $body['access_token'];
            }

        } catch (Exception $e) {
            error_log('Error refreshing Xero token: ' . $e->getMessage());
            ccpa_write_log( wp_debug_backtrace_summary() );
            return false;
        }
    }

    error_log('Xero authorisation required. No valid tokens available.');
    return false;
}

// Create a callback endpoint to handle Xero's OAuth2 redirect during the initial setup process.
function handle_xero_callback() {
    global $rpm_theme_options;
    if (isset($_GET['code'])) {
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => $rpm_theme_options['xero_client_id'],
            'clientSecret'            => $rpm_theme_options['xero_client_secret'],
            'redirectUri'             => $rpm_theme_options['xero_redirect_uri'],
            'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
            'urlAccessToken'          => 'https://identity.xero.com/connect/token',
            'urlResourceOwnerDetails' => ''
        ]);

        try {
            // Exchange the authorization code for an access token
            $access_token = $provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            // Store tokens securely in WordPress options
            update_option('xero_access_token', $access_token->getToken());
            update_option('xero_refresh_token', $access_token->getRefreshToken());
            update_option('xero_token_expires', $access_token->getExpires());

            ccpa_write_log('handle_xero_callback: updated tokens stored');

            wp_redirect(admin_url()); // Redirect to the WordPress admin page after successful authorization
            exit;

        } catch (Exception $e) {
            error_log('Xero OAuth callback error: ' . $e->getMessage());
        }
    }
}
add_action('init', 'handle_xero_callback', 99);

// Initialize the Xero API Client
function get_xero_client() {
    $access_token = get_xero_access_token();
    if (!$access_token) {
        error_log('No valid Xero access token found.');
        return false;
    }

    $config = XeroAPI\XeroPHP\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
    return new XeroAPI\XeroPHP\Api\AccountingApi(new GuzzleHttp\Client(), $config);
}

/**
 * Run a Background Task to Refresh the Token
 */
function add_xero_cron_interval($schedules) {
    $schedules['every_25_minutes'] = array(
        'interval' => 1500, // 25 minutes in seconds
        'display'  => __('Every 25 Minutes')
    );
    return $schedules;
}
add_filter('cron_schedules', 'add_xero_cron_interval');

function schedule_xero_token_refresh() {
    if (!wp_next_scheduled('xero_refresh_token_event')) {
        wp_schedule_event(time(), 'every_25_minutes', 'xero_refresh_token_event');
    }
}
add_action('init', 'schedule_xero_token_refresh');

function refresh_xero_token_cron() {
    $token = get_xero_access_token(); // This will refresh the token if needed
    if( ! $token ){
        ccpa_write_log('ERROR: function refresh_xero_token_cron Failed to refresh Xero token.');
    }
}
add_action('xero_refresh_token_event', 'refresh_xero_token_cron');

// manually trigger a token refresh
add_shortcode( 'trigger_xero_token_refresh', function(){
    $html = 'trigger_xero_token_refresh';
    $token = get_xero_access_token(); // forces a refresh if necessary
    if( $token ){
        $html .= "<br>New token: " . esc_html($token);
    }else{
        $html .= '<br>Failed to refresh token.';
    }
    return $html;
});

// Find out which tenants the user has consented to by calling the connections endpoint with the access token provided during the authorisation flow.
add_shortcode( 'get_xero_tenants', function(){
    $html = 'get_xero_tenants';
    $access_token = get_xero_access_token(); // Fetch the access token

    if (!$access_token) {
        $html .= '<br>No valid Xero access token found ... bailing!';
        return $html;
    }

    try {
        $client = new GuzzleHttp\Client();
        $response = $client->request('GET', 'https://api.xero.com/connections', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Accept'        => 'application/json',
            ],
        ]);

        $body = json_decode($response->getBody(), true);

        $html .= print_r( $body, true );
        /* response ...
        Array ( 
            [0] => Array ( 
                [id] => b68636c0-85d9-445b-bbca-03c5d59e5f90 
                [authEventId] => 27670463-c171-4ee4-be98-1ac2cd5ca1ff 
                [tenantId] => 3ff94d2d-507b-456a-ba09-77a15d313401 
                [tenantType] => ORGANISATION 
                [tenantName] => Contextual Consulting Limited 
                [createdDateUtc] => 2025-02-05T12:25:26.5853900 
                [updatedDateUtc] => 2025-02-05T14:38:26.7658710
        ) )
        */
    } catch (Exception $e) {
        $html .= '<br>Error fetching Xero tenant ID: '.$e->getMessage();
    }
    return $html;
});

function get_xero_tracking_categories() {
    global $rpm_theme_options;
    $access_token = get_xero_access_token();
    $tenant_id = $rpm_theme_options['xero_tenant_id'];

    if (!$access_token || !$tenant_id) {
        error_log('Missing Xero credentials for fetching tracking categories.');
        return false;
    }

    /*
    ccpa_write_log('function get_xero_tracking_categories');
    ccpa_write_log('access_token: '.$access_token);
    ccpa_write_log('tenant_id: '.$tenant_id);
    ccpa_write_log('xero_token_expires: '.get_option('xero_token_expires').' = '.date('d/m/Y H:i:s',get_option('xero_token_expires') ) );
    $token_parts = explode(".", $access_token);
    if (count($token_parts) === 3) {
        $payload = json_decode(base64_decode($token_parts[1]), true);
        ccpa_write_log('Decoded Xero Token: ' . print_r($payload, true));
    }
    */

    try {
        $client = new GuzzleHttp\Client();
        $response = $client->request('GET', 'https://api.xero.com/api.xro/2.0/TrackingCategories', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Accept'        => 'application/json',
                'Xero-Tenant-Id' => $tenant_id,
            ],
        ]);

        $body = json_decode($response->getBody(), true);
        return $body['TrackingCategories'] ?? [];

    } catch (Exception $e) {
        // error_log('Error fetching Xero tracking categories: ' . $e->getMessage());
        // Get the response body for debugging
        $responseBody = $e->getResponse()->getBody()->getContents();
        error_log('Error fetching Xero tracking categories: ' . $e->getMessage());
        error_log('Response Body: ' . $responseBody);
        return false;
    }
}

// function create_xero_tracking_category($category_name, $options = []) {
function create_xero_tracking_category_option( $training_category_id, $training_course ) {
    global $rpm_theme_options;
    // ccpa_write_log('function create_xero_tracking_category_option');
    // ccpa_write_log($training_category_id.' '.$training_course);

    /*
    ccpa_write_log('function create_xero_tracking_category_option');
    ccpa_write_log('training_category_id: '.$training_category_id);
    ccpa_write_log('training_course: '.$training_course);
    */

    $access_token = get_xero_access_token();
    $tenant_id = $rpm_theme_options['xero_tenant_id'];

    if (!$access_token || !$tenant_id) {
        error_log('Missing Xero credentials for creating tracking category option.');
        return false;
    }

    try {
        $client = new GuzzleHttp\Client();
        // Note: "PUT" not "POST"!
        $response = $client->request('PUT', 'https://api.xero.com/api.xro/2.0/TrackingCategories/' . $training_category_id.'/Options', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Xero-Tenant-Id' => $tenant_id,
            ],
            'json' => [
                'Name' => $training_course
            ]
        ]);
        $body = json_decode($response->getBody(), true);
        // ccpa_write_log('body ...');
        // ccpa_write_log($body);
        // ccpa_write_log('body Options ...');
        // ccpa_write_log($body['Options']);
        // ccpa_write_log('body Options 0 ...');
        // ccpa_write_log($body['Options'][0]);
        // ccpa_write_log('body Options 0 optionID...');
        // ccpa_write_log($body['Options'][0]['TrackingOptionID']);
        // Ensure the response contains the Option
        if (!empty($body['Options'][0]['TrackingOptionID'])) {
            // ccpa_write_log('option id not empty: '.$body['Options'][0]['TrackingOptionID']);
            return $body['Options'][0]['TrackingOptionID'];
        }else{
            // ccpa_write_log('option id empty!!!!!!!!!!!');
        }
        return false;

    } catch ( \GuzzleHttp\Exception\RequestException $e ) {
        error_log('Error creating Xero tracking category option: ' . $e->getMessage());
        if ($e->hasResponse()) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            error_log('Response Body: ' . $responseBody);
        }
        return false;
    }
}

// returns the tracking category option id
function ensure_xero_tracking_exists($training_course) {
    global $rpm_theme_options;
    // ccpa_write_log('function ensure_xero_tracking_exists');
    // ccpa_write_log($training_course);

    $tracking_categories = get_xero_tracking_categories();

    if (!$tracking_categories) {
        error_log('Failed to fetch tracking categories from Xero.');
        return false;
    }

    // Look for "Training" tracking category
    $training_category_id = '';
    foreach ($tracking_categories as $category) {
        if ( strtolower( $category['Name'] ) == 'training') {
            $training_category_id = $category['TrackingCategoryID'];
            // Check if the specific course option exists
            foreach ($category['Options'] as $option) {
                /* $option looks like this:
                Array(
                    [TrackingOptionID] => 8e6e22a6-eb0c-4654-a36c-662ebebf9779
                    [Name] => Applying RFT in therapeutic Liv 25 Feb 2025 12925
                    [Status] => ACTIVE
                    [HasValidationErrors] => 
                    [IsDeleted] => 
                    [IsArchived] => 
                    [IsActive] => 1
                ) */
                if ($option['Name'] === $training_course) {
                    // Tracking category and option already exist
                    // ccpa_write_log($option);
                    return $option['TrackingOptionID'];
                }
            }

            // Add the new course option if the category exists but not the option
            $option_id = create_xero_tracking_category_option( $training_category_id, $training_course );
            // ccpa_write_log('option id returned was: '.$option_id);
            return $option_id;
        }
    }

    // ccpa_write_log('If the category itself does not exist, create it first');
    $training_category_id = create_xero_tracking_category();
    return create_xero_tracking_category_option( $training_category_id, $training_course );
}

function create_xero_tracking_category(){
    global $rpm_theme_options;
    // ccpa_write_log('function create_xero_tracking_category');

    $access_token = get_xero_access_token();
    $tenant_id = $rpm_theme_options['xero_tenant_id'];

    if (!$access_token || !$tenant_id) {
        error_log('Missing Xero credentials for creating tracking category.');
        return false;
    }

    try {
        $client = new GuzzleHttp\Client();
        $response = $client->request('POST', 'https://api.xero.com/api.xro/2.0/TrackingCategories', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Xero-Tenant-Id' => $tenant_id,
            ],
            'json' => [
                'Name' => 'Training'
            ]
        ]);

        $body = json_decode($response->getBody(), true);
        // ccpa_write_log($body);
        // ccpa_write_log('TrackingCategoryID: '.$body['TrackingCategories'][0]['TrackingCategoryID']);
        return $body['TrackingCategories'][0]['TrackingCategoryID'] ?? false;

    } catch (Exception $e) {
        // error_log('Error creating Xero tracking category: ' . $e->getMessage());
        $responseBody = $e->getResponse()->getBody()->getContents();
        error_log('Error creating Xero tracking category: ' . $e->getMessage());
        error_log('Response Body: ' . $responseBody);
        return false;
    }
}

/*
add_shortcode('test_add_option', function(){
    global $rpm_theme_options;
    $access_token = get_xero_access_token();
    $tenant_id = $rpm_theme_options['xero_tenant_id'];
    $html = '';
    // $html .= print_r( get_xero_tracking_categories(), true );

    $client = new GuzzleHttp\Client();
    $response = $client->request('GET', "https://api.xero.com/api.xro/2.0/TrackingCategories", [
        'headers' => [
            'Authorization' => 'Bearer ' . $access_token,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Xero-Tenant-Id' => $tenant_id,
        ]
    ]);
    $body = json_decode($response->getBody(), true);
    $html .= print_r($body, true); // Verify that the correct ID exists

    $html .= create_xero_tracking_category_option( '756be60a-7866-4ae8-8bbb-6d63068b4e27', 'TestOption456' );
    return $html;
});
*/

// retrieve the Training tracking category ID
function get_xero_training_tracking_category_id(){
    global $rpm_theme_options;
    $xero = get_xero_client();

    $trackingCategories = $xero->getTrackingCategories( $rpm_theme_options['xero_tenant_id'] )->getTrackingCategories();
    $trainingCategory = null;

    foreach ($trackingCategories as $category) {
        if (strtolower($category->getName()) === "training") {
            $trainingCategory = $category;
            break;
        }
    }

    if (!$trainingCategory) {
        error_log("Training tracking category not found.");
    }

    return $trainingCategory->getTrackingCategoryID();
}

/*
add_shortcode( 'test_create_tracking_category_option', function () {
    create_xero_tracking_category_option( get_xero_training_tracking_category_id(), 'Applying RFT in therapeutic  Liv 25 Feb 2025 12925' );
} );
*/

/*
// void the loads of invoices created in error! ARGH!
add_shortcode( 'deleteDuplicateInvoices', function (){
    global $rpm_theme_options;
    $html = 'deleteDuplicateInvoices';
    $xero = get_xero_client();

    $duplicated_invoices = array(
        // array( 'ref' => 'ch_3QRh9ZCbNJKjn0bn1wp40nQb', 'inv_to_keep' => 'INV-4330' ),
        array( 'ref' => 'ch_3QRfabCbNJKjn0bn1N0o1KOF', 'inv_to_keep' => 'INV-4331' ),
        array( 'ref' => 'ch_3QRfZHCbNJKjn0bn0fNXtsnL', 'inv_to_keep' => 'INV-4332' ),
        array( 'ref' => 'ch_3QRfUPCbNJKjn0bn0vzdcjq9', 'inv_to_keep' => 'INV-4333' ),
        array( 'ref' => 'ch_3QRegYCbNJKjn0bn0gFJ9VmZ', 'inv_to_keep' => 'INV-4334' ),
        array( 'ref' => 'ch_3QRaFKCbNJKjn0bn1gXfDVtQ', 'inv_to_keep' => 'INV-4335' ),
        array( 'ref' => 'ch_3QRZOUCbNJKjn0bn14ozyVjj', 'inv_to_keep' => 'INV-4336' ),
    );

    $count = 0;
    foreach ($duplicated_invoices as $keys) {
        try {
            // Step 1: Get all invoices with the specified reference
            $invoicesResponse = $xero->getInvoices( $rpm_theme_options['xero_tenant_id'], null, 'Reference=="' . $keys['ref'] . '"');
            $invoices = $invoicesResponse->getInvoices();
            
            if (empty($invoices)) {
                $html .= "No invoices found with reference ".$keys['ref']."<br>";
                return;
            }

            foreach ($invoices as $invoice) {
                // Step 2: Skip the original invoice
                if ($invoice->getInvoiceNumber() === $keys['inv_to_keep']) {
                    $html .= "Skipping invoice: " . $invoice->getInvoiceNumber() . "<br>";
                    continue;
                }

                // Step 3: Void the duplicate invoice
                /* this approach failed!
                $invoice->setStatus('VOIDED');
                $xero->updateInvoice( $rpm_theme_options['xero_tenant_id'], $invoice->getInvoiceID(), 
                    new \XeroAPI\XeroPHP\Models\Accounting\Invoice(['Invoices' => [$invoice]])
                ); *//*

                // Step 3: Prepare and send the void update
                $updatedInvoice = new \XeroAPI\XeroPHP\Models\Accounting\Invoice();
                $updatedInvoice->setInvoiceID($invoice->getInvoiceID());
                $updatedInvoice->setStatus('VOIDED');

                $xero->updateInvoice( $rpm_theme_options['xero_tenant_id'], $invoice->getInvoiceID(), $updatedInvoice);

                $html .= "Voided duplicate invoice: " . $invoice->getInvoiceNumber() . "<br>";

                $count ++;

                if( $count > 50 ){
                    $html .= "Pausing for 60 seconds to avoid API limits...<br>";
                    sleep(60);
                    $count = 0;
                }
            }

        } catch (Exception $e) {
            $html .= "Error: " . $e->getMessage() . "<br>";
        }
    }
    return $html;
});
*/

add_shortcode( 'createMissingPayments', function() {
    global $wpdb, $rpm_theme_options;

    $html = 'createMissingPayments<br>';


    $xero = get_xero_client();
    $xero_tenant_id = $rpm_theme_options['xero_tenant_id'];
    $start_date = '2024-10-27 00:00:00';
    $end_date = '2024-10-27 23:59:59';
    $bank_account_id = 'DB6714DB-931B-4963-895C-CAA1865DF42A'; // Stripe bank account


    $accountsResponse = $xero->getAccounts($xero_tenant_id);
    $accounts = $accountsResponse->getAccounts();
    foreach ($accounts as $account) {
        $html .= "Name: " . $account->getName() . "<br>";
        $html .= "ID: " . $account->getAccountID() . "<br>";
        $html .= "Type: " . $account->getType() . "<br>";
        $html .= "Enable Payments: " . ($account->getEnablePaymentsToAccount() ? 'Yes' : 'No') . "<br>";
        $html .= "----------------------<br>";
    }




    // Step 1: Get payment records from WP that have an invoice number & were updated in the given date range
    $payments_table = $wpdb->prefix.'ccpa_payments';
    $query = $wpdb->prepare("
        SELECT invoice_no, payment_amount, currency, last_update, invoice_id
        FROM $payments_table
        WHERE last_update BETWEEN %s AND %s 
        AND invoice_no != ''", 
        $start_date, $end_date
    );

    $payments_to_process = $wpdb->get_results($query, ARRAY_A);

    $html .= count( $payments_to_process ).' payments found<br>';

    if (empty($payments_to_process)) {
        $html .= "No payment records found in the given date range.<br>";
        return $html;
    }

    $count = 0;
    foreach ($payments_to_process as $cc_payment) {
        $invoice_no = $cc_payment['invoice_no'];
        $invoice_id = $cc_payment['invoice_id'];
        $payment_amount = $cc_payment['payment_amount'];
        $currency = $cc_payment['currency'];
        $invoice_date = date( 'Y-m-d', strtotime( $cc_payment['last_update'] ) );

        // Step 2: Get the Xero invoice using the invoice number
        $where = 'InvoiceNumber=="' . $invoice_no . '"';
        $invoiceResponse = $xero->getInvoices($xero_tenant_id, null, $where);
        $invoices = $invoiceResponse->getInvoices();

        if (empty($invoices)) {
            $html .= "Invoice not found in Xero: " . $invoice_no . "<br>";
            continue;
        }

        $invoice = $invoices[0];

        $html .= 'Invoice status is '.$invoice->getStatus().'<br>';
        $html .= 'Invoice amount is '.$invoice->getAmountDue().'<br>';
        $html .= 'Payment currency is '.$currency.'<br>';

        $accountResponse = $xero->getAccount($xero_tenant_id, $bank_account_id);
        $account = $accountResponse->getAccounts()[0];
        if( $account->getEnablePaymentsToAccount() ){
            $html .= 'The bank account is the right type';
        }else{
            $html .= 'The bank account is the WRONG type';
        }


        // Step 3: Check if a payment already exists
        $paymentsResponse = $xero->getPayments($xero_tenant_id, null, 'Invoice.InvoiceID==GUID("' . $invoice->getInvoiceID() . '")');
        $existing_payments = $paymentsResponse->getPayments();

        if (!empty($existing_payments)) {
            $html .= "Skipping invoice " . $invoice_no . " - payment already exists.<br>";
            continue;
        }

        if (empty($invoice_id)) {
            $html .= "❌ Error: Invoice ID is missing!<br>";
        }
        if (empty($bank_account_id)) {
            $html .= "❌ Error: Bank Account ID is missing!<br>";
        }
        if (empty($payment_amount) || $payment_amount <= 0) {
            $html .= "❌ Error: Payment amount is invalid!<br>";
        }
        if (empty($invoice_date)) {
            $html .= "❌ Error: Payment date is missing!<br>";
        }

        /*
        $payment_data = [
            'Invoice' => ['InvoiceID' => $invoice_id],
            'Account' => ['AccountID' => $bank_account_id],  
            'Date' => $invoice_date,
            'Amount' => (float) $payment_amount,
        ];
        */

        $pmt_invoice = new \XeroAPI\XeroPHP\Models\Accounting\Invoice();
        $pmt_invoice->setInvoiceID($invoice_id);
        $pmt_account = new \XeroAPI\XeroPHP\Models\Accounting\Account();
        $pmt_account->setAccountID($bank_account_id);
        $single_payment = new \XeroAPI\XeroPHP\Models\Accounting\Payment();
        $single_payment->setInvoice($pmt_invoice)
                ->setAccount($pmt_account)
                ->setDate(new DateTime($invoice_date))
                ->setAmount(floatval($payment_amount));


        // $html .= print_r( json_encode( $payment_data, JSON_PRETTY_PRINT ), true );
        // $html .= print_r( $payment_data, true );

        // Step 4: Create a new payment
                /*
        $payment = new \XeroAPI\XeroPHP\Models\Accounting\Payment([
            'Invoice' => ['InvoiceID' => $invoice_id],
            'Account' => ['AccountID' => $bank_account_id],  
            'Date' => $invoice_date,
            'Amount' => (float) $payment_amount,
        ]);
        */

        $html .= print_r( $single_payment, true );
        // $html .= print_r( json_encode( $single_payment, JSON_PRETTY_PRINT ), true );

    return $html;

        $paymentsArray = new \XeroAPI\XeroPHP\Models\Accounting\Payments([
            'Payments' => [$single_payment]
        ]);
        $response = $xero->createPayments($xero_tenant_id, $paymentsArray);


        // $xero->createPayments($xero_tenant_id, new \XeroAPI\XeroPHP\Models\Accounting\Payments(['Payments' => [$single_payment]]));

        $html .= "Payment created for invoice: " . $invoice_no . "<br>";

        $count++;

        // Step 5: Pause every 50 payments to avoid hitting Xero's API rate limit
        if ($count % 50 === 0) {
            $html .= "Pausing for 60 seconds to avoid hitting API limits...<br>";
            sleep(60);
        }
    }
    return $html;
});

