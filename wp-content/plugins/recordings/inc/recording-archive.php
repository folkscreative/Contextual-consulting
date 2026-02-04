<?php
/**
 * Recording archive stuff
 */

/**
 * The recording archive page must only show recordings that are:
 * - publicly viewable
 * - or available to purchase
 * - or viewable by the signed in user  <-- NOT ANY MORE! v2.3
 * see https://macarthur.me/posts/using-the-posts-where-filter-in-wordpress
 * - and it must show featured posts before the others
 * - all very tricky!!!
 * 
 * WP_Query probably can do it but is v. complicated
 * my attempts at this are still here ... commented out
 * instead accessing the DB directly seems to be marginally simpler ...
 */

// replaces the query on the archive page to show recordings ...
function recording_archive_get_posts($paginated=true){
    global $wpdb;
    $response = array(
        'recordings' => array(),
        'pages' => 1,
        'page_num' => 1,
    );
    // we'll get all IDs and then select what we need
    $sql = "SELECT ID FROM $wpdb->posts WHERE post_type = 'recording' AND post_status = 'publish' ORDER BY post_date DESC";
    $all_recordings = $wpdb->get_col($sql);
    $feat_recordings = array();
    $recordings = array();
    foreach ($all_recordings as $recording_id) {
        // is it one we want to show?
        $recording_for_sale = get_post_meta($recording_id, 'recording_for_sale', true);
        if($recording_for_sale == 'closed' || $recording_for_sale == 'unlisted'){
            continue;
        }
        $recording_featured = get_post_meta($recording_id, 'recording_featured', true);
        if($recording_featured == 'yes'){
            $feat_recordings[] = $recording_id;
        }else{
            $recordings[] = $recording_id;
        }
    }
    $combined_recordings = array_merge($feat_recordings, $recordings);
    if($paginated){
        $response['pages'] = ceil( count( $combined_recordings ) / 10 );
        if(isset($_GET['page'])){
            $response['page_num'] = (int) $_GET['page'];
        }else{
            $response['page_num'] = 1;
        }
        $recordings_to_skip = 10 * ($response['page_num'] - 1);
        $response['recordings'] = array_slice($combined_recordings, $recordings_to_skip, 10);
        return $response;
    }
    return $combined_recordings;
}

// get all recordings that are available for sale (usually includes unlisted)
// $pub_since is a compatible strtotime string or array of year, month, day (see wp_query)
// May 2025: changed to use courses instead
function recording_get_all_available( $order_by='', $pub_since='', $criteria='', $search_term='', $inc_unlisted=true ){
    global $wpdb;
    $args = array(
        'post_type' => 'course',
        'numberposts' => -1,
        'meta_key' => '_course_type',
        'meta_value' => 'on-demand'
    );
    if( $order_by == '' ){
        $args['orderby'] = 'ID';
        $args['order'] = 'DESC';
    }else{
        $args['orderby'] = $order_by;
        $args['order'] = 'ASC';
    }
    if( $pub_since <> '' ){
        $args['date_query'] = array(
            array(
                'after' => $pub_since
            )
        );
    }
    /*
    if( $search_term <> '' ){
        $args['s'] = $search_term; // removed cos it's not fuzzy
    }
    */
    $recordings = get_posts($args);
    foreach ($recordings as $key => $recording) {
        // ignore closed ones
        $course_status = get_post_meta($recording->ID, '_course_status', true);
        if($course_status == 'closed'){
            unset($recordings[$key]);
        }elseif( $inc_unlisted == false && $course_status == 'unlisted' ){
            unset($recordings[$key]);
        }elseif( $criteria == 'free' ){
            $course_pricing = course_pricing_get( $recording->ID );
            if( $course_pricing['price_gbp'] <> 0 ){
                unset($recordings[$key]);
            }
        }
    }
    return cc_search_match( $recordings, $search_term );
}




