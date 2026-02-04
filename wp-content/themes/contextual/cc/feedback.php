<?php
/**
 * Feedback
 */

// create the table that holds the feedback
add_action('init', 'cc_feedback_update_table_def');
function cc_feedback_update_table_def(){
	global $wpdb;
	$cc_feedback_table_ver = 1;
	$installed_table_ver = get_option('cc_feedback_table_ver');
	if($installed_table_ver <> $cc_feedback_table_ver){
		$table_name = $wpdb->prefix.'feedback';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9) NOT NULL,
			training_id mediumint(9) NOT NULL,
			event_id mediumint(9) NOT NULL,
			updated datetime NOT NULL,
			feedback text NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		if( !function_exists( 'dbDelta' ) ){
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		}
		$response = dbDelta($sql);
		update_option('cc_feedback_table_ver', $cc_feedback_table_ver);
	}
}

// return feedback by id
// returns an array or NULL if not found
function cc_feedback_get_by_id($id){
	global $wpdb;
	$feedback_table = $wpdb->prefix.'feedback';
	$sql = "SELECT * FROM $feedback_table WHERE id = $id LIMIT 1";
	return $wpdb->get_row($sql, ARRAY_A);
}

// return feedback by user/training
// returns an array or NULL if not found
function cc_feedback_get_by_user_training($training_id, $event_id, $user_id){
	global $wpdb;
	$feedback_table = $wpdb->prefix.'feedback';
	$sql = "SELECT * FROM $feedback_table WHERE training_id = $training_id AND $event_id = $event_id AND user_id = $user_id LIMIT 1";
	return $wpdb->get_row($sql, ARRAY_A);
}

// get all feedback for training
// returns an empty array if there is none
function cc_feedback_for_training($training_id, $event_id=0){
	global $wpdb;
	$feedback_table = $wpdb->prefix.'feedback';
	$sql = "SELECT * FROM $feedback_table WHERE training_id = $training_id AND $event_id = $event_id";
	return $wpdb->get_results($sql, ARRAY_A);
}

// updates or inserts a row into the feedback table
function cc_feedback_update($data){
	global $wpdb;
	$feedback_table = $wpdb->prefix.'feedback';
	if(isset($data['id']) && $data['id'] > 0){
		// update
		$where = array(
			'id' => $data['id'],
		);
		unset($data['id']);
		return $wpdb->update($feedback_table, $data, $where, array('%d', '%d', '%d', '%s', '%s'));
	}else{
		// insert
		unset($data['id']);
		$wpdb->insert($feedback_table, $data, array('%d', '%d', '%d', '%s', '%s'));
		return $wpdb->insert_id;
	}
}

// creates an obfuscated code for the feedback link
function cc_feedback_link_code($training_id, $event_id, $user_id){
    $daft_number = $training_id * $training_id + $event_id * $event_id + $user_id * $user_id + 51679;
    $string = $training_id.'|'.$event_id.'|'.$user_id.'|'.$daft_number;
    return base64_encode($string);
}

// converts a feedback link back to its elements
function cc_feedback_link_elements($code){
	$string = base64_decode($code);
	list($training_id, $event_id, $user_id, $daft_number) = explode("|", $string);
	if($daft_number == $training_id * $training_id + $event_id * $event_id + $user_id * $user_id + 51679){
		// code good
		return array(
			'training_id' => $training_id,
			'event_id' => $event_id,
			'user_id' => $user_id,
		);
	}
	// code bad!
	return false;
}

// [fb_form field="radio" name="overall" question="How did you find the overall standard of the session?" options="Excellent,Very good, Good,Fair,Poor" required="yes"]
add_shortcode('fb_form', 'cc_feedback_fb_form');
function cc_feedback_fb_form($atts){
	$atts = shortcode_atts( array(
		'field' => '',
		'name' => '',
		'question' => '',
		'options' => '',
		'required' => 'no',
	), $atts, 'fb_form' );

	$options = array_map('trim', explode(',', $atts['options']));
	if($atts['required'] == 'yes'){
		$required = 'required';
	}else{
		$required = '';
	}

	$html = '<div class="fb-form-field-wrap mb-3">';

	if($atts['question'] <> ''){
		$html .= '<h6 class="fb-form-q">'.$atts['question'];
		if($atts['required'] == 'yes'){
			$html .= '<span class="reqd">*</span>';
		}
		$html .= '</h6>';
	}

	switch ($atts['field']) {
		case 'radio':
			$count = 0;
			foreach ($options as $option) {
				$id = $atts['name'].'-'.$count;
				$html .= '
					<div class="form-check">
						<input class="form-check-input" type="radio" name="'.$atts['name'].'" id="'.$id.'" value="'.$option.'" '.$required.'>
						<label class="form-check-label" for="'.$id.'">'.$option.'</label>
					</div>
				';
			}
			break;

		case 'text':
			$html .= '<input class="form-control form-control-lg" type="text" name="'.$atts['name'].'" '.$required.'>';
			break;
		
		case 'textarea':
			$html .= '<textarea class="form-control form-control-lg" name="'.$atts['name'].'" rows="3" '.$required.'></textarea>';
			break;
		
		default:
			// code...
			break;
	}

	$html .= '</div><!-- .fb-form-field-wrap -->';

	return $html;
}

