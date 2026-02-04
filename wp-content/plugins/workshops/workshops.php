<?php
/*
Plugin Name: Workshops
Plugin URI: 
Description: A simple wordpress plugin for workshop custom post types
Version: 2.34.1
Author: SaaSora
Author URI: https://saasora.com
*/

/**
 * Update the version number at the end of the comments .........!!!
 */

/**
 * v2.34 May 2024
 * - Accommodate training series
 * v2.33 Apr 2024
 * - hides non-published workshops (previously they could appear through an upsell)
 * v2.32 Apr 2024
 * - added block for NLFT people
 * v2.31 Mar 2024
 * _ fixed bug with display of featured training on my account page
 * v2.30 Feb 2024
 * - added the xero tracking code
 * - adjusted for the my account refresh
 * v2.29 Jan 2024
 * - added london and locale start date to the date calculator
 * v2.28 Jan 2024
 * - allows for a historical workshop price to be calculated (based on earlybird expiry)
 * v2.27 Jan 2024
 * - incorporate revised training accordions
 * v2.26 Nov 2024
 * - Upsells now include recordings too
 * v2.25 Oct 2024
 * - added CE credits to the training summary
 * - compressed the training summary
 * v2.24 Sep 2024
 * - added ICF certificate
 * v2.23 Sep 2024
 * - added in person to card
 * - added training levels
 * v2.22 Aug 2024
 * - hyperlinked venue
 * - corrected npcc to be nbcc
 * v2.21 Jul 2024
 * - added extra certificates
 * v2.20 Jul 2024
 * - change venue icon 
 * - restore venue link
 * v2.19 Jul 2024
 * - slug now live-training
 * v2.18 Jun 2024
 * - workshop price lookup will now look at historical prices
 * v2.16 Apr 2024
 * - show presenter names on training cards
 * v2.15 Feb 2024
 * - removed checkboxes from the workshop page as they do not save when doing a quick edit
 * v2.14 Feb 2024
 * - moved the voucher offer on the training pages
 * v2.13 Feb 2024
 * - changed default times to AM/PM
 * v2.12 Jan 2024
 * - Added resource hub and associated taxonomies
 * v2.11 Sep 2023
 * - change of timezone now also accommodates date changing
 * v2.10 Aug 2023
 * - bug in timezone update process fixed
 * v2.9 Aug 2023
 * - bug in pretty date formatting fixed
 * v2.8 Jun 2023
 * - extended the What to show section across training (already on pages and posts) and added options to set height and wash opacity
 * v2.7 Jun 2023
 * - bug correct selection of workshops for the archive page
 * - bug fix - added stripslashes when saving training accordions
 * v2.6 Jun 2023
 * - Added CE Credits
 * v2.5 Apr 2023
 * - added default training accordions
 * v2.4 Mar 2023
 * - added training accordions
 * v2.3 Feb 2023
 * - multi-session event
 * - removed all '+ 0' sanitisation
 * v2.2 Feb 2023
 * - added earlybird_name
 * v2.1 Jan 2023
 * - bug - upsells required function to get workshop by title, copied in from ccpa
 * v2.0 Jul 2022
 * - Changes made to accommodate the new theme
 * - added 'presenter'
 * v1.3.20 Apr 2022
 * - fixed last time for sale to correctly store in UTC
 * v1.3.19 Jan 2022
 * - resources files folder
 * v1.3.18 Nov 2021
 * - allow for currency in the middle of being changed when determining the price
 * v1.3.17 Sep 2021
 * - adjustments to keep the new workshops users table up to date
 * v1.3.16 Jul 2021
 * - adjusted currency switcher to accommodate using it on the payint form
 * v1.3.15 Jul 2021
 * - fixed all events discount which was incorrectly discounting when all remaining events selected (when some had passed)
 * v1.3.14 Jul 2021
 * - removed deprecated money_format calls
 * - added VAT for all currencies
 * v1.3.13 Mar 2021
 * - stop upsells being offered if the workshop has passed
 * v1.3.12 Feb 2021
 * - fix workshop-list shortcode display - was not comapring to "today" correctly
 * v1.3.11 Jan 2021
 * - change workshop-list shortcode to be more accurate in deciding which workshops to show
 * v1.3.10 Dec 2020
 * - change backend time from UTC/GMT to London time
 * v1.3.9 Dec 2020
 * - fix workshop time to be based on UTC
 * v1.3.8 Nov 2020
 * adjusted text of upsell offer
 * v1.3.7 Oct 2020
 * - adjust the workshop shortcode to only display future workshops, especially if today's the day
 * v1.3.6 Aug 2020
 * - workshop page display fix for multi-event workshop with only one event left to show
 * v1.3.5 Aug 2020
 * - multi currency upsells
 * v1.3.4 Jul 2020
 * - accommodate date ranges so that workshops are shown in a better order on the workshop list page
 * v1.3.3 Jul 2020
 * - show subtitle on home page
 * v1.3.2 Jun 2020
 * - dates can now be hidden on workshop page
 * - workshops with one event now can be multi-currency and display price on workshop page
 * - events can now have different prices
 * v1.3.1 May 2020
 * - moved enqueues to the correct hook
 * v1.3.0 Apr 2020
 * - expanded to 10 events
 * v1.2.2 Feb 2020
 * - Upsells
 * v1.2.1 Jun 2019
 * - adjust confirmation email
 * v1.2 Jan 2019
 * - now allows for online payment amount - linked to the payments plugin
 * v1.1 Sep 2015
 * - home page shortcode now counts the number of workshops and only uses the carousel if more than 3
 * - past workshop list order set to descending order
 */

define('CC_WORKSHOPS_VER', '2.30.3');

