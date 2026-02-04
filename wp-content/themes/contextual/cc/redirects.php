<?php
/**
 * Old Training Redirects
 */

// add the control pages to the backend
add_action( 'admin_menu', 'cc_redirects_admin_menu' );
function cc_redirects_admin_menu(){
	// add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = ”, int|float $position = null )
	add_submenu_page( 'edit.php?post_type=workshop', 'Redirects', 'Redirects', 'publish_pages', 'workshop-redirects', 'cc_redirects_workshops_page' );
	add_submenu_page( 'edit.php?post_type=recording', 'Redirects', 'Redirects', 'publish_pages', 'recording-redirects', 'cc_redirects_recordings_page' );
}

// the workshops redirect control page
function cc_redirects_workshops_page(){
	add_thickbox();
	?>
	<div class="wrap">
		<h1>Workshop redirects</h1>
		<?php // echo $GLOBALS['hook_suffix'];
		$args = array(
			'post_type' => 'workshop',
			'post_status' => array( 'publish', 'archive', 'pending', 'draft', 'future', 'private' ),
			'numberposts' => -1,
		);
		$workshops = get_posts( $args );
		$now = time();
		$year_ago = strtotime( '-1 year' );
		?>
		<div class="table-responsive">
			<table class="table table-condensed striped cc-redirects-table">
				<thead>
					<tr>
						<th class="ccr-id">ID</th>
						<th class="ccr-tit">Title</th>
						<th class="ccr-stat" title="workshop status: only publish is shown">Status</th>
						<th class="ccr-st" title="workshop start date">Start</th>
						<th colspan="2" class="ccr-rech" title="Is there a linked recording and is it open for registration?">Recording</th>
						<th class="ccr-oth" title="link to another URL (not recording)">Other</th>
						<th class="ccr-msg" title="Display a message panel instead of redirecting">Message</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($workshops as $workshop) { ?>
						<tr>
							<td class="ccr-id"><?php echo $workshop->ID; ?></td>
							<td class="ccr-tit"><?php
								echo $workshop->post_title;
								echo ' <a href="'.get_edit_post_link( $workshop->ID ).'" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>';
							?></td>
							<td id="ccredir-<?php echo $workshop->ID; ?>-archive" class="ccr-stat"><?php
								$workshop_start_timestamp = get_post_meta( $workshop->ID, 'workshop_start_timestamp', true );
								echo cc_redirect_archive_cell( $workshop, $workshop_start_timestamp );
							?></td>
							<td class="ccr-st"><?php
								if( $workshop_start_timestamp == '' ){
									echo 'Unknown';
								}else{
									if( $workshop_start_timestamp < $year_ago ){
										echo '<span class="text-danger">';
									}elseif( $workshop_start_timestamp < $now ){
										echo '<span class="text-warning">';
									}else{
										echo '<span class="text-success">';
									}
									echo date( 'd/m/Y', $workshop_start_timestamp ).'</span>';
								}
							?></td>
							<td class="ccr-rec1"><?php
								$recording_id = (int) get_post_meta( $workshop->ID, 'workshop_recording', true );
								if( $recording_id > 0 ){
									$recording_url = get_permalink( $recording_id );
									$course_status = get_post_meta( $recording_id, '_course_status', true );
									if( $course_status == 'closed' ){
										echo '<a class="text-danger" href="'.get_edit_post_link( $recording_id ).'" target="_blank" title="'.get_the_title( $recording_id ).'">Closed</a>';
									}else{
										echo '<a class="text-success" href="'.get_edit_post_link( $recording_id ).'" target="_blank" title="'.get_the_title( $recording_id ).'">Open</a>';
									}
								}else{
									$recording_url = '';
									$course_status = '';
									echo 'No';
								}
							?></td>
							<td id="ccredir-<?php echo $workshop->ID; ?>-recording" class="ccr-rec2"><?php
								$msg_panel = get_post_meta( $workshop->ID, '_redir_msg_panel', true );
								$links_to = get_post_meta( $workshop->ID, '_links_to', true );
								echo cc_redirect_recording_cell( $workshop->ID, $workshop_start_timestamp, $recording_id, $links_to, $recording_url, $course_status, $workshop->post_status, $msg_panel );
							?></td>
							<td id="ccredir-<?php echo $workshop->ID; ?>-other" class="ccr-oth"><?php
								echo cc_redirect_other_cell( $workshop->ID, $workshop_start_timestamp, $recording_id, $links_to, $recording_url, $workshop->post_status, $msg_panel );
							?></td>
							<td id="ccredir-<?php echo $workshop->ID; ?>-message" class="ccr-msg"><?php
								echo cc_redirect_msg_cell( $workshop->ID, $workshop_start_timestamp, $links_to, $workshop->post_status, $msg_panel );
							?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="wrap">
		<h2>Redirect Message</h2>
		<p>Displayed in a panel on workshops where selected above. Please note that the panel will also include links to the training pages.</p>
		<form id="cc-redir-panel-form" method="post" action="">
			<table class="form-table">
				<tr>
					<th>
						<label for="cc-redir-panel-form-heading">Heading</label>
					</th>
					<td>
						<input type="text" class="regular-text" id="cc-redir-panel-form-heading" name="" placeholder="" value="<?php echo get_option('cc-redir-panel-form-heading', ''); ?>">
					</td>
				</tr>
				<tr>
					<th>
						<label for="cc-redir-panel-form-text">Text</label>
					</th>
					<td>
						<textarea name="" id="cc-redir-panel-form-text" cols="60" rows="5" class="large-text code" placeholder=""><?php echo get_option('cc-redir-panel-form-text', ''); ?></textarea>
					</td>
				</tr>
			</table>
			<?php
			submit_button( 'Update redirect message', 'secondary', 'submit', true, array( 'id' => 'cc-redir-panel-form-submit' ) );
			?>
			<span id="cc-redir-panel-form-msg"></span>
		</form>
	</div>
	<?php
}

// the recordings redirect control page
function cc_redirects_recordings_page(){
	add_thickbox();
	?>
	<div class="wrap">
		<h1>Recording redirects</h1>
		<?php
		$args = array(
			'post_type' => 'recording',
			'post_status' => array( 'publish', 'archive', 'pending', 'draft', 'future', 'private' ),
			'numberposts' => -1,
		);
		$recordings = get_posts( $args );
		$now = time();
		$year_ago = strtotime( '-1 year' );
		?>
		<div class="table-responsive">
			<table class="table table-condensed striped cc-redirects-table">
				<thead>
					<tr>
						<th class="ccr-id">ID</th>
						<th class="ccr-tit">Title</th>
						<th class="ccr-stat" title="recording status: only publish is shown">Status</th>
						<th class="ccr-st" title="published date">Published</th>
						<th class="ccr-oth" title="link to another URL">Redirect</th>
						<th class="ccr-msg" title="Display a message panel instead of redirecting">Message</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($recordings as $recording) { ?>
						<tr>
							<td class="ccr-id"><?php echo $recording->ID; ?></td>
							<td class="ccr-tit"><?php
								echo $recording->post_title;
								echo ' <a href="'.get_edit_post_link( $recording->ID ).'" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>';
							?></td>
							<td id="ccredir-<?php echo $recording->ID; ?>-archive" class="ccr-stat"><?php
								echo cc_redirect_archive_cell( $recording );
							?></td>
							<td class="ccr-st"><?php
								echo get_the_date( 'd M Y', $recording->ID );
							?></td>
							<td id="ccredir-<?php echo $recording->ID; ?>-other" class="ccr-oth"><?php
								$links_to = get_post_meta( $recording->ID, '_links_to', true );
								$msg_panel = get_post_meta( $recording->ID, '_redir_msg_panel', true );
								echo cc_redirect_recording_other_cell( $recording->ID, $links_to, $recording->post_status, $msg_panel );
							?></td>
							<td id="ccredir-<?php echo $recording->ID; ?>-message" class="ccr-msg"><?php
								echo cc_redirect_msg_cell( $recording->ID, '', $links_to, $recording->post_status, $msg_panel );
							?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="wrap">
		<h2>Redirect Message</h2>
		<p>Please go to Workshops > Redirect to modify the redirect message.</p>
	</div>
	<?php
}

// the archive table cell
function cc_redirect_archive_cell( $training, $workshop_start_timestamp='' ){
	$html = '';
	$now = time();
	$year_ago = strtotime( '-1 year' );
	if( $training->post_type == 'workshop' ){
		if( $workshop_start_timestamp < $year_ago && $training->post_status == 'publish' ){
			$xclass = 'text-danger';
		}elseif( $workshop_start_timestamp < $now && $training->post_status == 'publish' ){
			$xclass = 'text-warning';
		}else{
			$xclass = 'text-success';
		}
		$html .= '<span class="'.$xclass.'">'.$training->post_status.'</span> ';
		if( $workshop_start_timestamp < $now && $training->post_status == 'publish' ){
			$html .= '<a href="javascript:void(0);" class="button button-secondary '.$xclass.' ccredir-btn" data-tid="'.$training->ID.'" data-action="archive" data-cell="archive" title="Archive it"><i class="fa-solid fa-box-archive"></i></a>';
		}elseif( $training->post_status == 'archive' ){
			$html .= '<a href="javascript:void(0);" class="button button-secondary ccredir-btn" data-tid="'.$training->ID.'" data-action="unarchive" data-cell="archive" title="Un-archive it"><i class="fa-solid fa-arrow-up-from-bracket"></i></a>';
		}else{
			$html .= '<span class="text-primary">'.$training->post_status.'</span>';
		}
	}else{
		if( $training->post_status == 'archive' ){
			$xclass = 'text-warning';
			$html .= '<span class="'.$xclass.'">'.$training->post_status.'</span> ';
			$html .= '<a href="javascript:void(0);" class="button button-secondary '.$xclass.' ccredir-btn" data-tid="'.$training->ID.'" data-action="unarchive" data-cell="archive" title="Un-archive it"><i class="fa-solid fa-arrow-up-from-bracket"></i></a>';
		}elseif( $training->post_status == 'publish' ){
			$xclass = 'text-success';
			$html .= '<span class="'.$xclass.'">'.$training->post_status.'</span> ';
			$html .= '<a href="javascript:void(0);" class="button button-secondary '.$xclass.' ccredir-btn" data-tid="'.$training->ID.'" data-action="archive" data-cell="archive" title="Archive it"><i class="fa-solid fa-box-archive"></i></a>';
		}else{
			$html .= '<span class="text-primary">'.$training->post_status.'</span>';
		}
	}
	return $html;
}

// the recording link table cell
function cc_redirect_recording_cell( $training_id, $workshop_start_timestamp, $recording_id, $links_to, $recording_url, $course_status, $post_status, $msg_panel ){
	$html = '';
	$now = time();
	if( $workshop_start_timestamp < $now && $recording_id > 0 && $post_status == 'publish' ){
		if( $links_to == $recording_url ){
			if( $course_status == 'closed' ){
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-danger ccredir-btn" data-tid="'.$training_id.'" data-cell="recording" data-action="unlinkrec" title="Remove redirect to recording"><i class="fa-solid fa-forward"></i> <i class="fa-solid fa-square-check"></i></a>';
			}else{
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-success ccredir-btn" data-tid="'.$training_id.'" data-cell="recording" data-action="unlinkrec" title="Remove redirect to recording"><i class="fa-solid fa-forward"></i> <i class="fa-solid fa-square-check"></i></a>';
			}
		}else{
			if( $msg_panel == 'yes' ){
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-secondary ccredir-btn" data-tid="'.$training_id.'" data-cell="recording" data-action="linkrec" title="Add redirect to recording"><i class="fa-solid fa-forward"></i> <i class="fa-solid fa-square-xmark"></i></a>';
			}elseif( $course_status == 'closed' ){
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-warning ccredir-btn" data-tid="'.$training_id.'" data-cell="recording" data-action="linkrec" title="Add redirect to recording"><i class="fa-solid fa-forward"></i> <i class="fa-solid fa-square-xmark"></i></a>';
			}else{
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-danger ccredir-btn" data-tid="'.$training_id.'" data-cell="recording" data-action="linkrec" title="Add redirect to recording"><i class="fa-solid fa-forward"></i> <i class="fa-solid fa-square-xmark"></i></a>';
			}
		}
	}
	return $html;
}

// the table cell for the redirect of a workshop to another url
function cc_redirect_other_cell( $training_id, $workshop_start_timestamp, $recording_id, $links_to, $recording_url, $post_status, $msg_panel ){
	$html = '';
	$now = time();
	if( $workshop_start_timestamp < $now && $post_status == 'publish' ){
		if( $links_to == '' ){
			if( $msg_panel == 'yes' ){
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-secondary ccredir-btn" data-tid="'.$training_id.'" data-cell="other" data-action="linkother" title="Add redirect to other URL"><i class="fa-solid fa-forward"></i> <i class="fa-solid fa-square-xmark"></i></a> <span class="cc-redirect-link-wrap"></span>';
			}else{
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-danger ccredir-btn" data-tid="'.$training_id.'" data-cell="other" data-action="linkother" title="Add redirect to other URL"><i class="fa-solid fa-forward"></i> <i class="fa-solid fa-square-xmark"></i></a> <span class="cc-redirect-link-wrap"></span>';
			}
		}else{
			if( $recording_id > 0 && $links_to == $recording_url ){
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-secondary ccredir-btn" data-tid="'.$training_id.'" data-cell="other" data-action="linkother" title="Swap redirect from recording to other URL"><i class="fa-solid fa-forward"></i> <i class="fa-solid fa-square-xmark"></i></a> <span class="cc-redirect-link-wrap"></span>';
			}else{
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-success ccredir-btn" data-tid="'.$training_id.'" data-cell="other" data-action="unlinkother" title="Remove redirect to other URL"><i class="fa-solid fa-forward"></i> <i class="fa-solid fa-square-check"></i></a> <span class="cc-redirect-link-wrap"><a href="'.$links_to.'" target="_blank" title="'.$links_to.'"><i class="fa-solid fa-arrow-up-right-from-square"></i></a></span>';
			}
		}
	}
	return $html;
}

// the table cell for the redirect of a recording to another url
function cc_redirect_recording_other_cell( $training_id, $links_to, $post_status, $msg_panel ){
	$html = '';
	if( $post_status == 'publish' ){
		if( $links_to == '' ){
			if( $msg_panel == 'yes' ){
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-secondary ccredir-btn" data-tid="'.$training_id.'" data-cell="other" data-action="linkother" title="Add redirect to other URL"><i class="fa-solid fa-forward"></i> <i class="fa-solid fa-square-xmark"></i></a> <span class="cc-redirect-link-wrap"></span>';
			}else{
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-danger ccredir-btn" data-tid="'.$training_id.'" data-cell="other" data-action="linkother" title="Add redirect to other URL"><i class="fa-solid fa-forward"></i> <i class="fa-solid fa-square-xmark"></i></a> <span class="cc-redirect-link-wrap"></span>';
			}
		}else{
			$html .= '<a href="javascript:void(0);" class="button button-secondary text-success ccredir-btn" data-tid="'.$training_id.'" data-cell="other" data-action="unlinkother" title="Remove redirect to other URL"><i class="fa-solid fa-forward"></i> <i class="fa-solid fa-square-check"></i></a> <span class="cc-redirect-link-wrap"><a href="'.$links_to.'" target="_blank" title="'.$links_to.'"><i class="fa-solid fa-arrow-up-right-from-square"></i></a></span>';
		}
	}
	return $html;
}

// the table cell for the message panel instead of a redirect
// $workshop_start_timestamp == '' for recording
function cc_redirect_msg_cell( $training_id, $workshop_start_timestamp, $links_to, $post_status, $msg_panel ){
	$html = '';
	$now = time();
	if( ( $workshop_start_timestamp == '' || $workshop_start_timestamp < $now ) && $post_status == 'publish' ){
		if( $msg_panel == 'yes' ){
			$html .= '<a href="javascript:void(0);" class="button button-secondary text-success ccredir-btn" data-tid="'.$training_id.'" data-cell="msg" data-action="panelhide" title="Remove the message panel">[msg]</a>';
		}else{
			if( $links_to == '' ){
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-danger ccredir-btn" data-tid="'.$training_id.'" data-cell="msg" data-action="panelshow" title="Show the message panel">[msg]</a>';
			}else{
				$html .= '<a href="javascript:void(0);" class="button button-secondary text-secondary ccredir-btn" data-tid="'.$training_id.'" data-cell="msg" data-action="panelshow" title="Show the message panel">[msg]</a>';
			}
		}
	}
	return $html;
}

// add the post_status of archive to trainings
add_action( 'init', 'cc_redirect_archive_post_status', 0 );
function cc_redirect_archive_post_status() {
	$args = array(
		'label'                     => 'Archived',
		'label_count'               => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>' ),
		'public'                    => false,
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => true,
		'exclude_from_search'       => true,
	);
	register_post_status( 'archive', $args );
}

// Using jQuery to add it to post status dropdown
add_action( 'admin_print_footer_scripts', 'cc_redirect_append_post_status_list' );
function cc_redirect_append_post_status_list() {
	// Must be a valid WP_Post object
	global $post;
	if ( !isset($post) ) return;
	if ( !($post instanceof WP_Post) ) return;
	if ( !in_array( $post->post_type, array( 'workshop', 'recording') ) ) return;
	$is_selected = $post->post_status == 'archive';
	?>
	<script type="text/javascript">
	// Single post edit screen, add dropdowns and text to publish box:
	jQuery(function() {
		var archive_selected = <?php echo $is_selected ? 1 : 0; ?>;
		var $post_status = jQuery("#post_status");
		var $post_status_display = jQuery("#post-status-display");
		$post_status.append('<option value="archive">Archived</option>');
		if ( archive_selected ) {
			$post_status.val( 'archive' );
			$post_status_display.text('Archived');
		}
	});
	// Post listing screen: Add quick edit functionality:
	jQuery(function() {
		// See: /wp-admin/js/inline-edit-post.js -> Window.inlineEditPost.edit
		var insert_archived_status_to_inline_edit = function(t, post_id, $row) {
			// t = window.inlineEditPost
			// post_id = post_id of the post (eg: div#inline_31042 -> 31042)
			// $row = The original post row <tr> which contains the quick edit button, post title, columns, etc.
			var $editRow = jQuery('#edit-' + post_id); // The quick edit row that appeared.
			var $rowData = jQuery('#inline_' + post_id); // A hidden row that contains relevant post data
			var status = jQuery('._status', $rowData).text(); // Current post status
			var $status_select = $editRow.find('select[name="_status"]'); // Dropdown to change status
			// Add archive status to dropdown, if not present
			if ( $status_select.find('option[value="archive"]').length < 1 ) {
				$status_select.append('<option value="archive">Archived</option>');
			}
			// Select archive from dropdown if that is the current post status
			if ( status === 'archive' ) $status_select.val( 'archive' );
			// View information:
			// console.log( id, $row, $editRow, $rowData, status, $status_select );
		};
		// On click, wait for default functionality, then apply our customizations
		var inline_edit_post_status = function() {
			var t = window.inlineEditPost;
			var $row = jQuery(this).closest('tr');
			var id = t.getId(this);
			// Use next frame if browser supports it, or wait 0.25 seconds
			if ( typeof requestAnimationFrame === 'function' ) {
				requestAnimationFrame(function() { return insert_archived_status_to_inline_edit( t, post_id, $row ); });
			}else{
				setTimeout(function() { return insert_archived_status_to_inline_edit( t, post_id, $row ); }, 250 );
			}
		};
		// Bind click event before inline-edit-post.js has a chance to bind it
		jQuery('#the-list').on('click', '.editinline', inline_edit_post_status);
	});
	</script>
	<?php
}

// Display "— Archived" after post name on the dashboard, like you would see "— Draft" for draft posts.
// $post_states is an array of displayable post states for this post (eg draft, private). it does not include "published"
add_filter( 'display_post_states', 'cc_redirect_display_status_label', 10, 2 );
function cc_redirect_display_status_label( $post_states, $post ) {
	if ( 'archive' === $post->post_status ) {
		$post_states['archive'] = _x( 'Archived', 'post status' );
	}
	/*


	// Receive the post status details
	$post_status_details = get_post_status_object( $post->post_status );
	// Checks if the label exists
	if ( in_array( $post_status_details->label, $post_states, true ) ) {
		return $post_states;
	}

	// Adds the label of the current post status
	$post_states[ $post_status_details->name ] = $post_status_details->label;

	return $post_states;	if( get_query_var( 'post_status' ) != 'archive' ){ // not for pages with all posts of this status
		if( $post->post_status == 'archive' ){ // if the post_status is archive
			return array('Archived'); // returning our status label
		}
	}
	*/
	return $post_states; // returning the array with default post_states
}

// ajax update from the redirect pages
add_action( 'wp_ajax_cc_redirect_update', 'cc_redirect_update' );
function cc_redirect_update(){
	$response = array(
		'status' => 'error',
		'msg' => 'Unknown error',
		'archive' => '',
		'recording' => '',
		'other' => '',
		'message' => '',
	);
	$training_id = 0;
	if( isset( $_POST['trainingID'] ) ){
		$training_id = absint( $_POST['trainingID'] );
	}
	$action = '';
	if( isset( $_POST['redirAction'] ) ){
		$action = stripslashes( sanitize_text_field( $_POST['redirAction'] ) );
	}
	$other_url = '';
	if( isset( $_POST['url'] ) ){
		$other_url = stripslashes( sanitize_url( trim( $_POST['url'] ) ) );
	}

	if( $training_id > 0 ){
		$training = get_post( $training_id );
		if( $training->post_type == 'workshop' || $training->post_type == 'recording' ){
			switch ($action) {
				case 'archive':
					// archive it
					if( $training->post_status <> 'archive' ){
						$args = array(
							'ID' => $training_id,
							'post_status' => 'archive',
						);
						if( wp_update_post( $args ) ){
							$training->post_status = 'archive';
							$response['status'] = 'ok';
						}else{
							$response['msg'] = 'Update failed';
						}
					}else{
						$response['msg'] = 'Already archived';
					}
					break;

				case 'unarchive':
					// un-archive it
					if( $training->post_status <> 'publish' ){
						$args = array(
							'ID' => $training_id,
							'post_status' => 'publish',
						);
						if( wp_update_post( $args ) ){
							$response['status'] = 'ok';
							$training->post_status = 'publish';
						}else{
							$response['msg'] = 'Update failed';
						}
					}else{
						$response['msg'] = 'Already published';
					}
					break;

				case 'linkrec':
					// redirect workshop to recording
					$links_to = get_post_meta( $training_id, '_links_to', true );
					$recording_url = $course_status = '';
					$recording_id = (int) get_post_meta( $training_id, 'workshop_recording', true );
					if( $recording_id > 0 ){
						$recording_url = get_permalink( $recording_id );
						$course_status = get_post_meta( $recording_id, '_course_status', true );
					}
					if( $links_to <> $recording_url ){
						$links_to = $recording_url;
						update_post_meta( $training_id, '_links_to', $links_to );
						update_post_meta( $training_id, '_redir_msg_panel', 'no' );
						$response['status'] = 'ok';
					}else{
						$response['msg'] = 'Already redirected to that recording';
					}
					break;

				case 'unlinkrec':
				case 'unlinkother':
					// remove link to recording or anywhere else
					update_post_meta( $training_id, '_links_to', '' );
					$response['status'] = 'ok';
					break;

				case 'linkother':
					// redirect to another URL
					$links_to = get_post_meta( $training_id, '_links_to', true );
					if( $other_url <> '' ){
						update_post_meta( $training_id, '_links_to', $other_url );
						update_post_meta( $training_id, '_redir_msg_panel', 'no' );
						$response['status'] = 'ok';
						$response['msg'] = 'Success';
					}else{
						$response['msg'] = 'No URL submitted';
					}
					break;

				case 'panelhide':
					// hide the message panel
					update_post_meta( $training_id, '_redir_msg_panel', 'no' );
					$response['status'] = 'ok';
					$response['msg'] = 'Success';
					break;

				case 'panelshow':
					// show the message panel
					update_post_meta( $training_id, '_redir_msg_panel', 'yes' );
					$response['status'] = 'ok';
					$response['msg'] = 'Success';
					break;
				
				default:
					// code...
					break;
			}
			if( $response['status'] == 'ok' ){
				$workshop_start_timestamp = get_post_meta( $training_id, 'workshop_start_timestamp', true );
				$links_to = get_post_meta( $training_id, '_links_to', true );
				$recording_url = $course_status = '';
				$recording_id = (int) get_post_meta( $training_id, 'workshop_recording', true );
				if( $recording_id > 0 ){
					$recording_url = get_permalink( $recording_id );
					$course_status = get_post_meta( $recording_id, '_course_status', true );
				}
				$msg_panel = get_post_meta( $training_id, '_redir_msg_panel', true );
				$response['archive'] = cc_redirect_archive_cell( $training, $workshop_start_timestamp );
				$response['recording'] = cc_redirect_recording_cell( $training_id, $workshop_start_timestamp, $recording_id, $links_to, $recording_url, $course_status, $training->post_status, $msg_panel );
				$response['other'] =  cc_redirect_other_cell( $training_id, $workshop_start_timestamp, $recording_id, $links_to, $recording_url, $training->post_status, $msg_panel );
				$response['message'] = cc_redirect_msg_cell( $training_id, $workshop_start_timestamp, $links_to, $training->post_status, $msg_panel );
				$response['msg'] = 'Success';
			}
		}else{
			$response['msg'] = 'Training not found';
		}
	}else{
		$response['msg'] = 'Training ID invalid';
	}
   	echo json_encode($response);
	die();
}

// we'll put the thickbox content form into the admin footer
add_filter('admin_footer-workshop_page_workshop-redirects', 'cc_redirects_modal_content');
add_filter('admin_footer-recording_page_recording-redirects', 'cc_redirects_modal_content');
function cc_redirects_modal_content(){ ?> 
    <div id="cc-redirects-modal" class="cc-redirects-modal" style="display:none;" >
    	<div class="cc-redirects-modal-outer">
	    	<div class="cc-redirects-modal-inner">
	    		<h2 class="cc-redirects-modal-header">Redirect To Other URL</h2>
	    		<form action="">
	    			<input type="hidden" id="cc-redirects-training-id">
	    			<div class="cc-redirects-field-wrap">
	    				<label for="cc-redirects-ext-url" class="cc-redirects-modal-label">URL:</label><br>
	    				<input type="text" id="cc-redirects-ext-url" class="cc-redirects-ext-url" placeholder="https://...">
	    			</div>
	    			<div class="cc-redirects-field-wrap">
	    				<div class="cc-redirects-half-col">
	    					<a href="javascript:void(0);" id="cc-redirects-modal-close">Close</a>
	    				</div>
	    				<div class="cc-redirects-half-col cc-redirects-save-wrap">
	    					<a href="javascript:void(0);" id="cc-redirects-save-btn" class="button-primary cc-redirects-save-btn">Save</a>
	    				</div>
	    			</div>
	    		</form>
	    	</div>
    	</div>
	</div>
	<?php     
}

// save the updated redirect panel message (workshops)
add_action( 'wp_ajax_cc_redirect_panel_msg_save', 'cc_redirect_panel_msg_save' );
function cc_redirect_panel_msg_save(){
	$response = array(
		'status' => 'ok',
	);

	$heading = '';
	if( isset( $_POST['heading'] ) ){
		$heading = stripslashes( sanitize_text_field( $_POST['heading'] ) );
	}
	$text = '';
	if( isset( $_POST['text'] ) ){
		$text = stripslashes( $_POST['text'] );
	}
	update_option( 'cc-redir-panel-form-heading', $heading );
	update_option( 'cc-redir-panel-form-text', $text );

   	echo json_encode($response);
	die();
}

// bulk redirect shortcode
add_shortcode( 'bulk_redirect_change', 'cc_redirect_bulk_change' );
function cc_redirect_bulk_change( $atts ){
	global $wpdb;
	$atts = shortcode_atts( array(
		'training' => 'workshops',				// workshops, recordings or all
		'from' => '',
		'to' => '',
		'verbose' => 'yes',
		'dry' => 'yes',
	), $atts );

	$html = '<h3>Bulk redirect change</h3>';

	$posts_table = $wpdb->prefix.'posts';
	$postmeta_table = $wpdb->prefix.'postmeta';

	if( $atts['from'] == '' ){
		$html .= '<p>No FROM URL specified</p>';
		return $html;
	}
	if( $atts['to'] == '' ){
		$html .= '<p>No TO URL specified</p>';
		return $html;
	}

	if( $atts['training'] == 'workshops' ){
		$posts_where = "p.post_type = 'workshop'";
	}elseif( $atts['training'] == 'recordings' ){
		$posts_where = "p.post_type = 'recording'";
	}elseif( $atts['training'] == 'all' ){
		$posts_where = "p.post_type IN ('workshop','recording')";
	}else{
		$html .= '<p>Invalid training selector: use workshops, recordings or all</p>';
		return $html;
	}

	$from = $atts['from'];

	// look for all redirects matching $from
	$sql = "SELECT p.ID, p.post_title
			FROM $posts_table AS p
			INNER JOIN $postmeta_table AS m
			ON p.ID = m.post_id
			WHERE $posts_where
			AND m.meta_key = '_links_to'
			AND m.meta_value = '$from'";
	$to_change = $wpdb->get_results( $sql, ARRAY_A );

	if( count( $to_change ) == 0 ){
		$html .= '<p>Nothing found to change</p>';
		return $html;
	}

	$html .= '<p>'.count( $to_change ).' redirects found to change</p>';

	if( $atts['dry'] == 'yes' ){
		$html .= '<p>Dry run selected - no updates will be made. Set dry="no" to apply the updates.</p>';
	}

	$count_updates = 0;
	foreach ($to_change as $training) {
		if( $atts['dry'] <> 'yes' ){
			update_post_meta( $training['ID'], '_links_to', $atts['to'] );
		}
		$count_updates ++;
		if( $atts['verbose'] == 'yes' ){
			$html .= '<p>Training '.$training['ID'].' '.$training['post_title'].' redirect changed</p>';
		}
	}

	$html .= '<p>'.$count_updates.' redirects changed</p>';

	return $html;
}