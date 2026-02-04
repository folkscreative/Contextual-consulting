<?php
/**
 * Stripe bits
 */

// return the required Stripe key
// $type = 'public' or 'secret'
function cc_get_stripe_key($type){
	return cc_stripe_key(cc_stripe_mode(), $type);
}

// are we processing payments in test or live mode
function cc_stripe_mode(){
	global $rpm_theme_options;
    // are we on the live site?
    if( ! LIVE_SITE ){
        return 'test';
    }
	// admins can use test cards on the live site
	if(is_user_logged_in() && current_user_can('edit_theme_options')){
		// it's an admin
		// is the switch set to use test keys for admins?
		$ccpa_stripe_admin_test = esc_attr($rpm_theme_options['stripe_test_mode']);
		if($ccpa_stripe_admin_test == 1){
			// we want to use test keys if we can
			$ccpa_stripe_public_test = esc_attr($rpm_theme_options['stripe_public_test']);
			$ccpa_stripe_secret_test = esc_attr($rpm_theme_options['stripe_secret_test']);
			if($ccpa_stripe_public_test <> '' && $ccpa_stripe_secret_test <> ''){
				return 'test';
			}
		}
	}
	return 'live';
}

// get the required Stripe key
// $mode = 'live' or 'test'
// $type = 'public' or 'secret'
function cc_stripe_key($mode, $type){
	global $rpm_theme_options;
	$append = '';
	if($mode == 'test'){
		$append = '_test';
	}
	return esc_attr($rpm_theme_options['stripe_'.$type.$append]);
}

// create (and return) a payment intent
function cc_stripe_pmt_intent_create_pi($args){
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
	\Stripe\Stripe::setApiKey(cc_get_stripe_key('secret'));
	return \Stripe\PaymentIntent::create($args);
}

// update a payment intent
// Your cc_stripe_pmt_intent_update_pi function calls ccpa_get_stripe_key instead of cc_get_stripe_key
function cc_stripe_pmt_intent_update_pi($pi_id, $args) {
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
    \Stripe\Stripe::setApiKey(cc_get_stripe_key('secret')); // Fixed function name
    return \Stripe\PaymentIntent::update($pi_id, $args);
}

// cancel a payment intent ........ function not used?
// Fix for cc_stripe_pmt_intent_cancel_pi function
function cc_stripe_pmt_intent_cancel_pi($pi_id, $args = []) {
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
    \Stripe\Stripe::setApiKey(cc_get_stripe_key('secret')); // Fixed function name
    return \Stripe\PaymentIntent::cancel($pi_id, $args);
}

// refunding a payment (payment intent or charge)
function cc_stripe_pmt_intent_refund($args){
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
	\Stripe\Stripe::setApiKey(cc_stripe_key('live', 'secret')); // hard coded to 'live' so that it works even when the site is in test mode
	try {
		return \Stripe\Refund::create($args);
	} catch (Exception $e) {
		ccpa_write_log('cc_stripe_pmt_intent_refund - Something went wrong');
		ccpa_write_log( 'Error Message is:' . $e->getMessage() . '\n' );
	}
	return false;
}

// retrieve a payment intent
function cc_stripe_pmt_intent_retrieve($args){
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
	\Stripe\Stripe::setApiKey(cc_stripe_key(cc_stripe_mode(), 'secret'));
	try {
		return \Stripe\PaymentIntent::retrieve($args);
	} catch(\Stripe\Exception\CardException $e) {
		ccpa_write_log('cc_stripe_pmt_intent_retrieve - it\'s a decline');
		// Since it's a decline, \Stripe\Exception\CardException will be caught
		ccpa_write_log( 'Status is:' . $e->getHttpStatus() . '\n' );
		ccpa_write_log( 'Type is:' . $e->getError()->type . '\n' );
		ccpa_write_log( 'Code is:' . $e->getError()->code . '\n' );
		// param is '' in this case
		ccpa_write_log( 'Param is:' . $e->getError()->param . '\n' );
		ccpa_write_log( 'Message is:' . $e->getError()->message . '\n' );
	} catch (\Stripe\Exception\RateLimitException $e) {
		// Too many requests made to the API too quickly
		ccpa_write_log('cc_stripe_pmt_intent_retrieve - Too many requests made to the API too quickly');
		ccpa_write_log( 'Message is:' . $e->getError()->message . '\n' );
	} catch (\Stripe\Exception\InvalidRequestException $e) {
		// Invalid parameters were supplied to Stripe's API
		ccpa_write_log('cc_stripe_pmt_intent_retrieve - Invalid parameters were supplied to Stripe\'s API');
		ccpa_write_log( 'Message is:' . $e->getError()->message . '\n' );
	} catch (\Stripe\Exception\AuthenticationException $e) {
		// Authentication with Stripe's API failed
		// (maybe you changed API keys recently)
		ccpa_write_log('cc_stripe_pmt_intent_retrieve - Authentication with Stripe\'s API failed');
		ccpa_write_log( 'Message is:' . $e->getError()->message . '\n' );
	} catch (\Stripe\Exception\ApiConnectionException $e) {
		// Network communication with Stripe failed
		ccpa_write_log('cc_stripe_pmt_intent_retrieve - Network communication with Stripe failed');
		ccpa_write_log( 'Message is:' . $e->getError()->message . '\n' );
	} catch (\Stripe\Exception\ApiErrorException $e) {
		// Display a very generic error to the user, and maybe send
		// yourself an email
		ccpa_write_log('cc_stripe_pmt_intent_retrieve - a very generic error');
		ccpa_write_log( 'Message is:' . $e->getError()->message . '\n' );
	} catch (Exception $e) {
		// Something else happened, completely unrelated to Stripe
		// this includes No such payment_intent: 'pi_3KMZ34CbNJKjn0bn0LzgSmj8'; a similar object exists in test mode, but a live mode key was used to make this request.
		ccpa_write_log('cc_stripe_pmt_intent_retrieve - Something else happened, completely unrelated to Stripe');
		ccpa_write_log( 'Error Message is:' . $e->getMessage() . '\n' );
	}
	return false;
}