// has this user submitted feedback for this training?
// return bool
function cc_feedback_submitted($training_id, $event_id=0, $user_id=0){
	if($user_id == 0){
		$user_id = get_current_user_id();
	}
	$feedback = cc_feedback_get_by_user_training($training_id, $event_id, $user_id);
	if($feedback === NULL){
		return false;
	}
	return true;
}

// the feedback questions metabox on the training (backend) pages
function cc_feedback_training_metabox($post){
	$feedback_questions = get_post_meta($post->ID, '_feedback_questions', true);
	// any feedback in the system?
	if( ! empty( cc_feedback_for_training($post->ID ) ) ){ ?>
		<div class="fbrep-link-wrap">
			<a class="button" href="<?php echo admin_url('admin.php?page=feedback_reporting&t='.$post->ID); ?>">View Feedback</a>
		</div>
	<?php }
	// questions already set?
	if($feedback_questions <> ''){
		$url = esc_url( add_query_arg(
			array(
				'f' => cc_feedback_link_code($post->ID, 0, 0),
			),
			site_url('/feedback')
		) );
		?>
		<p>The link to the feedback page (for any user): <strong><?php echo $url; ?></strong></p>
	<?php } ?>
	<div class="pre-inline">Feedback questions are specified using a <pre>[fb_form ...]</pre> shortcode. All fields must be given a unique (to this training) name <pre>name="most-useful"</pre>. Please specify the type of field <pre>field="radio"</pre>. The following field types are accepted: <pre>text</pre> (one line of text), <pre>textarea</pre> (multiple lines of text), <pre>radio</pre> (select from a choice of options). Question is used to set the question you want asked <pre>question="Which part of the session did you find most useful?"</pre>. Options is used for the radio fields to specify which options they can choose <pre>options="Excellent,Very good, Good,Fair,Poor"</pre>. And the required setting can be used if you require the field to always be completed <pre>required="yes"</pre>.</div>
	<textarea name="feedback_questions" class="widefat" rows="10" placeholder='eg [fb_form field="radio" name="most-useful" question="Which part of the session did you find most useful?" options="Excellent,Very good, Good,Fair,Poor" required="yes"]'><?php echo $feedback_questions; ?></textarea>
	<?php
}
/*
// save the feedback questions
add_action('save_post', 'cc_feedback_questions_save');
function cc_feedback_questions_save($post_id){
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if(isset($_POST['post_type']) && ( $_POST['post_type'] == 'workshop' || $_POST['post_type'] == 'recording' || $_POST['post_type'] == 'course' ) && current_user_can('edit_post', $post_id)){
		if(isset($_POST['feedback_questions'])){
			update_post_meta($post_id, '_feedback_questions', stripslashes( sanitize_textarea_field( $_POST['feedback_questions'] ) ) );
		}
	}
}
*/

/**
 * Enhanced save function that cleans problematic characters from Word and other rich text sources
 */

add_action('save_post', 'cc_feedback_questions_save');
function cc_feedback_questions_save($post_id){
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(isset($_POST['post_type']) && ( $_POST['post_type'] == 'workshop' || $_POST['post_type'] == 'recording' || $_POST['post_type'] == 'course' ) && current_user_can('edit_post', $post_id)){
        if(isset($_POST['feedback_questions'])){
            $questions = $_POST['feedback_questions'];
            
            // Clean the text before saving
            $questions = clean_word_paste($questions);
            
            update_post_meta($post_id, '_feedback_questions', stripslashes( sanitize_textarea_field( $questions ) ) );
        }
    }
}

