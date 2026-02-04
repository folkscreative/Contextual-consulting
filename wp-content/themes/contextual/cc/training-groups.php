<?php
/**
 * Training groups
 * - added July 2025
 * - accommodates registration for an unlimited number of courses
 */

// create/update the training groups table
// one row per training/payment
add_action('init', 'update_training_groups_table');
function update_training_groups_table(){
	global $wpdb;
	$training_groups_table_ver = 2;
	// v2 (Jan 2026) added performance indexes for training access system
	$installed_table_ver = get_option('training_groups_table_ver');
	if($installed_table_ver <> $training_groups_table_ver){
		$table_name = $wpdb->prefix.'cc_training_groups';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			payment_id mediumint(9) NOT NULL,
			course_id mediumint(9) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('training_groups_table_ver', $training_groups_table_ver);

		if( $training_groups_table_ver == 2 ){
			cc_training_groups_add_performance_indexes($table_name);
		}
	}
}

/**
 * Add performance indexes to training groups table
 * Called during table version upgrade to v2
 */
function cc_training_groups_add_performance_indexes($table_name) {
	global $wpdb;
	
	// Get existing indexes
	$existing_indexes = $wpdb->get_results("SHOW INDEX FROM $table_name", ARRAY_A);
	$index_names = array();
	foreach($existing_indexes as $index) {
		$index_names[] = $index['Key_name'];
	}
	
	// Add indexes if they don't exist
	
	// Index for payment_id lookups (getting all courses for a payment)
	if(!in_array('idx_payment_id', $index_names)) {
		$wpdb->query("ALTER TABLE $table_name ADD INDEX idx_payment_id (payment_id)");
	}
	
	// Index for course_id lookups (finding all payments for a course)
	if(!in_array('idx_course_id', $index_names)) {
		$wpdb->query("ALTER TABLE $table_name ADD INDEX idx_course_id (course_id)");
	}
	
	// Combined index for payment+course lookups (most efficient)
	if(!in_array('idx_payment_course', $index_names)) {
		$wpdb->query("ALTER TABLE $table_name ADD INDEX idx_payment_course (payment_id, course_id)");
	}
	
	error_log('Training Access: Training groups table performance indexes added');
}