if(!class_exists('workshops_plugin')) {
	
	class workshops_plugin {

		public function __construct() {
			// Register custom post types
			require_once(sprintf("%s/post-types/workshops-post.php", dirname(__FILE__)));
			$Post_Type_Template = new Post_Type_Template();
			require_once(sprintf("%s/inc/upsells.php", dirname(__FILE__)));
			require_once(sprintf("%s/inc/next-workshop.php", dirname(__FILE__)));
			require_once(sprintf("%s/inc/workshop-cards.php", dirname(__FILE__)));
			require_once(sprintf("%s/inc/workshop-pricing.php", dirname(__FILE__)));
			require_once(sprintf("%s/inc/workshop-archive.php", dirname(__FILE__)));
			$plugin = plugin_basename(__FILE__);
			add_filter("plugin_action_links_$plugin", array( $this, 'plugin_settings_link' ));

			// Shortcode for homepage
			add_shortcode( 'workshop', array( $this , 'workshop_post_shortcode' ) );
			add_shortcode( 'workshop-list', array( $this , 'workshop_archive_shortcode' ) );

			// Get custom post template
			add_filter( "single_template", array( $this, "get_custom_post_type_template" ) );
			add_filter( 'page_template', array( $this, 'wpa3396_page_template' ) );
			add_filter( 'single-page_template', array( $this, 'wpa3396_page_template' ) );

			// payment form stuff
			add_action( 'before_payment_form', array( $this, 'before_payment_form' ) );
			add_action( 'payment_form_amount', array( $this, 'payment_form_amount' ) );
			add_action( 'ccpa_payment_ref', array( $this, 'payment_ref' ) );
			add_action( 'ccpa_confirmation_email_body', array( $this, 'confirmation_email_body' ) );
			add_action( 'ccpa_confirmation_email_shortcodes', array( $this, 'confirmation_email_shortcodes' ) );

			// Add new image size for homepage
			add_image_size( 'workshop', 300, 200);

			// enqueues
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueues' ) );

			// one-off stuff eg for upgrades
			add_action('init', array($this, 'one_off_stuff'));

		} // END public function __construct

		public static function activate() {
			// Do nothing
		} // END public static function activate

		public static function deactivate() {
			// Do nothing
		} // END public static function deactivate

		function one_off_stuff(){
			if(get_option('ccwkshps_start_timestamp_set', '') == ''){
				$args = array(
	            	'post_type' => 'workshop',
	            	'posts_per_page' => 99,
				);
				$workshops = get_posts($args);
				foreach ($workshops as $workshop) {
					if(get_post_meta($workshop->ID, 'workshop_start_timestamp', true) == ''){
						$start_date = get_post_meta($workshop->ID, 'meta_a', true);
						$date = DateTime::createFromFormat("d/m/Y", $start_date);
						if($date){
							update_post_meta($workshop->ID, 'workshop_start_timestamp', $date->getTimestamp());
						}
					}
				}
				update_option('ccwkshps_start_timestamp_set', 'done');
			}
		}

		public function enqueues(){
			// Get custom CSS
			wp_register_style( 'workshop-style', plugins_url( 'workshops/css/style.css' ), array(), 'v1.3.3.3');
			wp_enqueue_style( 'workshop-style' );
			// Get JQuery Cycle scripts for homepage slider
			wp_enqueue_script( 'cycle2', plugins_url( '/js/jquery.cycle2.js', __FILE__ ), array('jquery'));
			wp_enqueue_script( 'carousel', plugins_url( '/js/jquery.cycle2.carousel.js', __FILE__ ), array('jquery', 'cycle2'));
			wp_enqueue_script( 'center', plugins_url( '/js/jquery.cycle2.center.js', __FILE__ ), array('jquery', 'cycle2'));
			wp_enqueue_script( 'workshops', plugins_url( '/js/workshop2.js', __FILE__ ), array('jquery', 'carousel'), 'v1.3.3.3');
		}

		// Add the settings link to the plugins page
		function plugin_settings_link($links) {
			$settings_link = '<a href="edit.php?post_type=workshops_template">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
		}

		// Shortcode for homepage [workshop]
        function workshop_post_shortcode($atts) {
        	date_default_timezone_set('UTC');
            $args = array(
            	'post_type' => 'workshop',
            	'posts_per_page' => 99,
            	'orderby'   => 'meta_value_num',
            	'meta_key'  => 'workshop_start_timestamp',
            	'order' => 'ASC'
            );
            $loop = new WP_Query($args);
            $now = time();
            $today = date('Y-m-d');
            $num_workshops = 0;
            $inner_html = '';
            while ( $loop->have_posts() ) : $loop->the_post();
            	$workshop_id = get_the_ID();
                $show_wkshp = workshops_show_this_workshop($workshop_id);
                /* moved to the function
            	$workshop_timestamp = get_post_meta($workshop_id, 'workshop_timestamp', true ); // end date as a timestamp (or start date if there is no end date)
            	if($workshop_timestamp <> ''){
            		$wkshp_end_date = date('Y-m-d', $workshop_timestamp);
            		if($wkshp_end_date < $today){
            			$show_wkshp = false;
            		}elseif($wkshp_end_date > $today){
            			$show_wkshp = true;
            		}else{
            			// it's today
            			// what is the last event for the workshop?
            			$last_event_found = false;
            			for ($i=15; $i > 0; $i--) { 
            				$event_name = get_post_meta( $workshop_id, 'event_'.$i.'_name', true );
            				if($event_name <> ''){
            					// this is the last event
            					$last_event_found = true;
            					$event_time = get_post_meta( $workshop_id, 'event_'.$i.'_time', true );
            					break;
            				}
            			}
            			if(!$last_event_found){
            				// must be a workshop without events
            				// it's possible that there is a time againt the first event ...
        					$event_time = get_post_meta( $workshop_id, 'event_1_time', true );
        				}
    					if($event_time == ''){
    						$event_time = '08:00';
    					}
    					$hide_time = strtotime($wkshp_end_date.' '.$event_time);
    					if($hide_time < $now){
    						$show_wkshp = false;
    					}else{
    						$show_wkshp = true;
    					}
            		}
            	}
                */
            	// if($workshop_timestamp > $now){
            	if($show_wkshp){
	                $num_workshops ++;
	                $permalink = get_permalink();
	                $image = get_the_post_thumbnail( $workshop_id, 'workshop' );
	                $title = get_the_title();
	                $subtitle = get_post_meta(get_the_ID(), 'subtitle', true);
	                $excerpt = get_the_excerpt(); 
	                $inner_html .= '<div class="workshop-box workshop-'.$num_workshops.'">';
	                $inner_html .= '<a href="'.$permalink.'" title="'.esc_attr($title).'">'.$image.'</a>';
					$inner_html .= '<div class="workshop-text">';
					$inner_html .= '<h3>'.$title.'</h3>';
					if($subtitle <> ''){
						$inner_html .= '<h4>'.$subtitle.'</h4>';
					}
					if($excerpt <> ''){
						$inner_html .= '<p>'.$excerpt.'</p>';
					}
					$inner_html .= '<a href="'.$permalink.'" class="wms-button">Find Out More</a>';
					$inner_html .= '</div>';
					$inner_html .= '</div>';
				}
            endwhile;

            switch ($num_workshops) {
            	case 0:
            		$Html = '';
            		break;
        		case 1:
        			$HTML = '<div class="workshop-wrapper one-workshop">';
        			$HTML .= $inner_html;
        			$HTML .= '</div><!-- .workshop-wrapper -->';
        			break;
        		case 2:
        			$HTML = '<div class="workshop-wrapper two-workshops">';
        			$HTML .= $inner_html;
        			$HTML .= '</div><!-- .workshop-wrapper -->';
        			break;
        		case 3:
        			$HTML = '<div class="workshop-wrapper three-workshops">';
        			$HTML .= $inner_html;
        			$HTML .= '</div><!-- .workshop-wrapper -->';
        			break;
            	default:
		            $HTML = '<div class="workshop-wrapper many-workshops" data-cycle-fx=carousel  data-cycle-pause-on-hover="true" data-cycle-carousel-visible=3 data-cycle-timeout=8000 data-cycle-next="#nextid" data-cycle-prev="#previd" data-cycle-slides="> div" data-cycle-carousel-fluid=true>';
        			$HTML .= $inner_html;
		            $HTML .= '</div><!-- .workshop-wrapper -->';
		            $HTML .= '<div class="carouselcontrols"><a href="#" id="previd"><i class="fa fa-angle-left "></i></a><a href="#" id="nextid"><i class="fa fa-angle-right "></i></a></div>';
            		break;
            }

            return $HTML;
        }

        // shortcode for archive type page to list all workshops
        // [workshop-list timespan="past"]
        // timespan can be 'past', 'future' or 'all' .... default = 'future'
        function workshop_archive_shortcode($atts){
            $atts = shortcode_atts(
            	array(
            		'timespan' => 'future'
        		), 
        		$atts, 
        		'workshop-list'
        	);
        	if($atts['timespan'] == 'past'){
        		$wkshp_order = 'DESC';
        	}else{
        		$wkshp_order = 'ASC';
        	}
            $args = array(
            	'post_type' => 'workshop',
            	'posts_per_page' => 99,
            	'orderby'   => 'meta_value_num',
            	'meta_key'  => 'workshop_start_timestamp',
            	'order' => $wkshp_order
            );
            $loop = new WP_Query($args);
            $HTML = '<div class="workshop-archive '.$atts['timespan'].'-workshops">';
            $now = time();
            $today = date('Y-m-d');
            while ( $loop->have_posts() ) : 
            	$loop->the_post();
                $workshop_id = get_the_ID();
                $show_wkshp = false;
                if($atts['timespan'] == 'all'){
                	$show_wkshp = true;
                }else{
                	$future = workshops_show_this_workshop($workshop_id);
                	if($atts['timespan'] == 'future' && $future){
                		$show_wkshp = true;
                	}elseif($atts['timespan'] == 'past' && !$future){
            			$show_wkshp = true;
                	}
                }
                /*
                $workshop_timestamp = get_post_meta($workshop_id, 'workshop_timestamp', true ); // end date as a timestamp (or start date if there is no end date)
                // next bit added v1.3.11 Jan 2021
                // change workshop-list shortcode to be more accurate in deciding which workshops to show
                if($atts['timespan'] == 'all'){
                	$show_wkshp = true;
                }else{
                	if($workshop_timestamp <> ''){
	            		$wkshp_end_date = date('Y-m-d', $workshop_timestamp);
	            		if($wkshp_end_date < $today && $atts['timespan'] == 'past'){
	            			$show_wkshp = true;
	            		}elseif($wkshp_end_date > $today && $atts['timespan'] == 'future'){
	            			$show_wkshp = true;
	            		}else{
	            			// it's today
	            			// what is the last event for the workshop?
	            			$last_event_found = false;
	            			for ($i=15; $i > 0; $i--) { 
	            				$event_name = get_post_meta( $workshop_id, 'event_'.$i.'_name', true );
	            				if($event_name <> ''){
	            					// this is the last event
	            					$last_event_found = true;
	            					$event_time = get_post_meta( $workshop_id, 'event_'.$i.'_time', true );
	            					break;
	            				}
	            			}
	            			if(!$last_event_found){
	            				// must be a workshop without events
	            				// it's possible that there is a time againt the first event ...
	        					$event_time = get_post_meta( $workshop_id, 'event_1_time', true );
	        				}
	    					if($event_time == ''){
	    						$event_time = '08:00';
	    					}
	    					$hide_time = strtotime($wkshp_end_date.' '.$event_time);
	    					if($hide_time < $now && $atts['timespan'] == 'past'){
	    						$show_wkshp = true;
	    					}elseif($hide_time >= $now && $atts['timespan'] == 'future'){
	    						$show_wkshp = true;
	    					}
	            		}
                	}
                }
                // if($atts['timespan'] == 'all' || ($atts['timespan'] == 'past' && $workshop_timestamp < $now) || ($atts['timespan'] == 'future' && $workshop_timestamp > $now)){
            	*/
                if($show_wkshp){
	                $permalink = get_permalink();
	                $image = get_the_post_thumbnail( $workshop_id, 'workshop', array( 'class' => 'workshop-featimg' ) );
	                $title = get_the_title(); 
	                $summary = get_post_meta($workshop_id, 'summary', true);
	                $prettydates = workshop_calculated_prettydates($workshop_id);
	                // $start_date = get_post_meta($workshop_id, 'prettydates', true);
	                $HTML .= '<div class="workshop-item">';
	                $HTML .= '<a href="'.$permalink.'" title="'.esc_attr($title).'">'.$image.'</a>';
					$HTML .= '<div class="workshop-archive-text">';
					$HTML .= '<h3>'.$title.'</h3>';
					$HTML .= '<h6>'.$prettydates['london_date'].'</h6>';
					$HTML .= '<p>'.$summary.'</p>';
					$HTML .= '<a href="'.$permalink.'" class="wms-button alignright">Find Out More</a>';
					$HTML .= '<div class="clear"></div>';
					$HTML .= '</div>';
					$HTML .= '<div class="clear"></div>';
	                $HTML .= '</div><!-- .workshop-item -->';
	            }
            endwhile;
            $HTML .= '</div><!-- .workshop-archive -->';
            return $HTML;
        }

        // Get template for Workshop
        function get_custom_post_type_template($single_template) {
		    global $post;
		    if($post->post_title == 'Register'){
		        $single_template = dirname( __FILE__ ) . '/templates/register.php';
		    }
		    if ($post->post_type == 'workshop') {
		          $single_template = dirname( __FILE__ ) . '/templates/single-workshop.php';
		    }
		    return $single_template;
		}

		// Get register template
		function wpa3396_page_template( $page_template ) {
			// echo 'page template filter ';
		    if ( is_page( array('Register', 'register') ) ) {
		        $page_template = dirname( __FILE__ ) . '/templates/register.php';
		    }
		    return $page_template;
		}

		// tell people what they are paying for
		function before_payment_form(){
			if(isset($_POST['workshopID'])){
				$workshopID = $_POST['workshopID'];
				return '
				<div class="row">
					<div class="columns medium-6 medium-offset-3">
						<h2>You\'re registering for '.get_the_title($workshopID).'</h2>'.$this->registering_events().'
					</div>
				</div>
				';
			}
		}

		function registering_events(){
			$html = '';
			if(isset($_POST['eventID']) && strpos($_POST['eventID'], ',')){
				$event_ids = explode(',', $_POST['eventID']);
				$workshopID = $_POST['workshopID'];
				$html .= '<p>Events:<br>';
				sort($event_ids);
				foreach ($event_ids as $event_id) {
					$event_name = get_post_meta($workshopID, 'event_'.$event_id.'_name', true);
					if($event_name <> ''){
						$html .= '<strong>'.$event_name.'</strong>';
						if($event_id == 1){
							$event_date = get_post_meta($workshopID, 'meta_a', true);
						}else{
							$event_date = get_post_meta($workshopID, 'event_'.$event_id.'_date', true);
						}						
						if($event_date <> ''){
							$html .= ': '.$event_date;
						}
						$html .= '<br>';
					}
				}
				$html .= '</p>';
			}
			return $html;
		}

		// the payment form amount
		function payment_form_amount(){
			global $currency;
			// ccpa_write_log('payment_form_amount');
			if(isset($_POST['workshopID'])){
				$workshop_id = absint($_POST['workshopID']);
				if($workshop_id > 0){
					$tot_payable = 0;
					if(isset($_POST['eventID']) && $_POST['eventID'] <> ''){
						$event_ids = explode(',', $_POST['eventID']);
						foreach ($event_ids as $event_id) {
							if($event_id <> ''){
								if(get_post_meta($_POST['workshopID'], 'event_'.$event_id.'_free', true) <> 'yes'){
									if(isset($_POST['currency-switch']) && ($_POST['currency-switch'] == 'AUD' || $_POST['currency-switch'] == 'USD' || $_POST['currency-switch'] == 'GBP')){
										$calc_currency = $_POST['currency-switch'];
									}elseif(isset($_POST['currency']) && ($_POST['currency'] == 'AUD' || $_POST['currency'] == 'USD' || $_POST['currency'] == 'GBP')){
										$calc_currency = $_POST['currency'];
									}elseif($currency == 'AUD' || $currency == 'USD'){
										$calc_currency = $currency;
									}else{
										$calc_currency = 'GBP';
									}
									// ccpa_write_log('payment_form_amount currency:'.$calc_currency);
									$tot_payable = $tot_payable + workshops_event_price($workshop_id, $event_id, $calc_currency);
								}
							}
						}
					}
				}
				/*
				$paid_events = 1;
				if(isset($_POST['eventID']) && $_POST['eventID'] <> ''){
					$paid_events = 0;
					
					foreach ($event_ids as $event_id) {
						if($event_id <> ''){
							if(get_post_meta($_POST['workshopID'], 'event_'.$event_id.'_free', true) <> 'yes'){
								$paid_events ++;
								$tot_payable = $tot_payable + workshops_event_price($workshop_id, $event_id, $this_wkshp_currency);
							}
						}
					}
				}
				$currency = 'GBP';
				if(isset($_POST['currency']) && ($_POST['currency'] == 'AUD' || $_POST['currency'] == 'USD')){
					$currency = $_POST['currency'];
				}
				switch ($currency) {
					case 'GBP':
						return get_post_meta($_POST['workshopID'], 'online_pmt_amt', true) * $paid_events;
						break;
					case 'AUD':
						return get_post_meta($_POST['workshopID'], 'online_pmt_aud', true) * $paid_events;
						break;
					case 'USD':
						return get_post_meta($_POST['workshopID'], 'online_pmt_usd', true) * $paid_events;
						break;
				}
				*/
				return $tot_payable;
			}else{
				return 0;
			}
		}

		// applies a payment reference
		function payment_ref($pay_ref){
			if(isset($_POST['workshopID'])){
				$workshopID = absint($_POST['workshopID']);
				/*
				$workshop = get_post($workshopID);
				$pay_ref = $workshop->post_title;
				*/
				// get_the_title normally uses wptexturize which converts things to HTML entities. We don't want that to happen so we remove the filter just for a mo
				$wptexturize = remove_filter( 'the_title', 'wptexturize' );
				$pay_ref = get_the_title($workshopID);
				if ( $wptexturize ) add_filter( 'the_title', 'wptexturize' );
				if(isset($_POST['eventID'])){
					$eventID = absint($_POST['eventID']);
					$pay_ref .= ' '.get_post_meta($workshopID, 'event_'.$eventID.'_name', true );
				}
			}
			return $pay_ref;
		}

		// which shortcodes are allowed in the confirmation email
		function confirmation_email_shortcodes($shortcodes){
			$shortcodes[] = 'workshop';
			$shortcodes[] = 'event';
			$shortcodes[] = 'dates';
			return $shortcodes;
		}

		// adjust the confirmation email body for online registrations
		function confirmation_email_body($email_body){
			if(isset($_POST['workshopID'])){
				$workshopID = absint($_POST['workshopID']);
				$workshop_name = get_the_title($workshopID);
			}else{
				$workshop_name = '';
			}
			$email_body = str_replace('[workshop]', $workshop_name, $email_body);

			if($workshop_name <> '' && isset($_POST['eventID'])){
				$event_name = get_post_meta($workshopID, 'event_'.absint($_POST['eventID']).'_name', true );
			}else{
				$event_name = '';
			}
			$email_body = str_replace('[event]', $event_name, $email_body);

			if($workshop_name <> ''){
				$prettydates = get_post_meta( $workshopID, 'prettydates', true );
			}else{
				$prettydates = '';
			}
			$email_body = str_replace('[dates]', $prettydates, $email_body);

			return $email_body;
		}

	} // END class workshops_plugin
} // END if(!class_exists('workshops_plugin'))

