<?php
/**
 * Generate registration button HTML for courses and workshops
 * 
 * @param int $training_id The post ID (workshop, course, or series)
 * @param array $args Optional arguments to customize button behavior
 * @return array Contains 'main' and 'footer' button HTML
 */
function cc_training_register_button( $training_id, $args = array() ) {
    
    $defaults = array(
        'post_type' => get_post_type($training_id),
        'context' => 'full', // 'full', 'main_only', 'footer_only'
        'user_id' => get_current_user_id(),
        'button_class' => 'btn btn-reg btn-lge',
        'footer_button_class' => 'btn btn-reg btn-lge',
        'override_text' => '',
        'override_footer_text' => '',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $buttons = array(
        'main' => '',
        'footer' => '',
        'form' => '',
        'type' => 'standard', // standard, watch_now, add_to_account, external, disabled
        'reg_type' => '',
        'data' => array()
    );
    
    $user_id = $args['user_id'];
    $user = get_user_by('ID', $user_id);
    
    $can_add_to_account = cc_training_can_add_to_account( $user_id, $training_id );

    $currency = cc_currency_get_user_currency();
    
    // Get training type and status
    $post_type = $args['post_type'];
    $training_type = '';
    $training_status = '';
    $actual_training_id = $training_id; // The ID we'll actually use for registration
    $recording_fallback = false; // Flag to track if we're using a recording instead

    $buttons['data'] = array(
        'training_id' => $training_id,  // The ID being used for registration
        'display_training_id' => $training_id,  // The original ID (for display purposes)
        'post_type' => $post_type,
        'currency' => $currency, // may change later in the pricing lookup
        'price' => 0,
        'training_type' => '',
        'is_recording_fallback' => false,
        'is_multi_event' => false,
    );

    switch( course_training_type( $training_id ) ) {
        case 'workshop':
            // Check workshop availability - this can return true, false, or a recording ID
            $workshop_check = workshops_show_this_workshop($training_id);

            if ($workshop_check === false) {
                $training_status = 'closed';
                $training_type = 'w';

            } else {
                // Check if this is a multi-event workshop
                $is_multi_event = workshop_is_multi_event($training_id);
                
                if ($is_multi_event) {
                    // Multi-event workshops have a different flow
                    $training_status = 'multi_event';
                    $training_type = 'w';
                    $buttons['data']['is_multi_event'] = true;

                } else {
                    // Single event workshop - check availability
                    if ($workshop_check === true) {
                        $training_status = 'open';
                        $training_type = 'w';
                    } elseif (is_numeric($workshop_check) && $workshop_check > 1) {
                        // Workshop is closed but recording is available
                        $training_status = 'open'; // Can register for the recording
                        $training_type = 'r'; // Change to recording type
                        $actual_training_id = $workshop_check; // Use the recording ID
                        $recording_fallback = true;
                        $buttons['data']['training_id'] = $actual_training_id;
                        $buttons['data']['is_recording_fallback'] = true;
                    } else {
                        // Fallback for any other unexpected return value
                        $training_status = 'closed';
                        $training_type = 'w';
                    }
                }
            }
            break;

        case 'recording':
            $training_type = 'r'; // not 'c'!
            $training_status = cc_training_get_course_status($training_id);
            break;
        case 'series':
            $training_type = 's';
            $training_status = cc_training_get_series_status($training_id);
            break;
    }
    
    // Get pricing information
    $pricing_data = cc_training_get_pricing($training_id, $currency, $post_type);
    
    // Check for special conditions
    $portal_user = get_user_meta($user_id, 'portal_user', true);
    
    // Check both manual blocking AND contract expiry
    $blocking_info = cc_training_check_portal_blocking($actual_training_id, $portal_user);
    $is_blocked = $blocking_info['blocked'];
    $block_reason = $blocking_info['reason'];
    
    $has_access = cc_training_user_has_access( $user_id, $actual_training_id, $training_type );
    
    // Determine button type and generate HTML
    if( $training_status === 'multi_event' ){
        // Standard multi-event - link to event selection section
        // note that add to account is not an option here as it's too hard!
        $buttons = cc_training_generate_multi_event_button($actual_training_id, $args);
        $buttons['type'] = 'multi_event';
    }

    if( $has_access ){
        // User already has access - show "Watch Now" (only used for on-demand training)
        $buttons = cc_training_generate_access_button($actual_training_id, $args, $post_type);
        $buttons['type'] = 'watch_now';
    
    } elseif ( $is_blocked ) {
        // Blocked for portal users
        $buttons = cc_training_generate_blocked_button($training_id, $args, $block_reason);
        $buttons['type'] = 'disabled';

    } elseif ( $training_status === 'closed' ) {
        // Training is closed
        $buttons = cc_training_generate_closed_button($training_id, $args);
        $buttons['type'] = 'disabled';

    } elseif ( $can_add_to_account ) {
        // NEW: Add to Account button - but only if not already registered
        if ( !cc_training_access_user_already_registered($user_id, $actual_training_id) ) {
            $buttons = cc_training_generate_add_to_account_button($actual_training_id, $args, $pricing_data);
            $buttons['type'] = 'add_to_account';
        } else {
            // Already registered - show appropriate message or access button
            if ( $training_type == 'r' ) {
                // For recordings, show "Watch Now" button
                $buttons = cc_training_generate_access_button($actual_training_id, $args, $post_type);
                $buttons['type'] = 'watch_now';
            } else {
                // For workshops, just show a message
                $buttons['main'] = '<p class="text-success"><i class="fa-solid fa-check-circle"></i> Already in your account</p>';
                $buttons['footer'] = '<p class="text-success small">Already registered</p>';
                $buttons['type'] = 'disabled';
            }
        }

    } else {
        // Standard registration button
        $buttons = cc_training_generate_standard_button($actual_training_id, $args, $pricing_data, $training_type);
        $buttons['type'] = 'standard';
    }
    
    // Add the registration form if needed
    if ($buttons['type'] === 'standard' || $buttons['type'] === 'add_to_account') {
        $buttons['form'] = cc_training_generate_registration_form($actual_training_id, $args, $pricing_data, $training_type);
    } elseif ( $buttons['type'] === 'multi_event' ) {
        // Multi-event workshops need a different form with event selection capability
        $buttons['form'] = cc_training_generate_multi_event_form($actual_training_id, $args, $pricing_data);
    }

    // Store extra data
    $buttons['data']['price'] = $pricing_data['raw_price'];
    $buttons['data']['currency'] = $pricing_data['currency']; // could have reverted to GBP
    $buttons['data']['training_type'] = $training_type;
    
    return $buttons;
}

/**
 * Check if user can add training to account instantly
 * 
 * @param int $user_id The user ID
 * @param int $training_id The training post ID
 * @return bool Whether user can add to account
 */
function cc_training_can_add_to_account($user_id, $training_id) {
    if (!$user_id) {
        return false;
    }
    
    $portal_user = get_user_meta($user_id, 'portal_user', true);
    if ( $portal_user === 'cnwl' && cc_registration_user_dets_complete( $user_id ) ) {
        return true;
    }
    
    /*
    if( function_exists( 'contextual_get_user_subscription' ) ){
        $subscription_status = contextual_get_user_subscription($user_id);
        if ($subscription_status && 
            isset($subscription_status['has_subscription']) && 
            $subscription_status['has_subscription'] === true &&
            isset($subscription_status['type']) && 
            $subscription_status['type'] === 'unlimited' &&
            isset($subscription_status['status']) &&
            in_array($subscription_status['status'], array('active', 'grace_period'))) {
            return true;
        }
    }
    */

    if( cc_training_access_user_has_access( $user_id, $training_id ) ){
        return true;
    }
    
    return false;
}

/**
 * Generate "Add to Account" button
 */
function cc_training_generate_add_to_account_button($training_id, $args, $pricing_data) {
    $button_text = !empty($args['override_text']) ? $args['override_text'] : 'Add to account';
    $footer_text = !empty($args['override_footer_text']) ? $args['override_footer_text'] : 'Add<span class="d-none d-md-inline"> to account</span>';
    
    $nonce = wp_create_nonce('add_to_account_' . $training_id);
    
    $main_button = sprintf(
        '<button class="%s btn-add-to-account" data-training-id="%d" data-nonce="%s" data-action="add_to_account">%s</button>',
        esc_attr($args['button_class']),
        esc_attr($training_id),
        esc_attr($nonce),
        $button_text
    );
    
    $footer_button = sprintf(
        '<button class="%s btn-add-to-account" data-training-id="%d" data-nonce="%s" data-action="add_to_account">%s</button>',
        esc_attr($args['footer_button_class']),
        esc_attr($training_id),
        esc_attr($nonce),
        $footer_text
    );
    
    return array(
        'main' => $main_button,
        'footer' => $footer_button
    );
}

/**
 * Generate standard registration button
 */
function cc_training_generate_standard_button($training_id, $args, $pricing_data, $training_type) {
    $button_text = !empty($args['override_text']) ? $args['override_text'] : 'Register now';
    $footer_text = !empty($args['override_footer_text']) ? $args['override_footer_text'] : 'Register<span class="d-none d-md-inline"> Now</span>';
    
    // Check if we need special handling (early bird, student discount, etc.)
    $needs_expanded_footer = false;
    if ($args['post_type'] === 'course' || $args['post_type'] === 'workshop') {
        if ($pricing_data['has_early_bird'] || $pricing_data['has_student_discount']) {
            $needs_expanded_footer = true;
        }
    }
    
    $main_button = sprintf(
        '<button class="%s" form="reg-form">%s</button>',
        esc_attr($args['button_class']),
        $button_text
    );
    
    if ($needs_expanded_footer) {
        $footer_button = sprintf(
            '<button type="button" id="training-footer-btn" class="%s"><span class="d-none d-md-inline">Show </span>More</button>',
            esc_attr($args['footer_button_class'])
        );
    } else {
        $footer_button = sprintf(
            '<button class="%s" form="reg-form">%s</button>',
            esc_attr($args['footer_button_class']),
            $footer_text
        );
    }
    
    return array(
        'main' => $main_button,
        'footer' => $footer_button
    );
}

/**
 * Generate registration form HTML
 */
function cc_training_generate_registration_form($training_id, $args, $pricing_data, $training_type) {
    $user_timezone = cc_timezone_get_user_timezone($args['user_id']);
    $pretty_timezone = cc_timezone_get_user_timezone_pretty($args['user_id'], $user_timezone);
    
    // For series, use the special form
    if ($args['post_type'] === 'series') {
        return ''; // Series uses its own form in training_groups_selection_card()
    }
    
    $form = '<form id="reg-form" action="/registration" method="POST" class="d-none">';
    $form .= sprintf('<input type="hidden" name="training-type" value="%s">', esc_attr($training_type));
    $form .= sprintf('<input type="hidden" name="workshop-id" value="%d">', esc_attr($training_id));
    $form .= '<input type="hidden" name="eventID" value="">';
    $form .= sprintf('<input type="hidden" name="num-events" value="%d">', 1);
    $form .= '<input type="hidden" name="num-free" value="0">';
    $form .= sprintf('<input type="hidden" name="currency" value="%s">', esc_attr($pricing_data['currency']));
    $form .= sprintf('<input type="hidden" name="raw-price" value="%s">', esc_attr($pricing_data['raw_price']));
    $form .= sprintf('<input type="hidden" name="user-timezone" value="%s">', esc_attr($user_timezone));
    $form .= sprintf('<input type="hidden" name="user-prettytime" value="%s">', esc_attr($pretty_timezone));
    $form .= '<input type="hidden" name="student" value="no">';
    $form .= sprintf('<input type="hidden" name="student-price" value="%s">', esc_attr($pricing_data['student_price'] ?? 0));
    
    // Add tracking fields
    $form .= sprintf('<input type="hidden" name="source_page" value="%s">', esc_attr(get_the_title($training_id)));
    $form .= sprintf('<input type="hidden" name="utm_source" value="%s">', esc_attr($_GET['utm_source'] ?? ''));
    $form .= sprintf('<input type="hidden" name="utm_campaign" value="%s">', esc_attr($_GET['utm_campaign'] ?? ''));
    $form .= sprintf('<input type="hidden" name="utm_medium" value="%s">', esc_attr($_GET['utm_medium'] ?? ''));
    
    $form .= '</form>';
    
    return $form;
}

/**
 * Helper functions that would need to be implemented
 * These abstract the differences between workshop, course, and series
 */

function cc_training_get_pricing($training_id, $currency, $post_type) {
    $pricing_data = array(
        'raw_price' => 0,
        'display_price' => '',
        'currency' => $currency,
        'has_early_bird' => false,
        'has_student_discount' => false,
        'student_price' => 0,
    );
    
    switch($post_type) {
        case 'workshop':
            $workshop_pricing = cc_workshop_price($training_id, $currency);
            $pricing_data['raw_price'] = $workshop_pricing['raw_price']; // early bird if it applies
            $pricing_data['display_price'] = $workshop_pricing['price_text'];
            $pricing_data['currency'] = $workshop_pricing['curr_found'];
            $pricing_data['has_early_bird'] = ( $workshop_pricing['earlybird_msg'] == '' ) ? true : false;
            if( $workshop_pricing['student_price'] !== NULL && $workshop_pricing['student_price'] > 0 ){
                $pricing_data['has_student_discount'] = true;
                $pricing_data['student_price'] = $workshop_pricing['student_price'];
            }
            break;
            
        case 'course':
            $course_pricing = course_pricing_get($training_id);

            $pricing_data['raw_price'] = $course_pricing['price_' . strtolower($currency)] ?? 0;
            if( $currency <> 'GBP' && $pricing_data['raw_price'] == 0 ){
                $pricing_data['raw_price'] = $course_pricing['price_gbp'] ?? 0;
                if( $pricing_data['raw_price'] > 0 ){
                    $pricing_data['currency'] = 'GBP';
                }
            }

            $early_bird_discount = (float) $course_pricing['early_bird_discount'];
            if( $early_bird_discount > 0 && $early_bird_discount <= 100 && $course_pricing['early_bird_expiry'] <> NULL && $pricing_data['raw_price'] > 0 ){
                $early_bird_expiry = DateTime::createFromFormat( 'Y-m-d H:i:s', $course_pricing['early_bird_expiry'] );
                if( $early_bird_expiry ){
                    if( $early_bird_expiry->getTimestamp() > time() ){
                        $pricing_data['has_early_bird'] = true;
                        $discount_pence = round( $pricing_data['raw_price'] * $early_bird_discount ); // eg £100 * 20% = 2000p
                        $discount_price = $pricing_data['raw_price'] - ( $discount_pence / 100 ); // eg £100 - ( 2000p / 100 ) = £80
                        /**
                         * we were rounding this up to the next multiple of 5, but no longer
                         *
                        // however, we want this rounded up to the next multiple of 5
                        $raw_price = ceil($discount_price / 5) * 5;
                        */
                        $pricing_data['raw_price'] = $discount_price;
                    }
                }
            }

            if( $pricing_data['raw_price'] == 0 ){
                $pricing_data['display_price'] = 'Free';
            }else{
                $pricing_data['display_price'] = workshops_pretty_price( $pricing_data['raw_price'], $pricing_data['currency'] );
            }

            if( $course_pricing['student_discount'] > 0 ){
                $pricing_data['has_student_discount'] = true;
                $student_price = $pricing_data['raw_price'] - ( $pricing_data['raw_price'] * $course_pricing['student_discount'] / 100 );
                // however, we want this rounded up to the next multiple of 5
                $pricing_data['student_price'] = ceil( $student_price / 5 ) * 5;
            }
            break;
            
        case 'series':
            // Series pricing is handled differently via training_groups_selection_card
            break;
    }
    
    return $pricing_data;
}

function cc_training_get_workshop_status($workshop_id) {
    // Check if workshop is closed, upcoming, etc.
    $show_workshop = workshops_show_this_workshop($workshop_id);
    if ($show_workshop === false) {
        return 'closed';
    }
    return 'open';
}

function cc_training_get_course_status($course_id) {
    $course = course_get_all($course_id);
    if ($course && isset($course['_course_status'])) {
        return $course['_course_status'];
    }
    return 'open';
}

function cc_training_get_series_status($series_id) {
    $status = get_post_meta($series_id, '_series_status', true);
    return $status ?: 'open';
}

function cc_training_is_blocked_for_portal($training_id, $portal_user) {
    if (!$portal_user) {
        return false;
    }
    
    $block_nlft = get_post_meta($training_id, 'block_nlft', true);
    $block_cnwl = get_post_meta($training_id, 'block_cnwl', true);
    
    if (($portal_user === 'nlft' && $block_nlft === 'yes') || 
        ($portal_user === 'cnwl' && $block_cnwl === 'yes')) {
        return true;
    }
    
    return false;
}

/**
 * Check if training should be blocked for portal user
 * Checks both manual blocking and contract expiry with contract type awareness
 * 
 * @param int $training_id The training post ID
 * @param string $portal_user The portal user org code (cnwl/nlft)
 * @return array ['blocked' => bool, 'reason' => string]
 */
function cc_training_check_portal_blocking($training_id, $portal_user) {
    $result = array(
        'blocked' => false,
        'reason' => ''
    );
    
    if (!$portal_user) {
        return $result;
    }
    
    // First check manual blocking
    $block_nlft = get_post_meta($training_id, 'block_nlft', true);
    $block_cnwl = get_post_meta($training_id, 'block_cnwl', true);
    
    if (($portal_user === 'nlft' && $block_nlft === 'yes') || 
        ($portal_user === 'cnwl' && $block_cnwl === 'yes')) {
        $result['blocked'] = true;
        $result['reason'] = 'manual_block';
        return $result;
    }
    
    // Check contract expiry status
    if (function_exists('org_register_status')) {
        $org_status = org_register_status($portal_user, $training_id);
        
        if ($org_status['status'] == 'expired') {
            // Contract is expired - check the contract type
            if (function_exists('get_organisation_contract')) {
                $contract = get_organisation_contract($portal_user);
                $contract_type = !empty($contract->contract_type) ? $contract->contract_type : 'unlimited';
                
                // Both contract types block NEW registrations when expired
                $result['blocked'] = true;
                
                if ($contract_type == 'unlimited') {
                    $result['reason'] = 'contract_expired_unlimited';
                } else {
                    $result['reason'] = 'contract_expired_fixed';
                }
            } else {
                // Fallback if function doesn't exist
                $result['blocked'] = true;
                $result['reason'] = 'contract_expired';
            }
        }
    }
    
    return $result;
}

function cc_training_user_has_access( $user_id, $training_id, $training_type ) {
    if( $training_type == 'r' ){
        $recording_access = ccrecw_user_can_view( $training_id, $user_id );
        if( get_post_meta( $training_id, '_course_status', true ) == 'public' || $recording_access['access'] ){
            return true;
        }
    }

    return false;
}

// only used for on-demand training
function cc_training_generate_access_button($training_id, $args, $post_type) {
    $nonce = wp_create_nonce( 'course_id' . $course_id );

    $main_button = sprintf(
        '<form action="%s" method="post" class="mb-3">
            <input type="hidden" name="id" value="%s">
            <input type="hidden" name="training_delivery_nonce" value="%s">
            <button type="submit" class="btn btn-reg btn-lg">Watch now</button>
        </form>',
        esc_url( site_url( '/training-delivery/' ) ),
        esc_attr( $training_id ),
        esc_attr( $nonce )
    );

    $footer_button = sprintf(
        '<form action="%s" method="post" class="mb-3">
            <input type="hidden" name="id" value="%s">
            <input type="hidden" name="training_delivery_nonce" value="%s">
            <button type="submit" class="btn btn-reg btn-lg">Watch<span class="d-none d-md-inline"> Now</span></button>
        </form>',
        esc_url( site_url( '/training-delivery/' ) ),
        esc_attr( $training_id ),
        esc_attr( $nonce )
    );

    return array(
        'main' => $main_button,
        'footer' => $footer_button
    );
}

function cc_training_generate_blocked_button($training_id, $args, $reason = 'manual_block') {
    // Determine message based on reason
    $message = '';
    
    switch ($reason) {
        case 'contract_expired_unlimited':
            $message = 'Your organisation\'s contract has expired';
            break;
            
        case 'contract_expired_fixed':
            $message = 'Your organisation\'s contract has expired - you cannot register for new training';
            break;
            
        case 'contract_expired':
            $message = 'Your organisation\'s contract has expired';
            break;
            
        case 'manual_block':
        default:
            $message = 'Registration closed for your organisation';
            break;
    }
    
    return array(
        'main' => sprintf(
            '<a href="#register" class="btn btn-reg btn-lge disabled">Register now</a></p><p class="small lh-sm text-center">%s',
            esc_html($message)
        ),
        'footer' => sprintf(
            '<button type="button" id="training-footer-btn" class="btn btn-lge btn-reg disabled" disabled>Register<span class="d-none d-md-inline"> Now</span></button></p><p class="small lh-sm text-center">%s',
            esc_html($message)
        )
    );
}

function cc_training_generate_closed_button($training_id, $args) {
    return array(
        'main' => '<p class="mt-3">Registration closed</p>',
        'footer' => '<p class="mt-3">Registration closed</p>'
    );
}

/**
 * Generate multi-event workshop button (links to event selection)
 */
function cc_training_generate_multi_event_button($training_id, $args) {
    $button_text = !empty($args['override_text']) ? $args['override_text'] : 'Register now';
    $footer_text = !empty($args['override_footer_text']) ? $args['override_footer_text'] : 'Register<span class="d-none d-md-inline"> Now</span>';
    
    // Multi-event workshops typically link to an anchor for event selection
    $main_button = sprintf(
        '<a href="#register" class="%s">%s</a>',
        esc_attr($args['button_class']),
        $button_text
    );
    
    // For mobile, might want to trigger the expanded footer instead
    $footer_button = sprintf(
        '<button type="button" id="training-footer-btn" class="%s" data-action="show-events">%s</button>',
        esc_attr($args['footer_button_class']),
        $footer_text
    );
    
    return array(
        'main' => $main_button,
        'footer' => $footer_button
    );
}

/**
 * Generate multi-event registration form
 */
function cc_training_generate_multi_event_form($training_id, $args, $pricing_data) {
    $user_timezone = cc_timezone_get_user_timezone($args['user_id']);
    $pretty_timezone = cc_timezone_get_user_timezone_pretty($args['user_id'], $user_timezone);
    
    // Get event details for the workshop
    $num_events = 0;
    $num_free = 0;
    $raw_price = 0;
    $now = time();
    
    // Count available events
    for($i = 1; $i <= 15; $i++) {
        $event_name = get_post_meta($training_id, 'event_'.$i.'_name', true);
        if($event_name !== '') {
            $event_dt = workshop_event_display_date_time($training_id, $i, $user_timezone);
            if($event_dt['timestamp'] === '' || $now <= $event_dt['timestamp']) {
                $num_events++;
                $event_free = get_post_meta($training_id, 'event_'.$i.'_free', true);
                if($event_free === 'yes') {
                    $num_free++;
                }
            }
        }
    }
    
    // For multi-event, the price will be calculated dynamically based on selection
    $form = '<form id="reg-form" action="/registration" method="POST" class="d-none">';
    $form .= '<input type="hidden" name="training-type" value="w">';
    $form .= sprintf('<input type="hidden" name="workshop-id" value="%d">', esc_attr($training_id));
    $form .= '<input type="hidden" id="eventID" name="eventID" value="">'; // Will be populated by JS
    $form .= sprintf('<input type="hidden" id="num-events" name="num-events" value="%d">', $num_events);
    $form .= sprintf('<input type="hidden" id="num-free" name="num-free" value="%d">', $num_free);
    $form .= sprintf('<input type="hidden" name="currency" value="%s">', esc_attr($pricing_data['currency']));
    $form .= '<input type="hidden" id="raw-price" name="raw-price" value="0">'; // Will be calculated by JS
    $form .= sprintf('<input type="hidden" name="user-timezone" value="%s">', esc_attr($user_timezone));
    $form .= sprintf('<input type="hidden" name="user-prettytime" value="%s">', esc_attr($pretty_timezone));
    $form .= '<input type="hidden" name="student" value="no">';
    $form .= sprintf('<input type="hidden" name="student-price" value="%s">', esc_attr($pricing_data['student_price'] ?? 0));
    
    // Add tracking fields
    $form .= sprintf('<input type="hidden" name="source_page" value="%s">', esc_attr(get_the_title($training_id)));
    $form .= sprintf('<input type="hidden" name="utm_source" value="%s">', esc_attr($_GET['utm_source'] ?? ''));
    $form .= sprintf('<input type="hidden" name="utm_campaign" value="%s">', esc_attr($_GET['utm_campaign'] ?? ''));
    $form .= sprintf('<input type="hidden" name="utm_medium" value="%s">', esc_attr($_GET['utm_medium'] ?? ''));
    
    $form .= '</form>';
    
    return $form;
}