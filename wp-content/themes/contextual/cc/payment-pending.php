<?php
/**
 * Payment pending people
 * i.e. people who asked for an invoice and then did not pay it!
 */

// Create the custom role on theme activation
function create_payment_pending_role() {
    // Check if the role doesn't already exist
    if (!get_role('payment_pending')) {
        add_role(
            'payment_pending',
            'Payment Pending',
            array(
                'read' => false,  // Prevent access to dashboard
                'upload_files' => false,
                'edit_posts' => false,
                'edit_pages' => false,
                'publish_posts' => false,
                'publish_pages' => false,
                'edit_others_posts' => false,
                'edit_others_pages' => false,
                'edit_published_posts' => false,
                'edit_published_pages' => false,
                'delete_posts' => false,
                'delete_pages' => false,
                'delete_others_posts' => false,
                'delete_others_pages' => false,
                'delete_published_posts' => false,
                'delete_published_pages' => false,
                'delete_private_posts' => false,
                'edit_private_posts' => false,
                'read_private_posts' => false,
                'delete_private_pages' => false,
                'edit_private_pages' => false,
                'read_private_pages' => false,
                'manage_categories' => false,
                'manage_links' => false,
                'edit_comment' => false,
                'moderate_comments' => false,
                'unfiltered_html' => false,
                'upload_files' => false,
                'export' => false,
                'import' => false,
                'list_users' => false
            )
        );
    }
}

// run on init to ensure role exists
add_action('init', 'create_payment_pending_role');

/**
 * Helper function to assign payment pending role to a user
 * Usage: assign_payment_pending_role($user_id);
 */
function assign_payment_pending_role($user_id) {
    $user = new WP_User($user_id);
    
    // Check if this user is currently logged in
    $current_user_id = get_current_user_id();
    $is_current_user = ($current_user_id == $user_id);
    
    // Remove all current roles
    $user->set_role('');
    
    // Add the payment pending role
    $user->add_role('payment_pending');
    
    // Log out the user if they are currently logged in
    if ($is_current_user) {
        wp_logout();
    } else {
        // If it's not the current user, destroy all their sessions
        $sessions = WP_Session_Tokens::get_instance($user_id);
        $sessions->destroy_all();
    }
    
    return true;
}
/**
 * Helper function to restore user to subscriber role after payment
 * Usage: restore_subscriber_role($user_id);
 */
function restore_subscriber_role($user_id) {
    $user = new WP_User($user_id);
    
    // Remove payment pending role
    $user->remove_role('payment_pending');
    
    // Add subscriber role back
    $user->add_role('subscriber');
    
    return true;
}

/**
 * Check if user has payment pending role
 * Usage: if (user_has_payment_pending_role($user_id)) { ... }
 */
function user_has_payment_pending_role($user_id) {
    $user = new WP_User($user_id);
    return in_array('payment_pending', $user->roles);
}

/**
 * Prevent users with payment_pending role from accessing admin area
 */
function block_payment_pending_admin_access() {
    if (current_user_can('payment_pending') && is_admin() && !wp_doing_ajax()) {
        wp_redirect(home_url());
        exit;
    }
}
add_action('admin_init', 'block_payment_pending_admin_access');

/**
 * Hide admin bar for payment pending users
 */
function hide_admin_bar_for_payment_pending($show) {
    if (current_user_can('payment_pending')) {
        return false;
    }
    return $show;
}
add_filter('show_admin_bar', 'hide_admin_bar_for_payment_pending');

/**
 * Optional: Get all users with payment pending role
 * Usage: $pending_users = get_payment_pending_users();
 */
function get_payment_pending_users() {
    $users = get_users(array(
        'role' => 'payment_pending',
        'fields' => 'all'
    ));
    return $users;
}

/**
 * Monitor role changes and notify admin of unexpected roles
 * This function monitors when users are assigned roles other than subscriber or payment_pending
 */