if(class_exists('workshops_plugin')) {
	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('workshops_plugin', 'activate'));
	register_deactivation_hook(__FILE__, array('workshops_plugin', 'deactivate'));

	// instantiate the plugin class
	$workshops_plugin = new Workshops_Plugin();
}

// detects whether to show the currency switcher and if so, returns the currency switcher code
// it only gets shown on workshop pages that include an AUD or USD price
/*
function workshops_currency_switcher_code(){
	global $post;
	global $currency;
	$html = '';
	$show_switcher = false;
	if(isset($post->post_type) && $post->post_type == 'workshop'){
		$online_pmt_aud = get_post_meta($post->ID, 'online_pmt_aud', true);
		$online_pmt_usd = get_post_meta($post->ID, 'online_pmt_usd', true);
		if($online_pmt_aud <> '' || $online_pmt_usd <> ''){
			$show_switcher = true;
		}
	}elseif(isset($post->post_type) && $post->post_type == 'recording'){
		$online_pmt_aud = get_post_meta($post->ID, 'recording_price_aud', true);
		$online_pmt_usd = get_post_meta($post->ID, 'recording_price_usd', true);
		if($online_pmt_aud <> '' || $online_pmt_usd <> ''){
			$show_switcher = true;
		}
	}
	if($show_switcher){
		$html .= '<span class="hide-small">Show prices in </span>';
		$html .= '<form id="currency-switch-form" method="post"><select id="currency-switch" class="currency-switch" name="currency-switch">';
		$html .= '<option value="GBP">GBP &pound;</option>';
		$html .= '<option value="AUD" '.selected('AUD', $currency, false).'>AUD $</option>';
		$html .= '<option value="USD" '.selected('USD', $currency, false).'>USD $</option>';
		$html .= '</select></form>';
	}
	return $html;
}
*/

// all possible currencies
function workshops_currencies(){
	return array(
		array(
			'code' => 'GBP',
			'symbol' => '&pound;',
		),
		array(
			'code' => 'AUD',
			'symbol' => '$',
		),
		array(
			'code' => 'USD',
			'symbol' => '$',
		),
		array(
			'code' => 'EUR',
			'symbol' => '€',
		),
	);
}