/**
 * Comprehensive function to clean text pasted from Word, Google Docs, and other rich text sources
 * This prevents encoding issues in CSV exports and other data processing
 */
function clean_word_paste($text) {
    // 1. Convert Windows-1252 characters to UTF-8 if needed
    if (!mb_check_encoding($text, 'UTF-8')) {
        $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
    }
    
    // 2. Replace smart quotes and apostrophes
    // IMPORTANT: Arrays must be same size or replacements will be empty strings!
    $text = str_replace(
        array(
            "\xe2\x80\x9c", // " (left double quotation mark)
            "\xe2\x80\x9d", // " (right double quotation mark)
            "\xe2\x80\x98", // ' (left single quotation mark)
            "\xe2\x80\x99", // ' (right single quotation mark/apostrophe)
            "\xe2\x80\x9a", // ‚ (single low-9 quotation mark)
            "\xe2\x80\x9e", // „ (double low-9 quotation mark)
            "\xe2\x80\x9f", // ‟ (double high-reversed-9 quotation mark)
            "\xe2\x80\xb9", // ‹ (single left-pointing angle quotation)
            "\xe2\x80\xba", // › (single right-pointing angle quotation)
            "\xc2\xab",     // « (left-pointing double angle quotation)
            "\xc2\xbb",     // » (right-pointing double angle quotation)
            chr(145),       // Windows smart single quote left
            chr(146),       // Windows smart single quote right
            chr(147),       // Windows smart double quote left
            chr(148),       // Windows smart double quote right
        ),
        array(
            '"',    // left double quote → straight
            '"',    // right double quote → straight
            "'",    // left single quote → straight
            "'",    // right single quote → straight
            "'",    // low single quote → straight
            '"',    // low double quote → straight
            '"',    // high reversed double → straight
            '<',    // single left angle → less than
            '>',    // single right angle → greater than
            '<<',   // double left angle → double less than
            '>>',   // double right angle → double greater than
            "'",    // Windows smart single left → straight
            "'",    // Windows smart single right → straight
            '"',    // Windows smart double left → straight
            '"',    // Windows smart double right → straight
        ),
        $text
    );
    
    // 3. Replace dashes and hyphens
    $text = str_replace(
        array(
            "\xe2\x80\x90", // ‐ (hyphen)
            "\xe2\x80\x91", // ‑ (non-breaking hyphen)
            "\xe2\x80\x92", // ‒ (figure dash)
            "\xe2\x80\x93", // – (en dash)
            "\xe2\x80\x94", // — (em dash)
            "\xe2\x80\x95", // ― (horizontal bar)
            chr(150), chr(151), // Windows en and em dashes
        ),
        array(
            '-', '-', '-',
            '-',  // or '--' if you prefer
            '--', // or '---' if you prefer
            '-',
            '-', '--'
        ),
        $text
    );
    
    // 4. Replace ellipsis
    $text = str_replace(
        array(
            "\xe2\x80\xa6", // … (horizontal ellipsis)
            chr(133),       // Windows ellipsis
        ),
        '...',
        $text
    );
    
    // 5. Replace spaces
    $text = str_replace(
        array(
            "\xc2\xa0",     // Non-breaking space
            "\xe2\x80\x80", // En quad
            "\xe2\x80\x81", // Em quad
            "\xe2\x80\x82", // En space
            "\xe2\x80\x83", // Em space
            "\xe2\x80\x84", // Three-per-em space
            "\xe2\x80\x85", // Four-per-em space
            "\xe2\x80\x86", // Six-per-em space
            "\xe2\x80\x87", // Figure space
            "\xe2\x80\x88", // Punctuation space
            "\xe2\x80\x89", // Thin space
            "\xe2\x80\x8a", // Hair space
            "\xe2\x80\x8b", // Zero width space
            "\xe2\x80\xaf", // Narrow no-break space
            "\xe3\x80\x80", // Ideographic space
            chr(160),       // Windows non-breaking space
        ),
        ' ', // Replace all with regular space
        $text
    );
    
    // 6. Replace other problematic characters
    $text = str_replace(
        array(
            "\xe2\x80\xa2", // • (bullet)
            "\xe2\x80\xa3", // ‣ (triangular bullet)
            "\xe2\x80\xa4", // ⁃ (hyphen bullet)
            "\xe2\x84\xa2", // ™ (trademark)
            "\xc2\xa9",     // © (copyright)
            "\xc2\xae",     // ® (registered)
            "\xe2\x80\xa0", // † (dagger)
            "\xe2\x80\xa1", // ‡ (double dagger)
            "\xc2\xb0",     // ° (degree)
            "\xe2\x88\x9e", // ∞ (infinity)
            "\xc2\xb1",     // ± (plus-minus)
            "\xc2\xbc",     // ¼
            "\xc2\xbd",     // ½
            "\xc2\xbe",     // ¾
            chr(149),       // Windows bullet
        ),
        array(
            '*',    // or '-' for bullet points
            '*',    
            '-',    
            '(TM)',
            '(C)',
            '(R)',
            '+',    // or '[+]' if you prefer
            '++',   // or '[++]' if you prefer
            ' degrees',
            '[infinity]',
            '+/-',
            '1/4',
            '1/2',
            '3/4',
            '*'
        ),
        $text
    );
    
    // 7. Clean up line breaks (Word often uses different line break characters)
    $text = str_replace(
        array(
            "\r\n", // Windows line breaks
            "\r",   // Mac line breaks (old)
            "\xe2\x80\xa8", // Line separator
            "\xe2\x80\xa9", // Paragraph separator
            "\x0b", // Vertical tab
            "\x0c", // Form feed
        ),
        "\n", // Unix line breaks (standard)
        $text
    );
    
    // 8. Remove zero-width characters that cause issues
    $text = str_replace(
        array(
            "\xe2\x80\x8b", // Zero width space
            "\xe2\x80\x8c", // Zero width non-joiner
            "\xe2\x80\x8d", // Zero width joiner
            "\xef\xbb\xbf", // BOM (Byte Order Mark)
            "\xef\xbf\xbc", // Object replacement character
        ),
        '',
        $text
    );
    
    // 9. Remove any remaining control characters (except newline and tab)
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
    
    // 10. Normalize multiple spaces to single space (but preserve newlines)
    $text = preg_replace('/ {2,}/', ' ', $text);
    
    // 11. Trim whitespace from beginning and end
    $text = trim($text);
    
    return $text;
}


