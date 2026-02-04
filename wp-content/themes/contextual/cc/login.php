<?php
/**
 * Login stuff
 */

// return the enter email html or the password reset html or the reset password request html
function cc_login_starting_html(){
    global $wp_hasher;
    $action = '';
    $email = '';
    $key = '';
    $user = false;
    if(isset($_GET['login']) && $_GET['login'] <> ''){
        $email = strtolower( stripslashes( sanitize_email($_GET['login']) ) );
        $user = get_user_by('email', $email);
    }
    if(isset($_GET['action']) && $_GET['action'] == 'rp' && $user){
        if(isset($_GET['key']) && $_GET['key'] <> ''){
            $key = sanitize_text_field($_GET['key']);
            $action = 'reset';
        }else{
            $action = 'forgot';
        }
    }
    if($action == 'reset'){
        if ( false !== strpos( $user->user_activation_key, ':' ) ) { // only set on reset pwd request! :-(
            list( $pass_request_time, $pass_key ) = explode( ':', $user->user_activation_key, 2 );
            $expiration_duration = apply_filters( 'password_reset_expiration', DAY_IN_SECONDS ); // hook sets this to a week for CC
            $expiration_time = $pass_request_time + $expiration_duration;
            if ( empty( $wp_hasher ) ) {
                require_once ABSPATH . WPINC . '/class-phpass.php';
                $wp_hasher = new PasswordHash( 8, true );
            }
            if(time() < $expiration_time && $wp_hasher->CheckPassword( $key, $pass_key )){
                // it's a valid password reset request
                return cc_login_set_password($email, cc_users_user_name($user, true), true);
            }
        }
        return cc_login_email_html($email, false, '', 'That link is invalid or has expired. Please login', 'error');
    }
    if($action == 'forgot'){
        return cc_login_request_reset_html( $email, cc_users_user_name($user, true) );
    }
    return cc_login_email_html($email);
}

// The html to invite a user to enter their email
function cc_login_email_html($email, $error=false, $error_msg='', $top_msg='', $top_msg_class=''){
    if($error){
        $error_class = 'error';
    }else{
        $error_class = '';
    }
    $html = '
        <form id="cc-login-email" class="animated-card needs-validation" data-type="email" novalidate>
            <div class="cc-login2-wrap wms-background animated-card-inner">
                <div class="row">
                    <div class="col-12 text-center">
                        <h3 class="cc-login-title">Sign in</h3>
                        <p class="cc-login-text">To continue to your account:</p>';
    if($top_msg <> ''){
        $html .= '  <p class="cc-login-top-msg '.$top_msg_class.'">'.$top_msg.'</p>';
    }
    $html .= '      </div>
                </div>
                <div class="row">
                    <div id="ccll-email-wrap" class="col-12 ccll-wrap mb-3 '.$error_class.'">
                        <label for="ccll-email" class="form-label">Email:</label>
                        <input type="email" id="ccll-email" class="form-control form-control-lg" value="'.$email.'" required>
                        <div class="invalid-feedback">Please enter your email address</div>
                    </div>
                </div>
                <div class="row mb-3">
                	<div id="ccll-email-help" class="col-12 col-md-8">'.$error_msg.'</div>
                    <div class="col-12 col-md-4 text-end">
                        <button type="submit" id="cclle-submit" class="btn btn-primary">Next</button>
                    </div>
                </div>
            </div>
        </form>';
    return $html;
}

