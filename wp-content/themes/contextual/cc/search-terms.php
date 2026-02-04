<?php
/**
 * Search terms
 */

// the search terms table
add_action('init', 'cc_search_terms_table');
function cc_search_terms_table(){
	global $wpdb;
	$cc_search_terms_db_ver = 1;
	$installed_table_ver = get_option('cc_search_terms_db_ver');
	if($installed_table_ver <> $cc_search_terms_db_ver){
		$search_terms_table = $wpdb->prefix.'cc_search_terms';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $search_terms_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			search_term varchar(255) NOT NULL,
			count mediumint(9) NOT NULL,
			last_used datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('cc_search_terms_db_ver', $cc_search_terms_db_ver);
	}
}

// get a search term
// Returns null if no result is found
function cc_search_term_get_by( $by, $value ){
	global $wpdb;
	$search_terms_table = $wpdb->prefix.'cc_search_terms';
	$sql = "SELECT * FROM $search_terms_table WHERE $by = '$value' LIMIT 1";
	return $wpdb->get_row( $sql, ARRAY_A );
}

// get all search terms
function cc_search_term_get_all(){
	global $wpdb;
	$search_terms_table = $wpdb->prefix.'cc_search_terms';
	$sql = "SELECT * FROM $search_terms_table ORDER BY count DESC, last_used DESC LIMIT 100";
	return $wpdb->get_results( $sql, ARRAY_A );
}

// save a search term
// returns the ID of the search term row
function cc_search_term_save( $search_term ){
	global $wpdb;
	$search_terms_table = $wpdb->prefix.'cc_search_terms';
	$search_term_row = cc_search_term_get_by( 'search_term', $search_term );
	if( $search_term_row === NULL ){
		// new search term
		$data = array(
			'search_term' => $search_term,
			'count' => 1,
		);
		$wpdb->insert( $search_terms_table, $data, array( '%s', '%d' ) );
		return $wpdb->insert_id;
	}else{
		// add one
		$data = array(
			'count' => ( $search_term_row['count'] + 1 ),
		);
		$where = array(
			'search_term' => $search_term,
		);
		$wpdb->update( $search_terms_table, $data, $where, '%d' );
		return $search_term_row['id'];
	}
}

// instant lookup
add_action('wp_ajax_fetch_search_suggestions', 'cc_fetch_search_suggestions');
add_action('wp_ajax_nopriv_fetch_search_suggestions', 'cc_fetch_search_suggestions');
function cc_fetch_search_suggestions() {
    global $wpdb;
	$search_terms_table = $wpdb->prefix.'cc_search_terms';
    if (isset($_GET['query'])) {
        $query = sanitize_text_field($_GET['query']);
        if (strlen($query) < 2) {
            wp_send_json([]); // Return empty array if query is less than 2 characters
            wp_die();
        }
        // Query to fetch matching search terms, ordered by usage count
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT search_term FROM $search_terms_table WHERE search_term LIKE %s ORDER BY count DESC LIMIT 10",
            '%' . $wpdb->esc_like($query) . '%'
        ));
        $links = array();
        foreach ($results as $result) {
        	$links[] = '<a href="'.esc_url( add_query_arg( 's', $result, home_url() ) ).'">'.$result.'</a>';
        }
        wp_send_json($links); // Send the results back as JSON
    }
    wp_die();
}

/**
 * Add a stats widget to the dashboard.
 */
add_action( 'wp_dashboard_setup', 'cc_search_terms_dashboard_widget' );
function cc_search_terms_dashboard_widget() {
	// wp_add_dashboard_widget( $widget_id, $widget_name, $callback, $control_callback, $callback_args );
	wp_add_dashboard_widget( 'cc_search_stats', 'Search Stats', 'cc_search_terms_dashboard_stats' );
}
function cc_search_terms_dashboard_stats(){
	?>
	<div class="row">
		<div class="col-9"><strong>Search Term</strong></div>
		<div class="col-3"><strong>Searches</strong></div>
	</div>
	<div class="search-stats-wrap">
		<?php foreach ( cc_search_term_get_all() as $search_term) { ?>
			<div class="row">
				<div class="col-9"><?php echo $search_term['search_term']; ?></div>
				<div class="col-3"><?php echo $search_term['count']; ?></div>
			</div>
		<?php } ?>
	</div>
	<?php
}