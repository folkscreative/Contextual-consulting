<?php
/**
 * Feedback reporting page
 * linked to workshops and recordings
 * but not shown in the menus
 */

// add the hidden view bookings page
add_action('admin_menu', 'cc_fbrep_admin_menu');
function cc_fbrep_admin_menu(){
	// add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = '', int|float $position = null )
	add_submenu_page('options.php', 'Feedback Reporting', 'Feedback Reporting', 'edit_posts', 'feedback_reporting', 'cc_fbrep_page');
	// the url for this page will be something like https://contextualconsulting.co.uk/wp-admin/admin.php?page=feedback_reporting&t=1234
}

// add a link to the feedback reporting page to the workshop and recording page lists
// uses the row actions filter
add_filter('post_row_actions', 'cc_fbrep_post_row_actions', 10, 2);
function cc_fbrep_post_row_actions($actions, $post){
	if($post->post_type == 'workshop' || $post->post_type == 'recording'){
		// normally includes edit, quick edit, bin and view
		// unset($actions['view']);
		if( ! empty( cc_feedback_for_training($post->ID ) ) ){
			// there is some feedback
			$actions['feedback'] = '<a href="'.admin_url('admin.php?page=feedback_reporting&t='.$post->ID).'">View Feedback</a>';
		}
	}
	return $actions;
}

// add a link to the feedback to the submit postox on the training edit page
add_action( 'post_submitbox_misc_actions', 'cc_fbrep_post_submitbox_misc_actions' );
function cc_fbrep_post_submitbox_misc_actions( $post ) {
	if( $post->post_type == 'recording' || $post->post_type == 'workshop' ){
		if( ! empty( cc_feedback_for_training($post->ID ) ) ){
			?>
			<hr>
			<div class="misc-pub-section fbrep-link-wrap">
				<a class="button" href="<?php echo admin_url('admin.php?page=feedback_reporting&t='.$post->ID); ?>">View Feedback</a>
			</div>
			<?php
		}
	}
}

function cc_fbrep_page(){
	global $wpdb;
	if(!current_user_can('edit_posts')) wp_die('Go away!');
	if(isset($_GET['t'])){
		$training_id = (int) $_GET['t'];
		if($training_id > 0){
			$feedback_questions = get_post_meta($training_id, '_feedback_questions', true);
			$feedbacks = cc_feedback_for_training($training_id);

			// break the questions down into an array of shortcodes
			$shortcodes = explode(']', $feedback_questions);
			// assemble the questions into an array
			$questions = array();
			foreach ($shortcodes as $shortcode) {
				$atts = shortcode_parse_atts( trim( $shortcode ).']' );
				if( !isset( $atts['name'] ) ) continue;
				$question = array(
					'field' => $atts['field'],
					'name' => strtolower( $atts['name'] ),
					'question' => ($atts['question'] == '') ? $atts['name'] : $atts['question'],
					'required' => $atts['required'],
					'options' => '',
					'count' => 0,
				);
				if($atts['field'] == 'radio'){
					$question['options'] = array_map( 'trim', explode(',', $atts['options'] ) );
					$question['count'] = array();
				}
				$questions[] = $question;
			}

			// export .....
			$export_link = '';
			if(isset($_POST['fbrep-export-go'])){
				$filename = 'feedback-'.$training_id.'-'.get_post_field( 'post_name', get_post($training_id) ).'-'.date('Y-m-d-H-i-s').'.csv';

				//  could use PHPSpreadhseet .. if only we were using PHP 8+

		        $file_url = ABSPATH.'/'.$filename;
		        $file = fopen($file_url, 'w');

		        // headings
	        	$row = array();
	        	foreach ($questions as $question) {
	        		$row[] = $question['question'];
	        	}
	            fputcsv($file, $row);

	            // feedbacks
				$count_answers = 0;
				foreach ($feedbacks as $feedback) {
					$count_answers ++;
					$answers = maybe_unserialize($feedback['feedback']);
					$row = array();
					foreach ($questions as $q_key => $question) {
						$question_name = strtolower( $question['name'] );
						$row[] = $answers[$question_name];
						if($question['field'] == 'radio'){
							// what key is this answer in the question options
							$key = array_search( $answers[$question_name], $question['options'] );
							// use that key to count the answers 
							if( isset($question['count'][$key]) ){
								$questions[$q_key]['count'][$key] ++;
							}else{
								$questions[$q_key]['count'][$key] = 1;
							}
						}
					}
		            fputcsv($file, $row);
				}

				// totals
				$row = array();
				foreach ($questions as $question) {
					if( $question['field'] == 'radio' ){
						$cell = '';
						foreach ($question['options'] as $key => $value) {
							if( isset( $question['count'][$key] ) ){
								$cell .= $question['count'][$key];
							}else{
								$cell .= '0';
							}
							$cell .= ' = '.$value."\n";
						}
					}else{
						$cell = '';
					}
					$row[] = $cell;
				}
				fputcsv($file, $row);

		        fclose($file);

		        $export_link = '<a href="/'.$filename.'" target="_blank">Download Export File</a>';
			}

			// the export button .... or link
			$html = '<div class="fbrep-export-btn-wrap">';
			if($export_link == ''){
				$form_link = add_query_arg( array(
				    'page' => 'feedback_reporting',
				    't' => $training_id,
				), admin_url('admin.php') );
				$html .= '<form action="'.$form_link.'" method="post"><input id="fbrep-export-go" name="fbrep-export-go" class="button button-primary" type="submit" value="Export Feedback"></form>';
			}else{
				$html .= $export_link;
			}
			$html .= '</div>';

			$html .= '<h1>Feedback for '.get_the_title($training_id).'</h1>';
			$html .= '<p>Received by '.date('d/m/Y H:i').'</p>';

			// table header
			$html .= '<table class="table table-condensed fbrep-table"><thead><tr>';
			foreach ($questions as $question) {
				$html .= '<th valign="top">'.$question['question'].'</th>';
			}
			$html .= '</tr></thead><tbody>';

			// the rows
			$count_answers = 0;
			foreach ($feedbacks as $feedback) {
				$count_answers ++;
				$answers = maybe_unserialize($feedback['feedback']);
				$html .= '<tr>';
				foreach ($questions as $q_key => $question) {
					$question_name = strtolower( $question['name'] );
					$html .= '<td valign="top">'.$answers[$question_name].'</td>';
					if($question['field'] == 'radio'){
						// what key is this answer in the question options
						$key = array_search( $answers[$question_name], $question['options'] );
						// use that key to count the answers 
						if( isset($question['count'][$key]) ){
							$questions[$q_key]['count'][$key] ++;
						}else{
							$questions[$q_key]['count'][$key] = 1;
						}
					}
				}
				$html .= '</tr>';
			}

			// the totals
			$html .= '</tbody><tfoot><tr>';
			foreach ($questions as $question) {
				$html .= '<td valign="top">';
				if( $question['field'] == 'radio' ){
					foreach ($question['options'] as $key => $value) {
						if( isset( $question['count'][$key] ) ){
							$html .= $question['count'][$key];
						}else{
							$html .= '0';
						}
						$html .= ' = '.$value.'<br>';
					}
				}
				$html .= '</td>';
			}
			$html .= '</tr></tfoot></table>';

			$html .= '<p>'.$count_answers.' feedbacks submitted</p>';

			echo $html;
		}
	}
}
