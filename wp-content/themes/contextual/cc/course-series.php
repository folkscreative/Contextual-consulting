<?php
/**
 * Course Series stuff
 */

// register the series CPT
add_action('init', 'course_series_register_training_series_cpt');
function course_series_register_training_series_cpt() {
    $labels = array(
        'name'               => 'Training Series',
        'singular_name'      => 'Training Series',
        'menu_name'          => 'Training Series',
        'name_admin_bar'     => 'Training Series',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Training Series',
        'new_item'           => 'New Training Series',
        'edit_item'          => 'Edit Training Series',
        'view_item'          => 'View Training Series',
        'all_items'          => 'All Training Series',
        'search_items'       => 'Search Training Series',
        'not_found'          => 'No training series found.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => false,
        'supports'           => array('title', 'editor', 'excerpt', 'thumbnail'),
        'menu_position'      => 21,
        'menu_icon'          => 'dashicons-networking',
        'rewrite'            => array('slug' => 'series'),
    );

    register_post_type('series', $args);
}

// belt and braces to stop the archive page showing
add_action('template_redirect', function() {
    if (is_post_type_archive('series')) {
        // Option 1: Redirect to homepage
        wp_redirect(home_url());
        exit;

        // Option 2: Show 404
        // global $wp_query;
        // $wp_query->set_404();
        // status_header(404);
        // nocache_headers();
        // include(get_query_template('404'));
        // exit;
    }
});


/* debuging ...........
add_action('template_redirect', function () {
    global $wp_query;
    if (is_singular('series')) {
        ccpa_write_log($wp_query->posts);
        exit;
    }
});

add_action('init', function () {
    add_rewrite_rule('^debug-rules/?$', 'index.php?debug_rules=1', 'top');
    add_rewrite_tag('%debug_rules%', '1');
});

add_action('template_redirect', function () {
    if (get_query_var('debug_rules')) {
        echo '<pre>';
        print_r(get_option('rewrite_rules'));
        echo '</pre>';
        exit;
    }
});

add_action('template_redirect', function () {
    if (is_singular('series')) {
        global $wp_query;

        error_log('Series page matched');
        error_log('Post found: ' . ($wp_query->have_posts() ? 'YES' : 'NO'));
        if (!$wp_query->have_posts()) {
            error_log('WP Query vars: ' . print_r($wp_query->query_vars, true));
        }
    }
});

add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query()) {
        error_log('Main Query: ' . print_r($query->query_vars, true));
    }
});

*/




add_action('add_meta_boxes', 'course_series_add_series_meta_box');
function course_series_add_series_meta_box() {
    add_meta_box(
        'series_courses_meta_box',
        'Courses in this Series',
        'course_series_render_series_courses_meta_box',
        'series',
        'normal',
        'high'
    );
}