// the html to invite a user to enter their password
function cc_login_password_html($email, $name, $error=false, $error_msg=''){
    if($error){
        $error_class = 'error';
    }else{
        $error_class = '';
    }
    if($name == ''){
        $name = 'Sign in';
    }
    $html = '
        <form id="cc-login-pwd" class="animated-card needs-validation" data-type="pwd" novalidate>
            <div class="cc-login2-wrap wms-background animated-card-inner">
                <div class="row">
                    <div class="col-12 text-center">
                        <h3 class="cc-login-title">Hi '.$name.'</h3>
                        <p class="cc-login-text"><a href="javascript:void(0);" id="ccllp-back" class="">'.$email.'</a></p>
                    </div>
                </div>
                <div class="row">
                    <div id="ccll-password-wrap" class="col-12 ccll-wrap mb-3 '.$error_class.'">
                        <label for="ccll-password" class="form-label">Password:</label>
                        <input type="password" id="ccll-password" class="form-control form-control-lg" required>
                        <div class="invalid-feedback">Please enter your password</div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-8">
                        <div id="ccll-password-help" class="'.$error_class.'">'.$error_msg.'</div>
                        <div><a href="javascript:void(0);" id="ccllp-forgot" class="">Forgot password?</a></div>
                    </div>
                    <div class="col-4 text-end">
                        <button type="submit" id="ccllp-submit" class="btn btn-primary">Login</button>
                    </div>
                </div>
            </div>
        </form>';
    return $html;
}


// the html to set (and reset) your password
function cc_login_set_password($email, $name, $reset=false){
    if($name == ''){
        $name = 'Sign in';
    }
    $html = '
        <form id="cc-login-reset" class="animated-card needs-validation" novalidate>
            <div class="cc-login2-wrap wms-background animated-card-inner">
                <div class="row">
                    <div class="col-12 text-center">
                        <h3 class="cc-login-title">Hi '.$name.'</h3>
                        <p class="cc-login-text"><a href="javascript:void(0);" id="ccllp-back" class="">'.$email.'</a></p>';
    if($reset){
        $html .= '<p class="cc-login-reset-text reset">You can set a new password here.</p>';
    }else{
        $html .= '<p class="cc-login-reset-text set">Welcome! To access your account, please set a password.</p>';
    }
    $html .= '      </div>
                </div>
                <div class="ccll-pwd-fields-wrap mb-3 p-3">
                    <div class="row">
                        <div id="ccll-password1-wrap" class="col-12 ccll-wrap mb-3">
                            <label for="ccll-password1" class="form-label">Password:</label>
                            <input type="password" id="ccll-password1" class="ccll-password1 form-control form-control-lg" value="">
                        </div>
                        <div class="col-3">
                            Strength:
                        </div>
                        <div class="col-9">
                            <meter max="4" id="password-strength-meter" class="password-strength-meter"></meter>
                        </div>
                        <div class="col-12">
                            <p id="ccll-password1-help" class="ccll-password1-help">Password field is empty, please type something</p>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="ccll-pwd-fields-wrap mb-3 p-3">
                    <div class="row">
                        <div id="ccll-password2-wrap" class="col-12 ccll-wrap mb-3">
                            <label for="ccll-password2" class="form-label">Retype password:</label>
                            <input type="password" id="ccll-password2" class="form-control form-control-lg" value="">
                        </div>
                        <div id="ccll-password2-help" class="ccll-password2-help">Password field is empty, please type something</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <p id="ccll-wait-text" class="ccll-wait-text d-none small text-danger">Password strength must be "good" or "strong" and both passwords must match.</p>
                    </div>
                    <div class="col-6 text-end">
                        <button type="submit" id="cclls-submit" class="btn btn-primary" disabled>Next</button>
                    </div>
                </div>
            </div>
        </form>';
    return $html;
}

