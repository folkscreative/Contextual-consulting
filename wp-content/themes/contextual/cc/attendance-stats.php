<?php
/**
 * Attendance Stats
 */

// add the hidden attendance stats page
add_action('admin_menu', 'cc_attend_stats_admin_menu');
function cc_attend_stats_admin_menu(){
	// add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = '', int|float $position = null )
	add_submenu_page('options.php', 'Attendance Stats', 'Attendance Stats', 'edit_posts', 'attendance-stats', 'cc_attend_stats_page');
	// the url for this page will be something like https://contextualconsulting.co.uk/wp-admin/admin.php?page=attendance-stats&t=1234
}

// the Attendance Stats Page
function cc_attend_stats_page(){
	global $wpdb;
    $wkshop_users_table = $wpdb->prefix.'wshops_users';
    $recordings_users_table = $wpdb->prefix.'recordings_users';
	$attendees_table = $wpdb->prefix.'cc_attendees';
	$attendance_table = $wpdb->prefix.'attendance';
	if(!current_user_can('edit_posts')) wp_die('Go away!');
	if(isset($_GET['t'])){
		$training_id = (int) $_GET['t'];
		if($training_id > 0){
			$training = get_post( $training_id );
			if( $training ){

				// create an empty results table to sum the findings into
				$results = array();
				foreach ( the_professions() as $profession ) {
					$results[$profession['slug']] = array(
						'pretty' => $profession['pretty'],
						'attendees' => 0,
						'attendance' => 0,
					);
				}

				// we want to find who was registered to attend this training
				// we have to get this from the workshop/recording users tables
				if( $training->post_type == 'workshop'){
					// the wshops_users table includes registrants and attendees 
					// but the registrants may not also be attendees
					// so we need to only select the registrants from the table if they are also attendees
					// and we find that out using the attendees table
					// let's do this a step at a time ... KISS
					$sql = "SELECT * FROM $wkshop_users_table WHERE workshop_id = $training_id";
					$wkshop_users = $wpdb->get_results( $sql, ARRAY_A );
					// now we can selecte the real attendees from those
					$real_attendees = array();
					foreach ($wkshop_users as $wkshop_user) {
						$include_user = false;
						if( $wkshop_user['reg_attend'] == 'a' ){
							$include_user = true;
						}else{
							$sql = "SELECT * FROM $attendees_table WHERE payment_id = ".$wkshop_user['payment_id']." AND user_id = ".$wkshop_user['user_id']." LIMIT 1";
							$attendee_row = $wpdb->get_row( $sql, ARRAY_A );
							if( $attendee_row ){
								$include_user = true;
							}
						}
						if( $include_user ){
							// collate the data we need
							$sql = "SELECT * FROM $attendance_table WHERE user_id = ".$wkshop_user['user_id']." AND training_id = $training_id";
							$attendance_row = $wpdb->get_row( $sql, ARRAY_A );
							$job = get_user_meta( $wkshop_user['user_id'], 'job', true);

							if( isset( $results[$job] ) ){
								$results[$job]['attendees'] ++;
								if( $attendance_row ){
									$results[$job]['attendance'] ++;
								}
							}else{
								$results[$job] = array(
									'pretty' => $job,
									'attendees' => 1,
									'attendance' => 0,
								);
								if( $attendance_row ){
									$results[$job]['attendance'] ++;
								}
							}
						}
					}
				}else{
					// recording
					$sql = "SELECT * FROM $recordings_users_table WHERE recording_id = $training_id";
					$recordings_users = $wpdb->get_results( $sql, ARRAY_A );
					$real_attendees = array();
					foreach ($recordings_users as $recordings_user) {
						$sql = "SELECT * FROM $attendees_table WHERE payment_id = ".$recordings_user['payment_id']." AND user_id = ".$recordings_user['user_id']." LIMIT 1";
						$attendee_row = $wpdb->get_row( $sql, ARRAY_A );
						if( $attendee_row ){
							// collate the data we need
							$sql = "SELECT * FROM $attendance_table WHERE user_id = ".$recordings_user['user_id']." AND training_id = $training_id";
							$attendance_row = $wpdb->get_row( $sql, ARRAY_A );
							$job = get_user_meta( $recordings_user['user_id'], 'job', true);

							if( isset( $results[$job] ) ){
								$results[$job]['attendees'] ++;
								if( $attendance_row ){
									$results[$job]['attendance'] ++;
								}
							}else{
								$results[$job] = array(
									'pretty' => $job,
									'attendees' => 1,
									'attendance' => 0,
								);
								if( $attendance_row ){
									$results[$job]['attendance'] ++;
								}
							}
						}
					}
				}

				?>
				<div class="wrap">
					<h2>Attendance Stats</h2>
					<div id="poststuff">
						<div id="post-body">
							<div class="postbox" style="padding: 10px 30px 30px;">
								<h3>Attendance Stats For <?php echo get_the_title( $training_id ); ?></h3>
								<table class="widefat striped">
									<thead>
										<th>Profession</th>
										<th>Reg. Attendees</th>
										<th>Zoom Attendance</th>
									</thead>
									<tbody>
										<?php
										$count_attendees = 0;
										$count_attendance = 0;
										foreach ($results as $slug => $result) {
											$count_attendees = $count_attendees + $result['attendees'];
											$count_attendance = $count_attendance + $result['attendance'];
											?>
											<tr>
												<td><?php echo ( $result['pretty'] == '' ) ? 'Unknown' : $result['pretty']; ?></td>
												<td><?php echo $result['attendees']; ?></td>
												<td><?php echo $result['attendance']; ?></td>
											</tr>
										<?php } ?>
									</tbody>
									<tfoot>
										<td>TOTAL</td>
										<td><?php echo $count_attendees; ?></td>
										<td><?php echo $count_attendance; ?></td>
									</tfoot>
								</table>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
		}
	}
}

// add a link to the attendance stats to the submit postox on the training edit page
add_action( 'post_submitbox_misc_actions', 'cc_attend_stats_post_submitbox' );
function cc_attend_stats_post_submitbox( $post ) {
	if( $post->post_type == 'recording' || $post->post_type == 'workshop' || $post->post_type == 'course' ){
		?>
		<div class="misc-pub-section attend-stats-link-wrap" style="text-align:right;">
			<a class="button" href="<?php echo admin_url('admin.php?page=attendance-stats&t='.$post->ID); ?>">View Attendee Stats</a>
		</div>
		<?php
	}
}