// temporary hack ....
add_shortcode('feedback_summary', 'cc_feedback_summary');
function cc_feedback_summary(){
	$html = '';
	$training_id = 5501;
	// $training_id = 4864;
	$feedback_questions = get_post_meta($training_id, '_feedback_questions', true);
	$feedbacks = cc_feedback_for_training($training_id);
	$html .= '<h2>Feedback for '.get_the_title($training_id).'</h2>';
	$html .= '<p>Received by '.date('d/m/Y H:i').'</p>';

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

	/*
	$html .= '<br>Questions:';
	$html .= print_r( $questions, true );
	$html .= '<br>Answers:';
	foreach ($feedbacks as $feedback) {
		$answers = maybe_unserialize($feedback['feedback']);
		$html .= '<br>'.print_r( $answers, true );
	}
	*/

	// table header
	$html .= '<table><thead><tr>';
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

	return $html;
}

// capture new feedback sent by ajax from the my account page modal
add_action( 'wp_ajax_feedback_submit', 'feedback_submit' );
function feedback_submit(){
	$response = array(
		'status' => 'error',
		'msg' => '',
	);
	$training_id = isset( $_POST['trainingID'] ) ? absint( $_POST['trainingID'] ) : 0;
	$event_id = isset( $_POST['eventID'] ) ? absint( $_POST['eventID'] ) : 0;
	if( $training_id > 0 ){
		$user_id = get_current_user_id();
		// if already saved .... skip
		if( cc_feedback_get_by_user_training( $training_id, $event_id, $user_id ) === NULL){
			$raw_answers = array();
			parse_str( $_POST['formData'], $raw_answers );
			// Sanitize both keys and values
			$answers = array_combine(
			    array_map('sanitize_key', array_keys($raw_answers)),  // Sanitize keys
			    array_map('sanitize_text_field', array_values($raw_answers))  // Sanitize values
			);
			// save
		    $data = array(
		        'user_id' => $user_id,
		        'training_id' => $training_id,
		        'event_id' => $event_id,
		        'updated' => date('Y-m-d H:i:s'),
		        'feedback' => maybe_serialize($answers),
		    );
		    $feedback_id = cc_feedback_update($data);

			$response['status'] = 'ok';
			$response['msg'] = 'Thanks! Your feedback has been recorded.';

		}else{
			$response['status'] = 'ok';
			$response['msg'] = 'Saved previously';
		}
	}
   	echo json_encode($response);
	die();
}