// process a submitted email address
add_action('wp_ajax_cclle_submit', 'cc_login_cclle_submit');
add_action('wp_ajax_nopriv_cclle_submit', 'cc_login_cclle_submit');
function cc_login_cclle_submit(){
    $response = array(
        'status' => 'error',
        'html' => '',
    );
    $email = '';
    if(isset($_POST['email']) && $_POST['email'] <> ''){
        $email = stripslashes( sanitize_email($_POST['email']) );
    }
    $user = cc_users_get_user($email);
    if($user){
        if( user_has_payment_pending_role( $user->ID ) ){
            $response['html'] = cc_login_email_html($email, true, 'There is a problem logging in to your account. Please <a href="/contact">contact us</a> for assistance.');
        }else{
            // when did the user last login (this could be as they registered ... even for a new user that has not yet set their pwd)
            $last_login = get_user_meta($user->ID, 'last_login', true);
            // do we need a new password to be set? - set to yes when a new user registers
            $force_new_password = get_user_meta($user->ID, 'force_new_password', true);
            // has a new password just been set?
            $new_password = get_user_meta($user->ID, 'new_password', true);
            // if new_password has just been set, we always want them to login now
            // if not, then unless force_new_password = '' and they have logged in before, we always need then to choose a new password now
            if($new_password == 'yes'){
                // login
                $response['html'] = cc_login_password_html( $user->user_email, cc_users_user_name($user, true) );
            }else{
                if($force_new_password == '' && $last_login <> 'never' && $last_login <> ''){
                    // login
                    $response['html'] = cc_login_password_html( $user->user_email, cc_users_user_name($user, true) );
                }else{
                    // set password
                    $response['html'] = cc_login_set_password( $user->user_email, cc_users_user_name($user, true) );
                }
            }
            $response['status'] = 'ok';
        }
    }else{
        $response['html'] = cc_login_email_html($email, true, 'We don\'t recognise that email. Please use the email you registered with');
    }
    echo json_encode($response);
    die();
}

// process a submitted password (for logon)
add_action('wp_ajax_ccllp_submit', 'cc_login_ccllp_submit');
add_action('wp_ajax_nopriv_ccllp_submit', 'cc_login_ccllp_submit');
function cc_login_ccllp_submit(){
    $response = array(
        'status' => 'error',
        'html' => '',
        'page' => '',
    );
    $email = '';
    if(isset($_POST['email']) && $_POST['email'] <> ''){
        $email = stripslashes( sanitize_email($_POST['email']) );
    }
    $pass = '';
    if(isset($_POST['password']) &&  $_POST['password'] <> ''){
        $pass = sanitize_text_field($_POST['password']);
    }
    $creds = array(
        'user_login'    => $email,
        'user_password' => $pass,
        'remember'      => true
    );
    $user = wp_signon( $creds, is_ssl() );
    if(is_wp_error($user)){
        $user = cc_users_get_user($email);
        if($user){
            $response['html'] = cc_login_password_html( $email, cc_users_user_name($user, true), true, 'Incorrect login details, please try again' );
        }else{
            $response['html'] = cc_login_email_html($email, true, 'We don\'t recognise that email. Please use the email you registered with');
        }
    }elseif(!$user->has_cap('read')){
        // no role
        wp_logout();
        $response['html'] = cc_login_email_html('', true, 'Invalid email or password. Please try again');
    }else{
        wp_set_current_user( $user->ID );
        update_user_meta($user->ID, 'last_login', time());
        update_user_meta($user->ID, 'new_password', '');
        update_user_meta($user->ID, 'force_new_password', '');
        $response['status'] = 'ok';
        if(current_user_can('edit_pages')){
            $response['page'] = '/wp-admin';
        }else{
            $response['page'] = '/my-account';
        }
    }
    echo json_encode($response);
    die();
}

// go back from password to email form
add_action('wp_ajax_ccllp_back', 'cc_login_ccllp_back');
add_action('wp_ajax_nopriv_ccllp_back', 'cc_login_ccllp_back');
function cc_login_ccllp_back(){
    $response = array(
        'status' => 'error',
        'html' => '',
    );
    $email = '';
    if(isset($_POST['email']) &&  $_POST['email'] <> ''){
        $email = stripslashes( sanitize_email($_POST['email']) );
    }
    $user = cc_users_get_user($email);
    if($user){
        if( user_has_payment_pending_role( $user->ID ) ){
            $response['html'] = cc_login_email_html($email, true, 'There is a problem logging in to your account. Please <a href="/contact">contact us</a> for assistance.');
        }else{
            $response['html'] = cc_login_email_html($email);
            $response['status'] = 'ok';
        }
    }else{
        $response['html'] = cc_login_email_html($email, true, 'We don\'t recognise that email. Please use the email you registered with');
    }
    echo json_encode($response);
    die();
}

