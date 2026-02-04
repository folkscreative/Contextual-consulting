<?php
/**
 * ReduxFramework Config File for the Contextual theme
 */

if ( ! class_exists( 'Redux' ) ) {
    return;
}

// This is your option name where all the Redux data is stored.
$opt_name = "contextual_options";

// If Redux is running as a plugin, this will remove the demo notice and links
add_action( 'redux/loaded', 'remove_demo' );

/**
 * ---> SET ARGUMENTS
 * For full documentation on arguments, please refer to: https://github.com/ReduxFramework/ReduxFramework/wiki/Arguments
 * */

$theme = wp_get_theme(); // For use with some settings. Not necessary.

$args = array(
    // TYPICAL -> Change these values as you need/desire
    'opt_name'             => $opt_name,
    // This is where your data is stored in the database and also becomes your global variable name.
    'display_name'         => $theme->get( 'Name' ),
    // Name that appears at the top of your panel
    'display_version'      => $theme->get( 'Version' ),
    // Version that appears at the top of your panel
    'menu_type'            => 'menu',
    //Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only)
    'allow_sub_menu'       => true,
    // Show the sections below the admin menu item or not
    'menu_title'           => __( 'Contextual Options', 'contextual' ),
    'page_title'           => __( 'Contextual Options', 'contextual' ),
    // You will need to generate a Google API key to use this feature.
    // Please visit: https://developers.google.com/fonts/docs/developer_api#Auth
    'google_api_key'       => 'AIzaSyA9Dg-t6ZZhHp4fLLPzmtHvcAUYT3yaSto',
    // Set it you want google fonts to update weekly. A google_api_key value is required.
    'google_update_weekly' => true,
    // Must be defined to add google fonts to the typography module
    'async_typography'     => true,
    // Use a asynchronous font on the front end or font string
    //'disable_google_fonts_link' => true,                    // Disable this in case you want to create your own google fonts loader
    'admin_bar'            => true,
    // Show the panel pages on the admin bar
    'admin_bar_icon'       => 'dashicons-portfolio',
    // Choose an icon for the admin bar menu
    'admin_bar_priority'   => 50,
    // Choose an priority for the admin bar menu
    'global_variable'      => 'rpm_theme_options',
    // Set a different name for your global variable other than the opt_name
    'dev_mode'             => false,
    // Show the time the page took to load, etc
    'update_notice'        => true,
    // If dev_mode is enabled, will notify developer of updated versions available in the GitHub Repo
    'customizer'           => false,
    // Enable basic customizer support
    //'open_expanded'     => true,                    // Allow you to start the panel in an expanded way initially.
    //'disable_save_warn' => true,                    // Disable the save warning when a user changes a field

    // OPTIONAL -> Give you extra features
    'page_priority'        => '90',
    // Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
    'page_parent'          => 'themes.php',
    // For a full list of options, visit: http://codex.wordpress.org/Function_Reference/add_submenu_page#Parameters
    'page_permissions'     => 'manage_options',
    // Permissions needed to access the options panel.
    'menu_icon'            => '',
    // Specify a custom URL to an icon
    'last_tab'             => '',
    // Force your panel to always open to a specific tab (by id)
    'page_icon'            => 'icon-themes',
    // Icon displayed in the admin panel next to your menu_title
    'page_slug'            => 'contextual_options',
    // Page slug used to denote the panel, will be based off page title then menu title then opt_name if not provided
    'save_defaults'        => true,
    // On load save the defaults to DB before user clicks save or not
    'default_show'         => false,
    // If true, shows the default value next to each field that is not the default value.
    'default_mark'         => '',
    // What to print by the field's title if the value shown is default. Suggested: *
    'show_import_export'   => true,
    // Shows the Import/Export panel when not used as a field.

    // CAREFUL -> These options are for advanced use only
    'transient_time'       => 60 * MINUTE_IN_SECONDS,
    'output'               => true,
    // Global shut-off for dynamic CSS output by the framework. Will also disable google fonts output
    'output_tag'           => true,
    // Allows dynamic CSS to be generated for customizer and google fonts, but stops the dynamic CSS from going to the head
    // 'footer_credit'     => '',                   // Disable the footer credit of Redux. Please leave if you can help it.

    // FUTURE -> Not in use yet, but reserved or partially implemented. Use at your own risk.
    'database'             => '',
    // possible: options, theme_mods, theme_mods_expanded, transient. Not fully functional, warning!
    'system_info'          => false,
    // REMOVE
    'use_cdn'              => true,
    // If you prefer not to use the CDN for Select2, Ace Editor, and others, you may download the Redux Vendor Support plugin yourself and run locally or embed it in your code.
    //'compiler'             => true,
    // HINTS
    'hints'                => array(
        'icon'          => 'el el-question-sign',
        'icon_position' => 'right',
        'icon_color'    => 'lightgray',
        'icon_size'     => 'normal',
        'tip_style'     => array(
            'color'   => 'red',
            'shadow'  => true,
            'rounded' => false,
            'style'   => '',
        ),
        'tip_position'  => array(
            'my' => 'top left',
            'at' => 'bottom right',
        ),
        'tip_effect'    => array(
            'show' => array(
                'effect'   => 'slide',
                'duration' => '500',
                'event'    => 'mouseover',
            ),
            'hide' => array(
                'effect'   => 'slide',
                'duration' => '500',
                'event'    => 'click mouseleave',
            ),
        ),
    ),
    'load_on_cron'          => true,
);


// Add content after the form.
$args['footer_text'] = __( '<p>For help with these options, get in touch with us: <a href="mailto:info@saasora.com">info@saasora.com</a> or Tel: 01554 775 738.</p>', 'contextual' );
Redux::setArgs( $opt_name, $args );

/*
 * ---> END ARGUMENTS
 */

/*
 * ---> START SECTIONS
 */

// -> START Business Fields