// retrieve a charge
function cc_stripe_pmt_intent_retrieve_charge($args){
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
	\Stripe\Stripe::setApiKey(cc_stripe_key('live', 'secret'));
	try {
		return \Stripe\Charge::retrieve($args);
	} catch (Exception $e) {
		ccpa_write_log('cc_stripe_pmt_intent_retrieve_charge - Something went wrong');
		ccpa_write_log( 'Error Message is:' . $e->getMessage() . '\n' );
	}
	return false;
}

/**
 * Additional Stripe functions to complement your existing stripe-bits.php
 * Add these to your stripe-bits.php file
 */

// Create a Stripe customer
function cc_stripe_create_customer($args) {
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
    \Stripe\Stripe::setApiKey(cc_get_stripe_key('secret'));
    try {
        return \Stripe\Customer::create($args);
    } catch (Exception $e) {
        error_log('cc_stripe_create_customer - Error: ' . $e->getMessage());
        return false;
    }
}

// Retrieve a Stripe customer
function cc_stripe_retrieve_customer($customer_id) {
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
    \Stripe\Stripe::setApiKey(cc_get_stripe_key('secret'));
    try {
        return \Stripe\Customer::retrieve($customer_id);
    } catch (Exception $e) {
        error_log('cc_stripe_retrieve_customer - Error: ' . $e->getMessage());
        return false;
    }
}

// Update a Stripe customer
function cc_stripe_update_customer($customer_id, $args) {
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
    \Stripe\Stripe::setApiKey(cc_get_stripe_key('secret'));
    try {
        return \Stripe\Customer::update($customer_id, $args);
    } catch (Exception $e) {
        error_log('cc_stripe_update_customer - Error: ' . $e->getMessage());
        return false;
    }
}

// Create a payment method from token
function cc_stripe_create_payment_method($args) {
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
    \Stripe\Stripe::setApiKey(cc_get_stripe_key('secret'));
    try {
        return \Stripe\PaymentMethod::create($args);
    } catch (Exception $e) {
        error_log('cc_stripe_create_payment_method - Error: ' . $e->getMessage());
        return false;
    }
}

// Attach payment method to customer
function cc_stripe_attach_payment_method($payment_method_id, $customer_id) {
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
    \Stripe\Stripe::setApiKey(cc_get_stripe_key('secret'));
    try {
        $payment_method = \Stripe\PaymentMethod::retrieve($payment_method_id);
        return $payment_method->attach(['customer' => $customer_id]);
    } catch (Exception $e) {
        error_log('cc_stripe_attach_payment_method - Error: ' . $e->getMessage());
        return false;
    }
}

// Create a Stripe price
function cc_stripe_create_price($args) {
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
    \Stripe\Stripe::setApiKey(cc_get_stripe_key('secret'));
    try {
        return \Stripe\Price::create($args);
    } catch (Exception $e) {
        error_log('cc_stripe_create_price - Error: ' . $e->getMessage());
        return false;
    }
}

// List Stripe prices
function cc_stripe_list_prices($args = []) {
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
    \Stripe\Stripe::setApiKey(cc_get_stripe_key('secret'));
    try {
        return \Stripe\Price::all($args);
    } catch (Exception $e) {
        error_log('cc_stripe_list_prices - Error: ' . $e->getMessage());
        return false;
    }
}