// forgotten password
add_action('wp_ajax_ccllp_forgot', 'cc_login_ccllp_forgot');
add_action('wp_ajax_nopriv_ccllp_forgot', 'cc_login_ccllp_forgot');
function cc_login_ccllp_forgot(){
    $response = array(
        'status' => 'error',
        'html' => '',
    );
    $email = '';
    if(isset($_POST['email']) && $_POST['email'] <> ''){
        $email = stripslashes( sanitize_email($_POST['email']) );
    }
    $user = get_user_by( 'email', $email );
    $response['html'] = cc_login_request_reset_html( $email, cc_users_user_name($user, true) );
    echo json_encode($response);
    die();
}

// the html to invite a user to initiate a password reset email
function cc_login_request_reset_html($email, $name){
    if($name == ''){
        $name = 'Sign in';
    }
    $html = '
        <form id="cc-login-rconf" class="animated-card">
            <div class="cc-login2-wrap wms-background animated-card-inner">
                <div class="row mb-3">
                    <div class="col-12 text-center">
                        <h3 class="cc-login-title">Hi '.$name.'</h3>
                        <p class="cc-login-text"><a href="javascript:void(0);" id="ccllp-back" class="">'.$email.'</a></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">
                        <p>Forgotten your password? Not a problem. We can send you an email with a link to reset your password. This will be sent to '.$email.'. If you cannot access that email address then please <a href="/contact">contact us</a> for help.</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <a href="javascript:void(0);" id="ccllr-back" class="">Back to login</a>
                    </div>
                    <div class="col-6 text-right">
                        <button type="submit" id="ccllr-submit" class="btn btn-primary">Send password reset email</button>
                    </div>
                </div>
            </div>
        </form>';
    return $html;
}

// trigger password reset email
add_action('wp_ajax_ccllr_submit', 'cc_login_ccllr_submit');
add_action('wp_ajax_nopriv_ccllr_submit', 'cc_login_ccllr_submit');
function cc_login_ccllr_submit(){
    $response = array(
        'status' => 'error',
        'html' => '',
    );
    $email = '';
    if(isset($_POST['email']) && $_POST['email'] <> ''){
        $email = sanitize_email($_POST['email']);
    }
    // $response['email'] = $email;
    if($email <> ''){
        $user = get_user_by( 'email', $email );
        if($user){
            // $response['user_id'] = $user->ID;
            // $key = get_password_reset_key( $user );
            $key = get_password_reset_key( $user );
            if ( !is_wp_error( $key ) ){
                // $response['key'] = $key;
                $subscriber = mailster('subscribers')->get_by_mail($email);
                if($subscriber){
                    $mailster_tags = array(
                        'password_reset_link' => network_site_url( "member-login?action=rp&key=$key&login=" . rawurlencode( $user->user_email ), 'login' ),
                    );
                    $mailster_hook = 'send_password_reset_email';
                    sysctrl_mailster_ar_hook($mailster_hook, $subscriber->ID, $mailster_tags);
                    $message = '<p>A password reset email is on its way to you now. If you don\'t receive it within a couple of minutes, please check your junk mail folder.</p>';
                    $response['html'] = cc_login_message_html($email, cc_users_user_name($user, true), $message);
                    $response['status'] = 'ok';
                    echo json_encode($response);
                    die();
                }
            }
        }
    }
    $message = '<p>Something went wrong and we were unable to send you a password reset email. Please <a href="/contact">contact us</a> for help.</p>';
    $response['html'] = cc_login_message_html($email, cc_users_user_name($user, true), $message);
    echo json_encode($response);
    die();
}

// html for a general messsage to the user
// include <p> tags etc in the message if you want
function cc_login_message_html($email, $name, $message){
    if($name == ''){
        $name = 'Sign in';
    }
    $html = '
        <form id="cc-login-login" class="animated-card">
            <div class="cc-login2-wrap wms-background animated-card-inner">
                <div class="row mb-3">
                    <div class="col-12 text-center">
                        <h3 class="cc-login-title">Hi '.$name.'</h3>
                        <p class="cc-login-text"><a href="javascript:void(0);" id="ccllp-back" class="">'.$email.'</a></p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-12">'.$message.'</div>
                </div>
            </div>
        </form>';
    return $html;
}