// retrieve event prices or return zeros for all currencies if they are not found
function workshops_events_prices($workshop_id, $event_id){
	$event_prices = get_post_meta($workshop_id, 'event_'.$event_id.'_prices', true);
	if(!is_array($event_prices)){
		$event_prices = array();
		foreach (workshops_currencies() as $currency) {
			$event_prices[$currency['code']] = 0;
		}
	}else{
		foreach (workshops_currencies() as $currency){
			if(!isset($event_prices[$currency['code']])){
				$event_prices[$currency['code']] = 0;
			}
		}
	}
	return $event_prices;
}

// returns the price for an event
// ignores the fact that the event may be free
function workshops_event_price($workshop_id, $event_id, $this_wkshp_currency){
	$event_prices = workshops_events_prices($workshop_id, $event_id);
	// ccpa_write_log('workshops_event_price event_Prices:');
	// ccpa_write_log($event_prices);
	if(isset($event_prices[$this_wkshp_currency]) && $event_prices[$this_wkshp_currency] > 0){
		return $event_prices[$this_wkshp_currency];
	}
	switch ($this_wkshp_currency) {
		case 'AUD':		$meta_key = 'online_pmt_aud';		break;
		case 'USD':		$meta_key = 'online_pmt_usd';		break;
		case 'EUR':		$meta_key = 'online_pmt_eur';		break;
		default:		$meta_key = 'online_pmt_amt';		break;
	}
	return (float) get_post_meta($workshop_id, $meta_key, true);
}

// returns price prefix
function workshops_price_prefix($this_wkshp_currency){
	switch ($this_wkshp_currency) {
		case 'AUD':		return 'A$';		break;
		case 'USD':		return 'US$';		break;
		case 'EUR':		return '€';			break;
		default:		return '&pound;';	break;
	}
}

// returns formatted price
function workshops_pretty_price($price, $this_wkshp_currency){
	// $html = workshops_price_prefix($this_wkshp_currency).number_format($price,2);
	$html = cc_money_format($price, $this_wkshp_currency);
	if($this_wkshp_currency == 'GBP'){
		$html .= ' <small>+ VAT</small>';
	}else{
		$html .= ' <small>(+ VAT if applicable)</small>';
	}
	return $html;
}

// Whether or not to show this workshop based on current date and workshop dates
// v1.45 now returns:
// - true = show it
// - false = don't show it
// - $recording_id = show the workshop but only offer registration for the recording
/**
 * Dates and times ....
 *
 * We could have a workshop that just takes place for a few hours on one day
 * Or one that could take place over multiple days
 * We want to show workshops that have not started yet
 * Or where it's a multi-event workshop, where one or more events have not started yet
 * We'll use the following fields to help us achieve this:
 * - Post meta: workshop_start_timestamp - this is a timestamp but actually only the date is of interest to us, the time is rubbish! This will be the date that the workshop starts (or the first of the multi-events starts) - the time part of the field contains the time of day when the field was last updated = useless!
 * - Post meta: meta_a is the start date in dd/mm/yyyy format
 * - Post meta: workshop_timestamp - this is also a timestamp but the time is good! It is the end date and time of the workshop or last event of a multi-event workshop. It can be empty so use the start timestamp as a fallback
 * - Post meta: event_'.$i.'_time - where $i = 1 to 15 - this is the start time in hh:mm format for individual events or, for $i = 1 where there is only one event, the start time for the workshop. This is blank where the workshop is an all-day event.
 * So, if this is a single event workshop with a specified time, eg: start = 1603378358, end = 1603378358 (both = 22/10/2020), event_1_time = 16:00
 * then we need to show this only up until 22/10/2020 16:00
 * If this is a single event workshop without a time eg: start = 1603378358, end = 1603378358 (both = 22/10/2020), event_1_time = ''
 * then we use 08:00 as a fallback time so we show this until 22/10/2020 08:00
 * Things get tricker with multi events.
 * If we have a workshop with a few events then we need to find the time for the last event and use that with the end time to decide when to show this up until
 * We'll know that there are multiple events because the event name (event_'.$i.'_name) will be non-blank for more than the first event
 * Plus, don't forget that all these dates and times refer to London time, not UTC.
 **/
function workshops_show_this_workshop($workshop_id){
	if( get_post_status( $workshop_id ) <> 'publish' ){
		return false;
	}
	$course_status = get_post_meta( $workshop_id, 'course_status', true );
	if($course_status == 'unlisted'){
		return false;
	}
    $now = time();
    // use last date/time for sale if it is set
    $last_datetime_for_sale = get_post_meta($workshop_id, 'last_datetime_for_sale', true); // Y-m-d H:i (UTC!)
    if($last_datetime_for_sale <> ''){
    	$datetime = DateTime::createFromFormat("Y-m-d H:i", $last_datetime_for_sale, new DateTimeZone('UTC'));
    	if($datetime){
    		if($datetime->getTimestamp() > $now){
    			return true;
    		}else{
    			// past the sell by date
    			// if the workshop is still in the future and there is a recording then we still show the workshop but set up registration for the recording
    			$recording_id = (int) get_post_meta($workshop_id, 'workshop_recording', true);
    			if( $recording_id > 0 && workshop_incomplete($workshop_id)){
    				return $recording_id;
    			}
    			return false;
    		}
    	}
		// invalid format so we ignore it    	
    }
    // failing that use dates in the workshop to determine whether to show the workshop or not
    return workshop_incomplete($workshop_id);
}

// checks if there is still something left to start for this workshop
// useful if it is closed for sale but still in the future
// looks for start time of either the workshop for a single event workshop or the latest event of a multi-event workshop
// returns bool
function workshop_incomplete($workshop_id){
    $now = time();
    // use dates in the workshop to determine whether it is still to start
    $workshop_timestamp = get_post_meta($workshop_id, 'workshop_timestamp', true ); // end date/time as a timestamp (if set)
    if($workshop_timestamp <> '' && $workshop_timestamp < $now){
    	// workshop has finished
    	return false;
    }
    $workshop_start_timestamp = get_post_meta($workshop_id, 'workshop_start_timestamp', true ); // time is rubbish
    if($workshop_start_timestamp <> ''){
    	$workshop_start_date = date('Y-m-d', $workshop_start_timestamp);
    	if($workshop_start_date > date('Y-m-d')){
    		// workshop starting tomorrow or later
    		return true;
    	}
    }
    // workshop is "on" ... or at least some of the events are
    // are any of the events yet to start?
    for ($i=1; $i < 16; $i++){
    	$event_start_time = get_post_meta( $workshop_id, 'event_'.$i.'_time', true ); // H:i (UTC)
        if($event_start_time == ''){
            $event_start_time = '08:00';
        }
    	if($i == 1){
    		$event_start_date = get_post_meta($workshop_id, 'meta_a', true); // d/m/y
    	}else{
    		$event_start_date = get_post_meta($workshop_id, 'event_'.$i.'_date', true); // d/m/y
    	}
    	if($event_start_date <> ''){
	    	$datetime = DateTime::createFromFormat("d/m/Y H:i", $event_start_date.' '.$event_start_time, new DateTimeZone('UTC'));
	    	if($datetime){
	    		if($datetime->getTimestamp() > $now){
	    			// this event is yet to start
	    			return true;
	    		}
	    	}
	    }
    }
    // no event found that is yet to start
    return false;
}

// is this workshop a multi-event workshop?
// returns bool
function workshop_is_multi_event($workshop_id){
	$num_events = 0;
	for($i = 1; $i<16; $i++){
		$event_name = get_post_meta( $workshop_id, 'event_'.$i.'_name', true );
		if($event_name <> ''){
			$num_events ++;
			if($num_events > 1){
				return true;
			}
		}
	}
	return false;
}

// calculate workshop end timestamp
// each event can have an end date (if not, the start date applies)
// each event can have an end time (if not, the start time is used - even though this will be wrong - it's better than nothing!)
// for event one, the workshop start date is used as the event start date
// and for event one there can be multiple sessions
// returns a timestamp or false
function workshop_calculate_end_timestamp($workshop_id){
	$wkshp_end_timestamp = false;
	for ($i=1; $i < 16; $i++) {
		$event_end_timestamp = workshop_event_calc_end_timestamp($workshop_id, $i);
		if($event_end_timestamp && $event_end_timestamp > $wkshp_end_timestamp){
			$wkshp_end_timestamp = $event_end_timestamp;
		}
	}
	return $wkshp_end_timestamp;
}

