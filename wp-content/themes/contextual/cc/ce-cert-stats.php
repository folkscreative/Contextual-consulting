<?php
/**
 * CE Certificate Stats
 */

// create the CE Certs log table ... logs all CE Certs requested
add_action('init', 'cc_certs_log_table_def');
function cc_certs_log_table_def(){
	global $wpdb;
	$cc_certs_log_table_ver = 1;
	$installed_table_ver = get_option('cc_certs_log_table_ver');
	if($installed_table_ver <> $cc_certs_log_table_ver){
		$table_name = $wpdb->prefix.'ce_certs_log';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT,
			generated_time datetime DEFAULT CURRENT_TIMESTAMP,
			user_id mediumint(9) NOT NULL,
			training_id mediumint(9) NOT NULL,
			event_id mediumint(9) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		if( !function_exists( 'dbDelta' ) ){
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}
		$response = dbDelta($sql);
		update_option('cc_certs_log_table_ver', $cc_certs_log_table_ver);

		// and, the first time through, we'll populate the table
		if($cc_certs_log_table_ver == 1){
			populate_ce_certs_log();
		}

	}
}

add_shortcode('populate_ce_certs_log', 'populate_ce_certs_log');
function populate_ce_certs_log(){
	global $wpdb;
	$html = 'populate_ce_certs_log';
	$debug_log_table = $wpdb->prefix.'debug_log';
	$ce_certs_log = $wpdb->prefix.'ce_certs_log';
	$sql = "SELECT * FROM $debug_log_table WHERE log LIKE 'CE Cert generated%'";
	$logs = $wpdb->get_results($sql, ARRAY_A);
	$html .= '<br>logs found: '.count($logs);
	$format = array( '%s', '%d', '%d','%d' );
	foreach ($logs as $log) {
		// log entries look like this:
		// CE Cert generated for user: 20127 for training_id: 5432 event_id: 0
		$html .= '<br>'.$log['log'];
		$bits = explode(': ', $log['log']);
		// $bits now looks like:
		// array( 'CE Cert generated for user', '20127 for training_id', '5432 event_id', '0' )
		$html .= '<br>bits:'.print_r( $bits, true );
		$html .= '<br>'.$log['ts'];

		$data = array(
			'generated_time' => $log['ts'],
			'user_id' => 0,
			'training_id' => 0,
			'event_id' => 0,
		);

		if( isset( $bits[1] ) ){
			$sub_bits = explode( ' ', $bits[1] );
			if( isset( $sub_bits[0] ) ){
				$data['user_id'] = (int) $sub_bits[0];
			}
		}
		if ( isset( $bits[2] ) ){
			$sub_bits = explode( ' ', $bits[2] );
			if( isset( $sub_bits[0] ) ){
				$data['training_id'] = (int) $sub_bits[0];
			}
		}
		if( isset( $bits[3] ) ){
			$data['event_id'] = (int) $bits[3];
		}
		$html .= '<br>data: '.print_r( $data, true );

		if( $data['user_id'] > 0 && $data['training_id'] > 0 ){
			$wpdb->insert( $ce_certs_log, $data, $format );
		}
	}
	return $html;
}

// log a CE Cert request
function cc_cert_log_request($user_id, $training_id, $event_id=0){
	global $wpdb;
	$ce_certs_log = $wpdb->prefix.'ce_certs_log';
	$data = array(
		'user_id' => $user_id,
		'training_id' => $training_id,
		'event_id' => $event_id,
	);
	$format = array( '%d', '%d','%d' );
	$wpdb->insert( $ce_certs_log, $data, $format );
}

// count the number of CE Certificates that have been requested for a training
function cc_cert_training_count($training_id){
	global $wpdb;
	$ce_certs_log = $wpdb->prefix.'ce_certs_log';
	$sql = "SELECT COUNT(*) FROM $ce_certs_log WHERE training_id = $training_id";
	$num_certs = $wpdb->get_var($sql);
	if($num_certs === NULL){
		return 0;
	}else{
		return $num_certs;
	}
}

// get all CE Cert log rows for a given training
function cc_cert_training_all($training_id){
	global $wpdb;
	$ce_certs_log = $wpdb->prefix.'ce_certs_log';
	$sql = "SELECT * FROM $ce_certs_log WHERE training_id = $training_id ORDER BY generated_time DESC";
	return $wpdb->get_results($sql, ARRAY_A);
}

// add the hidden CE Certs list page
add_action('admin_menu', 'cc_ce_certs_admin_menu');
function cc_ce_certs_admin_menu(){
	// add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = '', int|float $position = null )
	add_submenu_page('options.php', 'CE Certificates Requested', 'CE Certs', 'edit_posts', 'ce_certs_list', 'cc_ce_certs_list_page');
	// the url for this page will be something like https://contextualconsulting.co.uk/wp-admin/admin.php?page=ce_certs_list&t=1234 where t = training_id
}

// add a link to the ce certs list to the submit postox on the training edit page
add_action( 'post_submitbox_misc_actions', 'cc_ce_certs_list_post_submitbox_misc_actions' );
function cc_ce_certs_list_post_submitbox_misc_actions( $post ) {
	if( $post->post_type == 'course' || $post->post_type == 'workshop' ){
		$num_certs = cc_cert_training_count( $post->ID );
		?>
		<hr>
		<div class="misc-pub-section ce-cert-list-link-wrap">
			<?php echo $num_certs; ?>
			CE Certificates generated to date.
			<?php if($num_certs > 0){ ?>
				<a class="button" href="<?php echo admin_url('admin.php?page=ce_certs_list&t='.$post->ID); ?>">Details</a>
			<?php } ?>
		</div>
		<?php
	}
}

// the list of all CE Certificates Generated
function cc_ce_certs_list_page(){
	global $wpdb;
	if(!current_user_can('edit_posts')) wp_die('Go away!');
	if(isset($_GET['t'])){
		$training_id = (int) $_GET['t'];
		if($training_id > 0){

			$html = '<h1>CE Certificates for '.get_the_title($training_id).'</h1>';

			// table header
			$html .= '<table class="table table-condensed ce-certs-list-table"><thead><tr>';
			$html .= '<th valign="top">When</th>';
			$html .= '<th valign="top">Who</th>';
			$html .= '<th valign="top">Event</th>';
			$html .= '</tr></thead><tbody>';

			$certs = cc_cert_training_all($training_id);

			foreach ($certs as $cert) {
				$html .= '<tr>';

				$html .= '<td>'.$cert['generated_time'].'</td>';

				$html .= '<td>';
				$user = get_user_by( 'ID', $cert['user_id'] );
				if($user){
					if($user->first_name <> '' || $user->last_name <> ''){
						$html .= $user->first_name.' '.$user->last_name;
					}else{
						$html .= 'Name unknown';
					}
					$html .= ' ('.$user->user_email.')';
					$html .= ' <a href="/wp-admin/user-edit.php?user_id='.$cert['user_id'].'" target="_blank"><i class="fas fa-external-link-alt"></i></a>';
				}else{
					$html .= 'User ID: '.$cert['user_id'].' - Unknown';
				}
				$html .= '</td>';

				$html .= '<td>';
				if($cert['event_id'] > 0){
					echo $cert['event_id'];
				}
				$html .= '</td>';

				$html .= '</tr>';				
			}

			$html .= '</tbody></table>';

			echo $html;
		}
	}
}
