<?php
/***
 * Team page stuff
 */

// add the team info meta box to the page
add_action('add_meta_boxes_page', 'wms_team_add_metaboxes');
function wms_team_add_metaboxes($post){
	global $rpm_theme_options;
	if(get_page_template_slug($post->ID) == 'page-team.php'){
    	// add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
    	add_meta_box( 'team-meta', 'Team details', 'wms_team_metabox_callback', 'page', 'normal', 'high' );
	}
}

// build the meta box for the team details
function wms_team_metabox_callback($post){
	global $rpm_theme_options;
	$team_dets = wms_team_dets_get($post->ID);
	wp_nonce_field( 'team_metabox', 'team_metabox_nonce' );
	?>
	<div id="team-metabox" class="wms-team-metabox">
		<table class="form-table">
			<tr>
				<th>Number of team members</th>
				<td>
					<input type="number" name="teamsize" value="<?php echo $team_dets['teamsize']; ?>" class="regular-text" min="0">
					<p class="description">If you reduce this number, then the bottom member shown below will be deleted (so you might need to change their order first?).</p>
				</td>
			</tr>
			<?php
			$new_order = 0;
			foreach ($team_dets['members'] as $member) {
				$new_order ++;
				?>
				<tr>
					<td colspan="2">
						<hr>
					</td>
				</tr>
				<tr>
					<th>Name</th>
					<td>
						<input type="text" class="regular-text" name="membername[<?php echo $new_order; ?>]" value="<?php echo $member['membername']; ?>">
					</td>
				</tr>
				<tr>
					<th>Role</th>
					<td>
						<input type="text" class="regular-text" name="role[<?php echo $new_order; ?>]" value="<?php echo $member['role']; ?>">
					</td>
				</tr>
				<tr>
					<th>Photo</th>
					<td>
						<div id="team-img-container-<?php echo $new_order; ?>" class="team-img-container-std" style="width:300px;">
							<?php
							$team_img_found = false;
							if($member['photo'] <> ''){
								$team_img_src = wp_get_attachment_image_src( $member['photo'], 'xs' );
								$team_img_found = is_array($team_img_src);
								if($team_img_found){ ?>
									<img src="<?php echo $team_img_src[0] ?>" alt="" style="max-width:100%;">
								<?php }
							} ?>
						</div>
					    <a id="team-img-upload-<?php echo $new_order; ?>" 
					    	class="upload-team-img upload-team-img-std button button-secondary <?php if ( $team_img_found  ) { echo 'hidden'; } ?>" 
							href="<?php echo esc_url( get_upload_iframe_src( 'image', $post->ID ) ); ?>"
							data-order="<?php echo $new_order; ?>">
							Choose photo
					    </a>
					    <a id="team-img-delete-<?php echo $new_order; ?>" 
					    	class="delete-team-img delete-team-img-std button button-secondary <?php if ( ! $team_img_found  ) { echo 'hidden'; } ?>" 
							href="#"
							data-order="<?php echo $new_order; ?>">
							Remove photo
					    </a>
					    <input id="team-img-id-<?php echo $new_order; ?>" class="team-img-id-std" name="photo[<?php echo $new_order; ?>]" type="hidden" value="<?php echo esc_attr( $member['photo'] ); ?>">
					</td>
				</tr>
				<tr>
					<th>Bio</th>
					<td>
						<textarea name="bio[<?php echo $new_order; ?>]" cols="50" rows="5" class="large-text"><?php echo $member['bio']; ?></textarea>
					</td>
				</tr>
				<tr>
					<th>Order</th>
					<td>
						<input type="number" name="order[<?php echo $new_order; ?>]" value="<?php echo $new_order; ?>" class="regular-text" min="1">
					</td>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php }

// first time team dets
function wms_team_empty_dets(){
	return array(
		'teamsize' => 1,
		'members' => array(
			wms_team_empty_member(),
		),
	);
}

// empty member
function wms_team_empty_member(){
	return array(
		'order' => 1,
		'membername' => '',
		'role' => '',
		'photo' => '',
		'bio' => '',
	);
}

// get the team details, in required order
function wms_team_dets_get($postid){
	$team_dets = get_post_meta($postid, '_wms_team_dets', true);
	if($team_dets == ''){
		$team_dets = wms_team_empty_dets();
	}
	$members = $team_dets['members'];
	if(count($team_dets['members']) > 1){
		usort($members, function ($a, $b) { return ($a['order'] <=> $b['order']); });
	}
	return array(
		'teamsize' => $team_dets['teamsize'],
		'members' => $members,
	);
}

// save the metabox
add_action('save_post', 'wms_team_save_metabox');
function wms_team_save_metabox($postid){
	global $rpm_theme_options;
	if(get_page_template_slug($postid) <> 'page-team.php'){
        return $postid;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $postid;
    }
    if(!isset($_POST['team_metabox_nonce']) || !wp_verify_nonce($_POST['team_metabox_nonce'], 'team_metabox')){
        return $postid;
    }
    $teamsize = 1;
    if(isset($_POST['teamsize'])){
    	$teamsize = absint($_POST['teamsize']);
    	if($teamsize == 0) $teamsize = 1;
    }
    $members = array();
    for ($i=1; $i < $teamsize + 1; $i++) { 
    	$new_member = wms_team_empty_member();
    	$new_member['order'] = 99;
    	if(isset($_POST['order'][$i])){
    		$new_member['order'] = absint($_POST['order'][$i]);
    	}
    	if(isset($_POST['membername'][$i])){
    		$new_member['membername'] = stripslashes(sanitize_text_field($_POST['membername'][$i]));
    	}
    	if(isset($_POST['role'][$i])){
    		$new_member['role'] = stripslashes(sanitize_text_field($_POST['role'][$i]));
    	}
    	if(isset($_POST['photo'][$i])){
    		$new_member['photo'] = absint($_POST['photo'][$i]);
    		if($new_member['photo'] == 0){
    			$new_member['photo'] = '';
    		}
    	}
    	if(isset($_POST['bio'][$i])){
    		$new_member['bio'] = stripslashes(sanitize_textarea_field($_POST['bio'][$i]));
    	}
    	$members[] = $new_member;
    }
    if(count($members) > 1){
    	usort($members, function ($a, $b) { return ($a['order'] <=> $b['order']); });
    }
    $new_members = array();
    $new_order = 0;
    foreach ($members as $member) {
    	$new_order ++;
    	$new_member = $member;
    	$new_member['order'] = $new_order;
    	$new_members[] = $new_member;
    }
    $team_dets = array(
    	'teamsize' => $teamsize,
    	'members' => $new_members,
    );
    update_post_meta($postid, '_wms_team_dets', $team_dets);
}
