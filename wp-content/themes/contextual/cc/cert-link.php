<?php
/**
 * Cert Link admin page
 */

add_action( 'admin_menu', 'cc_cert_link_admin_menu' );
function cc_cert_link_admin_menu(){
	$my_page = add_submenu_page( 'edit.php?post_type=workshop', 'Certificates', 'Certificates', 'publish_pages', 'certificates', 'cc_cert_link_page' );
    // Load the scripts and styles conditionally
    add_action( 'load-' . $my_page, 'cc_cert_link_load_admin_stuff' );
}
// This function is only called when this page loads!
function cc_cert_link_load_admin_stuff(){
    // Unfortunately we can't just enqueue our scripts here - it's too early. So register against the proper action hook to do it
    add_action( 'admin_enqueue_scripts', 'cc_cert_link_enqueue_admin_stuff' );
}
function cc_cert_link_enqueue_admin_stuff(){
    wp_enqueue_style('cert-link-styles', get_stylesheet_directory_uri().'/css/cert-link.css', array(), '1.0.3.34x.0');
    wp_register_script( 'cert-link-scripts', get_stylesheet_directory_uri().'/js/cert-link.js', array('jquery'), '1.0.3.36.3');
    wp_localize_script( 'cert-link-scripts', 'ccAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        
    wp_enqueue_script( 'cert-link-scripts' );
}
function cc_cert_link_page(){
    ?>
    <div class="wrap">
        <h1>Certificate Links</h1>
        <p>Certificate links are unique for a given person/workshop (or recording). They are also encrypted to prevent anybody guessing the link and creating their own certificates. Who and what do you need the certificate link for?</p>
        <div class="row">
            <div class="col-5">
                <input type="text" id="user_name" placeholder="Start typing user name here">
                <div id="user-name-results" class="user-name-results"></div>
            </div>
            <div class="col-2">
                <input type="radio" name="wkshp_rec" id="wkshp_rec_wkshp" class="wkshp_rec" value="wkshp" checked> Live training
                <input type="radio" name="wkshp_rec" id="wkshp_rec_rec" class="wkshp_rec" value="rec"> On-demand
            </div>
            <div class="col-5">
                <input type="text" id="wksrec_name" placeholder="Start typing workshop/recording name here">
                <div id="wksrec-name-results" class="wksrec-name-results"></div>
            </div>
        </div>
        <p>&nbsp;</p>
        <p>Once you have selected the person and training above, please click the button below to generate the certificate link</p>
        <p>
            <a href="javascript:void(0)" class="button button-primary" id="gen-cert-link">Generate Certificate Link</a>
        </p>
        <p>&nbsp;</p>
        <div id="cert-link-response"></div>
    </div>
    <?php
}

// user name search
add_action('wp_ajax_cert_username_search', 'cc_cert_username_search');
function cc_cert_username_search(){
    $response = array(
        'results' => ''
    );
    $user_name = sanitize_text_field($_POST['username']);

    //search usertable
    $wp_user_query = new WP_User_Query(
        array(
            'search' => "*{$user_name}*",
            'search_columns' => array(
                'user_login',
                'user_nicename',
                'user_email',
            ),
            'number' => 51,
        )
    );
    $users = $wp_user_query->get_results();
    //search usermeta
    $wp_user_query2 = new WP_User_Query(
        array (
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'first_name',
                    'value' => $user_name,
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'last_name',
                    'value' => $user_name,
                    'compare' => 'LIKE'
                )
            ),
            'number' => 51,
        )
    );
    $users2 = $wp_user_query2->get_results();
    $totalusers_dup = array_merge($users,$users2);
    $totalusers = array_unique($totalusers_dup, SORT_REGULAR);

    if(empty($totalusers)){
        $response['results'] = 'Nothing found';
    }else{
        $user_count = 0;
        foreach ($totalusers as $user) {
            $user_count ++;
            if($user_count > 50){
                $response['results'] .= '<div>First 50 results shown above. Keep typing for a shorter list</div>';
                break;
            }
            $response['results'] .= '<div class="a-user" data-userid="'.$user->ID.'">';
            $response['results'] .= $user->first_name.' '.$user->last_name.' '.$user->user_email;
            $response['results'] .= '</div>';
        }
    }
    echo json_encode($response);
    die();
}

// workshop/recording search
add_action('wp_ajax_cert_wksrecname_search', 'cc_cert_cert_wksrecname_search');
function cc_cert_cert_wksrecname_search(){
    global $wpdb;
    $response = array(
        'results' => ''
    );

    $wksrec_name = sanitize_text_field($_POST['wksrecname']);

    if(isset($_POST['wksrectype']) && $_POST['wksrectype'] == 'recording'){
        
        $sql = "
            SELECT p.ID, p.post_title 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'course'
            AND p.post_status = 'publish'
            AND pm.meta_key = '_course_type'
            AND pm.meta_value = 'on-demand'
        ";
        
        if (!empty($wksrec_name)) {
            $search_term = '%' . $wpdb->esc_like($wksrec_name) . '%';
            $sql .= $wpdb->prepare(" AND p.post_title LIKE %s", $search_term);
        }
        
        $sql .= " ORDER BY p.post_date DESC LIMIT 51";

    }else{

        $search_term = '%' . $wpdb->esc_like($wksrec_name) . '%';
        $sql = $wpdb->prepare("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'workshop' AND post_title LIKE %s LIMIT 51", $search_term);

        // $sql = "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'workshop' AND post_title LIKE '%{$wksrec_name}%' LIMIT 51";
    }

    $posts = $wpdb->get_results($sql, ARRAY_A);
    
    $end_msg = '';
    if(count($posts) > 50){
        array_pop($posts); // ddrop the 51st result
        $end_msg = '<div>First 50 results shown above. Keep typing for a shorter list</div>';
    }
    foreach ($posts as $post) {
        $response['results'] .= '<div class="a-wksrec" data-wksrecid="'.$post['ID'].'">';
        $response['results'] .= $post['post_title'];
        $response['results'] .= '</div>';
    }
    $response['results'] .= $end_msg;
    echo json_encode($response);
    die();
}

// generate the certificate link
add_action('wp_ajax_cert_link_generate', 'cc_cert_link_generate');
function cc_cert_link_generate(){
    $response = array(
        'results' => ''
    );
    $userid = (int) $_POST['userid'];
    $wksrec_type = 'workshop';
    if(isset($_POST['wksrectype']) && $_POST['wksrectype'] == 'recording'){
        $wksrec_type = 'recording';
    }
    $wksrecid = (int) $_POST['wksrecid'];
    if($wksrec_type == 'workshop'){
        $cert_parms = ccpdf_workshop_cert_parms_encode($wksrecid, $userid);
    }else{
        $cert_parms = ccpdf_recording_cert_parms_encode($wksrecid, $userid);
    }
    $cert_url = add_query_arg(array('c' => $cert_parms), site_url('/certificate/'));
    $response['results'] = '<p>Here\'s the certificate link:</p>';
    $response['results'] .= '<p>'.$cert_url.'</p>';
    $response['results'] .= '<p><a href="'.$cert_url.'" target="_blank">View the certificate</a></p>';
    echo json_encode($response);
    die();
}