// Test Stripe connection and configuration
function cc_stripe_test_connection() {
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
    \Stripe\Stripe::setApiKey(cc_get_stripe_key('secret'));
    
    try {
        $account = \Stripe\Account::retrieve();
        $mode = cc_stripe_mode();
        
        error_log("Stripe connection successful in {$mode} mode");
        error_log("Account ID: " . $account->id);
        error_log("Business name: " . ($account->business_profile->name ?? 'Not set'));
        error_log("Country: " . $account->country);
        error_log("Default currency: " . $account->default_currency);
        
        return [
            'success' => true,
            'mode' => $mode,
            'account' => $account
        ];
        
    } catch (\Stripe\Exception\AuthenticationException $e) {
        error_log('Stripe authentication failed: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Authentication failed - check your API keys',
            'mode' => cc_stripe_mode()
        ];
        
    } catch (Exception $e) {
        error_log('Stripe connection test failed: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'mode' => cc_stripe_mode()
        ];
    }
}

// Enhanced refund function that respects the current mode ... don't use this one????
function cc_stripe_pmt_intent_refund_enhanced($args) {
    require_once ( get_stylesheet_directory().'/stripe-php-9.6.0/init.php' );
    \Stripe\Stripe::setApiKey(cc_get_stripe_key('secret')); // Uses current mode instead of hardcoded 'live'
    try {
        return \Stripe\Refund::create($args);
    } catch (Exception $e) {
        error_log('cc_stripe_pmt_intent_refund_enhanced - Error: ' . $e->getMessage());
        return false;
    }
}

// Admin function to test Stripe configuration
function cc_stripe_admin_test_button() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['test_stripe_connection'])) {
        $result = cc_stripe_test_connection();
        
        if ($result['success']) {
            echo '<div class="notice notice-success"><p><strong>Stripe Connection Successful!</strong><br>';
            echo 'Mode: ' . strtoupper($result['mode']) . '<br>';
            echo 'Account ID: ' . $result['account']->id . '<br>';
            echo 'Country: ' . $result['account']->country . '<br>';
            echo 'Currency: ' . strtoupper($result['account']->default_currency);
            echo '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p><strong>Stripe Connection Failed!</strong><br>';
            echo 'Mode: ' . strtoupper($result['mode']) . '<br>';
            echo 'Error: ' . $result['error'];
            echo '</p></div>';
        }
    }
    
    echo '<form method="post">';
    echo '<input type="submit" name="test_stripe_connection" class="button button-secondary" value="Test Stripe Connection">';
    echo '</form>';
}

// Utility function to format Stripe amounts for display
function cc_stripe_format_amount($amount_in_cents, $currency = 'gbp') {
    $symbols = [
        'gbp' => '£',
        'usd' => '$',
        'eur' => '€',
        'aud' => 'A$'
    ];
    
    $symbol = $symbols[strtolower($currency)] ?? strtoupper($currency);
    return $symbol . number_format($amount_in_cents / 100, 2);
}

// Debug function to log Stripe mode and keys (without exposing secrets)
function cc_stripe_debug_config() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $mode = cc_stripe_mode();
    $public_key = cc_get_stripe_key('public');
    $secret_key = cc_get_stripe_key('secret');
    
    error_log("=== STRIPE CONFIGURATION DEBUG ===");
    error_log("Mode: " . $mode);
    error_log("Public Key: " . ($public_key ? 'SET (' . substr($public_key, 0, 7) . '...)' : 'NOT SET'));
    error_log("Secret Key: " . ($secret_key ? 'SET (' . substr($secret_key, 0, 7) . '...)' : 'NOT SET'));
    error_log("Is Admin: " . (current_user_can('edit_theme_options') ? 'YES' : 'NO'));
    
    global $rpm_theme_options;
    if (isset($rpm_theme_options['stripe_test_mode'])) {
        error_log("Admin Test Mode Setting: " . $rpm_theme_options['stripe_test_mode']);
    }
    error_log("=== END STRIPE DEBUG ===");
}


function cc_stripe_get_default_payment_method($customer_id) {
    // TODO: Implement using your existing Stripe functions from stripe-bits.php
    // Example of what this should return:
    /*
    return [
        'last4' => '4242',
        'brand' => 'visa',
        'exp_month' => '12',
        'exp_year' => '2028'
    ];
    */
    
    // Placeholder return - remove when implementing real Stripe integration
    return null;
}


/**
 * Get Stripe price ID for tier and frequency
 */
function cc_get_stripe_price_id($tier, $frequency) {
    // TODO: Implement based on your Stripe product/price setup
    // This should return the appropriate Stripe price ID
    
    $price_mapping = [
        'essentials_month' => 'price_essentials_monthly',
        'essentials_year' => 'price_essentials_annual',
        'unlimited_month' => 'price_unlimited_monthly',
        'unlimited_year' => 'price_unlimited_annual'
    ];
    
    $key = $tier . '_' . $frequency;
    return $price_mapping[$key] ?? null;
}