// remove admin bar for all except admins
add_action('after_setup_theme', 'ccrecw_remove_admin_bar');
function ccrecw_remove_admin_bar() {
    if (!current_user_can('edit_pages') && !is_admin()) {
        show_admin_bar(false);
    }
}

// returns a message to be included in emails (for registrants only!)
// the link will allow new people to set a password and old people to login
// #### NOTE #### DO NOT USE QUOTES IN THE MESSAGE OR YOUR MESSAGE WILL NOT BE SENT!!!!!!!!!
function cc_login_account_password_msg($payment_data){
    global $rpm_theme_options;
    if($payment_data['reg_userid'] > 0){
        $user_id = $payment_data['reg_userid'];
    }else{
        // pre Jan 2023 registrations
        $user_id = cc_myacct_get_user($payment_data, 'r');
    }
    $user = get_user_by('id', $user_id);
    if($user){
        $my_acct_link = '<a href="'.esc_url( site_url("my-account/?login=".rawurlencode( $user->user_email ) ) ).'">My Account</a>';
    }else{
        $my_acct_link = '<a href="'.esc_url( site_url("my-account/" ) ).'">My Account</a>';
    }    
    if($payment_data['type'] == 'recording'){
        $acct_pwd_msg = '<br><br>To view details about your training and to watch your recordings, login here:<br><br>'.$my_acct_link;
    }else{
        $workshop_webinar = get_post_meta( $payment_data['workshop_id'], 'type', true );
        if($workshop_webinar == 'webinar'){
            $acct_pwd_msg = $rpm_theme_options['mailster-webinar-msg'];
        }else{
            $acct_pwd_msg = $rpm_theme_options['mailster-workshop-msg'];
        }
        $acct_pwd_msg = str_replace('{my_account_link}', $my_acct_link, $acct_pwd_msg);
    }
    return $acct_pwd_msg;
}

// change the default password strength messages
add_action( 'wp_enqueue_scripts', 'cc_login_strength_meter_localise_script' );
function cc_login_strength_meter_localise_script() {
    wp_localize_script( 'password-strength-meter', 'pwsL10n', array(
        'empty'    => 'Empty: Password field is empty, please type something',
        'short'    => 'Short: That\'s too short, please keep typing',
        'bad'      => 'Bad: Too simple, please try something more complex',
        'good'     => 'Good: That\'s ok but a more complex one would be better',
        'strong'   => 'Strong: Great, that\'s a good, strong, password',
        'mismatch' => 'Mismatch: Passwords are different, please try again',
    ) );
}

// process a password set/reset
add_action('wp_ajax_cclls_submit', 'cc_login_cclls_submit');
add_action('wp_ajax_nopriv_cclls_submit', 'cc_login_cclls_submit');
function cc_login_cclls_submit(){
    $response = array(
        'status' => 'error',
        'html' => '',
    );
    $email = '';
    if(isset($_POST['email']) && $_POST['email'] <> ''){
        // now we can use sanitize_email as we have sent back an email even if a username was entered earlier
        $email = sanitize_email($_POST['email']);
    }
    $pass = '';
    if(isset($_POST['password']) &&  $_POST['password'] <> ''){
        $pass = sanitize_text_field($_POST['password']);
    }
    if($email == '' || $pass == ''){
        $response['html'] = cc_login_email_html($email, false, '', 'Something went wrong - please try again', 'error');
    }else{
        $user = get_user_by('email', $email);
        if($user){
            reset_password( $user, $pass );
            update_user_meta($user->ID, 'new_password', 'yes');
            update_user_meta($user->ID, 'force_new_password', '');
            $response['status'] = 'ok';
            $response['html'] = cc_login_email_html($email, false, '', 'Password set. Now please login', 'success');
        }else{
            $response['html'] = cc_login_email_html($email, false, '', 'Something went wrong - please try again', 'error');
        }
    }
    echo json_encode($response);
    die();
}

