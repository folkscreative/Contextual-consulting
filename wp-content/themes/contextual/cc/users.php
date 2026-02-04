<?php
/**
 * User functions
 */

// checks to see if a user is logged in and has read access
// so that people with "no role" are blocked
// returns bool
function cc_users_is_valid_user_logged_in(){
    // ccpa_write_log('cc_users_is_valid_user_logged_in');
    // ccpa_write_log('user_id:'.get_current_user_id());
    if(!is_user_logged_in()) return false;
    // ccpa_write_log('user logged in');
    if(current_user_can('read')) return true;
    // ccpa_write_log('user has no permissions');
    wp_logout();
    return false;
}

// gets a user object
// looks for an email or a userid or a user email alias
// NOTE: $email might not be an email address ... it could be a username ... but only for admins
// returns a user object or false
function cc_users_get_user($email, $also_admin=true){
    // ccpa_write_log('cc_users_get_user '.$email);
    if($email == '') return false;
    if($email == sanitize_email($email)){
        // it's a valid email address
        // ccpa_write_log('valid email address');
        if($also_admin){
            $roles = array('subscriber', 'payment_pending', 'administrator', 'editor');
        }else{
            $roles = array('subscriber', 'payment_pending');
        }
        $user_query = new WP_User_Query( array(
            'role__in' => $roles,
            'search' => $email,
            'search_columns' => array( 'user_email' )
        ) );
        // ccpa_write_log($user_query);
        // ccpa_write_log('count: '.$user_query->get_total());
        if ( empty( $user_query->get_results() ) ){
            // not found
            // ccpa_write_log('not found');
            $user_query = new WP_User_Query( array( 
                'role' => 'Subscriber',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'user_email_alias_1',
                        'value' => $email,
                        'compare' => '=',
                    ),
                    array(
                        'key' => 'user_email_alias_2',
                        'value' => $email,
                        'compare' => '=',
                    ),
                    array(
                        'key' => 'user_email_alias_3',
                        'value' => $email,
                        'compare' => '=',
                    ),
                ),
            ) );
            // ccpa_write_log('count: '.$user_query->get_total());
        }
    }elseif($also_admin){
        // treat it as a username
        // ccpa_write_log('username');
        $user_query = new WP_User_Query( array(
            'role' => 'Administrator',
            'search' => $email,
            'search_columns' => array( 'user_login' )
        ) );
        // ccpa_write_log('count: '.$user_query->get_total());
    }
    if ( ! empty( $user_query->get_results() ) ){
        // ccpa_write_log('found');
        foreach ( $user_query->get_results() as $user ){
            return $user;
        }
    }
    // ccpa_write_log('nothing found');
    return false;
}

// is the email address the right one for the currently logged in user?
// returns bool
function cc_users_email_matches_curr_user($email){
    // ccpa_write_log('cc_users_email_matches_curr_user email: '.$email);
    if(cc_users_is_valid_user_logged_in()){
        // ccpa_write_log('cc_users_is_valid_user_logged_in returned true');
        $user = wp_get_current_user();
        // ccpa_write_log('current user id: '.$user->ID);
        if($user->user_email == $email){
            // ccpa_write_log('primary emails match');
            return true;
        }
        for ($i=1; $i < 4; $i++) { 
            if($email == get_user_meta($user->ID, 'user_email_alias_'.$i, true)){
                // ccpa_write_log('alias email '.$i.' matches');
                return true;
            }
        }
    }
    // ccpa_write_log('cc_users_email_matches_curr_user returning false');
    return false;
}

// returns a user's name if possible
// $user is a user object
function cc_users_user_name($user, $first_only=false){
    $user_meta = get_user_meta($user->ID);
    $user_name = '';
    if($user_meta){
        $user_name = $user_meta['first_name'][0];
        if(!$first_only){
            if($user_name <> ''){
                $user_name .= ' ';
            }
            $user_name .= $user_meta['last_name'][0];
        }
    }
    return $user_name;
}

// returns a user's address
function cc_users_user_address($user_id, $return='string'){
    $fields = array( 'org_name', 'line_1', 'line_2', 'town', 'county', 'postcode', 'country' );
    if($return == 'string'){
        $result = '';
    }else{
        $result = array();
    }
    foreach ($fields as $field) {
        if( $field == 'org_name' ){
            $meta_key = $field;
        }else{
            $meta_key = 'address_'.$field;
        }
        $value = get_user_meta($user_id, $meta_key, true);
        if($field == 'country' && $value <> ''){
            $value = ccpa_countries_name($value);
        }
        if($return == 'string'){
            if($value <> '' && $result <> ''){
                $result .= ', ';
            }
            $result .= $value;
        }else{
            $result[$field] = $value;
        }
    }
    return $result;
}