function monitor_user_role_changes($user_id, $role, $old_roles = array()) {
    // Define allowed roles for regular users
    $allowed_roles = array('subscriber', 'payment_pending');
    
    // Skip if the new role is one of the allowed roles
    if (in_array($role, $allowed_roles)) {
        return;
    }
    
    // Get user information
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return;
    }
    
    // Get current user (who made the change)
    $current_user = wp_get_current_user();
    $changed_by = $current_user->exists() ? $current_user->user_login : 'System';
    
    // Get admin email
    $admin_email = get_option('admin_email');
    
    // Get site name
    $site_name = get_bloginfo('name');
    $site_url = get_site_url();
    
    // Prepare email content
    $subject = '[' . $site_name . '] Unexpected User Role Assignment';
    
    $message = "Hello,\n\n";
    $message .= "A user has been assigned an unexpected role on your website.\n\n";
    $message .= "Details:\n";
    $message .= "User: " . $user->display_name . " (" . $user->user_email . ")\n";
    $message .= "Username: " . $user->user_login . "\n";
    $message .= "New Role: " . $role . "\n";
    $message .= "Previous Roles: " . (!empty($old_roles) ? implode(', ', $old_roles) : 'None') . "\n";
    $message .= "Changed by: " . $changed_by . "\n";
    $message .= "Date/Time: " . current_time('mysql') . "\n\n";
    $message .= "Expected roles for training users are: subscriber, payment_pending\n\n";
    $message .= "Please review this change to ensure it was intentional.\n\n";
    $message .= "You can manage user roles here: " . $site_url . "/wp-admin/users.php\n\n";
    $message .= "Site: " . $site_name . "\n";
    $message .= "URL: " . $site_url;
    
    // Send email notification
    wp_mail($admin_email, $subject, $message);
}

/**
 * Hook into role changes to monitor unexpected assignments
 */
function track_role_changes($user_id, $role, $old_roles) {
    monitor_user_role_changes($user_id, $role, $old_roles);
}
add_action('set_user_role', 'track_role_changes', 10, 3);

/**
 * Also monitor when roles are added (not just set)
 */
function track_role_additions($user_id, $role) {
    $user = get_user_by('id', $user_id);
    $old_roles = $user ? $user->roles : array();
    monitor_user_role_changes($user_id, $role, $old_roles);
}
add_action('add_user_role', 'track_role_additions', 10, 2);

// set up the first batch of payment_penders
add_shortcode( 'setup_payment_penders', function(){
	$emails = array(
		'cathy.tran@ggc.scot.nhs.uk',
		'minna.waljas@gmail.com',
		'ssimms3@btinternet.com',
		'alison.wren@nhs.scot',
		'helen@helencbt.co.uk',
		'h.alonezi@liverpool.ac.uk',
		'Louise.Roper@liverpool.ac.uk',
		'milcapg@gmail.com',
		'lironip@gmail.com',
		'vakindy@yahoo.com',
		'lisakrygger@gmail.com',
		'keziagmatheson@gmail.com',
		'madeleinedober@gmail.com',
		'amelie.bobsien@mkuh.nhs.uk',
		'eyalor8@gmail.com',
		'psychotherapie.soergel@gmail.com',
		'mel_lillie@yahoo.com',
		'inekecusters@gmail.com',
		'bregjekuis75@gmail.com',
		'mjlweeks@gmail.com',
		'erinlgrubbs@gmail.com',
		'emacmil@gmail.com',
		'info@gemmarovira.com',
		'innercirclecounselling@gmail.com',
		'kspanosyates@gmail.com',
		'parisandreas@gmail.com',
		'acharacounselling@gmail.com',
		'clairewilsonconsulting@gmail.com',
		'praktijkscope@gmail.com',
		'lorrainekenowski@gmail.com',
		'hannah.darvell@elysiumhealthcare.co.uk',
		'meredithjhamel@gmail.com',
		'drbobbidobsonpatterson@gmail.com',
		'keziamathiesoncbt@outlook.com',
		'contact@mariarhebaestante.net',
		'angelicadmartin@gmail.com',
	);
	$html = 'setup_payment_penders';
	foreach ($emails as $email) {
		$html .= '<br>'.$email;
		$user = get_user_by( 'email', $email );
		if( $user ){
			$html .= ' id= '.$user->ID;
			if( user_has_payment_pending_role( $user->ID ) ){
				$html .= ' IGNORED - already set to Payment Pending';
			}else{
				if( assign_payment_pending_role( $user->ID ) ){
					$html .= ' set to Payment Pending';
				}else{
					$html .= ' ### something went wrong - could not set Payment Pending role. ###';
				}
			}
		}else{
			$html .= ' ### not found ###';
		}
	}
	$html .= '<br>Done';
	return $html;
});