// login - from the old login page (now the CNWL/Org login page)
add_action('wp_ajax_ccll_submit', 'cc_login_ccll_submit');
add_action('wp_ajax_nopriv_ccll_submit', 'cc_login_ccll_submit');
function cc_login_ccll_submit(){
    $response = array(
        'status' => 'error',
        'page' => '',
        'code' => '0100',
    );
    $email = '';
    if(isset($_POST['email']) &&  $_POST['email'] <> ''){
        $email = stripslashes( sanitize_email($_POST['email']) );
    }
    $pass = '';
    if(isset($_POST['pass']) &&  $_POST['pass'] <> ''){
        $pass = sanitize_text_field($_POST['pass']);
    }
    list($local_part, $domain_name) = explode('@', $email);
    if( $pass == CNWL_PASSCODE && $domain_name == 'nhs.net' ){
        // ok to let them in (probably)
        $user = get_user_by('email', $email);
        $user_blocked = false;
        if($user){
            $user_id = $user->ID;
            $response['code'] = '0200';
            if($user->has_cap('read')){
                $response['code'] = '0210';
                $portal_user = get_user_meta($user_id, 'portal_user', true);
                if($portal_user <> 'cnwl'){
                    $response['code'] = '0220';
                    // an existing user, but not a CNWL user .... until now
                    if(cc_login_allow_another_cnwl_user()){
                        $response['code'] = '0230';
                        // we'll set them as a CNWL user and change their password
                        $args = array(
                            'ID' => $user_id,
                            'user_pass' => CNWL_PASSCODE,
                        );
                        wp_update_user($args);
                        update_user_meta($user_id, 'portal_user', 'cnwl');
                    }else{
                        $response['code'] = '0240';
                        $user_blocked = true;
                    }
                }else{
                    $response['code'] = '0250';
                    // existing cnwl user
                }
            }else{
                $response['code'] = '0260';
                // no role on this site
                $user_blocked = true;
            }
        }else{
            $response['code'] = '0300';
            // new user ... 
            if(cc_login_allow_another_cnwl_user()){
                $response['code'] = '0310';
                // create user
                $args = array(
                    'user_login' => $email,
                    'user_pass' => $pass,
                    'user_email' => $email,
                    'role' => 'subscriber',
                );
                $user_id = wp_insert_user( $args );
                update_user_meta($user_id, 'portal_user', 'cnwl');
            }else{
                $response['code'] = '0320';
                $user_blocked = true;
            }
        }
        if(!$user_blocked){
            $response['code'] = '0400';
            // log them in ...
            $creds = array(
                'user_login'    => $email,
                'user_password' => $pass,
                'remember'      => true
            );
            $user_logged_in = wp_signon( $creds, is_ssl() );
            if(!is_wp_error($user_logged_in)){
                $response['code'] = '0410';
                wp_set_current_user( $user_logged_in->ID );
                update_user_meta($user_logged_in->ID, 'last_login', time());
                update_user_meta($user_logged_in->ID, 'new_password', '');
                update_user_meta($user_logged_in->ID, 'force_new_password', '');
                $response['status'] = 'ok';
                if(current_user_can('manage_options')){
                    $response['code'] = '0420';
                    $response['page'] = '/wp-admin';
                }else{
                    $response['code'] = '0430';
                    $response['page'] = '/my-account';
                }
            }else{
                $response['code'] = '0440';
                // somehow they changed their password ... let's change it back!
                $args = array(
                    'ID' => $user_id,
                    'user_pass' => CNWL_PASSCODE,
                );
                wp_update_user($args);
                // and try to log them in again
                $user_logged_in_2 = wp_signon( $creds, is_ssl() );
                if(!is_wp_error($user_logged_in_2)){
                    $response['code'] = '0450';
                    wp_set_current_user( $user_logged_in_2->ID );
                    update_user_meta($user_logged_in_2->ID, 'last_login', time());
                    update_user_meta($user_logged_in_2->ID, 'new_password', '');
                    update_user_meta($user_logged_in_2->ID, 'force_new_password', '');
                    $response['status'] = 'ok';
                    if(current_user_can('manage_options')){
                        $response['page'] = '/wp-admin';
                    }else{
                        $response['page'] = '/my-account';
                    }
                }else{
                    $response['code'] = '0460';
                    // something wierd has happened
                    // cannot log them in
                }
            }
        }else{
            // just to be safe ...
            wp_logout();
        }
    }elseif( $pass == NLFT_PASSCODE && $domain_name == 'nhs.net' ){
        // ok to let them in (probably)
        $user = get_user_by('email', $email);
        $user_blocked = false;
        if($user){
            $user_id = $user->ID;
            $response['code'] = '0200';
            if($user->has_cap('read')){
                $response['code'] = '0210';
                $portal_user = get_user_meta($user_id, 'portal_user', true);
                if($portal_user <> 'nlft'){
                    // an existing user, but not a NLFT user .... until now
                    $response['code'] = '0230';
                    // we'll set them as a NLFT user and change their password
                    $args = array(
                        'ID' => $user_id,
                        'user_pass' => NLFT_PASSCODE,
                    );
                    wp_update_user($args);
                    update_user_meta($user_id, 'portal_user', 'nlft');
                }else{
                    $response['code'] = '0250';
                    // existing nlft user
                }
            }else{
                $response['code'] = '0260';
                // no role on this site
                $user_blocked = true;
            }
        }else{
            // new user ... 
            $response['code'] = '0310';
            // create user
            $args = array(
                'user_login' => $email,
                'user_pass' => $pass,
                'user_email' => $email,
                'role' => 'subscriber',
            );
            $user_id = wp_insert_user( $args );
            update_user_meta($user_id, 'portal_user', 'nlft');
        }
        if(!$user_blocked){
            $response['code'] = '0400';
            // try to log them in ...
            $creds = array(
                'user_login'    => $email,
                'user_password' => $pass,
                'remember'      => true
            );
            $user_logged_in = wp_signon( $creds, is_ssl() );
            if(!is_wp_error($user_logged_in)){
                // we are logged in
                $response['code'] = '0410';
                wp_set_current_user( $user_logged_in->ID );
                update_user_meta($user_logged_in->ID, 'last_login', time());
                update_user_meta($user_logged_in->ID, 'new_password', '');
                update_user_meta($user_logged_in->ID, 'force_new_password', '');
                $response['status'] = 'ok';
                if(current_user_can('manage_options')){
                    $response['code'] = '0420';
                    $response['page'] = '/wp-admin';
                }else{
                    $response['code'] = '0430';
                    $response['page'] = '/my-account';
                }
            }else{
                // valid nlft user but passcode is not their password
                // if this is an admin then this might be cos they have a better password
                $portal_admin = get_user_meta( $user_id, 'portal_admin', true);
                if( $portal_admin == 'yes' ){
                    // somebody is trying to login with the NLFT_PASSCODE but they should be using a better password instead
                    // we need to block this attempted login
                    $response['code'] = '0470';
                }else{
                    // not an admin so they should be using the passcode but somehow the password has been set to something else
                    // let's change it back
                    $response['code'] = '0440';
                    $args = array(
                        'ID' => $user_id,
                        'user_pass' => NLFT_PASSCODE,
                    );
                    wp_update_user($args);
                    // and try to log them in again
                    $user_logged_in_2 = wp_signon( $creds, is_ssl() );
                    if(!is_wp_error($user_logged_in_2)){
                        $response['code'] = '0450';
                        wp_set_current_user( $user_logged_in_2->ID );
                        update_user_meta($user_logged_in_2->ID, 'last_login', time());
                        update_user_meta($user_logged_in_2->ID, 'new_password', '');
                        update_user_meta($user_logged_in_2->ID, 'force_new_password', '');
                        $response['status'] = 'ok';
                        if(current_user_can('manage_options')){
                            $response['page'] = '/wp-admin';
                        }else{
                            $response['page'] = '/my-account';
                        }
                    }else{
                        $response['code'] = '0460';
                        // something wierd has happened
                        // cannot log them in
                    }
                }
            }
        }else{
            // just to be safe ...
            wp_logout();
        }
    }else{
        // org admins are now allowed to use real passwords
        $user = get_user_by('email', $email);
        if( $user && $user->has_cap( 'read' ) ){
            $portal_user = get_user_meta( $user->ID, 'portal_user', true );
            $portal_admin = get_user_meta( $user->ID, 'portal_admin', true);
            if( $user->has_cap( 'read' ) && $portal_user <> '' && $portal_admin == 'yes' ){
                $creds = array(
                    'user_login'    => $email,
                    'user_password' => $pass,
                    'remember'      => true
                );
                // try to log them in
                $user_logged_in = wp_signon( $creds, is_ssl() );
                if( ! is_wp_error( $user_logged_in )){
                    // logged in ok
                    $response['code'] = '0550';
                    wp_set_current_user( $user_logged_in->ID );
                    update_user_meta($user_logged_in->ID, 'last_login', time());
                    update_user_meta($user_logged_in->ID, 'new_password', '');
                    update_user_meta($user_logged_in->ID, 'force_new_password', '');
                    $response['status'] = 'ok';
                    if(current_user_can('manage_options')){
                        $response['page'] = '/wp-admin';
                    }else{
                        $response['page'] = '/my-account';
                    }
                }else{
                    // password does not work
                    $response['code'] = '0560';
                }
            }else{
                // No authority for them to use anything other than the official passcode
                if($domain_name <> 'nhs.net'){
                    $response['code'] = '0530';
                }else{
                    $response['code'] = '0540';
                }
            }
        }else{
            // not allowed
            if( $pass <> CNWL_PASSCODE && $pass <> NLFT_PASSCODE ){
                $response['code'] = '0120';
            }
            if($domain_name <> 'nhs.net'){
                $response['code'] = '0130';
            }
        }
    }
    echo json_encode($response);
    die();
}