Redux::setSection( $opt_name, array(
    'title' => __( 'Your Business', 'contextual' ),
    'id'    => 'your-business',
    'desc'  => __( '', 'contextual' ),
    'icon'  => 'el el-dashboard'
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Business Name', 'contextual' ),
    'id'         => 'business-name-sect',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'business-name',
            'type'     => 'text',
            'title'    => 'Business Name',
            'subtitle' => 'The name of your business or organisation',
            'default'  => get_bloginfo('name'),
        ),
    )
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Location', 'contextual' ),
    'desc'       => 'Address and location',
    'id'         => 'your-business-location',
    'subsection' => true,
    'fields'     => array(
        /*
        array(
            'id'       => 'google-maps-server-api',
            'type'     => 'text',
            'title'    => 'Google Maps Server API',
            'subtitle' => 'Google Maps Server API Key',
            'desc'     => 'You probably do not need to set this. It is only required to access Google Maps from the server (eg for background geocoding requests).',
            'default'  => '',
        ),
        */
        array(
            'id'       => 'google-maps-browser-api',
            'type'     => 'text',
            'title'    => 'Google Maps Browser API',
            'subtitle' => 'Google Maps Browser API Key',
            'desc'     => 'If you want to display a map on your site then you will need this. You need a Web Maps Javascript API Key (https://developers.google.com/maps/documentation/javascript/)',
            'default'  => '',
        ),
        /*
        array(
            'id'       => 'location-name',
            'type'     => 'text',
            'title'    => 'Location Name',
            'subtitle' => 'The name of this location. Keep it fairly short. Especially useful if you have multiple locations. Leave blank if not required.',
            'default'  => '',
        ),
        */
        array(
            'id'       => 'phone-number',
            'type'     => 'text',
            'title'    => 'Phone Number',
            'subtitle' => 'Your business phone number',
            'default'  => '',
        ),
        array(
            'id'       => 'phone-number-tel',
            'type'     => 'text',
            'title'    => 'Phone Number',
            'subtitle' => 'Your business phone number in international tel format (start with +44 and use hyphens in place of spaces)',
            'default'  => '',
            'placeholder' => '+44-1234-567-890',
        ),
        array(
            'id'       => 'business-email',
            'type'     => 'text',
            'title'    => 'Business Email',
            'subtitle' => 'Your business email address',
            'default'  => '',
        ),
        /*
        array(
            'id'       => 'address-line',
            'type'     => 'text',
            'title'    => 'Address Line',
            'subtitle' => 'Your address when shown in one line, maybe with commas after each element?',
            'default'  => '',
        ),
        array(
            'id'       => 'address-box',
            'type'     => 'textarea',
            'title'    => 'Address Box',
            'subtitle' => 'Your address when shown on multiple lines',
            'default'  => '',
        ),
        */
        array(
            'id'       => 'addr1-l1',
            'type'     => 'text',
            'title'    => 'Address Line 1',
            'subtitle' => 'First line of your address',
            'default'  => '',
        ),
        array(
            'id'       => 'addr1-l2',
            'type'     => 'text',
            'title'    => 'Address Line 2',
            'default'  => '',
        ),
        array(
            'id'       => 'addr1-town',
            'type'     => 'text',
            'title'    => 'Town',
            'default'  => '',
        ),
        array(
            'id'       => 'addr1-county',
            'type'     => 'text',
            'title'    => 'County',
            'default'  => '',
        ),
        array(
            'id'       => 'addr1-postcode',
            'type'     => 'text',
            'title'    => 'Postcode',
            'default'  => '',
        ),
        array(
            'id'       => 'location-info',
            'type'     => 'info',
            'title'    => 'Latitude and Longitude',
            'desc'     => 'Latitude and longitude are used for the Google Maps functions. In order to find the correct Latitude and Longitude entries please use the following website (note you only need to enter 6 places after the decimal points (eg 51.675208 is sufficient): <a href="https://developers.google.com/maps/documentation/utils/geocoder" target="_blank">https://developers.google.com/maps/documentation/utils/geocoder</a>. Include a map in your pages or posts with the <code>[map]</code> shortcode.',
        ),
        array(
            'id'       => 'latitude',
            'type'     => 'text',
            'title'    => 'Latitude',
            'subtitle' => 'Latitude of your location. eg 51.501364',
            'default'  => '51.501364',
        ),
        array(
            'id'       => 'longitude',
            'type'     => 'text',
            'title'    => 'Longitude',
            'subtitle' => 'Longitude of your location. eg -0.141890',
            'default'  => '-0.141890',
        ),
        array(
            'id'       => 'map-zoom',
            'type'     => 'text',
            'title'    => 'Map Zoom',
            'subtitle' => 'The zoom level that the map should initially be displayed at.',
            'default'  => '16',
        ),
        /*
        array(
            'id'       => 'findus-text',
            'type'     => 'text',
            'title'    => 'Find Us Text',
            'subtitle' => 'The "find us" text shown in the topbar',
            'default'  => 'Find us:',
            'required' => array( 'show-findus', '=', true )
        ),
        array(
            'id'       => 'findus-page',
            'type'     => 'select',
            'data'     => 'pages',
            'title'    => 'Find Us page',
            'subtitle' => 'Which page do you want the visitor sent to when they click the "find us" icon?',
            'required' => array(
                array( 'show-findus', '=', true )
            )
        ),
        array(
            'id'       => 'second-locn',
            'type'     => 'switch',
            'title'    => 'Second Location Required?',
            'subtitle' => 'Click <code>On</code> to show a second location',
            'default'  => false
        ),
        array(
            'id'   =>'second-locn-divider',
            'desc' => 'Second location ...',
            'type' => 'divide',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'location-name-2',
            'type'     => 'text',
            'title'    => 'Location Name',
            'subtitle' => 'The name of this location. Keep it fairly short. Especially useful if you have multiple locations. Leave blank if not required.',
            'default'  => '',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'phone-number-2',
            'type'     => 'text',
            'title'    => 'Phone Number',
            'subtitle' => 'Your business phone number',
            'default'  => '',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'phone-number-tel-2',
            'type'     => 'text',
            'title'    => 'Phone Number',
            'subtitle' => 'Your business phone number in international tel format (start with +44 and use hyphens in place of spaces)',
            'default'  => '',
            'placeholder' => '+44-1234-567-890',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'business-email-2',
            'type'     => 'text',
            'title'    => 'Business Email',
            'subtitle' => 'Your business email address',
            'default'  => '',
            'required' => array( 'second-locn', '=', true )
        ),
        /*
        array(
            'id'       => 'address-line-2',
            'type'     => 'text',
            'title'    => 'Address Line',
            'subtitle' => 'Your address when shown in one line, maybe with commas after each element?',
            'default'  => '',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'address-box-2',
            'type'     => 'textarea',
            'title'    => 'Address Box',
            'subtitle' => 'Your address when shown on multiple lines',
            'default'  => '',
            'required' => array( 'second-locn', '=', true )
        ),
        *//*
        array(
            'id'       => 'addr2-l1',
            'type'     => 'text',
            'title'    => 'Address Line 1',
            'subtitle' => 'First line of your address',
            'default'  => '',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'addr2-l2',
            'type'     => 'text',
            'title'    => 'Address Line 2',
            'default'  => '',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'addr2-town',
            'type'     => 'text',
            'title'    => 'Town',
            'default'  => '',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'addr2-county',
            'type'     => 'text',
            'title'    => 'County',
            'default'  => '',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'addr2-postcode',
            'type'     => 'text',
            'title'    => 'Postcode',
            'default'  => '',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'location-info-2',
            'type'     => 'info',
            'title'    => 'Latitude and Longitude',
            'desc'     => 'Latitude and longitude are used for the Google Maps functions. In order to find the correct Latitude and Longitude entries please use the following website (note you only need to enter 6 places after the decimal points (eg 51.675208 is sufficient): <a href="https://developers.google.com/maps/documentation/utils/geocoder" target="_blank">https://developers.google.com/maps/documentation/utils/geocoder</a>. Include a map in your pages or posts with the <code>[map]</code> shortcode.',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'latitude-2',
            'type'     => 'text',
            'title'    => 'Latitude',
            'subtitle' => 'Latitude of your location. eg 51.501364',
            'default'  => '51.501364',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'longitude-2',
            'type'     => 'text',
            'title'    => 'Longitude',
            'subtitle' => 'Longitude of your location. eg -0.141890',
            'default'  => '-0.141890',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'map-zoom-2',
            'type'     => 'text',
            'title'    => 'Map Zoom',
            'subtitle' => 'The zoom level that the map should initially be displayed at.',
            'default'  => '16',
            'required' => array( 'second-locn', '=', true )
        ),
        array(
            'id'       => 'findus-text-2',
            'type'     => 'text',
            'title'    => 'Find Us Text',
            'subtitle' => 'The "find us" text shown in the topbar',
            'default'  => 'Find us:',
            'required' => array(
                                array( 'second-locn', '=', true ),
                                array( 'show-findus', '=', true ),
                            ),
        ),
        array(
            'id'       => 'findus-page-2',
            'type'     => 'select',
            'data'     => 'pages',
            'title'    => 'Find Us page',
            'subtitle' => 'Which page do you want the visitor sent to when they click the "find us" icon?',
            'required' => array(
                                array( 'second-locn', '=', true ),
                                array( 'show-findus', '=', true ),
                            ),
        ),
        */
    )
) );
/*
Redux::setSection( $opt_name, array(
    'title'      => __( 'Page Signature', 'contextual' ),
    'desc'       => 'Signature to appear at the bottom of all pages and posts',
    'id'         => 'signature',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'page-signature',
            'type'     => 'editor',
            'title'    => 'Page Signature',
            'subtitle' => 'Use any of the features of the WordPress editor to create your signature!',
            'teeny'    => false,
        ),
        array(
            'id'       => 'page-signature-wpautop',
            'type'     => 'checkbox',
            'title'    => 'Automatically add paragraphs?',
            'subtitle' => '',
            'default'  => '1', // on
        ),
    )
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Blog/News', 'contextual' ),
    'desc'       => 'options for blog posts/news items',
    'id'         => 'blog-news',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'show-author',
            'type'     => 'switch',
            'title'    => 'Show the author name publicly?',
            'subtitle' => 'Click <code>No</code> to hide the author(s) names',
            'on'       => 'Yes',
            'off'      => 'No',
            'default'  => true
        ),
    )
) );
*/
// -> START Social media

