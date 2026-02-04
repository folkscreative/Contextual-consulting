<?php
/**
 * Abandoned cart processes ...... NOT USED!!!
 */

function create_abandoned_cart_table() {
    global $wpdb;

    $cc_abandoned_registrations_db_ver = 1;
    $installed_table_ver = get_option('cc_abandoned_registrations_db_ver');
    if($installed_table_ver <> $cc_abandoned_registrations_db_ver){

        $table_name = $wpdb->prefix . 'abandoned_registrations';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            subscriber_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            email_stage TINYINT(1) DEFAULT 0, -- 0 = no email, 1 = email1 sent, etc.
            completed BOOLEAN DEFAULT 0,
            token VARCHAR(64) NOT NULL,
            data LONGTEXT NOT NULL,
            PRIMARY KEY (id),
            INDEX (email),
            INDEX (created_at),
            INDEX (token)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('cc_abandoned_registrations_db_ver', $cc_abandoned_registrations_db_ver);

    }
}
add_action('init', 'create_abandoned_cart_table');


function log_abandoned_registration( $user_id, $data ) {
    global $wpdb;
    return;
    $table = $wpdb->prefix . 'abandoned_registrations';

    $user = get_user_by('id', $user_id);
    if($user){
        // we need a subscriber_id
        $subscriber = mailster( 'subscribers' )->get_by_mail( $user->user_email );
        if( $subscriber ){
            $subscriber_id = $subscriber->ID;
        }else{
            // add subscriber
            $userdata = array(
                'email' => $user->user_email,
            );
            if( $firstname <> '' ){
                $userdata['firstname'] = $user->first_name;
            }
            if( $lastname <> '' ){
                $userdata['lastname'] = $user->last_name;
            }
            $overwrite = true;
            $subscriber_id = mailster( 'subscribers' )->add( $userdata, $overwrite );
        }

        // Avoid duplicates for same user unless previously marked completed
        $existing = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE subscriber_id = %s AND completed = 0", $subscriber_id)
        );

        if (!$existing) {
            $wpdb->insert($table, [
                'subscriber_id' => $subscriber_id,
                'created_at' => current_time('mysql'),
                'data' => wp_json_encode( $data ),
                'token' => bin2hex( random_bytes( 16 ) ), // 32-char token
            ]);
        }
    }
}

function mark_registration_completed($subscriber_id) {
    global $wpdb;
    return;
    $table = $wpdb->prefix . 'abandoned_registrations';

    $wpdb->update(
        $table,
        ['completed' => 1],
        ['subscriber_id' => $subscriber_id]
    );
}

if (!wp_next_scheduled('send_abandoned_cart_emails_hook')) {
    wp_schedule_event(time(), 'hourly', 'send_abandoned_cart_emails_hook');
}
// add_action('send_abandoned_cart_emails_hook', 'send_abandoned_cart_emails');

function send_abandoned_cart_emails() {
    global $wpdb;
    return;
    $table = $wpdb->prefix . 'abandoned_registrations';

    $now = current_time('timestamp');

    // Get all entries not completed
    $registrations = $wpdb->get_results("SELECT * FROM $table WHERE completed = 0");

    foreach ($registrations as $reg) {
        $created_at = strtotime($reg->created_at);
        $diff = ($now - $created_at) / 60; // in minutes

        if ($reg->email_stage == 0 && $diff > 60) {
            send_abandoned_cart_email($reg, 1);
            update_email_stage($reg->id, 1);
        } elseif ($reg->email_stage == 1 && $diff > 1440) { // 24 hours
            send_abandoned_cart_email($reg, 2);
            update_email_stage($reg->id, 2);
        } elseif ($reg->email_stage == 2 && $diff > 4320) { // 3 days
            send_abandoned_cart_email($reg, 3);
            update_email_stage($reg->id, 3);
        }
    }
}

function update_email_stage($id, $stage) {
    global $wpdb;
    return;
    $table = $wpdb->prefix . 'abandoned_registrations';
    $wpdb->update($table, ['email_stage' => $stage], ['id' => $id]);
}

function send_abandoned_cart_email($reg, $stage) {
    global $wpdb;
    return;
    $table = $wpdb->prefix . 'abandoned_registrations';
	$mailster_tags = array(
        // 'firstname' => '',
        'training_details' => '',
        'resume_registration' => '',
    );
    $mailster_hooks = [
        1 => 'abandoned_cart_email_1',
        2 => 'abandoned_cart_email_2',
        3 => 'abandoned_cart_email_3',
    ];
    // $subscriber = mailster( 'subscribers' )->get( $reg->subscriber_id );
    // $mailster_tags['firstname'] = $subscriber->firstname;
    $mailster_tags['training_details'] = $reg->title.'<br>'.$reg->type.' '.$reg->price;
    $mailster_tags['resume_registration'] = '<div class="btn"><buttons><table class="textbutton" align="left" role="presentation"><tbody><tr><td align="center" width="auto"><a href="" editable="" label="Resume registration">Resume registration</a></td></tr></tbody></table>';






    

    $messages = [
        1 => 'Hi! It looks like you started registering for a course but didn’t finish. Click here to complete it!',
        2 => 'Reminder: Your course spot is still waiting. Complete your registration soon!',
        3 => 'This is your final reminder to complete your course registration!',
    ];

    wp_mail($email, $subjects[$stage], $messages[$stage]);
}

function cleanup_old_abandoned_registrations() {
    global $wpdb;
    return;
    $table = $wpdb->prefix . 'abandoned_registrations';

    $wpdb->query("DELETE FROM $table WHERE completed = 0 AND created_at < NOW() - INTERVAL 30 DAY");
}
// add_action('send_abandoned_cart_emails_hook', 'cleanup_old_abandoned_registrations');