// calculates the end time for an event
// returns a timestamp or false
function workshop_event_calc_end_timestamp($workshop_id, $event_id){
	$event_end_timestamp = 0;
	if($event_id == 1){
		$sessions = 6;
	}else{
		$sessions = 1;
	}
	for ($i=$sessions; $i > 0; $i--) {
		if($i == 1){
            $prefix = 'event_'.$event_id;
        }else{
            $prefix = 'event_'.$event_id.'_sess_'.$i;
		}
		$event_time_end = get_post_meta($workshop_id, $prefix.'_time_end', true);
		if($event_time_end <> ''){
			break;
		}
	}
	if($event_time_end == ''){
		// use start time
		$event_time_end = get_post_meta($workshop_id, $prefix.'_time', true);
	}
	$event_date_end = get_post_meta($workshop_id, $prefix.'_date_end', true);
	if($event_date_end == ''){
		// end date must be the same as start date
		if($event_id == 1 && $i == 1){
			$meta_key = 'meta_a';
		}else{
			$meta_key = $prefix.'_date';
		}
		$event_date_end = get_post_meta($workshop_id, $meta_key, true);
	}
	if($event_date_end == '') return false;
	// times are stored as UTC
	$date = DateTime::createFromFormat("d/m/Y H:i", $event_date_end.' '.$event_time_end, new DateTimeZone('UTC'));
	if(!$date) return false;
	return $date->getTimestamp();
}

// returns a timestamp of the event start time (or false)
function workshop_event_calc_start_timestamp($workshop_id, $event_id){
    if($event_id == 1){
        $meta_key = 'meta_a';
    }else{
        $meta_key = 'event_'.$event_id.'_date';
    }
    $event_start_date = get_post_meta( $workshop_id, $meta_key, true );
    if($event_start_date == '') return false;
    $calc_start_time = $event_start_time = get_post_meta( $workshop_id, 'event_'.$event_id.'_time', true );
    if($event_start_time == ''){
        // all day event
        $calc_start_time = '08:00';
    }
    $date = DateTime::createFromFormat("d/m/Y H:i", $event_start_date.' '.$calc_start_time, new DateTimeZone('UTC'));
    if($date){
    	return $date->getTimestamp();
    }
    return false;
}

// gets a workshop start datetime in am/pm format (if possible)
function workshop_start_datetime_ampm( $workshop_id, $user_id ){
	$workshop_start_date = get_post_meta($workshop_id, 'meta_a', true); // d/m/y
	if($workshop_start_date <> ''){
		$user_timezone = cc_timezone_get_user_timezone( $user_id );
		list($start_dd, $start_mm, $start_yyyy) = explode('/', $workshop_start_date);
		$start_time = get_post_meta($workshop_id, 'event_1_time', true); // H:i (UTC)
		if($start_time == ''){
			$workshop_start = datetime::createFromFormat("d/m/Y H:i", $workshop_start_date.' 00:00', new DateTimeZone('UTC'));
			if($workshop_start){
			    $start_locale = new DateTime('now', new DateTimeZone($user_timezone));
			    $start_locale->setTimestamp( $workshop_start->getTimestamp() );
				return $start_locale->format('jS M Y');
			}
		}else{
			$workshop_start = DateTime::createFromFormat("d/m/Y H:i", $workshop_start_date.' '.$start_time, new DateTimeZone('UTC'));
			if($workshop_start){
			    $start_locale = new DateTime('now', new DateTimeZone($user_timezone));
			    $start_locale->setTimestamp( $workshop_start->getTimestamp() );
				return 'Starts '.$start_locale->format('jS M Y g:i A').' ('.cc_timezone_get_user_timezone_pretty( $user_id, $user_timezone ).' time)';
			}
		}
	}
	return '';
}

// works out and returns the pretty dates for a workshop. returns London and, if requested, local prettydates. Returns '' if it cannot work it out
// returned dates return can be a range (eg 7th to 15th Sep), as can datetime (eg 7th to 15th Sep (starts 09:00)) start_datetime might be 7th Sep 09:00 and start_time might be 09:00
function workshop_calculated_prettydates( $workshop_id, $locale='Europe/London', $ampm=true ){
	$result = array(
		'london_date' => '',
		'locale_date' => '',
		'london_datetime' => '',
		'locale_datetime' => '',
		'london_start_datetime' => '',
		'london_start_date' => '',
		'london_start_time' => '',
		'locale_start_datetime' => '',
		'locale_start_date' => '',
		'locale_start_time' => '',
		'london_times' => '',
		'locale_times' => '',
	);
	// start date ...
	$workshop_start_date = get_post_meta($workshop_id, 'meta_a', true); // d/m/y
	if($workshop_start_date <> ''){
		list($start_dd, $start_mm, $start_yyyy) = explode('/', $workshop_start_date);
		// start time ...
		$found_start_time = false;
		$workshop_start_timestamp = false;
		$start_time = get_post_meta($workshop_id, 'event_1_time', true); // H:i (UTC)
		if($start_time == ''){
			$workshop_start = DateTime::createFromFormat("d/m/Y H:i", $workshop_start_date.' 00:00', new DateTimeZone('UTC'));
		}else{
			$workshop_start = DateTime::createFromFormat("d/m/Y H:i", $workshop_start_date.' '.$start_time, new DateTimeZone('UTC'));
		}
		if($workshop_start){
		    $workshop_start_timestamp = $workshop_start->getTimestamp();
		    if($start_time <> ''){
		    	$found_start_time = true;
		    }
		}
		// end ...
		$found_end_date = false;
		$workshop_timestamp = get_post_meta($workshop_id, 'workshop_timestamp', true); // time and date is good
		if($workshop_timestamp <> ''){
	        $workshop_end = new DateTime('now', new DateTimeZone('UTC'));
	        $workshop_end->setTimestamp($workshop_timestamp);
		    $found_end_date = true;
		}
		$result = workshop_event_pretty_date_range($workshop_start_timestamp, $workshop_timestamp, $locale, $found_start_time, $ampm);
	}else{
		$result['london_date'] = $result['locale_date'] = $result['london_datetime'] = $result['locale_datetime'] = $result['london_start_date'] = $result['locale_start_date'] = get_post_meta($workshop_id, 'prettydates', true);
	}
	return $result;
}