Redux::setSection( $opt_name, array(
    'title' => __( 'Social Media', 'contextual' ),
    'id'    => 'social',
    'desc'  => __( '', 'contextual' ),
    'icon'  => 'el el-share'
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Accounts', 'contextual' ),
    'desc'       => 'Your social media accounts. Enter links for any social media accounts you want shown on your website. Leave blank if you do not want an account shown.',
    'id'         => 'social-accounts',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'social-facebook',
            'type'     => 'text',
            'title'    => 'Facebook',
            'subtitle' => 'Your Facebook page.',
            'desc'     => 'Enter the bit after https://facebook.com/',
            'placeholder'  => 'YOURBUSINESS',
        ),
        array(
            'id'       => 'social-twitter',
            'type'     => 'text',
            'title'    => 'Twitter',
            'subtitle' => 'Your Twitter page.',
            'desc'     => 'Enter the bit after https://twitter.com/',
            'placeholder'  => 'YOURBUSINESS',
        ),
        /*
        array(
            'id'       => 'social-google',
            'type'     => 'text',
            'title'    => 'Google+',
            'subtitle' => 'Your Google+ page.',
            'desc'     => 'Enter the bit after https://plus.google.com/',
            'placeholder'  => 'YOURBUSINESS',
        ),
        */
        array(
            'id'       => 'social-youtube',
            'type'     => 'text',
            'title'    => 'YouTube',
            'subtitle' => 'Your YouTube page.',
            'desc'     => 'Enter the bit after https://youtube.com/user/',
            'placeholder'  => 'YOURBUSINESS',
        ),
        array(
            'id'       => 'social-linkedin',
            'type'     => 'text',
            'title'    => 'LinkedIn',
            'subtitle' => 'Your LinkedIn page.',
            'desc'     => 'Enter the bit after https://linkedin.com/company/',
            'placeholder'  => 'YOURBUSINESS',
        ),
        array(
            'id'       => 'social-pinterest',
            'type'     => 'text',
            'title'    => 'Pinterest',
            'subtitle' => 'Your Pinterest page.',
            'desc'     => 'Enter the bit after https://pinterest.com/',
            'placeholder'  => 'YOURBUSINESS',
        ),
        array(
            'id'       => 'social-instagram',
            'type'     => 'text',
            'title'    => 'Instagram',
            'subtitle' => 'Your Instagram page.',
            'desc'     => 'Enter the bit after https://instagram.com/',
            'placeholder'  => 'YOURBUSINESS',
        ),
    )
) );

// -> START Styling