// returns a user's address from form data
function cc_users_user_address_formdata( $form_data, $return='string' ){
    $fields = array( 'org_name', 'line_1', 'line_2', 'town', 'county', 'postcode', 'country' );
    if($return == 'string'){
        $result = '';
    }else{
        $result = array();
    }
    foreach ($fields as $field) {
        if( $field == 'org_name' ){
            $meta_key = $field;
        }else{
            $meta_key = 'address_'.$field;
        }
        $value = $form_data[$meta_key] ?? '';
        if($field == 'country' && $value <> ''){
            $value = ccpa_countries_name($value);
        }
        if($return == 'string'){
            if($value <> '' && $result <> ''){
                $result .= ', ';
            }
            $result .= $value;
        }else{
            $result[$field] = $value;
        }
    }
    return $result;
}

// return the user's phone number
function cc_users_user_phone($user_id){
    return esc_attr(get_user_meta($user_id, 'phone', true));
}

// returns the core details for a user
function cc_users_user_details($user=false){
    $result = array(
        'email' => '',
        'firstname' => '',
        'lastname' => '',
        'address_line_1' => '',
        'address_line_2' => '',
        'address_town' => '',
        'address_county' => '',
        'address_postcode' => '',
        'address_country' => '',
        'phone' => '',
        'job' => '',
        // nlft ...
        'org_name' => '',
        'nlft_service_type' => '',
        'nlft_borough' => '',
        'nlft_team' => '',
    );
    if($user){
        $result['email'] = $user->user_email;
        $result['firstname'] = $user->user_firstname;
        $result['lastname'] = $user->user_lastname;
        $result['org_name'] = get_user_meta($user->ID, 'org_name', true);
        $result['address_line_1'] = get_user_meta($user->ID, 'address_line_1', true);
        $result['address_line_2'] = get_user_meta($user->ID, 'address_line_2', true);
        $result['address_town'] = get_user_meta($user->ID, 'address_town', true);
        $result['address_county'] = get_user_meta($user->ID, 'address_county', true);
        $result['address_postcode'] = get_user_meta($user->ID, 'address_postcode', true);
        $result['address_country'] = get_user_meta($user->ID, 'address_country', true);
        $result['phone'] = get_user_meta($user->ID, 'phone', true);
        $result['job'] = get_user_meta($user->ID, 'job', true);
        $result['nlft_service_type'] = get_user_meta($user->ID, 'nlft_service_type', true);
        $result['nlft_borough'] = get_user_meta($user->ID, 'nlft_borough', true);
        $result['nlft_team'] = get_user_meta($user->ID, 'nlft_team', true);
    }
    return $result;
}

// update user details or insert new user with these details
// returns user_id
function cc_users_update_details($values){
    if($values['user_id'] > 0){
        $user_id = $values['user_id'];
        $args = array(
            'ID' => $user_id,
            'user_email' => $values['email'],
            'first_name' => $values['firstname'],
            'last_name' => $values['lastname'],
        );
        wp_update_user($args);
    }else{
        // user_login can only be 60 chars. Uniqid is 13 chars.
        $user_login = $values['firstname'].' '.$values['lastname'];
        if(strlen($user_login) > 46){
            $user_login = substr($user_login, 0, 46);
        }
        $user_login .= ' '.uniqid();
        $args = array(
            'user_login' => $user_login,
            'user_pass' => $values['password'],
            'user_email' => $values['email'],
            'first_name' => $values['firstname'],
            'last_name' => $values['lastname'],
        );
        $user_id = wp_insert_user($args);
        update_user_meta($user_id, 'source', $values['source']);
    }
    update_user_meta($user_id, 'org_name', $values['org_name']);
    update_user_meta($user_id, 'address_line_1', $values['address_line_1']);
    update_user_meta($user_id, 'address_line_2', $values['address_line_2']);
    update_user_meta($user_id, 'address_town', $values['address_town']);
    update_user_meta($user_id, 'address_county', $values['address_county']);
    update_user_meta($user_id, 'address_postcode', $values['address_postcode']);
    update_user_meta($user_id, 'address_country', $values['address_country']);
    update_user_meta($user_id, 'phone', $values['phone']);
    update_user_meta($user_id, 'job', $values['job']);
    if( isset( $values['nlft_service_type'] ) ){
        update_user_meta($user_id, 'nlft_service_type', $values['nlft_service_type']);    
    }
    if( isset( $values['nlft_borough'] ) ){
        update_user_meta($user_id, 'nlft_borough', $values['nlft_borough']);    
    }
    if( isset( $values['nlft_team'] ) ){
        update_user_meta($user_id, 'nlft_team', $values['nlft_team']);    
    }
    $user = get_user_by('ID', $user_id);
    cc_mailsterint_update_subs_name($user);
    cc_mailsterint_update_region($user);
    /*
    if($values['mailing_list'] == 'yes'){
        update_user_meta($user_id, 'mailing_list', $values['mailing_list']);
        cc_mailsterint_newsletter_subscribe($user);
    }
    */
    return $user_id;
}