// we need to add extra query vars to the wp_query loop for this
/*
add_action( 'pre_get_posts', function( $query ) {
    if ( !is_admin() && $query->is_main_query() && get_post_type() == 'recording' ) {
        $query->set( 'cc_query', 'cc_recording_archive_query' );
    }
});
*/

/*
add_filter('posts_join', 'recording_archive_posts_join', 10, 2);
function recording_archive_posts_join($join, $query){
    // ccpa_write_log('recording_archive_posts_join');
    // ccpa_write_log($join);
    // ccpa_write_log($query);
    if(!is_admin() && $query->is_main_query() && $query->query_vars['post_type'] == 'recording'){
        global $wpdb;
        $join .= " LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id ";
    }
    return $join;
}

add_filter('posts_where','recording_archive_posts_where',10,2);
function recording_archive_posts_where($where,$query) {
    // ccpa_write_log('recording_archive_posts_where');
    // ccpa_write_log($where);
    // ccpa_write_log($query);
    if( !is_admin() && $query->is_main_query() && $query->query_vars['post_type'] == 'recording' ){
    // if( isset( $query->query['cc_query'] ) && $query->query['cc_query'] == 'cc_recording_archive_query' ){
        global $wpdb;
        $where .= " AND ($wpdb->postmeta.meta_key = 'recording_for_sale' AND $wpdb->postmeta.meta_value != 'closed')";
            /*
        $where .= "
            AND ($wpdb->postmeta.meta_key = 'recording_for_sale' AND $wpdb->postmeta.meta_value != 'closed')
            AND ( ($wpdb->postmeta.meta_key = 'vimeo_id' AND $wpdb->postmeta.meta_value != '')
                OR ($wpdb->postmeta.meta_key = 'recording_url' AND $wpdb->postmeta.meta_value != '')
                OR ($wpdb->postmeta.meta_key = 'registration_link_id' AND $wpdb->postmeta.meta_value != '')
                OR ($wpdb->postmeta.meta_key = 'registration_link' AND $wpdb->postmeta.meta_value != '')
            )";
            *//*
    }
    return $where;
}

add_filter('posts_orderby', 'recording_archive_posts_orderby', 10, 2);
function recording_archive_posts_orderby($orderby, $query){
    ccpa_write_log('recording_archive_posts_orderby');
    ccpa_write_log($orderby);
    ccpa_write_log($query);
    if( !is_admin() && $query->is_main_query() && $query->query_vars['post_type'] == 'recording' ){
        $orderby = "argh!!! wp_posts.post_date DESC";
    }
    return $orderby;
}

// add_filter('posts_where','recording_archive_posts_where',10,2);
/*
function recording_archive_posts_where($where,$query) {
    global $wpdb;
    $new_where = " TRIM(IFNULL({$wpdb->postmeta}.meta_value,''))<>'' ";
    if (empty($where)){
        $where = $new_where;
    }else{
        $where = "{$where} AND {$new_where}";
    }
    return $where;
}
*/

/*
SELECT SQL_CALC_FOUND_ROWS  wp_posts.ID
                    FROM wp_posts  LEFT JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id 
                    WHERE 1=1  AND ((wp_posts.post_type = 'recording' AND (wp_posts.post_status = 'publish' OR wp_posts.post_status = 'paused' OR wp_posts.post_status = 'active' OR wp_posts.post_status = 'queued' OR wp_posts.post_status = 'finished' OR wp_posts.post_status = 'autoresponder')))
            AND (wp_postmeta.meta_key = 'recording_for_sale' AND wp_postmeta.meta_value != 'closed')
            AND ( (wp_postmeta.meta_key = 'vimeo_id' AND wp_postmeta.meta_value != '')
                OR (wp_postmeta.meta_key = 'recording_url' AND wp_postmeta.meta_value != '')
                OR (wp_postmeta.meta_key = 'registration_link_id' AND wp_postmeta.meta_value != '')
                OR (wp_postmeta.meta_key = 'registration_link' AND wp_postmeta.meta_value != '')
            )
                    
                    ORDER BY wp_posts.post_date DESC
                    LIMIT 0, 15

                    */