function course_series_render_series_courses_meta_box($post) {
    wp_nonce_field('series_courses_nonce_action', 'series_courses_nonce');

    // Load existing data
    $series_status = get_post_meta($post->ID, '_series_status', true);
    $series_discount = get_post_meta($post->ID, '_series_discount', true);

    // Get courses currently in this series
    // $selected_courses = get_post_meta($post->ID, '_series_courses', true);
    // $selected_courses = is_array($selected_courses) ? $selected_courses : [];
    $selected_courses = get_post_meta($post->ID, '_series_courses', true);
    $selected_courses = is_string($selected_courses) ? json_decode($selected_courses, true) : [];
    $selected_courses = is_array($selected_courses) ? $selected_courses : [];

    // Get all courses currently assigned to any series
    global $wpdb;
    $used_course_ids = $wpdb->get_col("
        SELECT meta_value
        FROM $wpdb->postmeta
        WHERE meta_key = '_series_courses'
    ");

    // Flatten and clean array of used courses
    $used_course_ids_flat = [];
    foreach ($used_course_ids as $value) {
        $ids = json_decode($value, true);
        if (is_array($ids)) {
            $used_course_ids_flat = array_merge($used_course_ids_flat, $ids);
        }
    }

    // Make sure selected (current) courses remain available
    $excluded_course_ids = array_diff($used_course_ids_flat, $selected_courses);

    // Fetch available (unused) courses
    $courses = get_posts([
        'post_type'      => array( 'course', 'workshop' ),
        'posts_per_page' => -1,
        'orderby'        => 'ID',
        'order'          => 'DESC',
        'post__not_in'   => $excluded_course_ids
    ]);

    ?>
    <p>
        <label for="series_status">Series Status:</label>
        <select name="series_status" id="series_status">
            <option value="visible" <?php selected($series_status, 'visible'); ?>>Visible</option>
            <option value="hidden" <?php selected($series_status, 'hidden'); ?>>Hidden</option>
        </select>
    </p>

    <p>
        <label for="series_discount">Discount (%) if full series booked:</label>
        <input type="number" name="series_discount" id="series_discount" value="<?php echo esc_attr($series_discount); ?>" min="0" max="100">
    </p>

    <h4>Courses in Series (drag to reorder):</h4>
    <ul id="series-courses-sortable">
        <?php
        // Display selected courses first in stored order
        foreach ($selected_courses as $course_id) {
            $course = get_post($course_id);
            if ($course && $course->post_type === 'course') {
                if( get_post_meta( $course_id, '_course_type', true ) == 'on-demand' ){
                    $course_type = 'on-d';
                }else{
                    $course_type = 'live';
                }
            }elseif ( $course && $course->post_type === 'workshop' ) {
                $course_type = 'live';
            }else{
                continue;
            }
            if( strlen( $course->post_title ) > 100 ){
                $course_title = substr( $course->post_title , 0, 100 ).' ...';
                $title_tag = esc_html( $course->post_title );
            }else{
                $course_title = $course->post_title;
                $title_tag = '';
            }
            echo '<li class="series-course-item" data-id="' . esc_attr($course->ID) . '" title="'.$title_tag.'">('.$course_type.') ' . esc_html( $course_title ) . ' <a href="#" class="remove-course">Remove</a></li>';

        }
        ?>
    </ul>

    <select id="add-course-to-series">
        <option value="">-- Add a course --</option>
        <?php foreach ($courses as $course) :
            if ( ! in_array($course->ID, $selected_courses ) ) :
                if ( $course->post_type === 'course') {
                    if( get_post_meta( $course->ID, '_course_type', true ) == 'on-demand' ){
                        $course_type = 'on-d';
                    }else{
                        $course_type = 'live';
                    }
                }elseif ( $course->post_type === 'workshop' ) {
                    $course_type = 'live';
                }else{
                    continue;
                }
                if( strlen( $course->post_title ) > 100 ){
                    $course_title = substr( $course->post_title , 0, 100 ).' ...';
                    $title_tag = esc_html( $course->post_title );
                }else{
                    $course_title = $course->post_title;
                    $title_tag = '';
                }
                ?>
                <option value="<?php echo esc_attr($course->ID); ?>" title="<?php echo $title_tag; ?>">(<?php echo $course_type; ?>) <?php echo esc_html( $course_title ); ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>
    <button type="button" id="add-course-btn">Add Course</button>

    <input type="hidden" name="series_courses_order" id="series_courses_order" value="<?php echo esc_attr(json_encode($selected_courses)); ?>">
    <style>
        #series-courses-sortable { list-style: none; margin: 0; padding: 0; }
        .series-course-item { margin: 5px 0; padding: 8px; background: #f1f1f1; cursor: move; }
        .remove-course { color: red; margin-left: 10px; }
    </style>
    <?php
}

add_action('save_post_series', 'course_series_save_series_meta_box');
function course_series_save_series_meta_box($post_id) {
    if (!isset($_POST['series_courses_nonce']) || !wp_verify_nonce($_POST['series_courses_nonce'], 'series_courses_nonce_action')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (!current_user_can('edit_post', $post_id)) return;

    $status = sanitize_text_field($_POST['series_status'] ?? 'visible');
    update_post_meta($post_id, '_series_status', $status);

    $discount = floatval($_POST['series_discount'] ?? 0);
    update_post_meta($post_id, '_series_discount', $discount);

    if (!empty($_POST['series_courses_order'])) {
        $courses = json_decode(stripslashes($_POST['series_courses_order']), true);
        $courses = array_filter(array_map('intval', $courses));
        // update_post_meta($post_id, '_series_courses', $courses);
        update_post_meta($post_id, '_series_courses', json_encode(array_map('intval', $courses)));

    } else {
        delete_post_meta($post_id, '_series_courses');
    }
}

// gets the series id for the course
// normally returns false or an ID but for verbose mode, sends back more stuff
// if $visible is true, only includes series where _series_status is 'visible'
function series_id_for_course( $training_id, $verbose = false, $visible = true ) {
    $series_posts = get_posts([
        'post_type'      => 'series',
        'post_status'    => 'any',
        'numberposts'    => -1,
        'meta_key'       => '_series_courses',
    ]);

    $assigned_series = [];

    foreach ( $series_posts as $series_post ) {
        $series_id = $series_post->ID;

        // Check visibility, if required
        if ( $visible ) {
            $status = get_post_meta( $series_id, '_series_status', true );
            if ( $status !== 'visible' ) {
                continue;
            }
        }

        $course_ids = get_post_meta($series_id, '_series_courses', true);
        $course_ids = is_string($course_ids) ? json_decode($course_ids, true) : [];

        if ( is_array($course_ids) && in_array((int) $training_id, $course_ids, true) ) {
            if ( $verbose ) {
                $assigned_series[] = [
                    'id'        => $series_id,
                    'title'     => get_the_title( $series_id ),
                    'edit_link' => get_edit_post_link( $series_id ),
                ];
            } else {
                $assigned_series[] = $series_id;
            }
        }
    }

    if ( $verbose ) {
        return $assigned_series;
    }

    return empty($assigned_series) ? false : $assigned_series[0];
}


// the panel on the single course page
// Shown on a training page when the course is part of a series. Include the shortcodes [\'training_series_title\'], [\'training_series_other_courses\'] and/or [\'training_series_link text="here"\'] if required.
function course_series_panel( $series_id, $course_id, $currency='' ){
    global $rpm_theme_options;

    $series_status = get_post_meta( $series_id, '_series_status', true );
    if( $series_status <> 'visible' ) return '';

    if( $currency == '' ){
        $currency = cc_currency_get_user_currency();
    }

    $html = '<div class="course-series-banner-wrap my-3 p-3 dark-bg">';
    $html .= '<div class="course-series-banner">';
    $content = $rpm_theme_options['series-course-text'];

    $content = str_replace( "[training_series_title]", get_the_title( $series_id ), $content );

    if( strpos( $content, "[training_series_other_courses]") !== false ){
        // Get courses currently in this series
        $series_courses = get_post_meta($series_id, '_series_courses', true);
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];
        // remove this course
        if ( ( $key = array_search( $course_id, $series_courses ) ) !== false) {
            unset( $series_courses[$key] );
        }
        // get the details
        $details = array();
        foreach ($series_courses as $series_course_id) {
            $details[] = '<a href="'.get_permalink( $series_course_id ).'">'.get_the_title( $series_course_id ).'</a>';
        }

        $list = '';
        if( count( $details ) > 0 ){
            // combine into a list
            $list = '<ul>';
            foreach ($details as $detail) {
                $list .= '<li>'.$detail.'</li>';
            }
            $list .= '</ul>';
        }

        /* this used to put them into a string list
        // now combine the details into a string
        if ( count( $details ) >= 2) {
            $details[count( $details )-2] .= ' and ' . $details[count( $details )-1];
            unset( $details[count( $details )-1] );
        }
        $list = implode( ', ', $details );
        */

        $content = str_replace( "[training_series_other_courses]", $list, $content );
    }

    // Use a regex to find the ['training_series_link text="here"'] tag
    $pattern = '/\[training_series_link\s+text="([^"]+)"\]/i';
    $series_url = get_permalink( $series_id );
    // Callback function for replacement
    $replacement = function ( $matches ) use ($series_url) {
        $link_text = esc_html( $matches[1] );
        return '<a href="' . esc_url( $series_url ) . '">' . $link_text . '</a>';
    };
    // Perform the replacement
    $content = preg_replace_callback( $pattern, $replacement, $content );

    $content = nl2br( $content );

    $html .= $content;

    if( get_user_meta( get_current_user_id(), 'portal_user', true ) == '' ){
        // not a portal user

        $pricing = series_pricing( $series_id, $currency );
        if( $pricing ){
            if( $pricing['saving'] > 0 ){
                $html .= '<p class="">Register for the series and save '.$pricing['saving_text'].'. The normal price is '.$pricing['price_text'].' but you only pay '.$pricing['discounted_text'].'</p>';
                $html .= '<p class="small">* Plus VAT if applicable</p>';
            }
            $html .= '
                <div class="text-end">
                    <form action="/registration" method="POST">
                        <input type="hidden" id="training-type" name="training-type" value="s">
                        <input type="hidden" id="training-id" name="workshop-id" value="'.$series_id.'">
                        <input type="hidden" id="eventID" name="eventID" value="">
                        <input type="hidden" id="num-events" name="num-events" value="0">
                        <input type="hidden" id="num-free" name="num-free" value="0">
                        <input type="hidden" id="currency" name="currency" value="'.$currency.'">
                        <input type="hidden" id="raw-price" name="raw-price" value="'.$pricing['discounted'].'">
                        <input type="hidden" id="user-timezone" name="user-timezone" value="'.cc_timezone_get_user_timezone().'">
                        <input type="hidden" id="user-prettytime" name="user-prettytime" value="'.cc_timezone_get_user_timezone_pretty().'">
                        <input type="hidden" id="student" name="student" value="no">
                        <input type="hidden" id="student-price" name="student-price" value="0">
                        <input type="hidden" id="series_discount" name="series_discount" value="'.$pricing['saving'].'">
                        <button class="btn btn-reg btn-sm">Register for the series</button>
                    </form>
                </div>';
        }
    }


    $html .= '</div>';
    $html .= '</div>';

    return $html;
}


// gets the pricing for a series
// returns false if a price is missing for a currency in one of the trainings
function series_pricing( $series_id, $currency ){
    $response = array(
        'price'          => 0,
        'price_text'     => '',
        'discount'       => 0,
        'discounted'       => 0,
        'discounted_text'  => '',
        'saving'         => 0,
        'saving_text'    => '',
    );

    // if the series is hidden, we don't show prices
    $series_status = get_post_meta($series_id, '_series_status', true);
    if( $series_status <> 'visible' ){
        return $response;
    }
    if(!in_array($currency, cc_valid_currencies())){
        $currency = 'GBP';
    }

    // get all the training ids for the series
    $series_courses = get_post_meta($series_id, '_series_courses', true);
    $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
    $series_courses = is_array($series_courses) ? $series_courses : [];

    // add the individual training prices together
    $series_price = 0;
    foreach ($series_courses as $training_id) {
        if( get_post_type( $training_id ) == 'workshop' ){
            $workshop_pricing = cc_workshop_price( $training_id, $currency );
            if( $workshop_pricing['curr_found'] <> $currency ){
                return false;
            }
            $series_price = $series_price + $workshop_pricing['raw_price'];
        }else{
            $course = course_get_all( $training_id );
            if( ! $course ){
                return false;
            }
            $series_price = $series_price + $course['pricing']['price_'.strtolower( $currency )];
        }
    }

    if( $series_price > 0 ){
        $response['price'] = $series_price;
    }
    if( $response['price'] > 0 ){
        $response['price_text'] = cc_money_format( $response['price'], $currency ).'*';

        $series_discount = (float) get_post_meta( $series_id, '_series_discount', true );
        if( $series_discount > 0 ){
            $response['discount'] = $series_discount;
            $discount = round( $response['price'] * $series_discount / 100, 2 );
            if( $discount > 0 ){
                $response['saving'] = $discount;
                $response['saving_text'] = cc_money_format( $discount, $currency ).'*';
                $response['discounted'] = $response['price'] - $discount;
                $response['discounted_text'] = cc_money_format( $response['discounted'], $currency ).'*';
            }
        }
    }else{
        $response['price_text'] = 'Free';
    }

    return $response;
}

/**
 * Get pricing information for a series in the standard format
 * This function wraps the existing series_pricing function to match 
 * the return format of cc_workshop_price_exact and cc_recording_price
 * 
 * @param int $series_id The ID of the series
 * @param string $currency The requested currency code
 * @return array Pricing information with keys: raw_price, curr_found, student_price, saving
 */
function cc_series_pricing($series_id, $currency) {
    // Initialize return array with default values
    $response = array(
        'raw_price' => 0,
        'curr_found' => $currency,
        'student_price' => NULL,  // Series don't have student pricing
        'saving' => 0,
        'price_text' => '',
        'discounted_price' => 0,
        'discounted_text' => ''
    );
    
    // Validate currency
    if (!in_array($currency, cc_valid_currencies())) {
        $currency = 'GBP';
    }
    $response['curr_found'] = $currency;
    
    // Check if series exists and is published
    if (!$series_id || get_post_status($series_id) !== 'publish') {
        return $response;
    }
    
    // Check if series is visible
    $series_status = get_post_meta($series_id, '_series_status', true);
    if ($series_status !== 'visible') {
        return $response;
    }
    
    // Call the existing series_pricing function to get detailed pricing
    $series_pricing_data = series_pricing($series_id, $currency);
    
    // If series_pricing returns false or empty, return default response
    if (!$series_pricing_data || $series_pricing_data === false) {
        return $response;
    }
    
    // Map the series_pricing data to our standard format
    $response['raw_price'] = $series_pricing_data['price'];
    $response['saving'] = $series_pricing_data['saving'];
    $response['price_text'] = $series_pricing_data['price_text'];
    $response['discounted_price'] = $series_pricing_data['discounted'];
    $response['discounted_text'] = $series_pricing_data['discounted_text'];
    
    // For series, the currency should be consistent across all courses
    // The series_pricing function returns false if currencies don't match
    // So if we got here, the currency is valid
    $response['curr_found'] = $currency;
    
    // Additional validation - ensure we have a valid price
    if ($response['raw_price'] <= 0) {
        // If no price found, try to calculate it directly
        $series_courses = get_post_meta($series_id, '_series_courses', true);
        $series_courses = is_string($series_courses) ? json_decode($series_courses, true) : [];
        $series_courses = is_array($series_courses) ? $series_courses : [];
        
        $total_price = 0;
        $currency_consistent = true;
        
        foreach ($series_courses as $training_id) {
            if (get_post_type($training_id) == 'workshop') {
                $workshop_pricing = cc_workshop_price($training_id, $currency);
                if ($workshop_pricing['curr_found'] != $currency) {
                    $currency_consistent = false;
                    break;
                }
                $total_price += $workshop_pricing['raw_price'];
            } else {
                // Recording/course
                $recording_pricing = cc_recording_price($training_id, $currency);
                if ($recording_pricing['curr_found'] != $currency) {
                    $currency_consistent = false;
                    break;
                }
                $total_price += $recording_pricing['raw_price'];
            }
        }
        
        if ($currency_consistent && $total_price > 0) {
            $response['raw_price'] = $total_price;
            
            // Calculate discount/saving
            $series_discount = (float) get_post_meta($series_id, '_series_discount', true);
            if ($series_discount > 0) {
                $response['saving'] = round($total_price * $series_discount / 100, 2);
                $response['discounted_price'] = $total_price - $response['saving'];
            }
        }
    }
    
    // Log for debugging if needed
    // ccpa_write_log('cc_series_pricing for series ' . $series_id . ':');
    // ccpa_write_log($response);
    
    return $response;
}