// the user address on the edit user page
function cc_users_edit_contacts($user) {
    if(isset($user->ID)){
        $user_id = $user->ID;
    }else{
        $user_id = 0;
    }
    ?>
    <h2>Address</h2>
    <table class="form-table">
        <?php
        $fields = array('line_1', 'line_2', 'town', 'county', 'postcode', 'country');
        foreach ($fields as $field) {
            ?>
            <tr>
                <th><label for="address_<?php echo $field; ?>"><?php echo ucfirst($field); ?></label></th>
                <td>
                    <?php if($field == 'country'){ ?>
                        <select name="address_country" id="address_country">
                            <option>Please select ...</option>
                            <?php echo ccpa_countries_options(esc_attr(get_user_meta($user_id, 'address_country', true))); ?>
                        </select>
                    <?php }else{ ?>
                        <input
                            type="text"
                            value="<?php echo esc_attr(get_user_meta($user_id, 'address_'.$field, true)); ?>"
                            name="address_<?php echo $field; ?>"
                            id="address_<?php echo $field; ?>"
                        >
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </table>
    <h2>Other</h2>
    <table class="form-table">
        <tr>
            <th><label for="phone">Phone</label></th>
            <td>
                <input type="text" id="phone" name="phone" value="<?php echo esc_attr(get_user_meta($user_id, 'phone', true)); ?>">
            </td>
        </tr>
        <tr>
            <th><label for="source">Source</label></th>
            <td>
                <input type="text" id="source" name="source" value="<?php echo esc_attr(get_user_meta($user_id, 'source', true)); ?>">
            </td>
        </tr>
        <tr>
            <th><label for="mailing_list">Newsletter list</label></th>
            <td>
                <input type="text" id="mailing_list" name="mailing_list" value="<?php echo esc_attr(get_user_meta($user_id, 'mailing_list', true)); ?>">
                <p class="description">This was when they signed up, things may have changed since then!</p>
            </td>
        </tr>
        <tr>
            <th><label for="job">Profession</label></th>
            <td>
                <?php
                $user_job = get_user_meta( $user_id, 'job', true);
                $portal_user = get_user_meta($user_id, 'portal_user', true);
                ?>
                <select name="job" id="job">
                    <option value="">Please select ...</option>
                    <?php
                    if($portal_user <> ''){
                        echo professions_options( $portal_user, $user_job ); // doesn't allow for "other" jobs not in the list
                    }else{
                        echo professions_options('std', $user_job);
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="raf_flag">Refer a Friend Flag</label></th>
            <td>
                <?php 
                $raf_flag = get_user_meta($user_id, 'refer_a_friend', true);
                ?>
                <select name="raf_flag" id="raf_flag">
                    <option value="" <?php selected( '', $raf_flag ); ?>>Disabled</option>
                    <option value="yes" <?php selected( 'yes', $raf_flag ); ?>>Enabled</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="last_registration">Last registration (dd/mm/yyyy)</label></th>
            <td>
                <?php
                $last_registration = get_user_meta( $user_id, 'last_registration', true );
                $last_registration_disp = '';
                if( $last_registration <> '' ){
                    $dt = DateTime::createFromFormat( "Y-m-d H:i:s", $last_registration );
                    if( $dt ){
                        $last_registration_disp = $dt->format('d/m/Y');
                    }
                }
                ?>
                <input type="text" id="last_registration" name="last_registration" value="<?php echo $last_registration_disp; ?>">
            </td>
        </tr>
        <tr>
            <th><label for="invoice_allowed">Invoice allowed?</label></th>
            <td>
                <?php
                $invoice_allowed = get_user_meta($user_id, 'invoice_allowed', true);
                ?>
                <select name="invoice_allowed" id="invoice_allowed">
                    <option value="" <?php selected( '', $invoice_allowed ); ?>>No</option>
                    <option value="yes" <?php selected( 'yes', $invoice_allowed ); ?>>Yes</option>
                </select>
                <p class="description">Can this user pay by invoice instead of just by card?
                    <?php 
                    if( ccpa_invoice_payment_possible_for( 'id', $user_id ) ){
                        echo '<br><strong>Note: this user\'s domain is already set to allow payment by invoice.</strong>';
                    }
                    ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'cc_users_edit_contacts'); // editing your own profile
add_action('edit_user_profile', 'cc_users_edit_contacts'); // editing another user
add_action('user_new_form', 'cc_users_edit_contacts'); // creating a new user

function cc_users_save_contacts($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }
    $fields = array('line_1', 'line_2', 'town', 'county', 'postcode', 'country');
    foreach ($fields as $field) {
        update_user_meta($user_id, 'address_'.$field, $_REQUEST['address_'.$field]);
    }
    update_user_meta($user_id, 'phone', $_REQUEST['phone']);
    update_user_meta($user_id, 'source', $_REQUEST['source']);
    update_user_meta($user_id, 'mailing_list', $_REQUEST['mailing_list']);
    update_user_meta($user_id, 'job', $_REQUEST['job']);
    if( isset( $_REQUEST['raf_flag'] ) && $_REQUEST['raf_flag'] == 'yes' ){
        update_user_meta( $user_id, 'refer_a_friend', 'yes' );
    }else{
        delete_user_meta( $user_id, 'refer_a_friend' );
    }
    if( isset( $_REQUEST['invoice_allowed'] ) && $_REQUEST['invoice_allowed'] == 'yes' ){
        update_user_meta( $user_id, 'invoice_allowed', 'yes' );
    }else{
        delete_user_meta( $user_id, 'invoice_allowed' );
    }
    if( isset( $_REQUEST['last_registration'] ) && $_REQUEST['last_registration'] <> '' ){
        $dt = DateTime::createFromFormat( "d/m/Y", $_REQUEST['last_registration'] );
        if( $dt ){
            update_user_meta( $user_id, 'last_registration', $dt->format( 'Y-m-d H:i:s' ) );
        }
    }
}
add_action('personal_options_update', 'cc_users_save_contacts');
add_action('edit_user_profile_update', 'cc_users_save_contacts');
add_action('user_register', 'cc_users_save_contacts');

// is this user allowed to register?
// returns '' if ok to register and a reason (msg) if not.
// really just applies to portal users (CNWL) as they can only register once every 6 months
// v1.20 changed from 6 mths to 3 mths
function cc_users_ok_to_register(){
    if(cc_users_is_valid_user_logged_in()) {
        $user_id = get_current_user_id();
        if($user_id > 0){
            $portal_user = get_user_meta($user_id, 'portal_user', true);
            if($portal_user == 'cnwl'){
                $last_registration = get_user_meta( $user_id, 'last_registration', true );
                $three_months_ago = date('Y-m-d H:i:s', strtotime('-3 months'));
                if($last_registration <> '' && $last_registration > $three_months_ago){
                    $dt = DateTime::createFromFormat("Y-m-d H:i:s", $last_registration);
                    $dt = $dt->modify("+3 months");
                    return 'Sorry but you are only allowed to register for one training every three months. You last registered on '.date('jS M Y', strtotime($last_registration)).'. You will be able to register for more training after '.$dt->format('jS M Y').'.';
                }
            }
        }
    }
    return '';
}

// get's a user by name
// this can find mutliple users (or none of course)!
function cc_users_get_by_name($firstname, $lastname){
    global $wpdb;
    $sql = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'first_name' AND meta_value = '$firstname'";
    $firstname_ids = $wpdb->get_col($sql);
    $sql = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'last_name' AND meta_value = '$lastname'";
    $lastname_ids = $wpdb->get_col($sql);
    return array_intersect($firstname_ids, $lastname_ids);
}