Redux::setSection( $opt_name, array(
    'title' => __( 'Styling', 'contextual' ),
    'id'    => 'styling',
    'desc'  => __( '', 'contextual' ),
    'icon'  => 'el el-edit'
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Logo', 'contextual' ),
    'desc'       => '',
    'id'         => 'logo',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'logo-img',
            'type'     => 'media',
            'url'      => true,
            'title'    => __( 'Website Logo', 'contextual' ),
            'subtitle'     => 'If you upload a logo here it will be used in the website header. The logo should be no larger than about 350 x 100 pixels. This can be a jpg or png file. This is the version that will be used on DARK backgrounds.',
            'library_filter' => array('jpg', 'jpeg', 'png'),
        ),
        array(
            'id'       => 'logo-img-alt',
            'type'     => 'media',
            'url'      => true,
            'title'    => __( 'Website Logo (alternate)', 'contextual' ),
            'subtitle'     => 'If you upload a logo here it will be used in the website header. The logo should be no larger than about 350 x 100 pixels. This can be a jpg or png file. This is the versions that will be used on LIGHT backgrounds.',
            'library_filter' => array('jpg', 'jpeg', 'png'),
        ),
    )
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'FAQs', 'contextual' ),
    'desc'       => '',
    'id'         => 'faqs',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'faq-img',
            'type'     => 'media',
            'url'      => true,
            'title'    => __( 'FAQs BG image', 'contextual' ),
            'subtitle'     => 'The background image to use behind FAQs on the training pages.',
            'library_filter' => array('jpg', 'jpeg', 'png'),
        ),
        array(
            'id' => 'faq-wash',
            'type' => 'color_rgba',
            'title' => 'FAQ Image colour wash',
            'desc' => 'The colour wash to put in top of the above image. Note opacity of zero means no wash and opacity of one means the wash completely obscures the image.',
        ),
    )
) );
/*
Redux::setSection( $opt_name, array(
    'title'      => __( 'Fonts', 'contextual' ),
    'desc'       => '',
    'id'         => 'fonts',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'fonts-body',
            'type'     => 'typography',
            'title'    => __( 'Body Font', 'contextual' ),
            'subtitle' => __( 'Specify the body font properties. This will be used across the website for normal text.', 'contextual' ),
            'google'   => true,
            'subsets'  => true,
            'all_styles' => true,
            'text-align' => false,
            'default'  => array(
                'color'       => '#555555',
                'font-size'   => '14px',
                'font-family' => '"Helvetica Neue",Helvetica,Arial,sans-serif',
                'font-weight' => 'Normal',
                'line-height' => '20'
            ),
        ),
        array(
            'id'       => 'fonts-links',
            'type'     => 'link_color',
            'title'    => __( 'Text Link Colours', 'contextual' ),
            'subtitle' => __( '', 'contextual' ),
            'desc'     => __( '', 'contextual' ),
            'regular'   => true,
            'hover'     => true,
            'active'    => true,
            'visited'   => false,
            'default'  => array(
                'regular' => '#337ab7',
                'hover'   => '#23527c',
                'active'  => '#23527c',
            )
        ),
        array(
            'id'       => 'fonts-headings',
            'type'     => 'typography',
            'title'    => __( 'Headings Font', 'contextual' ),
            'subtitle' => __( 'Specify the headings font properties.', 'contextual' ),
            'google'   => true,
            'all_styles' => true,
            'subsets'  => true,
            'text-align' => false,
            'font-size' => false,
            'line-height' => false,
            'text-transform' => true,
            'default'  => array(
                'color'       => '#555555',
                'font-size'   => '36px',
                'font-family' => 'inherit',
                'font-weight' => '500',
            ),
        ),
        array(
            'id'       => 'fonts-h1',
            'type'     => 'typography',
            'title'    => __( 'H1 Font', 'contextual' ),
            'subtitle' => __( 'H1 should be the largest font. This should only be used once per page.', 'contextual' ),
            'google'   => false,
            'subsets'  => false,
            'font-family' => false,
            'font-style' => true,
            'text-align' => false,
            'font-size' => true,
            'line-height' => true,
            'color'    => false,
            'default'  => array(
                'font-size'   => '36px',
                'font-weight' => '500',
                'line-height' => '40'
            ),
        ),
        array(
            'id'       => 'fonts-h2',
            'type'     => 'typography',
            'title'    => __( 'H2 Font', 'contextual' ),
            'subtitle' => __( '', 'contextual' ),
            'google'   => false,
            'subsets'  => false,
            'font-family' => false,
            'font-style' => true,
            'text-align' => false,
            'font-size' => true,
            'line-height' => true,
            'color'    => false,
            'default'  => array(
                'font-size'   => '30px',
                'font-weight' => '500',
                'line-height' => '33'
            ),
        ),
        array(
            'id'       => 'fonts-h3',
            'type'     => 'typography',
            'title'    => __( 'H3 Font', 'contextual' ),
            'subtitle' => __( '', 'contextual' ),
            'google'   => false,
            'subsets'  => false,
            'font-family' => false,
            'font-style' => true,
            'text-align' => false,
            'font-size' => true,
            'line-height' => true,
            'color'    => false,
            'default'  => array(
                'font-size'   => '24px',
                'font-weight' => '500',
                'line-height' => '26'
            ),
        ),
        array(
            'id'       => 'fonts-h4',
            'type'     => 'typography',
            'title'    => __( 'H4 Font', 'contextual' ),
            'subtitle' => __( '', 'contextual' ),
            'google'   => false,
            'subsets'  => false,
            'font-family' => false,
            'font-style' => true,
            'text-align' => false,
            'font-size' => true,
            'line-height' => true,
            'color'    => false,
            'default'  => array(
                'font-size'   => '18px',
                'font-weight' => '500',
                'line-height' => '20'
            ),
        ),
        array(
            'id'       => 'fonts-h5',
            'type'     => 'typography',
            'title'    => __( 'H5 Font', 'contextual' ),
            'subtitle' => __( '', 'contextual' ),
            'google'   => false,
            'subsets'  => false,
            'font-family' => false,
            'font-style' => true,
            'text-align' => false,
            'font-size' => true,
            'line-height' => true,
            'color'    => false,
            'default'  => array(
                'font-size'   => '14px',
                'font-weight' => '500',
                'line-height' => '15'
            ),
        ),
        array(
            'id'       => 'fonts-h6',
            'type'     => 'typography',
            'title'    => __( 'H6 Font', 'contextual' ),
            'subtitle' => __( '', 'contextual' ),
            'google'   => false,
            'subsets'  => false,
            'font-family' => false,
            'font-style' => true,
            'text-align' => false,
            'font-size' => true,
            'line-height' => true,
            'color'    => false,
            'default'  => array(
                'font-size'   => '12px',
                'font-weight' => '500',
                'line-height' => '13'
            ),
        ),
        array(
            'id'       => 'fonts-lead',
            'type'     => 'typography',
            'title'    => 'Lead Font',
            'subtitle' => 'The font used for the Lead paragraphs ([lead]...[/lead])',
            'google'   => false,
            'subsets'  => false,
            'font-family' => false,
            'font-style' => true,
            'text-align' => false,
            'font-size' => true,
            'line-height' => true,
            'color'    => false,
            'default'  => array(
                'font-size'   => '18px',
                'font-weight' => '600',
                'line-height' => '28'
            ),
        ),
    )
) );
Redux::setSection( $opt_name, array(
    'title' => 'Message Bar',
    'desc' => 'Shown at the top of the site',
    'id' => 'messagebar',
    'subsection' => true,
    'fields' => array(
        array(
            'id' => 'messagebar-switch',
            'type'     => 'switch',
            'title'    => 'Show the Message Bar?',
            'on'       => 'Yes',
            'off'      => 'No',
            'default'  => false
        ),
        array(
            'title' => 'Message Bar content',
            'id' => 'messagebar-content',
            'type' => 'editor',
            'args' => array(
                'media_buttons' => false,
                'textarea_rows' => 3,
            ),
        ),
        array(
            'id'       => 'messagebar-info',
            'type'     => 'info',
            'title'    => 'Admin Settings',
            'desc'     => 'Only you can see the following settings ...',
            'permissions' => 'rpm_admin',
        ),
        array(
            'id'       => 'messagebar-bg',
            'type'     => 'color',
            'title'    => 'Message Bar Background',
            'default'  => '#555555',
            'permissions' => 'rpm_admin',
        ),
        array(
            'id'       => 'messagebar-text',
            'type'     => 'color',
            'title'    => 'Message Bar Text Colour',
            'default'  => '#ffffff',
            'permissions' => 'rpm_admin',
        ),
        array(
            'id'       => 'messagebar-link',
            'type'     => 'link_color',
            'title'    => 'Message Bar Link Colours',
            'regular'   => true,
            'hover'     => true,
            'active'    => true,
            'visited'   => false,
            'default'  => array(
                'regular' => '#ffffff',
                'hover'   => '#ff9900',
                'active'  => '#ff9900'
            ),
            'permissions' => 'rpm_admin',
        ),
    ),
));
Redux::setSection( $opt_name, array(
    'title'      => __( 'Menu', 'contextual' ),
    'desc'       => '',
    'id'         => 'menu',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'menu-text',
            'type'     => 'link_color',
            'title'    => __( 'Menu Text Colours', 'contextual' ),
            'subtitle' => __( '', 'contextual' ),
            'desc'     => __( '', 'contextual' ),
            'regular'   => true,
            'hover'     => true,
            'active'    => true,
            'visited'   => false,
            'default'  => array(
                'regular' => '#555555',
                'hover'   => '#ffffff',
                'active'  => '#ffffff'
            )
        ),
        /*
        array(
            'id'       => 'menu-action',
            'type'     => 'select',
            'title'    => 'Menu hover action',
            'subtitle' => 'When hovering/clicking menu items, do you want to see a heavy underline or a background?',
            'options'  => array(
                'underline'    => 'Heavy underline',
                'background'     => 'Background',
            ),
            'default'  => 'underline',
        ),
        array(
            'id'       => 'menu-bg',
            'type'     => 'link_color',
            'title'    => __( 'Menu Background/Underline Colours', 'contextual' ),
            'subtitle' => __( '', 'contextual' ),
            'desc'     => __( '', 'contextual' ),
            'regular'   => true,
            'hover'     => true,
            'active'    => true,
            'visited'   => false,
            'default'  => array(
                'regular' => '#ffffff',
                'hover'   => '#999999',
                'active'  => '#555555'
            )
        ),
        array(
            'id'       => 'menu-toggle-width',
            'type'     => 'dimensions',
            'title'    => 'Menu Toggle Width',
            'subtitle' => 'At what screen width (pixels) should the menu toggle between collapsed and full? Default width is 768px. Standard content width toggling occurs at 768px, 992px and 1200px.',
            'units'    => false,
            'height'   => false,
            'default'  => array(
                'width' => 768
            )
        ),
        */
        /*
        array(
            'id'       => 'menu-social',
            'type'     => 'switch',
            'title'    => 'Show social links in the menu?',
            'on'       => 'Yes',
            'off'      => 'No',
            'default'  => false,
        ),
        array(
            'id'       => 'menu-phone',
            'type'     => 'switch',
            'title'    => 'Show phone number in the menu?',
            'on'       => 'Yes',
            'off'      => 'No',
            'default'  => false,
        ),
    )
) );
Redux::setSection( $opt_name, array(
    'title' => 'Side Link',
    'desc' => 'Link button shown on the side of the site',
    'id' => 'sidelink',
    'subsection' => true,
    'fields' => array(
        array(
            'id' => 'sidelink-switch',
            'type'     => 'switch',
            'title'    => 'Show the Side Link?',
            'on'       => 'Yes',
            'off'      => 'No',
            'default'  => false
        ),
        array(
            'title' => 'Link Text',
            'id' => 'sidelink-text',
            'type' => 'text',
            'placeholder' => 'eg Special Offer',
        ),
        array(
            'title' => 'Link Destination',
            'id' => 'sidelink-link',
            'type' => 'text',
            'placeholder' => 'http://...',
        ),
        array(
            'id'       => 'sidelink-info',
            'type'     => 'info',
            'title'    => 'Admin Settings',
            'desc'     => 'Only you can see the following settings ...',
            'permissions' => 'rpm_admin',
        ),
        array(
            'id'       => 'sidelink-btn-bg',
            'type'     => 'link_color',
            'title'    => 'button Background Colours',
            'regular'   => true,
            'hover'     => true,
            'active'    => true,
            'visited'   => false,
            'default'  => array(
                'regular' => '#0000ff',
                'hover'   => '#000099',
                'active'  => '#555555'
            ),
            'permissions' => 'rpm_admin',
        ),
        array(
            'id'       => 'sidelink-btn-border',
            'type'     => 'link_color',
            'title'    => 'button Border Colours',
            'regular'   => true,
            'hover'     => true,
            'active'    => true,
            'visited'   => false,
            'default'  => array(
                'regular' => '#0000ff',
                'hover'   => '#000099',
                'active'  => '#555555'
            ),
            'permissions' => 'rpm_admin',
        ),
        array(
            'id'       => 'sidelink-btn-text',
            'type'     => 'link_color',
            'title'    => 'button Text Colours',
            'regular'   => true,
            'hover'     => true,
            'active'    => true,
            'visited'   => false,
            'default'  => array(
                'regular' => '#ffffff',
                'hover'  => '#ff9900',
                'active'  => '#ff9900'
            ),
            'permissions' => 'rpm_admin',
        ),
    ),
));
Redux::setSection( $opt_name, array(
    'title'      => 'CTA',
    'desc'       => 'The Call to Action',
    'id'         => 'cta-sect',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'cta',
            'type'     => 'editor',
            'title'    => 'Call to Action',
            'subtitle' => 'Use within any section of the website with a shortcode of [cta]',
            'teeny'    => false,
        ),
    )
) );
Redux::setSection( $opt_name, array(
    'title'      => 'Team Page',
    'desc'       => '',
    'id'         => 'team-page-sect',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'team-page-layout',
            'type'     => 'select',
            'title'    => 'Team page(s) layout',
            'options'  => array(
                'modal'    => 'Popups for each person',
                'inline'   => 'All content inline',
            ),
            'default'  => 'modal',
        ),
    )
) );


Redux::setSection( $opt_name, array(
    'title'      => __( 'Panel', 'contextual' ),
    'desc'       => 'The colours for text panels such as slider text, blockquotes, accordions and appointment forms',
    'id'         => 'panel',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'panel-bg',
            'type'     => 'color',
            'title'    => __( 'Panel Background Colour', 'contextual' ),
            'subtitle' => __( 'The panel background colour', 'contextual' ),
            'default'  => '#eee',
        ),
        array(
            'id'       => 'panel-text',
            'type'     => 'color',
            'title'    => __( 'Panel Text Colour', 'contextual' ),
            'subtitle' => __( 'The panel text colour', 'contextual' ),
            'default'  => '#555555',
        ),
        array(
            'id'       => 'panel-emphasis',
            'type'     => 'color',
            'title'    => __( 'Panel Emphasis Colour', 'contextual' ),
            'subtitle' => __( 'The colour to be used for added emphasis in the panels', 'contextual' ),
            'default'  => '#ff9900',
        ),
    )
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'accordion', 'contextual' ),
    'desc'       => 'The colours for accordion headers',
    'id'         => 'accordion',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'accordion-header-bg',
            'type'     => 'link_color',
            'title'    => __( 'Accordion Header Background Colours', 'contextual' ),
            'subtitle' => __( '', 'contextual' ),
            'desc'     => __( '', 'contextual' ),
            'regular'   => true,
            'hover'     => true,
            'active'    => true,
            'visited'   => false,
            'default'  => array(
                'regular' => '#555555',
                'active'  => '#999999'
            )
        ),
        array(
            'id'       => 'accordion-header-text',
            'type'     => 'link_color',
            'title'    => __( 'Accordion Header Text Colours', 'contextual' ),
            'subtitle' => __( '', 'contextual' ),
            'desc'     => __( '', 'contextual' ),
            'regular'   => true,
            'hover'     => true,
            'active'    => true,
            'visited'   => false,
            'default'  => array(
                'regular' => '#ffffff',
                'active'  => '#ff9900'
            )
        ),
    )
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Maps', 'contextual' ),
    'desc'       => 'Maps display options',
    'id'         => 'maps',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'map-style',
            'type'     => 'select',
            'title'    => 'Map Styling',
            'subtitle' => 'Choose the style of maps to be displayed',
            'options'  => array(
                'std'    => 'Standard',
                'desat'     => 'Desaturated',
            ),
            'default'  => 'std',
        ),
    )
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Posts', 'contextual' ),
    'desc'       => 'Blog posts/news items options',
    'id'         => 'blog',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'blog-fallback-img',
            'type'     => 'media',
            'url'      => true,
            'title'    => __( 'Fallback image', 'contextual' ),
            'subtitle'     => 'This image will be used for the summary lists of posts if no image can be found within the post. A great size for this would be 720 x 480 px. Anything bigger will be shrunk to that size.'
        ),
    )
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Footer', 'contextual' ),
    'desc'       => '',
    'id'         => 'footer',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'footer-bg',
            'type'     => 'color',
            'title'    => __( 'Footer Background', 'contextual' ),
            'subtitle' => __( 'The colour of the footer background', 'contextual' ),
            'default'  => '#555555',
        ),
        array(
            'id'       => 'footer-text',
            'type'     => 'color',
            'title'    => __( 'Footer Text Colour', 'contextual' ),
            'subtitle' => __( 'The colour of the footer text', 'contextual' ),
            'default'  => '#ffffff',
        ),
        array(
            'id'       => 'sub-footer-bg',
            'type'     => 'color',
            'title'    => __( 'Sub-Footer Background', 'contextual' ),
            'subtitle' => __( 'The colour of the sub-footer background', 'contextual' ),
            'default'  => '#ffffff',
        ),
        array(
            'id'       => 'sub-footer-text',
            'type'     => 'color',
            'title'    => __( 'Sub-Footer Text Colour', 'contextual' ),
            'subtitle' => __( 'The colour of the sub-footer text', 'contextual' ),
            'default'  => '#555555',
        ),
        array(
            'id'       => 'seo-footer-bg',
            'type'     => 'color',
            'title'    => __( 'SEO Footer Background', 'contextual' ),
            'subtitle' => __( 'The colour of the SEO footer background', 'contextual' ),
            'default'  => '#333',
        ),
        array(
            'id'       => 'seo-footer-text',
            'type'     => 'color',
            'title'    => __( 'SEO Footer Text Colour', 'contextual' ),
            'subtitle' => __( 'The colour of the SEO footer text', 'contextual' ),
            'default'  => '#ffffff',
        ),
    )
) );
*/
Redux::setSection( $opt_name, array(
    'title'      => __( 'Custom CSS', 'contextual' ),
    'desc'       => '',
    'id'         => 'custom-css-sect',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'custom-css',
            'type'     => 'ace_editor',
            'title'    => __( 'Custom CSS', 'contextual' ),
            'subtitle' => __( 'To override or add to the standard CSS', 'contextual' ),
            'mode'     => 'css',
            'theme'    => 'chrome',
        ),
    )
) );