// format a range of pretty dates to look prettier
function workshop_event_pretty_date_range($start_timestamp, $end_timestamp, $locale='Europe/London', $start_time_ok=true, $ampm=true){
	$result = array(
		'london_date' => '',
		'locale_date' => '',
		'london_datetime' => '',
		'locale_datetime' => '',
		'london_start_datetime' => '',
		'london_start_date' => '',
		'london_start_time' => '',
		'locale_start_datetime' => '',
		'locale_start_date' => '',
		'locale_start_time' => '',
		'london_times' => '',
		'locale_times' => '',
	);
	$time_format = 'H:i';
	if( $ampm ){
		$time_format = 'g:i A';
	}
	if($start_timestamp){
		$start_yyyy = date('Y', $start_timestamp);
		$start_mm = (int) date('m', $start_timestamp);
		$start_dd = date('d', $start_timestamp);
	    $start_london = new DateTime('now', new DateTimeZone('Europe/London'));
	    $start_london->setTimestamp($start_timestamp);
	    $start_locale = new DateTime('now', new DateTimeZone($locale));
	    $start_locale->setTimestamp($start_timestamp);
		if($end_timestamp){
			$end_yyyy = date('Y', $end_timestamp);
			$end_mm = date('m', $end_timestamp);
			$end_dd = date('d', $end_timestamp);
		    $end_london = new DateTime('now', new DateTimeZone('Europe/London'));
		    $end_london->setTimestamp($end_timestamp);
		    $end_locale = new DateTime('now', new DateTimeZone($locale));
		    $end_locale->setTimestamp($end_timestamp);
		}else{
			$end_yyyy = $start_yyyy;
			$end_mm = $start_mm;
			$end_dd = $start_dd;
		}
		$months = array('', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		if($start_yyyy == $end_yyyy){
			if($start_mm == $end_mm){
				if($start_dd == $end_dd){
					if($end_timestamp && $start_time_ok && $start_timestamp <> $end_timestamp){
						$result['london_datetime'] = $start_london->format('jS M Y: '.$time_format).' - '.$end_london->format($time_format);
						$result['locale_datetime'] = $start_locale->format('jS M Y: '.$time_format).' - '.$end_locale->format($time_format);
						$result['london_date'] = $start_london->format('jS M Y');
						$result['locale_date'] = $start_locale->format('jS M Y');
						$result['london_start_datetime'] = $start_london->format('jS M Y: '.$time_format);
						$result['london_start_date'] = $start_london->format('jS M Y');
						$result['london_start_time'] = $start_london->format($time_format);
						$result['locale_start_datetime'] = $start_locale->format('jS M Y: '.$time_format);
						$result['locale_start_date'] = $start_locale->format('jS M Y');
						$result['locale_start_time'] = $start_locale->format($time_format);
						$result['london_times'] = $start_london->format($time_format).' - '.$end_london->format($time_format);
						$result['locale_times'] = $start_locale->format($time_format).' - '.$end_locale->format($time_format);
					}elseif($start_time_ok){
						$result['london_datetime'] = $start_london->format('jS M Y: '.$time_format);
						$result['locale_datetime'] = $start_locale->format('jS M Y: '.$time_format);
						$result['london_date'] = $start_london->format('jS M Y');
						$result['locale_date'] = $start_locale->format('jS M Y');
						$result['london_start_datetime'] = $start_london->format('jS M Y: '.$time_format);
						$result['london_start_date'] = $start_london->format('jS M Y');
						$result['london_start_time'] = $result['london_times'] = $start_london->format($time_format);
						$result['locale_start_datetime'] = $start_locale->format('jS M Y: '.$time_format);
						$result['locale_start_date'] = $start_locale->format('jS M Y');
						$result['locale_start_time'] = $result['locale_times'] = $start_locale->format($time_format);
					}elseif($end_timestamp){
						$result['london_date'] = $result['london_datetime'] = $result['london_start_date'] = $start_london->format('jS M Y');
						$result['locale_date'] = $result['locale_datetime'] = $result['locale_start_date'] = $start_locale->format('jS M Y');
					}else{
						$result['london_date'] = $result['locale_date'] = $result['london_datetime'] = $result['locale_datetime'] = $result['london_start_datetime'] = $result['locale_start_datetime'] = $result['london_start_date'] = $result['locale_start_date'] = $start_dd.' '.$months[$start_mm].' '.$start_yyyy;
					}
				}else{
					if($end_timestamp && $start_time_ok && $start_timestamp <> $end_timestamp){
						$result['london_datetime'] = $start_london->format('jS').' - '.$end_london->format('jS M Y').' (starts '.$start_london->format($time_format).')';
						$result['locale_datetime'] = $start_locale->format('jS').' - '.$end_locale->format('jS M Y').' (starts '.$start_locale->format($time_format).')';
						$result['london_date'] = $start_london->format('jS').' - '.$end_london->format('jS M Y');
						$result['locale_date'] = $start_locale->format('jS').' - '.$end_locale->format('jS M Y');
						$result['london_start_datetime'] = $start_london->format('jS M Y: '.$time_format);
						$result['london_start_date'] = $start_london->format('jS M Y');
						$result['london_start_time'] = $start_london->format('jS M Y: '.$time_format);
						$result['locale_start_datetime'] = $start_locale->format('jS M Y: '.$time_format);
						$result['locale_start_date'] = $start_locale->format('jS M Y');
						$result['locale_start_time'] = $start_locale->format('jS M Y: '.$time_format);
						$result['london_times'] = $start_london->format($time_format).' - '.$end_london->format($time_format);
						$result['locale_times'] = $start_locale->format($time_format).' - '.$end_locale->format($time_format);
					}elseif($start_time_ok){
						$result['london_datetime'] = $start_london->format('jS M Y: '.$time_format);
						$result['locale_datetime'] = $start_locale->format('jS M Y: '.$time_format);
						$result['london_date'] = $start_london->format('jS M Y');
						$result['locale_date'] = $start_locale->format('jS M Y');
						$result['london_start_datetime'] = $start_london->format('jS M Y: '.$time_format);
						$result['london_start_date'] = $start_london->format('jS M Y');
						$result['london_start_time'] = $start_london->format('jS M Y: '.$time_format);
						$result['locale_start_datetime'] = $start_locale->format('jS M Y: '.$time_format);
						$result['locale_start_date'] = $start_locale->format('jS M Y');
						$result['locale_start_time'] = $start_locale->format('jS M Y: '.$time_format);
						$result['london_times'] = $start_london->format($time_format).' - '.$end_london->format($time_format);
						$result['locale_times'] = $start_locale->format($time_format).' - '.$end_locale->format($time_format);
					}elseif($end_timestamp){
						$result['london_date'] = $result['london_datetime'] = $start_london->format('jS').' - '.$end_london->format('jS M Y');
						$result['locale_date'] = $result['locale_datetime'] = $start_locale->format('jS').' - '.$end_locale->format('jS M Y');
						$result['london_start_date'] = $start_london->format('jS M Y');
						$result['locale_start_date'] = $start_locale->format('jS M Y');
						$result['london_start_datetime'] = $start_london->format('jS M Y: '.$time_format);
						$result['locale_start_datetime'] = $start_locale->format('jS M Y: '.$time_format);
					}else{
						$result['london_date'] = $result['locale_date'] = $result['london_datetime'] = $result['locale_datetime'] = $result['london_start_datetime'] = $result['locale_start_datetime'] = $result['london_start_date'] = $result['locale_start_date'] = $start_dd.' '.$months[$start_mm].' '.$start_yyyy;
					}
				}
			}else{
				if($end_timestamp && $start_time_ok && $start_timestamp <> $end_timestamp){
					$result['london_datetime'] = $start_london->format('jS M').' - '.$end_london->format('jS M Y').' (starts '.$start_london->format($time_format).')';
					$result['locale_datetime'] = $start_locale->format('jS M').' - '.$end_locale->format('jS M Y').' (starts '.$start_locale->format($time_format).')';
					$result['london_date'] = $start_london->format('jS M').' - '.$end_london->format('jS M Y');
					$result['locale_date'] = $start_locale->format('jS M').' - '.$end_locale->format('jS M Y');
					$result['london_start_datetime'] = $start_london->format('jS M Y: '.$time_format);
					$result['london_start_date'] = $start_london->format('jS M Y');
					$result['london_start_time'] = $start_london->format('jS M Y: '.$time_format);
					$result['locale_start_datetime'] = $start_locale->format('jS M Y: '.$time_format);
					$result['locale_start_date'] = $start_locale->format('jS M Y');
					$result['locale_start_time'] = $start_locale->format('jS M Y: '.$time_format);
					$result['london_times'] = $start_london->format($time_format).' - '.$end_london->format($time_format);
					$result['locale_times'] = $start_locale->format($time_format).' - '.$end_locale->format($time_format);
				}elseif($start_time_ok){
					$result['london_datetime'] = $start_london->format('jS M Y: '.$time_format);
					$result['locale_datetime'] = $start_locale->format('jS M Y: '.$time_format);
					$result['london_date'] = $start_london->format('jS M Y');
					$result['locale_date'] = $start_locale->format('jS M Y');
					$result['london_start_datetime'] = $start_london->format('jS M Y: '.$time_format);
					$result['london_start_date'] = $start_london->format('jS M Y');
					$result['london_start_time'] = $start_london->format('jS M Y: '.$time_format);
					$result['locale_start_datetime'] = $start_locale->format('jS M Y: '.$time_format);
					$result['locale_start_date'] = $start_locale->format('jS M Y');
					$result['locale_start_time'] = $start_locale->format('jS M Y: '.$time_format);
					$result['london_times'] = $start_london->format($time_format).' - '.$end_london->format($time_format);
					$result['locale_times'] = $start_locale->format($time_format).' - '.$end_locale->format($time_format);
				}elseif($end_timestamp){
					$result['london_date'] = $result['london_datetime'] = $start_london->format('jS M').' - '.$end_london->format('jS M Y');
					$result['locale_date'] = $result['locale_datetime'] = $start_locale->format('jS M').' - '.$end_locale->format('jS M Y');
					$result['london_start_date'] = $start_london->format('jS M Y');
					$result['locale_start_date'] = $start_locale->format('jS M Y');
					$result['london_start_datetime'] = $start_london->format('jS M Y: '.$time_format);
					$result['locale_start_datetime'] = $start_locale->format('jS M Y: '.$time_format);
				}else{
					$result['london_date'] = $result['locale_date'] = $result['london_datetime'] = $result['locale_datetime'] = $result['london_start_datetime'] = $result['locale_start_datetime'] = $result['london_start_date'] = $result['locale_start_date'] = $start_dd.' '.$months[$start_mm].' '.$start_yyyy;
				}
			}
		}else{
			if($end_timestamp && $start_time_ok){
				$result['london_datetime'] = $start_london->format('jS M Y').' - '.$end_london->format('jS M Y').' (starts '.$start_london->format($time_format).')';
				$result['locale_datetime'] = $start_locale->format('jS M Y').' - '.$end_locale->format('jS M Y').' (starts '.$start_locale->format($time_format).')';
				$result['london_date'] = $start_london->format('jS M Y').' - '.$end_london->format('jS M Y');
				$result['locale_date'] = $start_locale->format('jS M Y').' - '.$end_locale->format('jS M Y');
				$result['london_start_datetime'] = $start_london->format('jS M Y: '.$time_format);
				$result['london_start_date'] = $start_london->format('jS M Y');
				$result['london_start_time'] = $start_london->format('jS M Y: '.$time_format);
				$result['locale_start_datetime'] = $start_locale->format('jS M Y: '.$time_format);
				$result['locale_start_date'] = $start_locale->format('jS M Y');
				$result['locale_start_time'] = $start_locale->format('jS M Y: '.$time_format);
				$result['london_times'] = $start_london->format($time_format).' - '.$end_london->format($time_format);
				$result['locale_times'] = $start_locale->format($time_format).' - '.$end_locale->format($time_format);
			}elseif($start_time_ok){
				$result['london_datetime'] = $start_london->format('jS M Y: '.$time_format);
				$result['locale_datetime'] = $start_locale->format('jS M Y: '.$time_format);
				$result['london_date'] = $start_london->format('jS M Y');
				$result['locale_date'] = $start_locale->format('jS M Y');
				$result['london_start_datetime'] = $start_london->format('jS M Y: '.$time_format);
				$result['london_start_date'] = $start_london->format('jS M Y');
				$result['london_start_time'] = $start_london->format('jS M Y: '.$time_format);
				$result['locale_start_datetime'] = $start_locale->format('jS M Y: '.$time_format);
				$result['locale_start_date'] = $start_locale->format('jS M Y');
				$result['locale_start_time'] = $start_locale->format('jS M Y: '.$time_format);
				$result['london_times'] = $start_london->format($time_format).' - '.$end_london->format($time_format);
				$result['locale_times'] = $start_locale->format($time_format).' - '.$end_locale->format($time_format);
			}elseif($end_timestamp){
				$result['london_date'] = $result['london_datetime'] = $start_london->format('jS M Y').' - '.$end_london->format('jS M Y');
				$result['locale_date'] = $result['locale_datetime'] = $start_locale->format('jS M Y').' - '.$end_locale->format('jS M Y');
				$result['london_start_date'] = $start_london->format('jS M Y');
				$result['locale_start_date'] = $start_locale->format('jS M Y');
				$result['london_start_datetime'] = $start_london->format('jS M Y: '.$time_format);
				$result['locale_start_datetime'] = $start_locale->format('jS M Y: '.$time_format);
			}else{
				$result['london_date'] = $result['locale_date'] = $result['london_datetime'] = $result['locale_datetime'] = $result['london_start_datetime'] = $result['locale_start_datetime'] = $result['london_start_date'] = $result['locale_start_date'] = $start_dd.' '.$months[$start_mm].' '.$start_yyyy;
			}
		}
	}
	return $result;
}

// how many sessions are there for this workshop?
// returns an integer
// if it's a multi-event, it counts the events, if not it counts event 1 sessions
function workshop_number_sessions($workshop_id){
	$num_events = 0;
	for($i = 1; $i<16; $i++){
		$event_name = get_post_meta( $workshop_id, 'event_'.$i.'_name', true );
		if($event_name <> ''){
			$num_events ++;
		}
	}
	if($num_events > 1){
		return $num_events;
	}
	$num_sess = 1;
	for ($i=2; $i <= 6; $i++) { 
		if( get_post_meta( $workshop_id, 'event_1_sess_'.$i.'_date', true ) <> '' ){
			$num_sess ++;
		}
	}
	return $num_sess;
}

// how many sessions for a workshop/event?
function workshop_event_num_sessions($workshop_id, $event_id){
	if($event_id > 1){
		return 1;
	}
	$num_sess = 1;
	for ($i=2; $i <= 6; $i++) { 
		if( get_post_meta( $workshop_id, 'event_1_sess_'.$i.'_date', true ) <> '' ){
			$num_sess ++;
		}
	}
	return $num_sess;
}

// gets the full schedule for multiple sessions
add_action('wp_ajax_workshop_times_modal_get', 'workshop_times_modal_get');
add_action('wp_ajax_nopriv_workshop_times_modal_get', 'workshop_times_modal_get');
function workshop_times_modal_get(){
	$response = array(
		'status' => 'error',
		'title' => '',
		'body' => '',
	);
	$time_format = 'g:i A'; // AM/PM format
	$user_id = get_current_user_id();
	$workshop_id = 0;
	if(isset($_POST['workshopID'])){
		$workshop_id = absint( $_POST['workshopID'] );
	}
	$timezone = '';
	if(isset($_POST['timezone']) && in_array($_POST['timezone'], timezone_identifiers_list()) ){
		$timezone = $_POST['timezone'];
	}
	if( $timezone == '' ){
		$timezone = cc_timezone_get_user_timezone( $user_id ); // eg Europe/London
	}
	$pretty_timezone = cc_timezone_get_user_timezone_pretty($user_id, $timezone);
	$show_timezone_msg = true;

	if($workshop_id > 0 && $timezone <> ''){
		$response['title'] = get_the_title($workshop_id).': Schedule';

	    $multi_event = workshop_is_multi_event( $workshop_id );
	    if( $multi_event ){
	    	for ($i=1; $i < 16; $i++) { 
	    		$event_name = get_post_meta( $workshop_id, 'event_'.$i.'_name', true );
	    		if( $event_name <> '' ){
	    			$response['body'] .= '<h4>'.$event_name.'</h4>';
	    			$session = 1;
	    			$events_times = workshop_event_sessions_datetimes( $workshop_id, $i );
	    			foreach ($events_times as $row) {
	    				$response['body'] .= workshop_session_datetimes_pretty( $session, $row, $timezone, $time_format );
	    				$session ++;
	    			}
	    		}
	    	}
	    }else{
			// is it a face to face workshop?
			$venue = get_post_meta( $workshop_id, 'event_1_venue_name', true );
			if( $venue <> '' ){
				// yes, it is ... always show London time
				$timezone = 'Europe/London';
				$pretty_timezone = 'London';
				$show_timezone_msg = false;
			}

			$session = 1;
			$events_times = workshop_event_sessions_datetimes( $workshop_id, 1 );
			foreach ($events_times as $row) {
				$response['body'] .= workshop_session_datetimes_pretty( $session, $row, $timezone, $time_format );
				$session ++;
			}
		}

		if( $show_timezone_msg ){
			$response['body'] .= '<p class="small mb-0">Training times shown are '.$pretty_timezone.' time</p>';
		}
		$response['status'] = 'ok';
	}
    echo json_encode($response);
    die();
}

// returns a tidy display of a session's times
function workshop_session_datetimes_pretty( $session, $row, $timezone, $time_format ){
	$html = '<div class="row"><div class="col-2"><span class="fa-stack fa-lg session-number"><i class="fa-solid fa-circle fa-stack-2x"></i><i class="fa-solid fa-stack-1x fa-inverse fa-'.$session.'"></i></span></div><div class="col-10">';
	$html .= '<h5 class="mb-1"> Session '.$session.':</h5>';
    $start_datetime = new DateTime('now', new DateTimeZone($timezone));
    $start_datetime->setTimestamp($row['start']);
    $html .= '<p>'.$start_datetime->format('D jS M Y: '.$time_format).' - ';
    $end_datetime = new DateTime('now', new DateTimeZone($timezone));
    $end_datetime->setTimestamp($row['end']);
    if($start_datetime->format('Y') == $end_datetime->format('Y')){
    	if($start_datetime->format('M') == $end_datetime->format('M')){
    		if($start_datetime->format('d') == $end_datetime->format('d')){
    			$end_format = $time_format;
    		}else{
    			$end_format = 'D jS: '.$time_format;
    		}
    	}else{
    		$end_format = 'D jS M: '.$time_format;
    	}
    }else{
    	$end_format = 'D jS M Y: '.$time_format;
    }
    $html .= $end_datetime->format($end_format).'</p>';
    $html .= '</div></div>';
    return $html;
}

// gets and tidies up the workshop session datetimes
function workshop_sessions_datetimes($workshop_id){
	$response = array();

	for ($i=1; $i <= 6; $i++) { 
		if($i == 1){
            $prefix = 'event_1';
        }else{
            $prefix = 'event_1_sess_'.$i;
		}

		$row = array(
			'start' => '',
			'end' => '',
		);
		if($i == 1){
			$meta_key = 'meta_a';
		}else{
			$meta_key = $prefix.'_date';
		}
		$start_date = get_post_meta($workshop_id, $meta_key, true); // d/m/y
		if($start_date <> ''){
			list($start_dd, $start_mm, $start_yyyy) = explode('/', $start_date);
			// start time ...
			$start_time = get_post_meta($workshop_id, $prefix.'_time', true); // H:i (UTC)
			if($start_time == ''){
				$start_datetime = DateTime::createFromFormat("d/m/Y H:i", $start_date.' 00:00', new DateTimeZone('UTC'));
			}else{
				$start_datetime = DateTime::createFromFormat("d/m/Y H:i", $start_date.' '.$start_time, new DateTimeZone('UTC'));
			}
			if($start_datetime){
			    $row['start'] = $start_datetime->getTimestamp();
				// end ...
				$end_date = get_post_meta($workshop_id, $prefix.'_date_end', true);
				if($end_date == ''){
					$end_date = $start_date;
				}
				$end_time = get_post_meta($workshop_id, $prefix.'_time_end', true); // H:i (UTC)
				if($end_time == ''){
					$end_datetime = DateTime::createFromFormat("d/m/Y H:i", $end_date.' 00:00', new DateTimeZone('UTC'));
				}else{
					$end_datetime = DateTime::createFromFormat("d/m/Y H:i", $end_date.' '.$end_time, new DateTimeZone('UTC'));
				}
				if($end_datetime){
					$row['end'] = $end_datetime->getTimestamp();
					$response[] = $row;
				}
			}
		}
	}

	return $response;
}

// gets the session times for an event of a workshop
function workshop_event_sessions_datetimes( $workshop_id, $event_id ){
	if( $event_id == 1 ){
		return workshop_sessions_datetimes( $workshop_id );
	}
	$response = array();
	$row = array(
		'start' => '',
		'end' => '',
	);
	$start_date = get_post_meta( $workshop_id, 'event_'.$event_id.'_date', true );
	$start_time = get_post_meta( $workshop_id, 'event_'.$event_id.'_time', true );
	if( $start_time == '' ) $start_time = '00:00';
	$start_datetime = DateTime::createFromFormat("d/m/Y H:i", $start_date.' '.$start_time, new DateTimeZone('UTC'));
	if( $start_datetime ){
		$row['start'] = $start_datetime->getTimestamp();

		$end_date = get_post_meta( $workshop_id, 'event_'.$event_id.'_date_end', true );
		if( $end_date == '' ) $end_date = $start_date;
		$end_time = get_post_meta( $workshop_id, 'event_'.$event_id.'_time_end', true );
		if( $end_time == '' ) $end_time = '00:00';
		$end_datetime = DateTime::createFromFormat("d/m/Y H:i", $end_date.' '.$end_time, new DateTimeZone('UTC'));
		if($end_datetime){
			$row['end'] = $end_datetime->getTimestamp();
		}
		$response[] = $row;
	}
	return $response;
}


// tidy up the workshop_timestamp
// one-off task needed Oct 2021 (v1.3.17)
// workshop_timestamp previously had rubbish times, we're goign to fix that..
add_shortcode('fix_workshop_timestamps', 'fix_workshop_timestamps');
function fix_workshop_timestamps(){
	$html = 'fix_workshop_timestamps';
	$args = array(
		'post_type' => 'workshop',
		'numberposts' => -1,
	);
	$all_workshops = get_posts( $args );
	$count_updates = 0;
	foreach ($all_workshops as $workshop) {
		$old_workshop_timestamp = get_post_meta($workshop->ID, 'workshop_timestamp', true);
		$html .= '<br>'.$workshop->ID.' was '.$old_workshop_timestamp.' ('.date('d/m/Y H:i', $old_workshop_timestamp).') ';
		$workshop_timestamp = workshop_calculate_end_timestamp($workshop->ID);
		$html .= ' is '.$workshop_timestamp.' ('.date('d/m/Y H:i', $workshop_timestamp).') ';
		if($workshop_timestamp){
			update_post_meta($workshop->ID, 'workshop_timestamp', $workshop_timestamp);
			$html .= ' updated';
			$count_updates ++;
		}
	}
	$html .= '<br>'.$count_updates.' updates';
	return $html;
}

/*
fix_workshop_timestamps
565 was 1633605416 (07/10/2021 11:16) is (01/01/1970 00:00)
454 was 1647355052 (15/03/2022 14:37) is (01/01/1970 00:00)
424 was 1656668798 (01/07/2022 09:46) is (01/01/1970 00:00)
363 was 1682853856 (30/04/2023 11:24) is (01/01/1970 00:00)
333 was 1666474808 (22/10/2022 21:40) is (01/01/1970 00:00)
330 was 1601483829 (30/09/2020 16:37) is (01/01/1970 00:00)
316 was 1546342336 (01/01/2019 11:32) is (01/01/1970 00:00)
266 was 1592144578 (14/06/2020 14:22) is (01/01/1970 00:00)
222 was 1602071112 (07/10/2020 11:45) is (01/01/1970 00:00)
163 was 1448543427 (26/11/2015 13:10) is (01/01/1970 00:00)
74 was 1436550490 (10/07/2015 17:48) is (01/01/1970 00:00)
71 was 1462741826 (08/05/2016 21:10) is (01/01/1970 00:00)
69 was 1429884881 (24/04/2015 14:14) is (01/01/1970 00:00)
0 updates
*/

// is the workshop event still to come (ie available to purchase)
// returns bool
// $when (Y-m-d H:i:s) is used to lookup a past price
function workshop_event_in_future($workshop_id, $event_id, $when=''){
	if($event_id == 1){
		$meta_key = 'meta_a';
	}else{
		$meta_key = 'event_'.$event_id.'_date';
	}
	$event_date = get_post_meta( $workshop_id, $meta_key, true );
	$calc_time = $event_time = get_post_meta( $workshop_id, 'event_'.$event_id.'_time', true );
	if($event_time == ''){
		$calc_time = '00:00';
	}
	if($event_date <> ''){
		$date = DateTime::createFromFormat("d/m/Y H:i", $event_date.' '.$calc_time, new DateTimeZone('UTC'));
		if($date){
			if( $when == '' ){
				$timestamp = time();
			}else{
				$wanted_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $when );
				$timestamp = $wanted_date->getTimestamp();
			}
			if($timestamp < $date->getTimestamp()){
				return true;
			}
		}
	}
	return false;
}

