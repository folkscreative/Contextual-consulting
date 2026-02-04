<?php
/*
Plugin Name: Recordings
Plugin URI: 
Description: Adds recording custom post types
Version: 2.34.0
Author URI: https://saasora.com
*/

/**
 * v2.34 Jul 2025
 * - on-demand training in site search now shown in ID DESC order
 * v2.33 Jun 2025
 * - quick hack to fix upsell pricing for courses
 * v2.32 Jun 2025
 * - fix lookup to find courses instead of recordings
 * - zoom chat upload moved to admin.js
 * v2.31 Feb 2025
 * - added xero tracking category
 * - mods for the revised my account look
 * v2.30 Jan 2025
 * - accommodates revised training accordions
 * v2.29 Jan 2025
 * - reminders now extended to happen on many days before expiry
 * v2.28 Nov 2024
 * - added upsells
 * v2.27 Nov 2024
 * - exclude unlisted training from public pages
 * v2.26 Nov 2024
 * - quiz override added for certificates
 * v2.25 Oct 2024
 * - added CE  credits to training summary
 * - compressed training summary
 * v2.24 Sep 2024
 * - added ICF certificate
 * v2.23 Sep 2024
 * - added training levels
 * - fixed bug with chat display for modules
 * - code tidy-up
 * - accommodate second zoom chat file
 * v2.22 Aug 2024
 * - hide currency change on free recordings
 * - corrected npcc to be nbcc
 * v2.21 Jul 2024
 * - added extra certificates
 * v2.20 Jul 2024
 * - fixed earlybird discount timing bug that meant it was ignoring the expiry data
 * v2.19 Jul 2024
 * - added zoom chat
 * v2.18 Jul 2024
 * - slug now online-training
 * v2.17 Jun 2024
 * - retirval of recording price now allows for historical lookup
 * v2.16 Apr 2024
 * - single recording now says "registration closed" if it is closed
 * v2.15 Jan 2024
 * - moved the voucher offer on the training pages
 * v2.14 Jan 2024
 * - Added resource and knowledge hub
 * v2.13 Oct 2023
 * - Added student discount
 * 1 - comment added re CE credit requirements 
 * v2.12 Aug 2023
 * - added CE credits stuff
 * v2.11 Jun 2023
 * - extended the What to show section across training (already on pages and posts) and added options to set height and wash opacity
 * v2.10 Jun 2023
 * - bug fix added stripslashed when saving trainign accordions
 * v2.9 May 2023
 * - recordings can now be set to "available but not listed"
 * - bug fix for recording card not being shown for recordings that use module videos instead of the main vimeo id
 * v2.8 May 2023
 * - extra function to get all recordings needed for the sales stats from Mailster
 * v2.7 Apr 2023
 * - added default training accordions
 * v2.6 Mar 2023
 * - added training accordions
 * v2.5 Mar 2023
 * - extra recording info - recording availability
 * v2.4 Feb 2023
 * - fixed link to Oli's recording on My acct page
 * - removed all '+ 0' sanitisation
 * v2.3 Feb 2023
 * - add extra recording info
 * - recording does not require first video
 * - recording archive page only shows recordings availabe to purchase/public
 * v2.2 Fab 2023
 * - added earlybird
 * v2.1 Jan 2023
 * - bug - correct link to watch now
 * v2.0 Sep 2022
 * - accommodate new theme
 * v1.14 Sep 2022
 * - added recording modules
 * v1.13 Jun 2022
 * - allow for "no role"
 * v1.12 Jan 2022
 * - added resource files
 * v1.11 Nov 2021
 * - added field for date of original workshop
 * v1.10 Nov 2021
 * - changed the link to the recordings payment page to allow the currency switcher to actually work on the payment page
 * v1.9 Sep 2021
 * - changes to accommodate the recordings users table and my account stuff
 * v1.8 Jul 2021
 * - VAT now (sometimes) applies to all currencies
 * v1.7.0 Mar 2021
 * - changed recording expiry time to be midnight
 * - adds new email to tell viewers of a new recording access expiry
 * - now accommodates only doing the reset for selected access times and also can set a specific expiry date
 * - added email reminder a few days before a recording expires for a user
 * v1.6.0 Feb 2021
 * - allowed more time in JS for the recording expiry reset to start
 * v1.5.0 Jan 2021
 * - Added recording expiry option and processes
 * - now includes Action Scheduler (https://actionscheduler.org/)
 * v1.4.6 Sep 2020
 * - added optional email message
 * v1.4.5 Sep 2020
 * - added the option of ids to the recording-list shortcode
 * v1.4.4 Jul 2020
 * - added promo codes for recordings
 * v1.4.3 Jul 2020
 * - allowed for publicly visible recordings
 * v1.4.2 Jun 2020
 * - added option of recording being unavailable for purchase
 * v1.4.1 May 2020
 * - moved enqueues to correct hook
 * - tidied up the recordings page
 * v1.4. Apr 2020
 * - multi-currency 
 * v1.3 Apr 2020
 * - Added Vimeo
 * v1.2 Apr 2020
 * - paid recordings
 * v1.1 Fab 2020
 * - added recording URL
 * v1.0 Jan 2019
 * - created based on workshops plugin v 1.1
 */