// communication
Redux::setSection( $opt_name, array(
    'title' => __( 'Communication', 'contextual' ),
    'id'    => 'communication',
    'desc'  => __( '', 'contextual' ),
    'icon'  => 'el el-envelope'
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Communication Options', 'contextual' ),
    'desc'       => '',
    'id'         => 'communication-options',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'email-from',
            'type'     => 'text',
            'title'    => 'Email from address',
        ),
        array(
            'id'       => 'email-from-name',
            'type'     => 'text',
            'title'    => 'Email from name',
        ),
        array(
            'id'       => 'client-to',
            'type'     => 'text',
            'title'    => 'Email address for general messages',
        ),
        array(
            'id'       => 'reg-to',
            'type'     => 'text',
            'title'    => 'Email address for new registrations',
            'subtitle' => 'Leave blank to not send them anywhere',
        ),
        array(
            'id'       => 'pay-error-to',
            'type'     => 'text',
            'title'    => 'Email address for payment processing problems',
        ),
        array(
            'id'       => 'therapy-to',
            'type'     => 'text',
            'title'    => 'Email address for therapy contact forms',
        ),
    ),
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'On-demand reminders', 'contextual' ),
    'desc'       => '',
    'id'         => 'on-demand-reminders',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'od-reminders',
            'type'     => 'multi_text',
            'title'    => 'Days before expiry to send reminders',
            'validate' => 'numeric',
            'subtitle' => 'Enter a number eg 5 or 90 etc. Must be greater than 0. Invalid numbers will be rejected!',
        ),
    ),
) );