// training group registration selection card
// used on the series pages
// NOTE mismatching currencies will result in no card being returned
function training_groups_selection_card( $series_id ){
    // Check if user is a portal user - they cannot register for series
    $user_id = get_current_user_id();
    if (cc_users_is_valid_user_logged_in()) {
        $portal_user = get_user_meta($user_id, 'portal_user', true);
        if (!empty($portal_user)) {
            return '<div class="tgsd-card my-5 dark-bg">
                <div class="tgsd-body p-3">
                    <p class="text-center">Series and group registrations are not available for your organisation. Please register for individual courses.</p>
                </div>
            </div>';
        }
    }
	
	// ccpa_write_log('function training_groups_selection_card');
	// ccpa_write_log($series_id.' is a '.get_post_type( $series_id ));
	if( get_post_type( $series_id ) <> 'series' ) return '';
	$series_title = get_the_title( $series_id );
    // Get courses currently in this series
    $series_courses = get_post_meta($series_id, '_series_courses', true);
    $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
    $series_courses = is_array($series_courses) ? $series_courses : [];

    // ccpa_write_log('series_courses:');
    // ccpa_write_log($series_courses);

	$user_id = get_current_user_id(); // 0 if not logged in
	$user_timezone = cc_timezone_get_user_timezone( $user_id );
	$pretty_timezone = cc_timezone_get_user_timezone_pretty( $user_id, $user_timezone );

    $currency = cc_currency_get_user_currency();
    $undiscounted_price = 0;
    
    // Get series discount for JavaScript
    $series_discount = (float) get_post_meta( $series_id, '_series_discount', true );

    // ccpa_write_log('series_discount:');
    // ccpa_write_log($series_discount);
    
	$html = '
		<div class="tgsd-card my-5 dark-bg" data-series-discount="'.$series_discount.'" data-currency="'.$currency.'">
			<div class="tgsd-header">
				<h4 class="tgsd-title text-center p-3 m-0">'.$series_title.'</h4>
			</div>
			<div class="tgsd-body p-3">';
	
	foreach ($series_courses as $training_id) {
		// get the price
        if( get_post_type( $training_id ) == 'workshop' ){
            $workshop_pricing = cc_workshop_price( $training_id, $currency );
            if( $workshop_pricing['curr_found'] <> $currency ){
                return '';
            }
            $training_price = $workshop_pricing['raw_price'];
        }else{
            $course = course_get_all( $training_id );
            if( ! $course ){
                return '';
            }
            $training_price = $course['pricing']['price_'.strtolower( $currency )];
        }
        $undiscounted_price = $undiscounted_price + $training_price;

        // ccpa_write_log($training_id.' '.$undiscounted_price);

        // set up the checkbox
        $html .= '
				<div class="form-check my-3">
					<input class="form-check-input fa-checkbox tgsd-cb" type="checkbox" id="tgsd-cb-'.$training_id.'" name="training_courses[]" value="'.$training_id.'" checked data-price="'.$training_price.'">
					<label class="form-check-label tgsd-cb-label" for="tgsd-cb-'.$training_id.'">'.get_the_title( $training_id ).'</label>
				</div>';
	}
	
	// Store total courses count for JavaScript
	$total_courses = count($series_courses);
	
	// link to series ... not shown on the series page itself
	if( get_the_id() <> $series_id ){
		$html .= '<p class="text-end tgsd-series-wrap small pb-2"><a href="'.get_permalink( $series_id ).'" target="_blank">Series details</a></p>';
	}
	
	// pricing
	$discounted_price = $undiscounted_price;
    if( $series_discount > 0 ){
        $discount = round( $undiscounted_price * $series_discount / 100, 2 );
        if( $discount > 0 ){
            $discounted_price = $undiscounted_price - $discount;
        }
    }
    
    // ccpa_write_log($discounted_price);

	// Prices centered to the full row width
	// VAT text positioned at the end
	/*
	$html .= '
				<div class="position-relative d-flex align-items-end my-4">
	                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-end justify-content-center">
	                    <span id="tgsd-price-undiscounted" class="tgsd-price-undiscounted me-2 lh-1" data-original-price="'.$undiscounted_price.'">'.cc_money_format( $undiscounted_price, $currency ).'</span>
	                    <span id="tgsd-price-discounted" class="tgsd-price-discounted lh-1" data-original-discounted="'.$discounted_price.'">'.cc_money_format( $discounted_price, $currency ).'</span>
	                </div>
	                <span class="tgsd-vat-text ms-auto small lh-1">+ VAT</span>
	            </div>';
	*/

	$html .= '
	            <div class="d-flex align-items-end justify-content-center my-4 position-relative">
	                <div class="d-flex align-items-end flex-wrap justify-content-center">
	                    <span id="tgsd-price-undiscounted" class="tgsd-price-undiscounted me-2 lh-1" data-original-price="'.$undiscounted_price.'">'.cc_money_format( $undiscounted_price, $currency ).'</span>
	                    <span id="tgsd-price-discounted" class="tgsd-price-discounted lh-1 me-2" data-original-discounted="'.$discounted_price.'">'.cc_money_format( $discounted_price, $currency ).'</span>
	                    <span class="tgsd-vat-text small lh-1">+ VAT</span>
	                </div>
	            </div>';

    
    // the button with data attributes
    $html .= '
		<form id="tgsd-form" class="tgsd-form" action="/registration" method="POST">
            <input type="hidden" id="training_type" name="training-type" value="s">
            <input type="hidden" name="workshop-id" value="'.$series_id.'">
		    <input type="hidden" name="eventID" value="">
		    <input type="hidden" name="num-events" value="1">
		    <input type="hidden" name="num-free" value="0">
            <input type="hidden" name="currency" value="'.$currency.'">
            <input type="hidden" id="amount_payable" name="raw-price" value="'.$discounted_price.'">
		    <input type="hidden" id="user-timezone" name="user-timezone" value="'.$user_timezone.'">
		    <input type="hidden" id="user-prettytime" name="user-prettytime" value="'.$pretty_timezone.'">
		    <input type="hidden" id="student" name="student" value="no">
		    <input type="hidden" id="student-price" name="student-price" value="0">
            <input type="hidden" id="series_saving" name="series_discount" value="'.($undiscounted_price - $discounted_price).'">
		    <!-- Add tracking data -->
		    <input type="hidden" name="source_page" value="'.$series_title.'">
		    <!-- training_id (array) is added here by JS -->
            <button type="submit" 
            	id="tgsd-btn" 
            	class="btn btn-lg w-100 btn-training tgsd-btn" 
            	data-total-courses="'.$total_courses.'" 
            	data-original-text="Register for the series" 
            	data-partial-text="Register for selected courses">
            		Register for the series
            </button>
        </form>';
    
	$html .= '
			</div>
		</div>';

	// ccpa_write_log('training_groups_selection_card done');

	return $html;
}
