<?php
/**
 * Contextual functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Contextual
 */

define('CNWL_PASSCODE', 'CNWL2022!');
define('NLFT_PASSCODE', 'TrainACTNLFT');

if ( ! function_exists( 'contextual_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function contextual_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on Contextual, use a find and replace
		 * to change 'contextual' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'contextual', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus( array(
			'primary' => esc_html__( 'Primary', 'contextual' ),
		) );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		// Set up the WordPress core custom background feature.
		add_theme_support( 'custom-background', apply_filters( 'contextual_custom_background_args', array(
			'default-color' => 'ffffff',
			'default-image' => '',
		) ) );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support( 'custom-logo', array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		) );
	}
endif;
add_action( 'after_setup_theme', 'contextual_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function contextual_content_width() {
	// This variable is intended to be overruled from themes.
	// Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$GLOBALS['content_width'] = apply_filters( 'contextual_content_width', 640 );
}
add_action( 'after_setup_theme', 'contextual_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function contextual_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Footer 1', 'contextual' ),
        'id'            => 'footer-1',
        'description'   => 'The first section in the footer',
        'before_widget' => '<aside id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</aside>',
        'before_title'  => '<h3 class="footer-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Footer 2', 'contextual' ),
        'id'            => 'footer-2',
        'description'   => 'The second section in the footer',
        'before_widget' => '<aside id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</aside>',
        'before_title'  => '<h3 class="footer-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Footer 3', 'contextual' ),
        'id'            => 'footer-3',
        'description'   => 'The third section in the footer',
        'before_widget' => '<aside id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</aside>',
        'before_title'  => '<h3 class="footer-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Footer 4', 'contextual' ),
        'id'            => 'footer-4',
        'description'   => 'The fourth section in the footer. If not used, the other 3 sections above will each use one-third of the width.',
        'before_widget' => '<aside id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</aside>',
        'before_title'  => '<h3 class="footer-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Sub-Footer', 'contextual' ),
        'id'            => 'sub-footer-1',
        'description'   => 'Full page width underneath the main footer',
        'before_widget' => '<aside id="%1$s" class="sub-footer-widget %2$s">',
        'after_widget'  => '</aside>',
        'before_title'  => '<h3 class="footer-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'SEO Footer 1', 'contextual' ),
        'id'            => 'seofooter-1',
        'description'   => 'The first section in the lower footer',
        'before_widget' => '<aside id="%1$s" class="seofooter-widget %2$s">',
        'after_widget'  => '</aside>',
        'before_title'  => '<h3 class="seofooter-widget-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'SEO Footer 2', 'contextual' ),
        'id'            => 'seofooter-2',
        'description'   => 'The second section in the lower footer',
        'before_widget' => '<aside id="%1$s" class="seofooter-widget %2$s">',
        'after_widget'  => '</aside>',
        'before_title'  => '<h3 class="seofooter-widget-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'SEO Footer 3', 'contextual' ),
        'id'            => 'seofooter-3',
        'description'   => 'The third section in the lower footer',
        'before_widget' => '<aside id="%1$s" class="seofooter-widget %2$s">',
        'after_widget'  => '</aside>',
        'before_title'  => '<h3 class="seofooter-widget-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'SEO Footer 4', 'contextual' ),
        'id'            => 'seofooter-4',
        'description'   => 'The fourth section in the lower footer',
        'before_widget' => '<aside id="%1$s" class="seofooter-widget %2$s">',
        'after_widget'  => '</aside>',
        'before_title'  => '<h3 class="seofooter-widget-title">',
        'after_title'   => '</h3>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Footer Keywords', 'contextual' ),
        'id'            => 'footerkeywords-1',
        'description'   => 'The keyword section at the bottom of the site',
        'before_widget' => '<aside id="%1$s" class="footerkeywords-widget %2$s">',
        'after_widget'  => '</aside>',
        'before_title'  => '<h3 class="footerkeywords-widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'contextual_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function contextual_scripts() {
    global $rpm_theme_options;
    global $post;
	$contextual_theme = wp_get_theme();
	$theme_ver = $contextual_theme->get('Version');
    wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0' );
    // IMPORTANT!
    // If you have a version number on the style then WP parses the URL parameters and removes duplicates. Therefore, only ONE font will be enqueued by the following command (the last one)
    // As a workaround for that, set the version to null and then WP will not parse the URL
    // wp_enqueue_style('contextual-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap', array(), null);
    // wp_enqueue_style('contextual-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Questrial&display=swap', array(), null);
    wp_enqueue_style('contextual-fonts', 'https://use.typekit.net/nyw0zqj.css', array(), null);
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', array(), '6.5.2' );
	// wp_enqueue_style( 'contextual-style', get_stylesheet_uri(), array(), $theme_ver );
    wp_enqueue_style( 'contextual-style', get_stylesheet_uri(), array(), filemtime( get_stylesheet_directory() . '/style.css' ) );
	wp_add_inline_style( 'contextual-style', wms_options_css() );
    if(file_exists(get_stylesheet_directory().'/inc/custom-styles.css')){
        wp_enqueue_style('custom-styles', get_stylesheet_directory_uri().'/inc/custom-styles.css', array('contextual-style'));
    }
    wp_enqueue_script( 'bootstrap-min-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.3.0', true );
    if($rpm_theme_options['google-maps-browser-api'] <> ''){
        wp_enqueue_script( 'google-maps', 'https://maps.googleapis.com/maps/api/js?key='.$rpm_theme_options['google-maps-browser-api'], array(), '3');
    }
    wp_enqueue_script( 'lazy-load-remastered', 'https://cdn.jsdelivr.net/npm/lazyload@2.0.0-rc.2/lazyload.js', array(), '2.0.0' );

    $contextual_script_path = get_template_directory() . '/js/contextual.js';
    $contextual_script_uri  = get_template_directory_uri() . '/js/contextual.js';
    $contextual_script_ver  = filemtime( $contextual_script_path );
    wp_register_script( 'contextual-script', $contextual_script_uri, array('jquery', 'jquery-ui-autocomplete'), $contextual_script_ver, true);
	// wp_register_script( 'contextual-script', get_template_directory_uri() . '/js/contextual.js', array('jquery', 'jquery-ui-autocomplete'), $theme_ver, true);
	wp_localize_script( 'contextual-script', 'ccAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
    wp_enqueue_script( 'contextual-script' );

    if(is_page_template( 'page-registration.php' ) || is_page_template( 'page-payment-details.php' ) || is_page_template( 'page-reg-confirmation.php' ) ){
        $registration_script_path = get_template_directory() . '/js/registration.js';
        $registration_script_uri  = get_template_directory_uri() . '/js/registration.js';
        $registration_script_ver  = filemtime( $registration_script_path );
        wp_register_script( 'registration-script', $registration_script_uri, array('jquery'), $registration_script_ver, true);
        
        // Enhanced ccAjax with current user data for standardized attendee processing
        $current_user = wp_get_current_user();
        wp_localize_script('registration-script', 'ccAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'current_user_id' => $current_user->ID,
            'current_user_firstname' => get_user_meta($current_user->ID, 'first_name', true),
            'current_user_lastname' => get_user_meta($current_user->ID, 'last_name', true),
            'current_user_email' => $current_user->user_email,
        ));        
        wp_enqueue_script( 'registration-script' );
    }
    /*
    if(is_page_template( 'page-registration.php' ) || is_page_template( 'page-payment-details.php' ) || is_page_template( 'page-reg-confirmation.php' ) ){
        $registration_script_path = get_template_directory() . '/js/registration.js';
        $registration_script_uri  = get_template_directory_uri() . '/js/registration.js';
        $registration_script_ver  = filemtime( $registration_script_path );
        wp_register_script( 'registration-script', $registration_script_uri, array('jquery'), $registration_script_ver, true);
        wp_localize_script( 'registration-script', 'ccAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
        wp_enqueue_script( 'registration-script' );
    }
    */
	wp_enqueue_script( 'contextual-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20151215', true );
    if(file_exists(get_stylesheet_directory().'/inc/custom-script.js')){
        wp_enqueue_script('custom-script', get_stylesheet_directory_uri().'/inc/custom-script.js', array('jquery'), '', true );
    }
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
    if(is_page_template('page-cc-login.php') || is_page_template('page-cc-login-2.php')){
        wp_enqueue_script( 'password-strength-meter' );
        wp_register_script( 'cc-login-scripts', get_stylesheet_directory_uri() . '/js/login-2.js', array('jquery'), $theme_ver, true);
        wp_localize_script( 'cc-login-scripts', 'rpmAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
        wp_enqueue_script( 'cc-login-scripts' );
    }
    if(is_page_template('page-my-account.php')){
        wp_enqueue_script( 'password-strength-meter' );
        wp_register_script( 'myacct-details-scripts', get_stylesheet_directory_uri() . '/js/myacct-details.js', array('jquery'), $theme_ver, true);
        wp_localize_script( 'myacct-details-scripts', 'rpmAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
        wp_register_script( 'myacct-workshops-scripts', get_stylesheet_directory_uri() . '/js/myacct-workshops.js', array('jquery'), $theme_ver, true);
        wp_localize_script( 'myacct-workshops-scripts', 'rpmAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
        wp_register_script( 'myacct-recordings-scripts', get_stylesheet_directory_uri() . '/js/myacct-recordings.js', array('jquery'), $theme_ver, true);
        wp_localize_script( 'myacct-recordings-scripts', 'rpmAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
        wp_register_script( 'myacct-merge-scripts', get_stylesheet_directory_uri() . '/js/myacct-merge.js', array('jquery'), $theme_ver, true);
        wp_localize_script( 'myacct-merge-scripts', 'rpmAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
        wp_enqueue_script( 'vimeo-script', 'https://player.vimeo.com/api/player.js');
        wp_register_script( 'watch_video_script', get_stylesheet_directory_uri() . '/js/myacct-video.js', array('jquery'), $theme_ver, true);
        wp_localize_script( 'watch_video_script', 'ccAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
        wp_enqueue_script( 'watch_video_script' );
    }

    if(is_page_template('page-training-delivery.php')){

        wp_enqueue_script( 'vimeo-script', 'https://player.vimeo.com/api/player.js');

        $training_delivery_script_path = get_template_directory() . '/js/training-delivery.js';
        $training_delivery_script_uri  = get_template_directory_uri() . '/js/training-delivery.js';
        $training_delivery_script_ver  = filemtime( $training_delivery_script_path );
        wp_register_script( 'training_delivery_script', $training_delivery_script_uri, array('jquery'), $training_delivery_script_ver, true);
        wp_localize_script( 'training_delivery_script', 'wpApiSettings', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'root' => esc_url_raw(rest_url())
        ));
        wp_enqueue_script( 'training_delivery_script' );
    }

    // Check if we're on a single page AND it's your specific custom post type
    if ( is_singular( array( 'course', 'series', 'workshop' ) ) ){
        $script_path = get_template_directory() . '/js/training-course-series.js';
        $script_uri  = get_template_directory_uri() . '/js/training-course-series.js';
        $script_ver  = filemtime( $script_path );
        wp_register_script( 'training_course_series_script', $script_uri, array('jquery'), $script_ver, true);
        wp_localize_script( 'training_course_series_script', 'wpApiSettings', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'root' => esc_url_raw(rest_url())
        ));
        wp_enqueue_script( 'training_course_series_script' );
    }

    // To best leverage Stripe’s advanced fraud functionality, include this script on every page on your site, not just the checkout page. Including the script on every page allows Stripe to detect anomalous behavior that may be indicative of fraud as users browse your website.
    wp_enqueue_script('stripe_script', 'https://js.stripe.com/v3/', array(), '3', true); // in footer
    if( is_page_template( 'page-payment.php' ) ){
        $script_path = get_template_directory() . '/js/payment.js';
        $script_uri  = get_template_directory_uri() . '/js/payment.js';
        $script_ver  = filemtime( $script_path );
        wp_register_script('cc-payment-scripts', $script_uri, array('jquery'), $script_ver, true);
        wp_localize_script('cc-payment-scripts', 'ccAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
        wp_enqueue_script( 'cc-payment-scripts' );
    }
}
add_action( 'wp_enqueue_scripts', 'contextual_scripts' );

add_action( 'admin_enqueue_scripts', 'cc_admin_scripts' );
function cc_admin_scripts($hook){
    $contextual_theme = wp_get_theme();
    $theme_ver = $contextual_theme->get('Version');

    // switch from theme version to time
    $admin_css_path = get_stylesheet_directory() . '/css/admin.css';
    $admin_css_uri  = get_stylesheet_directory_uri() . '/css/admin.css';
    $admin_css_ver  = filemtime($admin_css_path);    
    wp_enqueue_style('admin-styles', $admin_css_uri, array(), $admin_css_ver);

    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css', array(), '6.1.1' );
    wp_enqueue_style( 'jquery-ui-css', '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', array(), '1.13.2' );
    wp_enqueue_script( 'jquery-ui-tooltip' );

    // switch from theme version to time
    $admin_js_path = get_stylesheet_directory() . '/js/admin.js';
    $admin_js_uri  = get_stylesheet_directory_uri() . '/js/admin.js';
    $admin_js_ver  = filemtime($admin_js_path);
    wp_register_script( 'contextual-admin-js', $admin_js_uri, array('jquery', 'jquery-ui-tooltip'), $admin_js_ver);
    // more efficent localisation ......
    wp_localize_script('contextual-admin-js', 'ContextualData', [
        'rpmAjax' => ['ajaxurl' => admin_url('admin-ajax.php')],
        'resourceHub' => [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('resource_hub_nonce'),
        ],
        'upsellSearch' => [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('upsell_search_nonce'),
        ],
        'invoiceDomains' => [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ccpa_invoice_domain_nonce'),
        ],
    ]);
    /* was ...
    wp_localize_script( 'contextual-admin-js', 'rpmAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
    wp_localize_script( 'contextual-admin-js', 'resourceHub', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('resource_hub_nonce')
    ]);
    wp_localize_script( 'contextual-admin-js', 'upsellSearch', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('upsell_search_nonce')
    ]);
    */
    wp_enqueue_script( 'contextual-admin-js' );

    // we'll register the admin script/styles but only enqueue them when needed
    wp_register_script('ccpa-pay-edit-scripts', get_stylesheet_directory_uri().'/js/payment-edit.js', array('jquery'), $theme_ver, true);
    wp_localize_script('ccpa-pay-edit-scripts', 'ccAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
    wp_register_style('ccpa-pay-edit-style', get_stylesheet_directory_uri().'/css/payment-edit.css', array(), $theme_ver);
    wp_enqueue_media(); // added for category images
    // Tempus Dominus date/time picker
    // Popper.js (required for tooltips/dropdowns)
    wp_enqueue_script( 'popper-js', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js', array(), null, true );
    wp_enqueue_script( 'tempus-dominus-js', 'https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.9.4/dist/js/tempus-dominus.min.js', array('jquery', 'popper-js'), null, true );
    wp_enqueue_style( 'tempus-dominus-css', 'https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.9.4/dist/css/tempus-dominus.min.css', array(), null );
    // Enqueue Scripts for Drag-and-Drop Sorting & AJAX Saving
    if ( get_current_screen()->post_type == 'course' || get_current_screen()->post_type == 'series' ) {
        wp_enqueue_script('jquery-ui-sortable');
    }
    if ($hook === 'quiz_page_quiz-user-results') {
        $js_file = get_template_directory() . '/js/quiz-results-admin.js';
        wp_enqueue_script(
            'quiz-results-admin',
            get_template_directory_uri() . '/js/quiz-results-admin.js',
            array('jquery'),
            file_exists($js_file) ? filemtime($js_file) : '1.0.0',
            true
        );
        wp_localize_script('quiz-results-admin', 'quizResultsAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('quiz_details_nonce')
        ));
        $css_file = get_template_directory() . '/css/quiz-results-admin.css';
        wp_enqueue_style(
            'quiz-results-admin',
            get_template_directory_uri() . '/css/quiz-results-admin.css',
            array(),
            file_exists($css_file) ? filemtime($css_file) : '1.0.0'
        );
    }
}

/**
 * Implement the Custom Header feature.
 */
// require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

/**
 * Register Custom Navigation Walker
 */
function contextual_register_navwalker(){
	require_once get_template_directory() . '/inc/class-wp-bootstrap-navwalker.php';
}
add_action( 'after_setup_theme', 'contextual_register_navwalker' );

/**
 * Include the theme options via the Redux Options Framework
 */
// require_once( get_template_directory() . '/inc/loader.php' );
require_once( get_template_directory() . '/inc/contextual-config.php' );

// Remove Redux Ads
function contextual_remove_redux_ads() {
?>
<style type="text/css">
.rAds span {display: none !important;}
</style>
<?php
}
add_action('admin_head', 'contextual_remove_redux_ads');

// change the WP option so that images do not automatically have links
function rpm_imagelink_setup() {
    $image_set = get_option( 'image_default_link_type' );
    if ($image_set !== 'none') {
        update_option('image_default_link_type', 'none');
    }
}
add_action('admin_init', 'rpm_imagelink_setup', 10);

// remove the inner-page class from the body class for the 404 page
add_filter( 'body_class', function( $classes ) {
    if( is_404() ){
        unset( $classes[array_search( 'inner-page', $classes )] );
    }
    return $classes;
} );

/**
 * Add the Contextual header functions
 */
if(file_exists(get_stylesheet_directory().'/inc/contextual-header.php')){
    require get_stylesheet_directory().'/inc/contextual-header.php';
}

/**
 * Include WMS
 */
if(file_exists(get_stylesheet_directory().'/inc/wms.php')){
    require get_stylesheet_directory().'/inc/wms.php';
}

/**
 * Include any custom functions for this client if there are any
 */
if(file_exists(get_stylesheet_directory().'/inc/custom-functions.php')){
    require get_stylesheet_directory().'/inc/custom-functions.php';
}

// add all the contextual consulting specific functions
$cc_funcs = array('users', 'sections', 'resources', 'myacct-menu', 'myacct-details', 'myacct-workshops', 'myacct-recordings', 'myacct-merge', 'currencies', 'timezone-db', 'timezones', 'faqs', 'registration', 'login', 'sys-control', 'payint-countries', 'payint-client-secret', 'mailster-interface', 'discounts', 'vouchers-table', 'vouchers-core', 'vouchers-admin', 'vouchers-list', 'vouchers-usage', 'vouchers-alloc', 'voucher-offers', 'payment', 'stripe-bits', 'phrases', 'payment-db', 'payment-admin', 'edit-payment', 'stripe-fee', 'emails', 'pdf-support', 'recorded-workshops', 'navigation', 'presenters', 'free-registration', 'viewers', 'cancellation', 'cnwl-stats', 'cert-link', 'testimonials', 'debug', 'cnwl_jobs', 'hacks', 'site-faqs', 'site-faq-meta', 'training-accordions', 'professions', 'train-news-cards', 'sales-stats', 'sales-stats-page', 'ce-credits', 'feedback', 'attendees', 'quizzes', 'feedback-reporting', 'ce-cert-stats', 'loop', 'friend', 'friend-list', 'myacct-friend', 'user-analysis', 'topics', 'resource-hub', 'knowledge-hub', 'categories', 'attendance-stats', 'act-value-cards', 'act-value-cards-panels', 'avc-orders-page', 'training', 'redirects', 'prize-draw', 'facebook', 'zoom-chat', 'certificates', 'cookies', 'avatars', 'training-search', 'site-search', 'search-terms', 'upsells', 'topics-filter', 'therapy-contact', 'xero', 'myacct-orders', 'feedback-reminders', 'courses-db', 'courses-edit', 'courses-migration', 'nlft-bits', 'nlft-stats', 'courses', 'myacct-dashboard', 'dashboard', 'act-supervision-form', 'course-series', 'invoicing', 'vimeo', 'payment-pending', 'preview', 'viewing-stats', 'bulk-earlybird-discount', 'training-groups', 'temp-registrations', 'mailster-form', 'ip-geolocation', 'mailster-ebook-code', 'organisation-management', 'super-merge', 'user-quiz-results-admin', 'autoresponder-status', 'video-tracking-monitoring', 'training-register-btn', 'training-access-rules', 'training-access-metabox', 'payment-add-to-acct', 'portal-contract-banner' );
foreach ($cc_funcs as $cc_func) {
    if(file_exists(get_stylesheet_directory().'/cc/'.$cc_func.'.php')){
        require get_stylesheet_directory().'/cc/'.$cc_func.'.php';
    }
}