// common texts
Redux::setSection( $opt_name, array(
    'title' => __( 'Phrases', 'contextual' ),
    'id'    => 'phrases',
    'desc'  => __( '', 'contextual' ),
    'icon'  => 'el el-file-edit'
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Common Phrases', 'contextual' ),
    'desc'       => '',
    'id'         => 'common-phrases',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'terms',
            'type'     => 'textarea',
            'title'    => 'Terms and conditions',
            'subtitle' => 'Shown during registration. "[cancel_fee]" will be "10% of the training fee".',
            'default'  => false,
            'args' => array(
                'media_buttons' => false,
                'textarea_rows' => 6,
            ),
        ),
        array(
            'id'       => 'student',
            'type'     => 'textarea',
            'title'    => 'Student discount terms',
            'subtitle' => '',
            'default'  => false,
            'args' => array(
                'media_buttons' => false,
                'textarea_rows' => 3,
            ),
        ),
        array(
            'id'       => 'voucher-terms',
            'type'     => 'editor',
            'title'    => 'Gift Voucher terms',
            'subtitle' => '',
            'default'  => false,
            'teeny'    => true,
            'args' => array(
                'media_buttons' => false,
                'textarea_rows' => 3,
            ),
        ),
        array(
            'id'       => 'mailster-workshop-msg',
            'type'     => 'textarea',
            'title'    => 'Workshop joining message',
            'subtitle' => 'Can include {my_account_link}',
            'default'  => false,
            'args' => array(
                'media_buttons' => false,
                'textarea_rows' => 3,
            ),
        ),
        array(
            'id'       => 'mailster-webinar-msg',
            'type'     => 'textarea',
            'title'    => 'Workshop joining message (webinar)',
            'subtitle' => 'Can include {my_account_link}',
            'default'  => false,
            'args' => array(
                'media_buttons' => false,
                'textarea_rows' => 3,
            ),
        ),
        array(
            'id'       => 'mailster-promo-msg',
            'type'     => 'textarea',
            'title'    => 'Registration promo message',
            'subtitle' => 'Can include {promo_code}',
            'default'  => false,
            'args' => array(
                'media_buttons' => false,
                'textarea_rows' => 3,
            ),
        ),
        array(
            'id'       => 'workshop-intro',
            'type'     => 'editor',
            'title'    => 'Content for Live Training',
            'subtitle' => 'Content to be included at the top of the page listing all live training',
            'teeny'  => false,
        ),
        array(
            'id'       => 'recording-intro',
            'type'     => 'editor',
            'title'    => 'Content for On-Demand Training',
            'subtitle' => 'Content to be included at the top of the page(s) listing all on-demand training',
            'teeny'  => false,
        ),
        array(
            'id'       => 'resource_hub-intro',
            'type'     => 'editor',
            'title'    => 'Content for Resource Hub',
            'subtitle' => 'Content to be included at the top of the Resource Hub',
            'teeny'  => false,
        ),
        array(
            'id'       => 'knowledge_hub-intro',
            'type'     => 'editor',
            'title'    => 'Content for Knowledge Hub',
            'subtitle' => 'Content to be included at the top of the Knowledge Hub',
            'teeny'  => false,
        ),
        array(
            'id'       => 'ce-credits-modal-intro',
            'type'     => 'editor',
            'title'    => 'CE Credits Intro (workshops)',
            'subtitle' => 'Content to be included in the popup that appears in the My Account section when a user clicks the CE Credits button (workshops)',
            'teeny'  => false,
        ),
        array(
            'id'       => 'ce-credits-modal-intro-rec',
            'type'     => 'editor',
            'title'    => 'CE Credits Intro (recordings)',
            'subtitle' => 'Content to be included in the popup that appears in the My Account section when a user clicks the CE Credits button (recordings)',
            'teeny'  => false,
        ),
        array(
            'id'       => 'cookie-banner-text',
            'type'     => 'textarea',
            'title'    => 'Message to go onto the cookie/consent banner',
            'default'  => 'We use essential cookies to ensure our website works perfectly for you. You can choose to accept or reject optional performance and tracking cookies that help us improve your experience. The choice is yours!',
            'args' => array(
                'media_buttons' => false,
                'textarea_rows' => 3,
            ),
        ),
        array(
            'id'       => 'quiz-intro-text',
            'type'     => 'textarea',
            'title'    => 'Intro message to go onto the quiz popup in My Account',
            'default'  => 'Please complete ...',
            'desc'     => 'This can include shortcodes',
            'args' => array(
                'media_buttons' => false,
                'textarea_rows' => 3,
            ),
        ),
        array(
            'id'       => 'quiz-outro-text',
            'type'     => 'textarea',
            'title'    => 'Final quiz message to go onto the quiz popup in My Account',
            'default'  => 'Now submit ...',
            'desc'     => 'To be shown after they have answered all questions but before they submit their answers for scoring.',
            'args' => array(
                'media_buttons' => false,
                'textarea_rows' => 3,
            ),
        ),
        array(
            'id'       => 'series-course-text',
            'type'     => 'textarea',
            'title'    => 'Series intro text',
            'default'  => 'This training is part of the [training_series_title] series. The series includes [training_series_other_courses]. Read more [training_series_link text="here"].',
            'desc'     => 'Shown on a training page when the course is part of a series. Include the shortcodes [training_series_title], [training_series_other_courses] and/or [training_series_link text="here"] if required.',
            'args' => array(
                'media_buttons' => false,
                'textarea_rows' => 3,
            ),
        ),
    )
) );

