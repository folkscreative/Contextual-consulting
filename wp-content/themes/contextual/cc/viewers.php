<?php
/**
 * Viewers admin page
 */

add_action( 'admin_menu', 'ccviewers_admin_menu' );
function ccviewers_admin_menu(){
	add_submenu_page( 'edit.php?post_type=recording', 'Viewers', 'Viewers', 'publish_pages', 'viewers', 'ccviewers_viewers_page' );
}
// shows a list of viewers or shows an individual user's details
function ccviewers_viewers_page(){
	date_default_timezone_set('Europe/London');
	$top_msg = '';
	if(!isset($_GET['id']) || $_GET['id'] == ''){
		// showing a list ?>
		<div class="wrap">
			<div class="ccpa-search-wrap">
				<form action="<?php echo admin_url('edit.php'); ?>" method="get">
					<input type="hidden" name="post_type" value="recording">
					<input type="hidden" name="page" value="viewers">
					<table>
						<tr>
							<td><strong><label for="srch">Search:</label></strong></td>
							<td width="400px"><input type="text" id="srch" name="srch" class="widefat" placeholder="Enter part of name or email"></td>
							<td><input type="submit" id="ccpa-search-submit" name="" class="button button-primary" value="Search"></td>
						</tr>
					</table>
				</form>
			</div>
			<h1>Recording Viewers</h1>
			<table>
				<tr>
					<th>Email</th>
					<th>Name</th>
					<th>Last watched</th>
					<th>&nbsp;</th>
				</tr>
				<?php
				$args = array(
					'number' => 50,
				);
				$page_query_args = array(
					'post_type' => 'recording',
					'page' => 'viewers',
				);
				if(isset($_GET['srch']) && $_GET['srch'] <> ''){
					$search_term = '*'.sanitize_text_field($_GET['srch']).'*';
					$args['search'] = $search_term;
					$page_query_args['srch'] = $search_term;
				}
				$paged = 1;
				if(isset($_GET['pg']) && $_GET['pg'] <> ''){
					$paged = absint($_GET['pg']);
					$args['paged'] = $paged;
				}
				$users = get_users($args);
				if(empty($users)){ ?>
					<tr>
						<td colspan="4">Nobody found!</td>
					</tr>
				<?php }else{
					foreach ($users as $user) {
						$user_metas = get_user_meta($user->ID);
						$show_user = false;
						$last_recording = 0;
						$last_time = 0; // timestamp
						foreach ($user_metas as $key => $value) {
							if( preg_match( '/^cc_rec_wshop_\d+$/', $key ) ){ // training level only
								$show_user = true;
								$recording_meta = maybe_unserialize($value[0]);
								$recording_id = substr($key, 13);
								$recording_meta = sanitise_recording_meta( $recording_meta, $user->ID, $recording_id );
								$dt = DateTime::createFromFormat( 'd/m/Y H:i:s', $recording_meta['last_viewed']);
								if( $dt && $dt->getTimestamp() > $last_time){
									// $last_time = $recording_meta['last_viewed'];
									$last_time = $dt->getTimestamp();
									$last_recording = substr($key, 13);
								}
							}
						}
						if($show_user){ ?>
							<tr>
								<td><?php echo $user->user_email; ?></td>
								<?php /*
								<td><?php echo ($user->display_name == $user->user_email) ? '' : $user->display_name; ?></td>
								*/ ?>
								<td><?php echo $user->first_name.' '.$user->last_name; ?></td>
								<td>
									<?php
									if($last_time > 0){
										// echo date('d/m/Y H:i:s', strtotime($last_time)).': '.get_the_title($last_recording);
										echo date( 'd/m/Y H:i:s', $last_time ).': '.get_the_title( $last_recording );
									}
									?>
								</td>
								<td><a class="button" href="<?php echo add_query_arg('id', $user->ID, get_permalink()); ?>">Details</a></td>
							</tr>
						<?php }
					} ?>
					<tr>
						<td colspan="2">
							<?php
							if($paged > 1){
								$prev_pg = $paged - 1;
								$prev_link = add_query_arg(
									array_merge(
										$page_query_args, 
										array('pg' => $prev_pg)
									), admin_url('edit.php'));
								?>
								<a href="<?php echo $prev_link; ?>">Prev</a>
							<?php }
							$next_pg = $paged + 1;
							$next_link = add_query_arg(
								array_merge(
									$page_query_args, 
									array('pg' => $next_pg)
								), admin_url('edit.php'));
							?>
						</td>
						<td colspan="2" style="text-align:right;">
							<a href="<?php echo $next_link; ?>">Next</a>
						</td>
					</tr>
				<?php } ?>
			</table>
		</div>
	<?php }else{
		// showing recordings for this user
		$user_id = absint($_GET['id']);
		$grant_id = '';
		if(isset($_POST['access-submit'])){
			if(isset($_POST['withdraw'])){
				$recording_id = absint($_POST['withdraw']);
				if(ccrecw_withdraw_access($user_id, $recording_id)){
					$top_msg = 'Access to recording withdrawn';
					$top_msg_class = 'updated';
				}else{
					$top_msg = 'Problem attempting to withdraw access, no changes made';
					$top_msg_class = 'error';
				}
			}elseif(isset($_POST['grant'])){
				$recording_id = absint($_POST['grant']);
				$grant_id = $recording_id;
				if(ccrecw_add_recording_to_user($user_id, $recording_id) !== false){ // v1.0.3.40x.0 it can return zero ... which is ok for no payment
					$top_msg = 'Access to recording given';
					$top_msg_class = 'updated';
					if(isset($_POST['send-email']) && $_POST['send-email'] == 'yes'){
						ccrecw_send_extra_rec_email($user_id, $recording_id);
						$top_msg .= ' and email sent';
					}
				}else{
					$top_msg = 'Problem attempting to grant access, no changes made and no email sent.';
					$top_msg_class = 'error';
				}
			}elseif(isset($_POST['reminder'])){
				$recording_id = absint($_POST['reminder']);
				cc_reminders_send_reminder($user_id, $recording_id);
				$top_msg = 'Reminder email triggered';
				$top_msg_class = 'updated';
			}
		} ?>
		<div class="wrap">
			<p class="alignright"><a class="button" href="/wp-admin/edit.php?post_type=recording&page=viewers">Return to Viewers</a></p>
			<h1>Recording details</h1>
			<?php if($top_msg <> ''){ ?>
				<div id="message" class="<?php echo $top_msg_class; ?> notice">
					<p><?php echo $top_msg; ?></p>
				</div>
			<?php }
			$user = get_user_by('ID', $user_id);
			if(!$user){ ?>
				<h2>Cannot find that User!</h2>
			<?php }else{ ?>
				<h2>User: <?php
					echo $user->first_name.' '.$user->last_name.': '.$user->user_email;
				?></h2>
				<table>
					<tr>
						<th>Recording</th>
						<th>Access From</th>
						<th colspan="2">Access To</th>
						<th>Access Type</th>
						<th>Stripe</th>
						<th>Num Views</th>
						<th>First Viewed</th>
						<th>Last Viewed</th>
						<th>Last Viewed Time (H:M:S)</th>
						<th>Viewed To</th>
						<th>Tot View Time (H:M:S)</th>
						<th>&nbsp;</th>
					</tr>
					<?php
				    $args = array(
				    	'post_type' => 'course',
				    	'posts_per_page' => -1,
				        'meta_key' => '_course_type',
				        'meta_value' => 'on-demand'
				    );
				    $recordings = get_posts($args);
				    foreach ($recordings as $recording) {
				    	// $recording_meta = get_user_meta($user_id, 'cc_rec_wshop_'.$recording->ID, true);
				    	$recording_meta = get_recording_meta( $user_id, $recording->ID );
				    	// var_dump($recording_meta);
				    	?>
				    	<tr>
				    		<td><?php echo $recording->ID.': '.$recording->post_title; ?></td>
				    		<td><?php echo ccrecw_pretty_meta($recording_meta, 'access_time'); ?></td>
				    		<?php if(!isset($recording_meta['access_time']) || $recording_meta['access_time'] == ''){ ?>
				    			<td colspan="2">&nbsp;</td>
				    		<?php }else{
					    		if($recording_meta['closed_time'] == ''){
					    			$closed_date = '';
					    			$closed_time = '23:59:59';
					    		}else{
					    			$closed_date = date('d/m/Y', strtotime($recording_meta['closed_time']));
					    			$closed_time = date('H:i:s', strtotime($recording_meta['closed_time']));
					    		} ?>
					    		<td class="access-to-wrap">
					    			<div class="access-to-text-wrap">
						    			<?php echo ccrecw_pretty_meta($recording_meta, 'closed_time'); ?>
					    			</div>
					    			<div class="access-to-update-wrap">
					    				<input type="text" class="access-to-update-date" value="<?php echo $closed_date; ?>">
					    				<input type="text" class="access-to-update-time" value="<?php echo $closed_time; ?>">
					    				<a href="javascript:void(0);" class="button access-to-update-btn" data-user="<?php echo $user_id; ?>" data-recording="<?php echo $recording->ID; ?>">Upd.</a>
					    			</div>
					    		</td>
					    		<td class="access-to-btn-wrap">
					    			<a href="javascript:void(0);" id="" class="access-to-btn"><span class="dashicons dashicons-edit"></span></a>
					    		</td>
					    	<?php } ?>
				    		<td><?php echo ccrecw_pretty_meta($recording_meta, 'access_type'); ?></td>
				    		<td><?php echo ccrecw_pretty_meta($recording_meta, 'token'); ?></td>
				    		<td><?php echo ccrecw_pretty_meta($recording_meta, 'num_views'); ?></td>
				    		<td><?php echo ccrecw_pretty_meta($recording_meta, 'first_viewed'); ?></td>
				    		<td><?php echo ccrecw_pretty_meta($recording_meta, 'last_viewed'); ?></td>
				    		<td><?php echo ccrecw_pretty_meta($recording_meta, 'last_viewed_time'); ?></td>
				    		<td><?php 
				    			if( isset( $recording_meta['viewed_end'] ) && $recording_meta['viewed_end'] == 'yes' ){
				    				echo 'End';
				    			}elseif( isset( $recording_meta['last_playhead'] ) ){
				    				echo ccrecw_pretty_meta($recording_meta, 'last_playhead');
				    			}
				    		?></td>
				    		<td><?php echo ccrecw_pretty_meta($recording_meta, 'viewing_time'); ?></td>
				    		<td>
								<?php
								// added the test for $recording_id == $grant_id as there seems to be a delay in WP updating the DB above when granting access
								$recording_access = ccrecw_user_can_view($recording->ID, $user_id);
								if($recording->ID == $grant_id || $recording_access['access'] ){ ?>
									<form action="<?php the_permalink(); ?>" method="post">
										<input type="hidden" name="withdraw" value="<?php echo $recording->ID; ?>">
										<input type="submit" class="button" name="access-submit" value="Withdraw Access">
									</form>
									<form action="<?php the_permalink(); ?>" method="post">
										<input type="hidden" name="reminder" value="<?php echo $recording->ID; ?>">
										<input type="submit" class="button" name="access-submit" value="Send Access Reminder">
									</form>
					    		<?php }else{ ?>
									<form action="<?php the_permalink(); ?>" method="post">
										<input type="hidden" name="grant" value="<?php echo $recording->ID; ?>">
										<label>Email: </label><input type="checkbox" name="send-email" value="yes">
										<input type="submit" class="button" name="access-submit" value="Give Access">
									</form>
					    		<?php } ?>
				    		</td>
				    	</tr>
				    	<?php
						// modules
						$course = course_get_all( $recording->ID );
						foreach ( $course['modules'] as $module) { ?>
							<tr>
								<td colspan="13">
									&nbsp;&nbsp;&nbsp;Module: <?php echo $module['id'].' '.$module['title']; ?>
								</td>
							</tr>

							<?php
							foreach ($module['sections'] as $section) {
								$section_recording_meta = get_recording_meta( $user_id, $recording->ID, $section['id'] );
			                	?>
			                	<tr>
			                		<td colspan="6">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Section: <?php echo $section['id'].' '.$section['title']; ?></td>
			                		<td>
			                			<?php
			                			if(isset($section_recording_meta['num_views'])){
			                				echo $section_recording_meta['num_views'];
			                			}
			                			?>
			                		</td>
			                		<td>
			                			<?php
			                			if(isset($section_recording_meta['first_viewed']) && $section_recording_meta['first_viewed'] <> ''){
			                				echo $section_recording_meta['first_viewed'];
			                			}
			                			?>
			                		</td>
			                		<td>
			                			<?php
			                			if(isset($section_recording_meta['last_viewed']) && $section_recording_meta['last_viewed'] <> ''){
			                				echo $section_recording_meta['last_viewed'];
			                			}
			                			?>
			                		</td>
			                		<td>
			                			<?php
			                			if(isset($section_recording_meta['last_viewed_time']) && $section_recording_meta['last_viewed_time'] <> ''){
											$hours = floor($section_recording_meta['last_viewed_time'] / 3600);
											$minutes = floor(($section_recording_meta['last_viewed_time'] / 60) % 60);
											$seconds = $section_recording_meta['last_viewed_time'] % 60;
											echo sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
			                			}
			                			?>
			                		</td>
			                		<td>
			                			<?php
						    			if( isset( $section_recording_meta['viewed_end'] ) && $section_recording_meta['viewed_end'] == 'yes' ){
						    				echo 'End';
						    			}elseif( isset( $section_recording_meta['last_playhead'] ) ){
						    				echo ccrecw_pretty_meta($section_recording_meta, 'last_playhead');
						    			}
			                			?>
			                		</td>
			                		<td>
			                			<?php
			                			if(isset($section_recording_meta['viewing_time']) && $section_recording_meta['viewing_time'] <> ''){
											$hours = floor($section_recording_meta['viewing_time'] / 3600);
											$minutes = floor(($section_recording_meta['viewing_time'] / 60) % 60);
											$seconds = $section_recording_meta['viewing_time'] % 60;
											echo sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
			                			}
			                			?>
			                		</td>
			                		<td>&nbsp;</td>
			                	</tr>
			                <?php
			                }
						}
				    } ?>
				</table>
			<?php } ?>			
		</div>
	<?php }
}