// returns a displayable date and/or time for an event in a chosen timezone
function workshop_event_display_date_time($workshop_id, $event_id, $timezone){
	$response = array(
		'date' => '',
		'time' => '',
		'timestamp' => '',
	);
	// get the date and time from the DB
	if($event_id == 1){
		$meta_key = 'meta_a';
	}else{
		$meta_key = 'event_'.$event_id.'_date';
	}
	$event_date = get_post_meta( $workshop_id, $meta_key, true );
	if($event_date <> ''){
		$event_time = get_post_meta( $workshop_id, 'event_'.$event_id.'_time', true );
		if($event_time == ''){
			$event_time = '00:00';
		}
		$date = DateTime::createFromFormat("d/m/Y H:i", $event_date.' '.$event_time, new DateTimeZone('UTC'));
		if($date){
			$response['timestamp'] = $date->getTimestamp();
			$dt = new DateTime('now', new DateTimeZone($timezone));
			$dt->setTimestamp($date->getTimestamp());
			$response['date'] = $dt->format('D jS M Y');
			if($event_time <> '00:00'){
				$response['time'] = $dt->format('H:i');	
			}
		}
	}
	return $response;
}

// workshops as options
function ccpa_workshop_options(){
	// get all workshops
	$args = array(
		'post_type' => 'workshop',
		'posts_per_page' => -1,
	);
	$workshops = get_posts($args);
	$html = '';
	foreach ($workshops as $workshop) {
		$html .= '<option value="'.$workshop->ID.'">'.$workshop->post_title.'</option>';
	}
	return $html;
}

