<?php
/**
 * WMS
 * Plugins - Loads up the required/recommended plugins
 * uses TGMPA: http://tgmpluginactivation.com/
 * see http://tgmpluginactivation.com/configuration/ for detailed documentation.
 *
 * Includes:
 * - 
 */

/**
 * v5.0 5/10/18
 * - v5 rebuild using TGMPA v2.6.1
 */


/**
 * Include the TGM_Plugin_Activation class.
 */
if(file_exists(get_stylesheet_directory().'/inc/wms/tgmpa/class-tgm-plugin-activation.php')){
	require_once get_stylesheet_directory() . '/inc/wms/tgmpa/class-tgm-plugin-activation.php';
	add_action( 'tgmpa_register', 'wms_plugins_register_required_plugins' );
}

/**
 * Register the required plugins for this theme.
 *
 * This function is hooked into `tgmpa_register`, which is fired on the WP `init` action on priority 10.
 */
function wms_plugins_register_required_plugins() {
	$plugins = array(
        array(
            'name'               => 'Redux Framework',
            'slug'               => 'redux-framework',
            'required'           => true,
            'force_activation'   => true,
            'force_deactivation' => true
        ),
        array(
            'name'               => 'Get the Image',
            'slug'               => 'get-the-image',
            'required'           => true,
            'force_activation'   => true,
            'force_deactivation' => true
        ),
        // not essential for CC
        /*
        array(
            'name'               => 'Regenerate Thumbnails',
            'slug'               => 'regenerate-thumbnails',
            'required'           => false,
            'force_activation'   => false,
            'force_deactivation' => false
        ),
        */
        // Bootstrap 3 Shortcodes doesn't work so well these days ... we'll incorporate the bits we need into the theme instead
        /*
        array(
            'name'               => 'Bootstrap 3 Shortcodes',
            'slug'               => 'bootstrap-3-shortcodes',
            'required'           => false,
            'force_activation'   => false,
            'force_deactivation' => false
        ),
        */
        // not needed for CC
        /*
        array(
            'name'               => 'Easy WP SMTP',
            'slug'               => 'easy-wp-smtp',
            'required'           => false,
            'force_activation'   => false,
            'force_deactivation' => false
        ),
        */
		array(
			'name'        		 => 'WordPress SEO by Yoast',
			'slug'        		 => 'wordpress-seo',
            'required'           => false,
            'force_activation'   => false,
            'force_deactivation' => false
		),
		array(
			'name'        		 => 'White Label CMS',
			'slug'        		 => 'white-label-cms',
            'required'           => false,
            'force_activation'   => false,
            'force_deactivation' => false
		),
        array(
            'name'               => 'Classic Editor',
            'slug'               => 'classic-editor',
            'required'           => true,
            'force_activation'   => true,
            'force_deactivation' => true
        ),
        // added in v6.1
        array(
            'name'               => 'Classic Widgets',
            'slug'               => 'classic-widgets',
            'required'           => true,
            'force_activation'   => true,
            'force_deactivation' => true
        ),

	);
    $config = array(
		'id'           => 'wms',                   // Unique ID for hashing notices for multiple instances of TGMPA.
        'default_path' => '',                      // Default absolute path to pre-packaged plugins.
        'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'parent_slug'  => 'themes.php',            // Parent menu slug.
        'has_notices'  => true,                    // Show admin notices or not.
        'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
        'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
        'is_automatic' => false,                   // Automatically activate plugins after installation or not.
        'message'      => '',                      // Message to output right before the plugins table.
        'strings'      => array(
			'page_title'                      => __( 'Install Required Plugins', 'wms' ),
			'menu_title'                      => __( 'Install Plugins', 'wms' ),
			/* translators: %s: plugin name. */
			'installing'                      => __( 'Installing Plugin: %s', 'wms' ),
			/* translators: %s: plugin name. */
			'updating'                        => __( 'Updating Plugin: %s', 'wms' ),
			'oops'                            => __( 'Something went wrong with the plugin API.', 'wms' ),
			'notice_can_install_required'     => _n_noop(
				/* translators: 1: plugin name(s). */
				'This theme requires the following plugin: %1$s.',
				'This theme requires the following plugins: %1$s.',
				'wms'
			),
			'notice_can_install_recommended'  => _n_noop(
				/* translators: 1: plugin name(s). */
				'This theme recommends the following plugin: %1$s.',
				'This theme recommends the following plugins: %1$s.',
				'wms'
			),
			'notice_ask_to_update'            => _n_noop(
				/* translators: 1: plugin name(s). */
				'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
				'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
				'wms'
			),
			'notice_ask_to_update_maybe'      => _n_noop(
				/* translators: 1: plugin name(s). */
				'There is an update available for: %1$s.',
				'There are updates available for the following plugins: %1$s.',
				'wms'
			),
			'notice_can_activate_required'    => _n_noop(
				/* translators: 1: plugin name(s). */
				'The following required plugin is currently inactive: %1$s.',
				'The following required plugins are currently inactive: %1$s.',
				'wms'
			),
			'notice_can_activate_recommended' => _n_noop(
				/* translators: 1: plugin name(s). */
				'The following recommended plugin is currently inactive: %1$s.',
				'The following recommended plugins are currently inactive: %1$s.',
				'wms'
			),
			'install_link'                    => _n_noop(
				'Begin installing plugin',
				'Begin installing plugins',
				'wms'
			),
			'update_link' 					  => _n_noop(
				'Begin updating plugin',
				'Begin updating plugins',
				'wms'
			),
			'activate_link'                   => _n_noop(
				'Begin activating plugin',
				'Begin activating plugins',
				'wms'
			),
			'return'                          => __( 'Return to Required Plugins Installer', 'wms' ),
			'plugin_activated'                => __( 'Plugin activated successfully.', 'wms' ),
			'activated_successfully'          => __( 'The following plugin was activated successfully:', 'wms' ),
			/* translators: 1: plugin name. */
			'plugin_already_active'           => __( 'No action taken. Plugin %1$s was already active.', 'wms' ),
			/* translators: 1: plugin name. */
			'plugin_needs_higher_version'     => __( 'Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'wms' ),
			/* translators: 1: dashboard link. */
			'complete'                        => __( 'All plugins installed and activated successfully. %1$s', 'wms' ),
			'dismiss'                         => __( 'Dismiss this notice', 'wms' ),
			'notice_cannot_install_activate'  => __( 'There are one or more required or recommended plugins to install, update or activate.', 'wms' ),
			'contact_admin'                   => __( 'Please contact the administrator of this site for help.', 'wms' ),

			'nag_type'                        => 'updated', // Determines admin notice type - can only be one of the typical WP notice classes, such as 'updated', 'update-nag', 'notice-warning', 'notice-info' or 'error'. Some of which may not work as expected in older WP versions.
        )
    );
	tgmpa( $plugins, $config );
}