// ACT Values Cards
Redux::setSection( $opt_name, array(
    'title' => __( 'ACT Values Cards', 'contextual' ),
    'id'    => 'avc',
    'desc'  => __( '', 'contextual' ),
    'icon'  => 'el el-picture'
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'ACT Values Cards Settings', 'contextual' ),
    'desc'       => '',
    'id'         => 'avc-settings',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'avc_price_GBP',
            'type'     => 'text',
            'title'    => 'Price in GBP',
        ),
        array(
            'id'       => 'avc_price_AUD',
            'type'     => 'text',
            'title'    => 'Price in AUD',
        ),
        array(
            'id'       => 'avc_price_USD',
            'type'     => 'text',
            'title'    => 'Price in USD',
        ),
        array(
            'id'       => 'avc_price_EUR',
            'type'     => 'text',
            'title'    => 'Price in EUR',
        ),
        array(
            'id'       => 'avc-vat-msg',
            'type'     => 'textarea',
            'title'    => 'VAT message',
            'subtitle' => 'Shown on the first panel',
        ),
        array(
            'id'       => 'avc-pnp-msg',
            'type'     => 'textarea',
            'title'    => 'Postage and packaging message',
            'subtitle' => 'Shown on the first panel',
        ),
        array(
            'id'       => 'avc_ship_gb_one',
            'type'     => 'text',
            'title'    => 'UK Shipping price for one pack (£)',
        ),
        array(
            'id'       => 'avc_ship_gb_more',
            'type'     => 'text',
            'title'    => 'UK Shipping price for more than one pack (£)',
        ),
        array(
            'id'       => 'avc_ship_world_one',
            'type'     => 'text',
            'title'    => 'Non-UK Shipping price for one pack (£ but converted as needed)',
        ),
        array(
            'id'       => 'avc_ship_world_extra',
            'type'     => 'text',
            'title'    => 'Non-UK Shipping price for extra packs (£ but converted as needed)',
        ),
    ),
) );

// General
Redux::setSection( $opt_name, array(
    'title' => __( 'General', 'contextual' ),
    'id'    => 'general',
    'desc'  => __( '', 'contextual' ),
    'icon'  => 'el el-cog'
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'General Settings', 'contextual' ),
    'desc'       => '',
    'id'         => 'general-settings',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'free_training_btn_text',
            'type'     => 'text',
            'title'    => 'Free training button text',
            'subtitle' => 'This button is added to the home page alongside the live and on-demand buttons',
            'desc'     => 'No button is shown if this field is left blank',
        ),
        array(
            'id'       => 'free_training_btn_link',
            'type'     => 'text',
            'title'    => 'Free training button link',
            'subtitle' => 'Where do you want to send people when they click this button?',
            'desc'     => 'No button is shown if this field is left blank',
        ),
    ),
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Search settings', 'contextual' ),
    'desc'       => '',
    'id'         => 'search-settings',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'acronyms',
            'type'     => 'textarea',
            'title'    => 'Acronyms to exclude from the fuzzy search',
            'desc'     => 'Enter one acronym per line',
        ),
    ),
) );



// Stripe
Redux::setSection( $opt_name, array(
    'title' => __( 'Stripe/Xero', 'contextual' ),
    'id'    => 'stripe',
    'desc'  => __( '', 'contextual' ),
    'icon'  => 'el el-credit-card'
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Stripe and Xero Settings', 'contextual' ),
    'desc'       => '',
    'id'         => 'stripe-settings',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'stripe_public',
            'type'     => 'text',
            'title'    => 'Stripe Public Key',
        ),
        array(
            'id'       => 'stripe_secret',
            'type'     => 'text',
            'title'    => 'Stripe Secret Key',
        ),
        array(
            'id'       => 'stripe_public_test',
            'type'     => 'text',
            'title'    => 'Stripe Public Test Key',
        ),
        array(
            'id'       => 'stripe_secret_test',
            'type'     => 'text',
            'title'    => 'Stripe Secret Test Key',
        ),
        array(
            'id'       => 'stripe_test_mode',
            'type'     => 'checkbox',
            'title'    => 'Stripe Test Mode',
            'subtitle' => 'All payments for administrators are in test mode',
        ),
        array(
            'id'       => 'xero_client_id',
            'type'     => 'text',
            'title'    => 'Xero Client ID',
        ),
        array(
            'id'       => 'xero_client_secret',
            'type'     => 'text',
            'title'    => 'Xero Client Secret',
        ),
        array(
            'id'       => 'xero_redirect_uri',
            'type'     => 'text',
            'title'    => 'Xero Redirect URI',
        ),
        array(
            'id'       => 'xero_tenant_id',
            'type'     => 'text',
            'title'    => 'Xero Tenant ID',
        ),
    ),
) );