// gets a random featured workshop for the my account page
function workshop_featured_post($exclude=array()){
	$args = array(
		'numberposts' => 1,
		'post_type' => 'workshop',
		'orderby' => 'rand',
		'exclude' => $exclude,
		'meta_key'  => 'workshop_start_timestamp',
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'workshop_start_timestamp',
				'value' => time(),
				'compare' => '>',
			),
			array(
				'key' => 'workshop_featured',
				'value' => 'yes',
				'compare' => '=',
			),
		),
	);
	return get_posts($args);
}

// gets the last 20 workshops that have started (or completed)
// returns a set of options for a select clause
// returns them in date order (most recent start date first)
function workshops_started_options($workshop_id=0){
	$args = array(
		'post_type' => 'workshop',
		'numberposts' => 20,
		'meta_query' => array(
			array(
				'key' => 'workshop_start_timestamp',
				'value' => time(),
				'compare' => '<',
			),
		),
		'meta_key' => 'workshop_start_timestamp',
		'orderby' => 'meta_value_num',
	);
	$workshops = get_posts($args);
	$options = '';
	foreach ($workshops as $workshop) {
		$options .= '<option value="'.$workshop->ID.'" '.selected($workshop->ID, $workshop_id, false).'>'.$workshop->post_title.'</option>';
	}
	return $options;
}

// gets the next three upcoming workshops for a given taxonomy
// will get featured workshops first if they are relevant for the taxonomies
// returns an array of 0 to 3 IDs
// $taxonomies should be an array such as array( 'tax_issues' => array( 'tax_one_id', 'tax_two_id' ), 'next_taxonomy' => array( 'tax_three_id' ) )
function workshops_next_in_taxonomies( $taxonomies=array() ){
	// ccpa_write_log('workshops_next_in_taxonomies');
	// ccpa_write_log('parms:');
	// ccpa_write_log($taxonomies);
	$tax_query = cc_topics_tax_query( $taxonomies );
	// ccpa_write_log('tax_query:');
	// ccpa_write_log($tax_query);

	// look to see if there are any featured workshops in the future
	$args = array(
		'post_type' => 'workshop',
		'numberposts' => 3,
		'fields' => 'ids',
		'orderby'   => 'meta_value_num',
		'order' => 'ASC',
		'meta_key'  => 'workshop_start_timestamp',
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'workshop_start_timestamp',
				'value' => time(),
				'compare' => '>',
			),
			array(
				'key' => 'workshop_featured',
				'value' => 'yes',
				'compare' => '=',
			),
		),
	);
	if( is_array( $tax_query ) ){
		$args['tax_query'] = $tax_query;
	}
	$featured_workshop_ids = get_posts($args);
	// ccpa_write_log('featured_workshop_ids:');
	// ccpa_write_log($featured_workshop_ids);

	// do we also need some upcoming events (we always show 3 events if we can)?
	if(count($featured_workshop_ids) < 3){
		$numberposts = 3 - count($featured_workshop_ids);
		$args = array(
			'post_type' => 'workshop',
			'numberposts' => $numberposts,
			'fields' => 'ids',
			'orderby'   => 'meta_value_num',
			'order' => 'ASC',
			'meta_key'  => 'workshop_start_timestamp',
			'meta_query' => array(
				array(
					'key' => 'workshop_start_timestamp',
					'value' => time(),
					'compare' => '>',
				),
			),
		);
		if(!empty($featured_workshop_ids)){
			$args['post__not_in'] = $featured_workshop_ids;
		}
		if( is_array( $tax_query ) ){
			$args['tax_query'] = $tax_query;
		}
		$upcoming_workshop_ids = get_posts($args);
	}else{
		$upcoming_workshop_ids = array();
	}
	// ccpa_write_log('upcoming_workshop_ids:');
	// ccpa_write_log($upcoming_workshop_ids);

	return array_merge($featured_workshop_ids, $upcoming_workshop_ids);
}