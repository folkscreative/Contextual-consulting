<?php
if(!class_exists('Post_Type_Template')) {

	class Post_Type_Template {
		const POST_TYPE	= "workshop";
		private $_meta	= array(
            'type', // workshop type (workshop or webinar)
			'meta_a', // start date
			'meta_b', // registration code (event 1)
			'meta_c', // venue link (event 1)
            'workshop_timestamp',
            'prettydates',
            'summary',
            'end_date',
            'presenter',
            'category',
            'event_1_name',
            'event_1_closed',
            'event_1_time',
            'event_1_free',
            'event_2_name',
            'event_2_closed',
            'event_2_reg',
            'event_2_venue',
            'event_2_date',
            'event_2_time',
            'event_2_free',
            'event_3_name',
            'event_3_closed',
            'event_3_reg',
            'event_3_venue',
            'event_3_date',
            'event_3_time',
            'event_3_free',
            'event_4_name',
            'event_4_closed',
            'event_4_reg',
            'event_4_venue',
            'event_4_date',
            'event_4_time',
            'event_4_free',
            'event_5_name',
            'event_5_closed',
            'event_5_date',
            'event_5_time',
            'event_5_free',
            'event_6_name',
            'event_6_closed',
            'event_6_date',
            'event_6_time',
            'event_6_free',
            'event_7_name',
            'event_7_closed',
            'event_7_date',
            'event_7_time',
            'event_7_free',
            'event_8_name',
            'event_8_closed',
            'event_8_date',
            'event_8_time',
            'event_8_free',
            'event_9_name',
            'event_9_closed',
            'event_9_date',
            'event_9_time',
            'event_9_free',
            'event_10_name',
            'event_10_closed',
            'event_10_date',
            'event_10_time',
            'event_10_free',
            'event_11_name',
            'event_11_closed',
            'event_11_date',
            'event_11_time',
            'event_11_free',
            'event_12_name',
            'event_12_closed',
            'event_12_date',
            'event_12_time',
            'event_12_free',
            'event_13_name',
            'event_13_closed',
            'event_13_date',
            'event_13_time',
            'event_13_free',
            'event_14_name',
            'event_14_closed',
            'event_14_date',
            'event_14_time',
            'event_14_free',
            'event_15_name',
            'event_15_closed',
            'event_15_date',
            'event_15_time',
            'event_15_free',
            'online_pmt_amt',
            'online_pmt_aud',
            'online_pmt_usd',
            'online_pmt_eur',
            'all_events_discount',
            'webcast_attend',
            'hide_dates',
            'registration_message',
            'last_date_for_sale',
            'last_time_for_sale',
            'event_1_recording',
            'event_2_recording',
            'event_3_recording',
            'event_4_recording',
            'event_5_recording',
            'event_6_recording',
            'event_7_recording',
            'event_8_recording',
            'event_9_recording',
            'event_10_recording',
            'event_11_recording',
            'event_12_recording',
            'event_13_recording',
            'event_14_recording',
            'event_15_recording',
            'event_1_slides',
            'event_2_slides',
            'event_3_slides',
            'event_4_slides',
            'event_5_slides',
            'event_6_slides',
            'event_7_slides',
            'event_8_slides',
            'event_9_slides',
            'event_10_slides',
            'event_11_slides',
            'event_12_slides',
            'event_13_slides',
            'event_14_slides',
            'event_15_slides',
            'event_1_resources',
            'event_2_resources',
            'event_3_resources',
            'event_4_resources',
            'event_5_resources',
            'event_6_resources',
            'event_7_resources',
            'event_8_resources',
            'event_9_resources',
            'event_10_resources',
            'event_11_resources',
            'event_12_resources',
            'event_13_resources',
            'event_14_resources',
            'event_15_resources',
            'event_1_certificate',
            'event_2_certificate',
            'event_3_certificate',
            'event_4_certificate',
            'event_5_certificate',
            'event_6_certificate',
            'event_7_certificate',
            'event_8_certificate',
            'event_9_certificate',
            'event_10_certificate',
            'event_11_certificate',
            'event_12_certificate',
            'event_13_certificate',
            'event_14_certificate',
            'event_15_certificate',
            'event_1_feedback',
            'event_2_feedback',
            'event_3_feedback',
            'event_4_feedback',
            'event_5_feedback',
            'event_6_feedback',
            'event_7_feedback',
            'event_8_feedback',
            'event_9_feedback',
            'event_10_feedback',
            'event_11_feedback',
            'event_12_feedback',
            'event_13_feedback',
            'event_14_feedback',
            'event_15_feedback',
            'event_1_linkedin',
            'event_2_linkedin',
            'event_3_linkedin',
            'event_4_linkedin',
            'event_5_linkedin',
            'event_6_linkedin',
            'event_7_linkedin',
            'event_8_linkedin',
            'event_9_linkedin',
            'event_10_linkedin',
            'event_11_linkedin',
            'event_12_linkedin',
            'event_13_linkedin',
            'event_14_linkedin',
            'event_15_linkedin',
            'event_1_zoom',
            'event_2_zoom',
            'event_3_zoom',
            'event_4_zoom',
            'event_5_zoom',
            'event_6_zoom',
            'event_7_zoom',
            'event_8_zoom',
            'event_9_zoom',
            'event_10_zoom',
            'event_11_zoom',
            'event_12_zoom',
            'event_13_zoom',
            'event_14_zoom',
            'event_15_zoom',
            'event_1_joining',
            'event_2_joining',
            'event_3_joining',
            'event_4_joining',
            'event_5_joining',
            'event_6_joining',
            'event_7_joining',
            'event_8_joining',
            'event_9_joining',
            'event_10_joining',
            'event_11_joining',
            'event_12_joining',
            'event_13_joining',
            'event_14_joining',
            'event_15_joining',
            'event_1_venue_name',
            'event_2_venue_name',
            'event_3_venue_name',
            'event_4_venue_name',
            'event_5_venue_name',
            'event_6_venue_name',
            'event_7_venue_name',
            'event_8_venue_name',
            'event_9_venue_name',
            'event_10_venue_name',
            'event_11_venue_name',
            'event_12_venue_name',
            'event_13_venue_name',
            'event_14_venue_name',
            'event_15_venue_name',
            'workshop_recording',
            'workshop_slides',
            'workshop_resources',
            'workshop_certificate', // replaced by individual certs
            'workshop_feedback',
            'workshop_linkedin',
            'workshop_zoom',
            'workshop_joining',
            'event_1_date_end',
            'event_2_date_end',
            'event_3_date_end',
            'event_4_date_end',
            'event_5_date_end',
            'event_6_date_end',
            'event_7_date_end',
            'event_8_date_end',
            'event_9_date_end',
            'event_10_date_end',
            'event_11_date_end',
            'event_12_date_end',
            'event_13_date_end',
            'event_14_date_end',
            'event_15_date_end',
            'event_1_time_end',
            'event_2_time_end',
            'event_3_time_end',
            'event_4_time_end',
            'event_5_time_end',
            'event_6_time_end',
            'event_7_time_end',
            'event_8_time_end',
            'event_9_time_end',
            'event_10_time_end',
            'event_11_time_end',
            'event_12_time_end',
            'event_13_time_end',
            'event_14_time_end',
            'event_15_time_end',
            'workshop_featured',
            'rec_avail',
            'who_for',
            'student_discount',
            'earlybird_discount',
            'earlybird_expiry_date',
            'mailster_list',
            'earlybird_name',
            // added v2.3.0
            'event_1_sess_2_date',
            'event_1_sess_2_time',
            'event_1_sess_2_date_end',
            'event_1_sess_2_time_end',
            'event_1_sess_3_date',
            'event_1_sess_3_time',
            'event_1_sess_3_date_end',
            'event_1_sess_3_time_end',
            'event_1_sess_4_date',
            'event_1_sess_4_time',
            'event_1_sess_4_date_end',
            'event_1_sess_4_time_end',
            'event_1_sess_5_date',
            'event_1_sess_5_time',
            'event_1_sess_5_date_end',
            'event_1_sess_5_time_end',
            'event_1_sess_6_date',
            'event_1_sess_6_time',
            'event_1_sess_6_date_end',
            'event_1_sess_6_time_end',
            // added v2.6.0
            /*
            'event_1_sessions',
            'event_2_sessions',
            'event_3_sessions',
            'event_4_sessions',
            'event_5_sessions',
            'event_6_sessions',
            'event_7_sessions',
            'event_8_sessions',
            'event_9_sessions',
            'event_10_sessions',
            'event_11_sessions',
            'event_12_sessions',
            'event_13_sessions',
            'event_14_sessions',
            'event_15_sessions',
            */
            // added v1.67
            'ce_credits',
            'cert_cc',
            'cert_apa',
            'cert_bacb',
            'cert_nbcc',
            'cert_icf',
            // not added the event cert fields here cos there are just way too many of them ... but they will be addedd as they are used
            'block_nlft',
            'block_cnwl',
            'course_status',
		);
		
    	public function __construct() {
    		// register actions
    		add_action('init', array(&$this, 'init'));
    		add_action('admin_init', array(&$this, 'admin_init'));
            add_action( 'admin_enqueue_scripts', array(&$this, 'enqueue_admin_scripts') );
            // position the subtitle metabox where i want it
            add_action('edit_form_after_title', array(&$this, 'reposition_subtitle_metabox'));
    	} // END public function __construct()

    	public function init() {
    		// Initialize Post Type
    		$this->create_post_type();
    		add_action('save_post', array(&$this, 'save_post'));
    	} // END public function init()

    	public function create_post_type() {
    		register_post_type(self::POST_TYPE,
    			array(
    				'labels' => array(
    					'name' => __(sprintf('%ss', ucwords(str_replace("_", " ", self::POST_TYPE)))),
    					'singular_name' => __(ucwords(str_replace("_", " ", self::POST_TYPE)))
    				),
    				'public' => true,
    				'has_archive' => true,
    				'description' => __("Contextual Consulting Workshops"),
    				'supports' => array(
    					'title', 'editor', 'excerpt', 'thumbnail', 
    				),
                    'taxonomies' => array( 'tax_issues', 'tax_approaches', 'tax_rtypes', 'tax_others', 'tax_trainlevels' ),
                    'rewrite' => array('slug' => 'live-training'),
    			)
    		);
    	}
	
    	public function save_post($post_id) {
            // verify if this is an auto save routine. 
            // If it is our form has not been submitted, so we dont want to do anything
            if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            {
                return;
            }
            
    		if(isset($_POST['post_type']) && $_POST['post_type'] == self::POST_TYPE && current_user_can('edit_post', $post_id))
    		{
    			// empty out all the checkboxes as, if they have been unset, there will be no post for them
                // checkboxes removed as quick edit messes them up!
                // update_post_meta($post_id, 'webcast_attend', '');
                // for ($i=0; $i < 16; $i++) { 
                    // update_post_meta($post_id, 'event_'.$i.'_free', '');
                    // update_post_meta($post_id, 'event_'.$i.'_closed', '');
                // }
                foreach($this->_meta as $field_name){
                    if($field_name == 'meta_a'){ // start date (event 1 date)
                        if( isset( $_POST['meta_a'] ) ){
                            if( $_POST['meta_a'] == '' ){
                                update_post_meta($post_id, $field_name, '');
                            }else{
                                $date = DateTime::createFromFormat("d/m/Y", $_POST['meta_a']);
                                if($date){ // $date will be false if date is rubbish
                                    $meta_value = date('d/m/Y', $date->getTimestamp());
                                    update_post_meta($post_id, $field_name, $meta_value);
                                    $orig_workshop_start_timestamp = get_post_meta($post_id, 'workshop_start_timestamp', true );
                                    update_post_meta($post_id, 'workshop_start_timestamp', $date->getTimestamp());
                                    if($orig_workshop_start_timestamp <> $date->getTimestamp()){
                                        // workshop start has changed ... update the relevant workshops users table rows
                                        my_acct_workshop_update_start($post_id, $date->getTimestamp());
                                    }
                                    /* we'll now sort out the workshop_timestamp after all the meta data
                                    if($_POST['end_date'] == ''){
                                        // use the start date for the timestamp
                                        $workshop_timestamp = $date->getTimestamp();
                                        update_post_meta($post_id, 'workshop_timestamp', $workshop_timestamp);
                                    }
                                    */
                                }
                            }
                        }
                        continue;
                    }
                    if($field_name == 'end_date'){
                        // this field is no longer used .......
                        if( isset($_POST['end_date'])){
                            if( $_POST['end_date'] == '' ){
                                update_post_meta($post_id, $field_name, '');
                            }else{
                                $date = DateTime::createFromFormat("d/m/Y", $_POST['end_date']);
                                if($date){ // $date will be false if date is rubbish
                                    $meta_value = date('d/m/Y', $date->getTimestamp());
                                    update_post_meta($post_id, $field_name, $meta_value);
                                    // $workshop_timestamp = $date->getTimestamp();
                                    // update_post_meta($post_id, 'workshop_timestamp', $workshop_timestamp);
                                }
                            }
                        }
                        continue;
                    }
                    if(substr($field_name, -4) == 'date' || substr($field_name, -8) == 'date_end'){
                        if( isset( $_POST[$field_name] ) ){
                            if($_POST[$field_name] == ''){
                                update_post_meta($post_id, $field_name, '');
                            }else{
                                $date = DateTime::createFromFormat("d/m/Y", $_POST[$field_name]);
                                if($date){ // $date will be false if date is rubbish
                                    $meta_value = date('d/m/Y', $date->getTimestamp());
                                    update_post_meta($post_id, $field_name, $meta_value);
                                }
                            }
                        }
                        continue;
                    }
                    if(substr($field_name, -4) == 'time' || substr($field_name, -8) == 'time_end'){
                        if( isset( $_POST[$field_name] ) ){
                            if($_POST[$field_name] == ''){
                                update_post_meta($post_id, $field_name, '');
                            }else{
                                // time now entered as London time ... we'll store it as UTC
                                // that means we need to know the date as well as the time
                                // which means we need to know which event this is for
                                if($field_name == 'event_1_time'){
                                    $event_date_field = 'meta_a';
                                }else{
                                    $event_date_field = str_replace('time', 'date', $field_name);
                                }
                                if(isset($_POST[$event_date_field])){
                                    $event_date = $_POST[$event_date_field]; // d/m/Y
                                }else{
                                    $event_date = get_post_meta($post_id, $event_date_field, true); // d/m/Y
                                }
                                if($event_date == '' && substr($field_name, -8) == 'time_end'){
                                    // let's try using the start date then ...
                                    if($field_name == 'event_1_time_end'){
                                        $event_date_field = 'meta_a';
                                    }else{
                                        $event_date_field = str_replace('_end', '', $event_date_field);
                                    }
                                    $event_date = get_post_meta($post_id, $event_date_field, true); // d/m/Y
                                }
                                $datetime = DateTime::createFromFormat("d/m/Y H:i", $event_date.' '.$_POST[$field_name], new DateTimeZone('Europe/London'));
                                if($datetime){
                                    $utc_datetime = new DateTime('now', new DateTimeZone('UTC'));
                                    $utc_datetime->setTimestamp($datetime->getTimestamp());
                                    $meta_value = date('H:i', $utc_datetime->getTimestamp());
                                    update_post_meta($post_id, $field_name, $meta_value);
                                }
                            }
                        }
                        continue;
                    }
                    if($field_name == 'last_date_for_sale'){
                        // handled with last_time_for_sale below
                        continue;
                    }
                    if($field_name == 'last_time_for_sale'){
                        if( isset( $_POST['last_time_for_sale'] ) ){
                            $meta_value = '';
                            if($_POST['last_date_for_sale'] <> ''){
                                $date = false;
                                if($_POST['last_time_for_sale'] == ''){
                                    $date = DateTime::createFromFormat("d/m/Y H:i", $_POST['last_date_for_sale'].' 23:59', new DateTimeZone('Europe/London'));
                                }else{
                                    $date = DateTime::createFromFormat("d/m/Y H:i", $_POST['last_date_for_sale'].' '.$_POST['last_time_for_sale'], new DateTimeZone('Europe/London'));
                                }
                                if($date){
                                    $utc_datetime = new DateTime('now', new DateTimeZone('UTC'));
                                    $utc_datetime->setTimestamp($date->getTimestamp());
                                    $meta_value = date('Y-m-d H:i', $utc_datetime->getTimestamp());
                                }
                            }
                            update_post_meta($post_id, 'last_datetime_for_sale', $meta_value);
                        }
                        continue;
                    }
                    if( $field_name == 'registration_message' ){
                        if( isset( $_POST['registration_message'] ) ){
                            update_post_meta( $post_id, $field_name, wp_kses_post( wp_unslash( $_POST['registration_message'] ) ) );
                        }
                        continue;
                    }
                    // everything else (almost!)
    				if($field_name <> 'workshop_timestamp'){
                        if(isset($_POST[$field_name])){
                            update_post_meta($post_id, $field_name, trim( stripslashes( sanitize_text_field( $_POST[$field_name] ) ) ) );
                        }
                    }
    			}
                // now the workshop_timestamp
                // this is the end date and time of the workshop (ie of the final event)
                $workshop_timestamp = workshop_calculate_end_timestamp($post_id);
                if($workshop_timestamp){
                    update_post_meta($post_id, 'workshop_timestamp', $workshop_timestamp);
                }else{
                    update_post_meta($post_id, 'workshop_timestamp', '');
                }
                
                // now the upsells
                if( isset( $_POST['upsell_workshop'] ) ){
                    if( $_POST['upsell_workshop'] <> '' ){
                        $discount = 0;
                        if(isset($_POST['upsell_discount']) && $_POST['upsell_discount'] <> '' && is_numeric($_POST['upsell_discount'])){
                            $discount = $_POST['upsell_discount'];
                        }
                        $expiry = '0000-00-00 00:00:00';
                        if( isset( $_POST['upsell_expiry_date'] ) && $_POST['upsell_expiry_date'] <> '' ){
                            if( isset( $_POST['upsell_expiry_time'] ) && $_POST['upsell_expiry_time'] <> '' ){
                                $date_time_string = $_POST['upsell_expiry_date'].' '.$_POST['upsell_expiry_time'];
                            }else{
                                $date_time_string = $_POST['upsell_expiry_date'].' 00:00';
                            }
                            $datetime = DateTime::createFromFormat( "d/m/Y H:i", $date_time_string, new DateTimeZone( 'Europe/London' ));
                            if( $datetime ){
                                $expiry = $datetime->format( 'Y-m-d H:i' ).':00';
                            }
                        }
                        $result = cc_upsells_update_upsell($post_id, absint($_POST['upsell_workshop']), $discount, $expiry);
                    }else{
                        $result = cc_upsells_remove_upsell($post_id);
                    }
                }
                if(isset($_POST['subtitle'])){
                    update_post_meta($post_id, 'subtitle', sanitize_text_field($_POST['subtitle']));
                }
                // and the event price overrides
                for ($i=1; $i < 16; $i++) { 
                    $event_prices = array();
                    $found_prices = false;
                    foreach (workshops_currencies() as $currency) {
                        $value = 0;
                        if( isset( $_POST['event_'.$i.'_price_'.$currency['code']] ) ){
                            $found_prices = true;
                            if( $_POST['event_'.$i.'_price_'.$currency['code']] <> '' ){
                                $value = (float) $_POST['event_'.$i.'_price_'.$currency['code']];
                            }
                            $event_prices[$currency['code']] = $value;
                        }
                    }
                    if( $found_prices ){
                        update_post_meta($post_id, 'event_'.$i.'_prices', $event_prices);
                    }
                }

                // now the event certificate fields
                for ($i=1; $i < 16; $i++) { 
                    if( isset( $_POST['event_'.$i.'_ce_credits'] ) ){
                        if( (float) $_POST['event_'.$i.'_ce_credits'] > 0 ){
                            update_post_meta( $post_id, 'event_'.$i.'_ce_credits', (float) $_POST['event_'.$i.'_ce_credits'] );
                        }else{
                            delete_post_meta( $post_id, 'event_'.$i.'_ce_credits' );
                        }
                    }
                    $certs = array( 'cc', 'apa', 'bacb', 'nbcc', 'icf' );
                    foreach ($certs as $cert) {
                        if( isset( $_POST['event_'.$i.'_cert_'.$cert] ) && ( $_POST['event_'.$i.'_cert_'.$cert] == 'yes' || $_POST['event_'.$i.'_cert_'.$cert] == 'no' ) ){
                            update_post_meta( $post_id, 'event_'.$i.'_cert_'.$cert, $_POST['event_'.$i.'_cert_'.$cert] );
                        }
                    }
                }
                
                // and now the resource files folder
                resources_save_folder($post_id);

                // and now the training accordions
                /* doing it in a better way now ......
                if( isset( $_POST['tta-id-0'] ) ){
                    // some data here ... not a quick edit
                    $empty_rows = 0;
                    $maybe_more = true;
                    $row = 0;
                    while ($maybe_more) {
                        if(!isset($_POST['tta-ta-'.$row])){
                            $empty_rows ++;
                            if($empty_rows > 5){
                                $maybe_more = false;
                            }
                        }else{
                            $tta = cc_train_acc_empty_tta();
                            $tta['id'] = absint( $_POST['tta-id-'.$row] );
                            if( $_POST['tta-del-'.$row] == 'yes' ){
                                if($tta['id'] > 0){
                                    cc_train_acc_delete_row($tta['id']);
                                }
                            }else{
                                $tta['training_id'] = $post_id;
                                $tta['train_acc_id'] = absint( $_POST['tta-ta-'.$row] );
                                $tta['source'] = sanitize_text_field( $_POST['tta-src-'.$row] );
                                $tta['sequence'] = absint( $_POST['tta-ord-'.$row] );
                                $tta['hide'] = sanitize_text_field( $_POST['tta-hid-'.$row] );
                                if($tta['source'] == 'cust'){
                                    $tta['title'] = sanitize_text_field( $_POST['tta-tit-'.$row] );
                                    $tta['content'] = wp_kses_post( stripslashes( $_POST['tta-txt-'.$row] ) );
                                }
                                if($tta['train_acc_id'] > 0){
                                    $tta_id = cc_train_acc_update($tta);
                                }
                            }
                        }
                        $row ++;
                    }
                    // and ensure that fresh defaults are not set up for this training
                    update_post_meta($post_id, '_training_accordions_set', 'yes');
                }
                */
    		}
    		else
    		{
    			return;
    		} // if($_POST['post_type'] == self::POST_TYPE && current_user_can('edit_post', $post_id))
    	} // END public function save_post($post_id)

    	public function admin_init() {			
    		// Add metaboxes
    		add_action('add_meta_boxes_'.self::POST_TYPE, array(&$this, 'add_meta_boxes'));
    	} // END public function admin_init()
			
    	public function add_meta_boxes() {
    		// Add this metabox to every selected post
            // add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
    		add_meta_box( 
    			sprintf('workshops_plugin_%s_section', self::POST_TYPE),
    			sprintf('%s Information', ucwords(str_replace("_", " ", self::POST_TYPE))),
    			array(&$this, 'add_inner_meta_boxes'),
    			self::POST_TYPE
    	    );
            add_meta_box( 'workshops_plugin_training_accordions', 'Training Accordions', 'cc_train_acc_add_training_accordion_metabox', self::POST_TYPE, 'advanced', 'default' );
            add_meta_box( 'workshops_plugin_feedback', 'Feedback Questions', 'cc_feedback_training_metabox', self::POST_TYPE, 'advanced', 'default' );
            add_meta_box('workshops_plugin_subtitle_metabox', 'Workshop Subtitle', array(&$this, 'add_subtitle_metabox'), self::POST_TYPE, 'extraordinary', 'high');
    	} // END public function add_meta_boxes()

        // after title ...
        public function reposition_subtitle_metabox(){
            # Get the globals:
            global $post, $wp_meta_boxes;
            if($post->post_type == 'workshop'){
                # Output the "extraordinary" meta boxes:
                do_meta_boxes( get_current_screen(), 'extraordinary', $post );
                # Remove the initial "extraordinary" meta boxes:
                unset($wp_meta_boxes['post']['extraordinary']);
            }
        }

        public function enqueue_admin_scripts($hook){
            if($hook <> 'post-new.php' && $hook <> 'post.php') return;
            wp_enqueue_script('workshop_admin_validate', plugin_dir_url( __FILE__ ).'../js/jquery.validate.min.js', array('jquery'), '1.19.1');
            wp_enqueue_script( 'workshop-admin-script', plugin_dir_url( __FILE__ ) . '../js/workshop-admin.js', array(), '2.13.0' );
        }

        public function add_subtitle_metabox($post){ ?>
            <input class="large-text" type="text" id="subtitle" name="subtitle" value="<?php echo @get_post_meta($post->ID, 'subtitle', true); ?>" />
        <?php }
	
		public function add_inner_meta_boxes($post)	{ ?>
            <div class="container">
                <div class="row">
                    <div class="col-4">
                        <label class="form-label" for="type">Type</label><br>
                        <?php $workshop_type = get_post_meta($post->ID, 'type', true); ?>
                        <select name="type" id="type" class="required form-control">
                            <option value="">Please select</option>
                            <option value="workshop" <?php selected($workshop_type, 'workshop'); ?>>Workshop</option>
                            <option value="webinar" <?php selected($workshop_type, 'webinar'); ?>>Webinar</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="workshop_featured">Featured workshop?</label><br>
                        <?php $workshop_workshop_featured = get_post_meta($post->ID, 'workshop_featured', true); ?>
                        <select name="workshop_featured" id="workshop_featured" class="form-control">
                            <option value="">No</option>
                            <option value="yes" <?php selected($workshop_workshop_featured, 'yes'); ?>>Yes</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="webcast_attend">Allow attend. by webcast?</label><br>
                        <select name="webcast_attend" id="webcast_attend" class="form-control">
                            <option value="">No</option>
                            <option value="yes" <?php selected( 'yes', get_post_meta($post->ID, 'webcast_attend', true) ); ?>>Yes</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <label class="form-label" for="category"><i class="fa-solid fa-folder fa-fw"></i> Category</label><br>
                        <input type="text" id="category" name="category" class="form-control" value="<?php echo @get_post_meta($post->ID, 'category', true); ?>" />
                    </div>
                    <div class="col-4">
                        <label for="block_nlft" class="form-label">Block NLFT registrations?</label><br>
                        <select name="block_nlft" id="block_nlft" class="form-control">
                            <option value="">No</option>
                            <option value="yes" <?php selected( 'yes', get_post_meta($post->ID, 'block_nlft', true) ); ?>>Yes</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label for="block_cnwl" class="form-label">Block CNWL registrations?</label><br>
                        <select name="block_cnwl" id="block_cnwl" class="form-control">
                            <option value="">No</option>
                            <option value="yes" <?php selected( 'yes', get_post_meta($post->ID, 'block_cnwl', true) ); ?>>Yes</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <label class="form-label" for="meta_a"><i class="fa-solid fa-calendar-days fa-fw"></i> Start Date (and Event 1 date)</label><br>
                        <input type="text" id="meta_a" name="meta_a" class="form-control" value="<?php echo @get_post_meta($post->ID, 'meta_a', true); ?>" placeholder="dd/mm/yyyy" />
                        <div class="form-text">
                            <?php
                            echo get_post_meta($post->ID, 'workshop_start_timestamp', true);
                            echo ' - ';
                            echo get_post_meta($post->ID, 'workshop_timestamp', true);
                            ?>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="hide_dates">Hide all dates?</label><br>
                        <select name="hide_dates" id="hide_dates" class="form-control">
                            <option value="">No</option>
                            <option value="yes" <?php selected( 'yes', get_post_meta($post->ID, 'hide_dates', true) ); ?>>Yes</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <?php $course_status = get_post_meta( $post->ID, 'course_status', true ); ?>
                        <label class="form-label" for="course_status">Course status</label><br>
                        <select name="course_status" id="course_status" class="form-control">
                            <option value="">Available to purchase</option>
                            <?php /*
                            <option value="closed" <?php selected('closed', $course_status); ?>>Closed for purchase</option>
                            <option value="public" <?php selected('public', $course_status); ?>>No purchase necessary</option>
                            */ ?>
                            <option value="unlisted" <?php selected('unlisted', $course_status); ?>>Available but unlisted</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <label class="form-label" for="prettydates"><i class="fa-solid fa-hourglass-half fa-fw"></i> Time commitment (any format you want)</label><br>
                        <input type="text" id="prettydates" name="prettydates" class="form-control" value="<?php echo @get_post_meta($post->ID, 'prettydates', true); ?>" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <label class="form-label" for="">Last date for sale (optional)</label><br>
                        <?php
                        $last_date_for_sale = '';
                        $last_time_for_sale = '';
                        $last_datetime_for_sale = get_post_meta($post->ID, 'last_datetime_for_sale', true);
                        if($last_datetime_for_sale <> ''){
                            $datetime = DateTime::createFromFormat("Y-m-d H:i", $last_datetime_for_sale, new DateTimeZone('UTC'));
                            if($datetime){
                                $london_datetime = new DateTime('now', new DateTimeZone('Europe/London'));
                                $london_datetime->setTimestamp($datetime->getTimestamp());
                                $last_date_for_sale = $london_datetime->format('d/m/Y');
                                $last_time_for_sale = $london_datetime->format('H:i');
                            }
                        }
                        ?>
                        <input type="text" name="last_date_for_sale" class="form-control" value="<?php echo $last_date_for_sale; ?>" placeholder="dd/mm/yyyy">
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="">Last time for sale (optional)</label><br>
                        <input type="text" name="last_time_for_sale" class="form-control" value="<?php echo $last_time_for_sale; ?>" placeholder="hh:mm">
                    </div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <label class="form-label" for="rec_avail"><i class="fa-solid fa-video fa-fw"></i> Recording text</label><br>
                        <input type="text" id="rec_avail" name="rec_avail" class="form-control" value="<?php echo @get_post_meta($post->ID, 'rec_avail', true); ?>" placeholder="e.g. Recording available for 6 months">
                    </div>
                </div>
                <div class="row">
                    <div class="col-8">
                        <label class="form-label" for="who_for"><i class="fa-solid fa-award fa-fw"></i> Audience (who's it for?)</label><br>
                        <input type="text" id="who_for" name="who_for" class="form-control" value="<?php echo @get_post_meta($post->ID, 'who_for', true); ?>">
                        <div class="form-text">Add small text with ... "Normal text [small]small text[/small] more normal text"</div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-12">
                        <label for="summary" class="form-label">Summary (shown on the Workshop summary pages)</label>
                        <textarea name="summary" id="summary" cols="100" rows="4" class="form-control" ><?php echo @get_post_meta($post->ID, 'summary', true); ?></textarea>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <label for="" class="form-label">Prices (excl VAT)</label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-3">
                        <label for="online_pmt_amt" class="form-label">GBP &pound;</label>
                        <input id="online_pmt_amt" class="form-control" type="number" name="online_pmt_amt" min="0" step="0.01" value="<?php echo @get_post_meta($post->ID, 'online_pmt_amt', true); ?>">
                    </div>
                    <div class="col-3">
                        <label for="online_pmt_aud" class="form-label">AUD $</label>
                        <input id="online_pmt_aud" class="form-control" type="number" name="online_pmt_aud" min="0" step="0.01" value="<?php echo @get_post_meta($post->ID, 'online_pmt_aud', true); ?>">
                    </div>
                    <div class="col-3">
                        <label for="online_pmt_usd" class="form-label">USD $</label>
                        <input id="online_pmt_usd" class="form-control" type="number" name="online_pmt_usd" min="0" step="0.01" value="<?php echo @get_post_meta($post->ID, 'online_pmt_usd', true); ?>">
                    </div>
                    <div class="col-3">
                        <label for="online_pmt_eur" class="form-label">EUR €</label>
                        <input id="online_pmt_eur" class="form-control" type="number" name="online_pmt_eur" min="0" step="0.01" value="<?php echo @get_post_meta($post->ID, 'online_pmt_eur', true); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <label for="student_discount" class="form-label">Student discount % (single-event only)</label>
                        <input type="number" id="student_discount" name="student_discount" class="form-control" min="0" step="0.0001" value="<?php echo @get_post_meta($post->ID, 'student_discount', true); ?>">
                    </div>
                    <div class="col-4">
                        <label for="all_events_discount" class="form-label">Discount for booking all events % (multi-event only)</label>
                        <input type="number" id="all_events_discount" name="all_events_discount" class="form-control" min="0" step="0.0001" value="<?php echo @get_post_meta($post->ID, 'all_events_discount', true); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <label for="earlybird_discount" class="form-label">Early-bird discount % (single-event only)</label>
                        <input type="number" id="earlybird_discount" name="earlybird_discount" class="form-control" min="0" step="0.0001" value="<?php echo @get_post_meta($post->ID, 'earlybird_discount', true); ?>">
                        <div class="form-text">Date must also be set</div>
                    </div>
                    <div class="col-4">
                        <label for="earlybird_expiry_date" class="form-label">Early-bird expiry (at the end of the day)</label>
                        <input type="text" id="earlybird_expiry_date" name="earlybird_expiry_date" class="form-control" value="<?php echo @get_post_meta($post->ID, 'earlybird_expiry_date', true); ?>" placeholder="dd/mm/yyyy">
                        <div class="form-text">Discount must also be set</div>
                    </div>
                    <div class="col-4">
                        <label for="earlybird_name" class="form-label">Early-bird name (if not set will be "Early-bird")</label>
                        <input type="text" id="earlybird_name" name="earlybird_name" class="form-control" value="<?php echo @get_post_meta($post->ID, 'earlybird_name', true); ?>" placeholder="eg Discount">
                    </div>
                </div>
                <div class="row">
                    <?php
                    echo cc_upsells_backend_field( $post->ID );
                    /*
                    <div class="col-4">
                        <label for="upsell_workshop" class="form-label">Upsell workshop (single event only)</label>
                        <select name="upsell_workshop" id="upsell_workshop" class="form-control">
                            <?php echo cc_upsells_workshop_options($post->ID); ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <label for="upsell_discount" class="form-label">Upsell Discount %</label>
                        <input type="number" id="upsell_discount" name="upsell_discount" class="form-control" min="0" step="0.0001" max="100" value="<?php echo cc_upsells_discount($post->ID); ?>">
                    </div>
                    <div class="col-4">
                        <label for="upsell_expiry" class="form-label">Upsell expiry date if required (DD/MM/YYYY)</label>
                        <input type="text" id="upsell_expiry" name="upsell_expiry" class="form-control" value="<?php echo cc_upsells_expiry($post->ID); ?>" />
                    </div>
                    */ ?>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label class="form-label">Xero tracking code (automatically generated)</label>
                        <input type="text" class="form-control" value="<?php echo get_post_meta( $post->ID, '_xero_tracking_code', true ); ?>" disabled>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <label for="registration_message" class="form-label">Registration Message (included in the registration email, if needed):</label>
                        <?php
                        $registration_message = get_post_meta($post->ID, 'registration_message', true);
                        wp_editor($registration_message, 'registration_message', array('textarea_rows' => 7));
                        ?>
                        <div class="form-text"><strong>IMPORTANT!</strong> Leave ONE blank line after the message so that it separates it from the other text in the email.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-4">
                        <label for="workshop_recording" class="form-label">Recording</label>
                        <select name="workshop_recording" id="workshop_recording" class="form-control">
                            <option value="">Not set</option>
                            <?php echo cc_recordings_options(get_post_meta($post->ID, 'workshop_recording', true)); ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <label for="workshop_slides" class="form-label">Slides</label>
                        <input type="text" name="workshop_slides" id="workshop_slides" class="form-control" value="<?php echo @get_post_meta($post->ID, 'workshop_slides', true); ?>">
                    </div>
                    <div class="col-4">
                        <label for="workshop_resources" class="form-label">Resources</label>
                        <input type="text" name="workshop_resources" id="workshop_resources" class="form-control" value="<?php echo @get_post_meta($post->ID, 'workshop_resources', true); ?>">
                    </div>
                </div>
                <div class="row">
                    <?php /*
                    <div class="col-4">
                        <label for="workshop_certificate" class="form-label">Certificate</label>
                        <input type="text" name="workshop_certificate" id="workshop_certificate" class="form-control" value="<?php echo @get_post_meta($post->ID, 'workshop_certificate', true); ?>">
                    </div>
                    */ ?>
                    <input type="hidden" name="workshop_certificate" id="workshop_certificate" class="form-control" value="<?php echo @get_post_meta($post->ID, 'workshop_certificate', true); ?>">
                    <div class="col-4">
                        <label for="workshop_feedback" class="form-label">Feedback</label>
                        <input type="text" name="workshop_feedback" id="workshop_feedback" class="form-control" value="<?php echo @get_post_meta($post->ID, 'workshop_feedback', true); ?>">
                    </div>
                    <div class="col-4">
                        <label for="workshop_linkedin" class="form-label">LinkedIn</label>
                        <input type="text" name="workshop_linkedin" id="workshop_linkedin" class="form-control" value="<?php echo @get_post_meta($post->ID, 'workshop_linkedin', true); ?>">
                    </div>
                </div>
                <?php
                $cert_cc = cc_certs_setting( $post->ID, 0, 'cc' );
                $cert_apa = cc_certs_setting( $post->ID, 0, 'apa' );
                $cert_bacb = cc_certs_setting( $post->ID, 0, 'bacb' );
                $cert_nbcc = cc_certs_setting( $post->ID, 0, 'nbcc' );
                $cert_icf = cc_certs_setting( $post->ID, 0, 'icf' );
                ?>
                <div class="row">
                    <div class="col-2">
                        <label for="ce_credits" class="form-label"><i class="fa-solid fa-graduation-cap"></i> CE Credits</label>
                        <input type="number" id="ce_credits" name="ce_credits" class="form-control" value="<?php echo get_post_meta( $post->ID, 'ce_credits', true ); ?>">
                    </div>
                    <div class="col-2">
                        <label for="cert_cc" class="form-label">CC Cert?</label>
                        <select name="cert_cc" id="cert_cc" class="form-control">
                            <option value="no" <?php selected( 'no', $cert_cc ); ?>>No</option>
                            <option value="yes" <?php selected( 'yes', $cert_cc ); ?>>Yes</option>
                        </select>
                    </div>
                    <div class="col-2">
                        <label for="cert_apa" class="form-label">APA Cert?</label>
                        <select name="cert_apa" id="cert_apa" class="form-control">
                            <option value="no" <?php selected( 'no', $cert_apa ); ?>>No</option>
                            <option value="yes" <?php selected( 'yes', $cert_apa ); ?>>Yes</option>
                        </select>
                    </div>
                    <div class="col-2">
                        <label for="cert_bacb" class="form-label">BACB Cert?</label>
                        <select name="cert_bacb" id="cert_bacb" class="form-control">
                            <option value="no" <?php selected( 'no', $cert_bacb ); ?>>No</option>
                            <option value="yes" <?php selected( 'yes', $cert_bacb ); ?>>Yes</option>
                        </select>
                    </div>
                    <div class="col-2">
                        <label for="cert_nbcc" class="form-label">NBCC Cert?</label>
                        <select name="cert_nbcc" id="cert_nbcc" class="form-control">
                            <option value="no" <?php selected( 'no', $cert_nbcc ); ?>>No</option>
                            <option value="yes" <?php selected( 'yes', $cert_nbcc ); ?>>Yes</option>
                        </select>
                    </div>
                    <div class="col-2">
                        <label for="cert_icf" class="form-label">ICF Cert?</label>
                        <select name="cert_icf" id="cert_icf" class="form-control">
                            <option value="no" <?php selected( 'no', $cert_icf ); ?>>No</option>
                            <option value="yes" <?php selected( 'yes', $cert_icf ); ?>>Yes</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <label for="workshop_zoom" class="form-label">Zoom</label>
                        <input type="text" name="workshop_zoom" id="workshop_zoom" class="form-control" value="<?php echo @get_post_meta($post->ID, 'workshop_zoom', true); ?>">
                    </div>
                    <div class="col-8">
                        <label for="workshop_joining" class="form-label">Joining info</label>
                        <textarea name="workshop_joining" id="workshop_joining" cols="100" rows="4" class="form-control"><?php echo @get_post_meta($post->ID, 'workshop_joining', true); ?></textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <label for="" class="form-label">Resources</label>
                        <?php echo resources_metabox_fields($post->ID, NULL, false); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <label for="mailster_list" class="form-label">Newsletter list</label>
                        <?php
                        $mailster_list = get_post_meta($post->ID, 'mailster_list', true);
                        $lists = mailster( 'lists' )->get();
                        ?>
                        <select name="mailster_list" id="mailster_list" class="form-control">
                            <option value="">Will be set up when the first person registers</option>
                            <?php foreach ($lists as $list) { ?>
                                <option value="<?php echo $list->ID; ?>" <?php selected($list->ID, $mailster_list); ?>><?php echo $list->name; ?></option>
                            <?php } ?>
                        </select>
                        <div class="form-text">Automatically generated but you can override it here</div>
                    </div>
                </div>

                <div id="workshop-events" class="accordion">
                    <?php 
                    $accordion_button_class = '';
                    $accordion_collapse_class = 'show';
                    $timezones = array('UTC', 'Europe/London', 'Australia/Melbourne', 'America/New_York');
                    for ($i=1; $i < 16; $i++) { ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button <?php echo $accordion_button_class; ?>" data-bs-toggle="collapse" data-bs-target="#event-<?php echo $i; ?>">Event <?php echo $i; ?>: <?php echo @get_post_meta($post->ID, 'event_'.$i.'_name', true); ?></button>
                            </h3>
                            <div id="event-<?php echo $i; ?>" class="accordion-collapse collapse <?php echo $accordion_collapse_class; ?>" data-bs-parent="workshop-events">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-8">
                                            <label class="form-label" for="event_<?php echo $i; ?>_name">Event <?php echo $i; ?> name</label>
                                            <input type="text" id="event_<?php echo $i; ?>_name" name="event_<?php echo $i; ?>_name" class="form-control" value="<?php echo @get_post_meta($post->ID, 'event_'.$i.'_name', true); ?>" />
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label" for="event_<?php echo $i; ?>_closed">Registration closed?</label>
                                            <select name="event_<?php echo $i; ?>_closed" id="event_<?php echo $i; ?>_closed" class="form-control">
                                                <option value="">No</option>
                                                <option value="closed" <?php selected( 'closed', get_post_meta($post->ID, 'event_'.$i.'_closed', true) ); ?>>Yes</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <?php if($i == 1){
                                                $meta_key = 'meta_b';
                                            }else{
                                                $meta_key = 'event_'.$i.'_reg';
                                            } ?>
                                            <label class="form-label" for="<?php echo $meta_key; ?>">Registration Code</label>
                                            <textarea class="form-control" name="<?php echo $meta_key; ?>" id="<?php echo $meta_key; ?>" cols="100" rows="4"><?php echo @get_post_meta($post->ID, $meta_key, true); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label" for="event_<?php echo $i; ?>_venue_name">
                                                <?php if($i == 1){ ?>
                                                    <i class="fa-solid fa-location-dot fa-fw"></i>
                                                <?php } ?>
                                                Venue Name</label>
                                            <input type="text" id="event_<?php echo $i; ?>_venue_name" name="event_<?php echo $i; ?>_venue_name" class="form-control" value="<?php echo @get_post_meta($post->ID, 'event_'.$i.'_venue_name', true); ?>">
                                        </div>
                                        <div class="col-6">
                                            <?php if($i == 1){
                                                $meta_key = 'meta_c'; ?>
                                                <i class="fa-solid fa-link"></i>
                                                <?php
                                            }else{
                                                $meta_key = 'event_'.$i.'_venue';
                                            } ?>
                                            <label class="form-label" for="<?php echo $meta_key; ?>">Venue Link</label>
                                            <input type="text" id="<?php echo $meta_key; ?>" name="<?php echo $meta_key; ?>" class="form-control code" value="<?php echo @get_post_meta($post->ID, $meta_key, true); ?>" placeholder="https://..." />
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <label class="form-label" for="">Event session times (Enter dates and times as London time)</label>
                                        </div>
                                    </div>
                                    <?php
                                    if($i == 1){
                                        $sessions = 6;
                                    }else{
                                        $sessions = 1;
                                    }
                                    for ($j=1; $j <= $sessions; $j++) {
                                        if($j == 1){
                                            $prefix = 'event_'.$i;
                                        }else{
                                            $prefix = 'event_'.$i.'_sess_'.$j;
                                        }
                                        ?>
                                        <div class="row">
                                            <div class="col-6 col-lg-3">
                                                <label for="" class="form-label">Session <?php echo $j; ?> Start date/time</label><br>
                                                <?php if($i == 1 && $j == 1){
                                                    $event_date = get_post_meta($post->ID, 'meta_a', true);
                                                    ?>
                                                    <div class="dummy-input"><?php echo $event_date; ?></div>
                                                    <?php
                                                }else{
                                                    $event_date = get_post_meta($post->ID, $prefix.'_date', true) ?>
                                                    <input type="text" id="<?php echo $prefix; ?>_date" name="<?php echo $prefix; ?>_date" class="narrow-date" value="<?php echo $event_date; ?>" placeholder="dd/mm/yyyy" />
                                                <?php }
                                                $event_time = @get_post_meta($post->ID, $prefix.'_time', true); // UTC
                                                $london = cc_timezones_convert($event_date, $event_time, 'UTC', 'Europe/London');
                                                ?>
                                                <input type="text" id="<?php echo $prefix; ?>_time" name="<?php echo $prefix; ?>_time" class="narrow-time" value="<?php echo $london['time']; ?>" placeholder="hh:mm" />
                                            </div>
                                            <div class="col-6 col-lg-3">
                                                <?php if( $london['time'] <> '' ){ ?>
                                                    <table class="timezone-table">
                                                        <?php
                                                        foreach ($timezones as $timezone) { ?>
                                                            <tr>
                                                                <th><?php echo $timezone; ?>:</th>
                                                                <td>
                                                                    <?php
                                                                    $new_time = cc_timezones_convert($event_date, $event_time, 'UTC', $timezone, 'D j/m H:i', true);
                                                                    echo $new_time['datetime'];
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                    </table>
                                                <?php } ?>
                                            </div>
                                            <div class="col-6 col-lg-3">
                                                <label for="" class="form-label">Session <?php echo $j; ?> End date/time</label><br>
                                                <?php
                                                $event_date_end = get_post_meta($post->ID, $prefix.'_date_end', true);
                                                ?>
                                                <input type="text" id="<?php echo $prefix; ?>_date_end" name="<?php echo $prefix; ?>_date_end" class="narrow-date" value="<?php echo $event_date_end; ?>" placeholder="dd/mm/yyyy" />
                                                <?php
                                                $event_time_end = @get_post_meta($post->ID, $prefix.'_time_end', true); // UTC
                                                if($event_date_end == ''){
                                                    $event_date_end = $event_date;
                                                }
                                                $london = cc_timezones_convert($event_date_end, $event_time_end, 'UTC', 'Europe/London');
                                                ?>
                                                <input type="text" id="<?php echo $prefix; ?>_time_end" name="<?php echo $prefix; ?>_time_end" class="narrow-time" value="<?php echo $london['time']; ?>" placeholder="hh:mm" />
                                            </div>
                                            <div class="col-6 col-lg-3">
                                                <?php if( $london['time'] <> '' ){ ?>
                                                    <table class="timezone-table">
                                                        <?php
                                                        foreach ($timezones as $timezone) { ?>
                                                            <tr>
                                                                <th><?php echo $timezone; ?>:</th>
                                                                <td>
                                                                    <?php
                                                                    $new_time = cc_timezones_convert($event_date_end, $event_time_end, 'UTC', $timezone, 'D j/m H:i', true);
                                                                    echo $new_time['datetime'];
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                    </table>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <div class="row">
                                        <div class="col-12">
                                            <label for="" class="form-label">Price override for this event (excl VAT)</label>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-2">
                                            <label class="form-label" for="event_<?php echo $i; ?>_free">Event Free?</label>
                                            <select class="form-control" name="event_<?php echo $i; ?>_free" id="event_<?php echo $i; ?>_free">
                                                <option value="">No</option>
                                                <option value="yes" <?php selected( 'yes', get_post_meta($post->ID, 'event_'.$i.'_free', true) ); ?>>Yes</option>
                                            </select>
                                        </div>
                                        <?php
                                        $event_prices = workshops_events_prices($post->ID, $i);
                                        foreach (workshops_currencies() as $currency) {
                                            ?>
                                            <div class="col-2">
                                                <label for="" class="form-label"><?php echo $currency['code'].' '.$currency['symbol']; ?></label>
                                                <input class="form-control" type="text" name="event_<?php echo $i; ?>_price_<?php echo $currency['code']; ?>" value="<?php echo ($event_prices[$currency['code']] == 0) ? '' : $event_prices[$currency['code']]; ?>">
                                            </div>
                                        <?php } ?>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <h3>Resources</h3>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4">
                                            <label for="" class="form-label">Recording</label>
                                            <select name="event_<?php echo $i; ?>_recording" id="event_<?php echo $i; ?>_recording" class="form-control">
                                                <option value="">Not set</option>
                                                <?php echo cc_recordings_options(get_post_meta($post->ID, 'event_'.$i.'_recording', true)); ?>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <label for="" class="form-label">Slides</label>
                                            <input type="text" name="event_<?php echo $i; ?>_slides" id="event_<?php echo $i; ?>_slides" class="form-control" value="<?php echo @get_post_meta($post->ID, 'event_'.$i.'_slides', true); ?>">
                                        </div>
                                        <div class="col-4">
                                            <label for="" class="form-label">Resources</label>
                                            <input type="text" name="event_<?php echo $i; ?>_resources" id="event_<?php echo $i; ?>_resources" class="form-control" value="<?php echo @get_post_meta($post->ID, 'event_'.$i.'_resources', true); ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <?php /*
                                        <div class="col-4">
                                            <label for="" class="form-label">Certificate</label>
                                            <input type="text" name="event_<?php echo $i; ?>_certificate" id="event_<?php echo $i; ?>_certificate" class="form-control" value="<?php echo @get_post_meta($post->ID, 'event_'.$i.'_certificate', true); ?>">
                                        </div>
                                        */ ?>
                                        <input type="hidden" name="event_<?php echo $i; ?>_certificate" id="event_<?php echo $i; ?>_certificate" class="form-control" value="<?php echo @get_post_meta($post->ID, 'event_'.$i.'_certificate', true); ?>">
                                        <div class="col-4">
                                            <label for="" class="form-label">Feedback</label>
                                            <input type="text" name="event_<?php echo $i; ?>_feedback" id="event_<?php echo $i; ?>_feedback" class="form-control" value="<?php echo @get_post_meta($post->ID, 'event_'.$i.'_feedback', true); ?>">
                                        </div>
                                        <div class="col-4">
                                            <label for="" class="form-label">LinkedIn</label>
                                            <input type="text" name="event_<?php echo $i; ?>_linkedin" id="event_<?php echo $i; ?>_linkedin" class="form-control" value="<?php echo @get_post_meta($post->ID, 'event_'.$i.'_linkedin', true); ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-2">
                                            <label for="ce_credits" class="form-label">CE Credits</label>
                                            <input type="number" id="event_<?php echo $i; ?>_ce_credits" name="event_<?php echo $i; ?>_ce_credits" class="form-control" value="<?php echo get_post_meta( $post->ID, 'event_'.$i.'_ce_credits', true ); ?>">
                                        </div>
                                        <div class="col-2">
                                            <label for="cert_cc" class="form-label">CC Cert?</label>
                                            <select name="event_<?php echo $i; ?>_cert_cc" id="event_<?php echo $i; ?>_cert_cc" class="form-control">
                                                <option value="yes">Yes</option>
                                                <option value="no" <?php selected( 'no', get_post_meta( $post->ID, 'event_'.$i.'_cert_cc', true ) ); ?>>No</option>
                                            </select>
                                        </div>
                                        <div class="col-2">
                                            <label for="cert_apa" class="form-label">APA Cert?</label>
                                            <select name="event_<?php echo $i; ?>_cert_apa" id="event_<?php echo $i; ?>_cert_apa" class="form-control">
                                                <option value="yes">Yes</option>
                                                <option value="no" <?php selected( 'no', get_post_meta( $post->ID, 'event_'.$i.'_cert_apa', true ) ); ?>>No</option>
                                            </select>
                                        </div>
                                        <div class="col-2">
                                            <label for="cert_bacb" class="form-label">BACB Cert?</label>
                                            <select name="event_<?php echo $i; ?>_cert_bacb" id="event_<?php echo $i; ?>_cert_bacb" class="form-control">
                                                <option value="yes">Yes</option>
                                                <option value="no" <?php selected( 'no', get_post_meta( $post->ID, 'event_'.$i.'_cert_bacb', true ) ); ?>>No</option>
                                            </select>
                                        </div>
                                        <div class="col-2">
                                            <label for="cert_nbcc" class="form-label">NBCC Cert?</label>
                                            <select name="event_<?php echo $i; ?>_cert_nbcc" id="event_<?php echo $i; ?>_cert_nbcc" class="form-control">
                                                <option value="yes">Yes</option>
                                                <option value="no" <?php selected( 'no', get_post_meta( $post->ID, 'event_'.$i.'_cert_nbcc', true ) ); ?>>No</option>
                                            </select>
                                        </div>
                                        <div class="col-2">
                                            <label for="cert_icf" class="form-label">ICF Cert?</label>
                                            <select name="event_<?php echo $i; ?>_cert_icf" id="event_<?php echo $i; ?>_cert_icf" class="form-control">
                                                <option value="yes">Yes</option>
                                                <option value="no" <?php selected( 'no', get_post_meta( $post->ID, 'event_'.$i.'_cert_icf', true ) ); ?>>No</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-4">
                                            <label for="" class="form-label">Zoom</label>
                                            <input type="text" name="event_<?php echo $i; ?>_zoom" id="event_<?php echo $i; ?>_zoom" class="form-control" value="<?php echo @get_post_meta($post->ID, 'event_'.$i.'_zoom', true); ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <label for="" class="form-label">Joining info</label>
                                            <textarea name="event_<?php echo $i; ?>_joining" id="event_<?php echo $i; ?>_joining" cols="100" rows="4" class="form-control"><?php echo @get_post_meta($post->ID, 'event_'.$i.'_joining', true); ?></textarea>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <?php
                        $accordion_button_class = 'collapsed';
                        $accordion_collapse_class = '';
                    } ?>
                </div>


                <div class="row">
                    <div class="col-2"></div>
                </div>

            </div>

            <?php
        } // END public function add_inner_meta_boxes($post)

	} // END class Post_Type_Template
} // END if(!class_exists('Post_Type_Template'))