/*
// start Action Button

Redux::setSection( $opt_name, array(
    'title' => __( 'Action Button', 'contextual' ),
    'id'    => 'action',
    'desc'  => __( '', 'contextual' ),
    'icon'  => 'el el-arrow-right'
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Action Button', 'contextual' ),
    'desc'       => '',
    'id'         => 'action-button',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'action-button-required',
            'type'     => 'switch',
            'title'    => 'Show Action Button?',
            'subtitle' => 'Click <code>On</code> to show an "action" button such as "Request an appointment", "Special offer", etc.',
            'default'  => false
        ),
        array(
            'id'       => 'action-headline',
            'type'     => 'text',
            'title'    => 'Action Headline',
            'subtitle' => 'eg "Request an appointment", "Special offer", etc.',
            'required' => array( 'action-button-required', '=', true )
        ),
        array(
            'id'       => 'action-button-content',
            'type'     => 'select',
            'title'    => 'Action Button Content',
            'subtitle' => 'Select either a form is to be completed or a link to a URL or a page on your website when somebody clicks the action button',
            'options'  => array(
                'form'    => 'Form to complete',
                'url'     => 'Link to a URL',
                'page'    => 'Link to a page',
                'code'    => 'Custom code'
            ),
            'default'  => 'form',
            'select2'  => array( 'allowClear' => false ),
            'required' => array( 'action-button-required', '=', true )
        ),
        array(
            'id'       => 'action-form',
            'type'     => 'text',
            'title'    => 'Action Form',
            'subtitle' => 'Enter the code for the form you want displayed on the home page here. This code might be something like [contact-form-7 id="1277" title="Appointment Home"]',
            'required' => array(
                array( 'action-button-required', '=', true ),
                array( 'action-button-content', '=', 'form' )
            )
        ),
        array(
            'id'       => 'action-link',
            'type'     => 'text',
            'title'    => 'Action Link',
            'subtitle' => 'The URL (link) to send people to when they click the action button. Important, start this link with "http://" (or "https://").',
            'required' => array(
                array( 'action-button-required', '=', true ),
                array( 'action-button-content', '=', 'url' )
            )
        ),
        array(
            'id'       => 'action-link-target',
            'type'     => 'select',
            'title'    => 'Action Link Target',
            'subtitle' => 'Open this link in a new window?',
            'options'  => array(
                'no'    => 'Open link in same window/tab',
                'yes'     => 'Open link in a new window/tab'
            ),
            'default'  => 'no',
            'select2'  => array( 'allowClear' => false ),
            'required' => array(
                array( 'action-button-required', '=', true ),
                array( 'action-button-content', '=', 'url' )
            )
        ),
        array(
            'id'       => 'action-page',
            'type'     => 'select',
            'data'     => 'pages',
            'title'    => __( 'Action Links To:', 'contextual' ),
            'subtitle' => __( 'Which page do you want the visitor sent to when they click this button?', 'contextual' ),
            'required' => array(
                array( 'action-button-required', '=', true ),
                array( 'action-button-content', '=', 'page' )
            )
        ),
        array(
            'id'        => 'action-code-html',
            'type'      => 'ace_editor',
            'title'     => 'Action button custom code (html)',
            'subtitle'  => 'eg for a custom form',
            'mode'      => 'html',
            'theme'     => 'monokai',
            'required' => array(
                array( 'action-button-required', '=', true ),
                array( 'action-button-content', '=', 'code' )
            )
        ),
        array(
            'id'        => 'action-code-js',
            'type'      => 'ace_editor',
            'title'     => 'Action button custom code (javascript)',
            'subtitle'  => 'to go with the above html (if required)',
            'mode'      => 'javascript',
            'theme'     => 'monokai',
            'required' => array(
                array( 'action-button-required', '=', true ),
                array( 'action-button-content', '=', 'code' )
            )
        ),
        array(
            'id'        => 'action-code-css',
            'type'      => 'ace_editor',
            'title'     => 'Action button custom code (css)',
            'subtitle'  => 'to go with the above html (if required)',
            'mode'      => 'css',
            'theme'     => 'monokai',
            'required' => array(
                array( 'action-button-required', '=', true ),
                array( 'action-button-content', '=', 'code' )
            )
        ),
    )
) );

// start Slides
/*
Redux::setSection( $opt_name, array(
    'title' => __( 'Home Slideshow', 'contextual' ),
    'id'    => 'home-slideshow',
    'desc'  => __( '', 'contextual' ),
    'icon'  => 'el el-picture'
) );
Redux::setSection( $opt_name, array(
    'title'      => __( 'Home Slideshow', 'contextual' ),
    'desc'       => '',
    'id'         => 'home-slideshow-sect',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'responsive-slideshow',
            'type'     => 'switch',
            'title'    => 'Responsive Slideshow?',
            'subtitle' => 'The default setting of "no" creates slides that are cropped to the width and height below. The central section of these images will be shown on all devices. This means that mobiles will only show the central section of the image. Selecting "yes" will cause multiple versions of the slideshow images to be created. These multiple versions will be made available to the browser so that it can display the most appropriate image for the screen size. This means that more of an image may be displayed on a mobile screen. <strong>IMPORTANT! If you change this setting then you may need to upload new versions of the slideshow images or <a href="/wp-admin/tools.php?page=regenerate-thumbnails">regenerate thumbnails</a></strong>',
            'on'       => 'Yes',
            'off'      => 'No',
            'default'  => false,
        ),
        array(
            'id'       => 'feat-slide-width',
            'type'     => 'select',
            'title'    => 'Slideshow width',
            'subtitle' => 'This setting only makes a difference on larger screens where, by default, the slideshow will be the full width of the screen even though the site contents will be narrower. Changing this setting to "site width" constrains the slideshow to the same with as the site contents. <strong>IMPORTANT! If you change this setting then you may need to upload new versions of the slideshow images or <a href="/wp-admin/tools.php?page=regenerate-thumbnails">regenerate thumbnails</a></strong>',
            'options'  => array(
                'full-screen' => 'Full screen width',
                'container'   => 'Site width'
            ),
            'default'  => 'full-screen',
        ),
        array(
            'id'       => 'feat-slide-height',
            'type'     => 'text',
            'title'    => 'Slideshow height',
            'subtitle' => '<i class="el el-warning-sign el-2x"></i> The height of the images to be shown in the slideshow. This should be expressed as a number (eg 450). This number is the height in pixels on a large desktop and will be scaled appropriately for smaller screens. <strong>After you have changed this setting and hit SAVE CHANGES, all future</strong> slides (and featured images on posts/pages) will also have a version created at this size. Previously inserted images <strong>will not be changed</strong>. If you need previous images changed to match a new size you have entered here, then you will need to <a href="/wp-admin/tools.php?page=regenerate-thumbnails">regenerate thumbnails</a>.',
            'default'  => '450',
        ),
        array(
            'id'       => 'home-slides',
            'type'     => 'slides',
            'title'    => 'Home Slideshow',
            'subtitle' => 'Slides to be shown on the home page. These will be automatically resized and should, for best quality, be 1920px (or more) wide.',
        ),
    )
) );
*/

// start System
Redux::setSection( $opt_name, array(
    'title' => 'System',
    'id'    => 'system',
    'icon'  => 'el el-cog',
) );
if( isset($_SERVER['REMOTE_ADDR']) ){
    $ip_address = $_SERVER['REMOTE_ADDR'];
}else{
    $ip_address = 'unknown';
}
Redux::setSection( $opt_name, array(
    'title'      => 'Override',
    'desc'       => '',
    'id'         => 'override',
    'subsection' => true,
    'fields'     => array(
        array(
            'id'       => 'sys-override-info',
            'type'     => 'info',
            'title'    => 'Your IP',
            'desc'     => 'You are using IP address: '.$ip_address,
        ),
        array(
            'id'       => 'sys-override-ip',
            'type'     => 'text',
            'title'    => 'Location override: IP',
            'subtitle' => 'Enter the IP address that you want to use as if it were in another country (probably the one shown above?)',
        ),
        array(
            'id'       => 'sys-override-locn',
            'type'     => 'text',
            'title'    => 'Location override: Location',
            'subtitle' => 'Enter the two character country code that you want to pretend to be in (UK, AU, NZ, US, etc.)',
        ),
        array(
            'id'       => 'google-analytics',
            'type'     => 'text',
            'title'    => 'Google Analytics ID',
            'subtitle' => 'Only use on live site!',
        ),
        array(
            'id'       => 'google-ads',
            'type'     => 'text',
            'title'    => 'Google Ads ID',
            'subtitle' => 'Only use on live site!',
        ),
    )
) );
Redux::setSection( $opt_name, array(
    'title'      => 'Sections',
    'desc'       => '',
    'id'         => 'sections',
    'subsection' => true,
    'permissions' => 'rpm_admin',
    'fields'     => array(
        array(
            'id'       => 'sys-sections-info',
            'type'     => 'info',
            'title'    => 'Sections',
            'desc'     => 'Get in touch with us: <a href="mailto:info@saasora.com">info@saasora.com</a> if you need changes made to the structure of your website.',
        ),
        array(
            'id'       => 'home-sections',
            'type'     => 'text',
            'title'    => 'Home Page Sections',
            'subtitle' => 'How many sections to be shown on the Home Page?',
            'default' => '5',
        ),
    )
) );


// Remove the demo link and the notice of integrated demo from the redux-framework plugin
function remove_demo() {

    // Used to hide the demo mode link from the plugin page. Only used when Redux is a plugin.
    if ( class_exists( 'ReduxFrameworkPlugin' ) ) {
        remove_filter( 'plugin_row_meta', array(
            ReduxFrameworkPlugin::instance(),
            'plugin_metalinks'
        ), null, 2 );

        // Used to hide the activation notice informing users of the demo panel. Only used when Redux is a plugin.
        remove_action( 'admin_notices', array( ReduxFrameworkPlugin::instance(), 'admin_notices' ) );
    }
}