// do we want to allow more CNWL users?
// returns bool
function cc_login_allow_another_cnwl_user(){
    // now allowing an unlimited number!
    return true;
    $args = array(
        'role' => 'Subscriber',
        'meta_key' => 'portal_user',
        'meta_value' => 'cnwl'
    );
    $users = get_users($args);
    if(count($users) > 100) return false;
    return true;
}

// redirect from the normal login page to our page
// std WP rp link looks like this: http://contextual.roderickpughmarketing.com/wp-login.php?action=rp&key=Flu7rNpSFergcbitmf5O&login=test2%40roderickpughmarketing.com
add_action('init', 'cc_login_redirect_wp_login');
function cc_login_redirect_wp_login() {
    // WP tracks the current page - global the variable to access it
    global $pagenow;
    // Check if a $_GET['action'] is set, and if so, load it into $action variable
    $action = (isset($_GET['action'])) ? $_GET['action'] : '';
    // Check if we're on the login page, and ensure the action is not 'logout'
    if( $pagenow == 'wp-login.php' && $action <> 'logout') {
        // var_dump($_GET);
        // echo '<br>';
        // echo add_query_arg($_GET, '/member-login');
        // echo '<br>';
        // echo esc_url( add_query_arg($_GET, '/member-login') );
        // echo '<br>';
        // echo wp_sanitize_redirect(esc_url( add_query_arg($_GET, '/member-login') ));
        // wp_redirect( esc_url( add_query_arg($_GET, '/member-login') ) );
        wp_redirect( add_query_arg($_GET, '/member-login') );
        exit;
    }
    // if( $pagenow == 'wp-login.php' && ( ! $action || ( $action && ! in_array($action, array('logout', 'lostpassword', 'rp', 'resetpass'))))) {
    // if( $pagenow == 'wp-login.php' && $action == 'logout') {
        // Load the home page url
        // $page = get_bloginfo('url');
        // Redirect to the home page
        // wp_redirect($page);
        // Stop execution to prevent the page loading for any reason
        // exit();
    // }
}