if(!class_exists('recordings_plugin')) {
	
	class recordings_plugin {

		public function __construct() {
			// Register custom post types
			require_once(sprintf("%s/post-types/recordings-post.php", dirname(__FILE__)));
			$Post_Type_Template = new Recording_Post_Type_Template();
			require_once(sprintf("%s/inc/recording-archive.php", dirname(__FILE__)));
			require_once(sprintf("%s/inc/recording-cards.php", dirname(__FILE__)));
			require_once(sprintf("%s/inc/recording-pricing.php", dirname(__FILE__)));
			$plugin = plugin_basename(__FILE__);
			add_filter("plugin_action_links_$plugin", array( $this, 'plugin_settings_link' ));

			// Shortcode for homepage
			add_shortcode( 'recording', array( $this , 'recording_post_shortcode' ) );
			add_shortcode( 'recording-list', array( $this , 'recording_archive_shortcode' ) );

			// Get custom post template
			add_filter( "single_template", array( $this, "get_custom_post_type_template" ) );
			add_filter( 'page_template', array( $this, 'wpa3396_page_template' ) );
			add_filter( 'single-page_template', array( $this, 'wpa3396_page_template' ) );

			// Add new image size for homepage
			add_image_size( 'recording', 300, 200);

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueues' ) );

			// added v1.5.0
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueues' ) );

		} // END public function __construct

		public static function activate() {
			// Do nothing
		} // END public static function activate

		public static function deactivate() {
			// Do nothing
		} // END public static function deactivate

		public function enqueues(){
			// Get custom CSS
			wp_register_style( 'recording-style', plugins_url( 'recordings/css/style.css' ), array(), '2.23.0' );
			wp_enqueue_style( 'recording-style' );
			// Get JQuery Cycle scripts for homepage slider
			wp_enqueue_script( 'cycle2', plugins_url( '/js/jquery.cycle2.js', __FILE__ ), array('jquery'));
			wp_enqueue_script( 'carousel', plugins_url( '/js/jquery.cycle2.carousel.js', __FILE__ ), array('jquery', 'cycle2'));
			wp_enqueue_script( 'center', plugins_url( '/js/jquery.cycle2.center.js', __FILE__ ), array('jquery', 'cycle2'));
			wp_enqueue_script( 'recordings', plugins_url( '/js/recording2.js', __FILE__ ), array('jquery', 'carousel'));
		}

		public function admin_enqueues($hook_suffix){
			if( in_array($hook_suffix, array('post.php', 'post-new.php') ) ){
				$screen = get_current_screen();
				if( is_object( $screen ) && $screen->post_type == 'recording' ){
					wp_register_script( 'recording-admin-js', plugins_url( '/js/recordings-admin.js', __FILE__ ), array('jquery'), '2.23.0');
					wp_localize_script( 'recording-admin-js', 'rpmAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
				    wp_enqueue_script( 'recording-admin-js' );
					wp_enqueue_style( 'recording-admin-css', plugins_url( '/css/admin.css', __FILE__ ), array(), '2.23.0' );
				}
			}
		}

		// Add the settings link to the plugins page
		function plugin_settings_link($links) {
			$settings_link = '<a href="edit.php?post_type=recordings_template">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
		}

		// Shortcode for homepage [recording]
        function recording_post_shortcode($atts) {
            $args = array(
            	'post_type' => 'recording',
            	'posts_per_page' => 99,
            	'orderby'   => 'meta_value_num',
            	'meta_key'  => 'recording_timestamp',
            	'order' => 'ASC'
            );
            $loop = new WP_Query($args);
            $num_recordings = 0;
            $inner_html = '';
            while ( $loop->have_posts() ) : $loop->the_post();
            	$recording_id = get_the_ID();
                $num_recordings ++;
                $permalink = get_permalink();
                $image = get_the_post_thumbnail( $recording_id, 'recording' );
                $title = get_the_title(); 
                $excerpt = get_the_excerpt(); 
                $inner_html .= '<div class="recording-box recording-'.$num_recordings.'">';
                $inner_html .= '<a href="'.$permalink.'" title="'.esc_attr($title).'">'.$image.'</a>';
				$inner_html .= '<div class="recording-text">';
				$inner_html .= '<h3>'.$title.'</h3>';
				$inner_html .= '<p>'.$excerpt.'</p>';
				$inner_html .= '</div>';
				$inner_html .= '<a href="'.$permalink.'" class="wms-button">Find Out More</a>';
				$inner_html .= '</div>';
            endwhile;

            switch ($num_recordings) {
            	case 0:
            		$Html = '';
            		break;
        		case 1:
        			$HTML = '<div class="recording-wrapper one-recording">';
        			$HTML .= $inner_html;
        			$HTML .= '</div><!-- .recording-wrapper -->';
        			break;
        		case 2:
        			$HTML = '<div class="recording-wrapper two-recordings">';
        			$HTML .= $inner_html;
        			$HTML .= '</div><!-- .recording-wrapper -->';
        			break;
        		case 3:
        			$HTML = '<div class="recording-wrapper three-recordings">';
        			$HTML .= $inner_html;
        			$HTML .= '</div><!-- .recording-wrapper -->';
        			break;
            	default:
		            $HTML = '<div class="recording-wrapper many-recordings" data-cycle-fx=carousel  data-cycle-pause-on-hover="true" data-cycle-carousel-visible=3 data-cycle-timeout=8000 data-cycle-next="#nextid" data-cycle-prev="#previd" data-cycle-slides="> div" data-cycle-carousel-fluid=true>';
        			$HTML .= $inner_html;
		            $HTML .= '</div><!-- .recording-wrapper -->';
		            $HTML .= '<div class="carouselcontrols"><a href="#" id="previd"><i class="fa fa-angle-left "></i></a><a href="#" id="nextid"><i class="fa fa-angle-right "></i></a></div>';
            		break;
            }

            return $HTML;
        }

        // shortcode for archive type page to list all recordings
        // [recording-list ids="1, 2, 3"]
        function recording_archive_shortcode($atts){
			$a = shortcode_atts(array(
				'ids' => '',
			), $atts);
			$ids = "{$a['ids']}";
			$post_ids = array();
			if($ids <> ''){
				$post_ids = array_map('trim', explode(',', $ids));
			}
            $args = array(
            	'post_type' => 'recording',
            	'posts_per_page' => 99,
            );
            if(!empty($post_ids)){
            	$args['post__in'] = $post_ids;
            }
            $loop = new WP_Query($args);
            // $HTML = '<div class="recording-archive '.$atts['timespan'].'-recordings">';
            $HTML = '<div class="recording-archive">';
            $now = time();
            while ( $loop->have_posts() ) : $loop->the_post();
                $recording_id = get_the_ID();
                $recording_for_sale = get_post_meta($recording_id, 'recording_for_sale', true);
                if($recording_for_sale <> 'closed' && $recording_for_sale <> 'unlisted'){
	                $permalink = get_permalink();
	                $image = get_the_post_thumbnail( $recording_id, 'recording', array( 'class' => 'recording-featimg' ) );
	                $title = get_the_title();
	                // get_the_excerpt without it being filtered etc
	                // $summary = get_the_excerpt($recording_id);
	                $summary = '';
	                $post = get_post( $recording_id );
	                if(!empty($post)){
	                	$summary = $post->post_excerpt;
	                }
	                $HTML .= '<div class="recording-item">';
	                $HTML .= '<a href="'.$permalink.'" title="'.esc_attr($title).'">'.$image.'</a>';
					$HTML .= '<div class="recording-archive-text">';
					$HTML .= '<h3>'.$title.'</h3>';
					$HTML .= '<p>'.$summary.'</p>';
					$HTML .= '<a href="'.$permalink.'" class="wms-button alignright">Find Out More</a>';
					$HTML .= '<div class="clear"></div>';
					$HTML .= '</div>';
					$HTML .= '<div class="clear"></div>';
	                $HTML .= '</div><!-- .recording-item -->';
	            }
            endwhile;
            $HTML .= '</div><!-- .recording-archive -->';
            return $HTML;
        }

        // Get template for recording
        function get_custom_post_type_template($single_template) {
		    global $post;
		    if($post->post_title == 'Register'){
		        $single_template = dirname( __FILE__ ) . '/templates/register.php';
		    }
		    if ($post->post_type == 'recording') {
		          $single_template = dirname( __FILE__ ) . '/templates/single-recording.php';
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
	} // END class recordings_plugin
} // END if(!class_exists('recordings_plugin'))

if(class_exists('recordings_plugin')) {
	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('recordings_plugin', 'activate'));
	register_deactivation_hook(__FILE__, array('recordings_plugin', 'deactivate'));

	// instantiate the plugin class
	$recordings_plugin = new Recordings_Plugin();
}

include 'page-templater.php';
if(file_exists(plugin_dir_path( __FILE__ ).'inc/expiry.php')){
    require plugin_dir_path( __FILE__ ).'inc/expiry.php';
}
if(file_exists(plugin_dir_path( __FILE__ ).'inc/reminders.php')){
    require plugin_dir_path( __FILE__ ).'inc/reminders.php';
}
if(file_exists(plugin_dir_path( __FILE__ ).'inc/recordings-functions.php')){
    require plugin_dir_path( __FILE__ ).'inc/recordings-functions.php';
}
// Action Scheduler (https://actionscheduler.org/) used for the background recording expiry updates
// require_once( plugin_dir_path( __FILE__ ) . '/action-scheduler-3.1.6/action-scheduler.php' );
require_once( plugin_dir_path( __FILE__ ) . '/action-scheduler-3.9.3/action-scheduler.php' );