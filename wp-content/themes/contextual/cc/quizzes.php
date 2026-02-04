<?php
/***
 * Recording Quizzes for CE Credits
 */

// Register Custom Post Type
function cc_quizzes_cpt() {

	$labels = array(
		'name'                  => 'Quizzes',
		'singular_name'         => 'Quiz',
		'menu_name'             => 'Quizzes',
		'name_admin_bar'        => 'Quiz',
		'archives'              => 'Quiz Archives',
		'attributes'            => 'Quiz Attributes',
		'parent_item_colon'     => 'Parent Quiz:',
		'all_items'             => 'All Quizzes',
		'add_new_item'          => 'Add New Quiz',
		'add_new'               => 'Add New',
		'new_item'              => 'New Quiz',
		'edit_item'             => 'Edit Quiz',
		'update_item'           => 'Update Quiz',
		'view_item'             => 'View Quiz',
		'view_items'            => 'View Quizzes',
		'search_items'          => 'Search Quiz',
		'not_found'             => 'Not found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Insert into Quiz',
		'uploaded_to_this_item' => 'Uploaded to this Quiz',
		'items_list'            => 'Quizzes list',
		'items_list_navigation' => 'Quizzes list navigation',
		'filter_items_list'     => 'Filter Quizzes list',
	);
	$args = array(
		'label'                 => 'Quiz',
		'description'           => 'CE Credit Quiz',
		'labels'                => $labels,
		'supports'              => array( 'title' ),
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 35,
		'menu_icon'             => 'dashicons-star-empty',
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,	// was true
		'capability_type'       => 'page',
		'register_meta_box_cb'  => 'cc_quizzes_add_metaboxes',
	);
	register_post_type( 'quiz', $args );

}
add_action( 'init', 'cc_quizzes_cpt', 0 );

// adds the metaboxes
function cc_quizzes_add_metaboxes() {
	add_meta_box(
		'questions',			// id
		'Questions',				// title
		'cc_quizzes_questions_metabox',	// callback
		'quiz',						// screen
		'normal',						// context
		'default'						// priority
	);
	add_meta_box(
		'recording_link',			// id
		'Recording',				// title
		'cc_quizzes_recording_metabox',	// callback
		'quiz',						// screen
		'side',						// context
		'default'						// priority
	);
}

// the questions metabox
function cc_quizzes_questions_metabox(){
	global $post;
	$questions = get_post_meta( $post->ID, '_questions', true );
	if($questions == ''){
		$questions = array();
	}
	?>
	<h3>Questions</h3>
	<div id="questions-wrap">
	<?php
	$q_count = 1;
	$high_qnum = 1;
	foreach ($questions as $qnum => $question) {
		echo cc_quizzes_backend_html($q_count, $qnum, $question);
		if($qnum > $high_qnum){
			$high_qnum = $qnum;
		}
		$q_count ++;
	}
	if($q_count == 1){
		echo cc_quizzes_backend_html(1, '1', cc_quizzes_empty_question());
		$q_count ++;
	}
	?>
	</div>
	<a href="javascript:void(0);" id="quizzes-add-question" class="button button-primary" data-highkey="<?php echo $q_count; ?>" data-highqnum="<?php echo $high_qnum; ?>">Add A Question</a>
	<div id="empty-question" class="d-none">
		<?php echo cc_quizzes_backend_html('##COUNT##', '99999', cc_quizzes_empty_question()); ?>
	</div>
	<?php
}

// the recording link metabox
function cc_quizzes_recording_metabox(){
	global $post;
	$recording_id = get_post_meta( $post->ID, '_quiz_recording_id', true );
	?>
	<p>Which recording is this quiz for?</p>
	<select name="quiz-rec-id">
		<option value="">Please select ...</option>
		<?php echo ccrecw_free_rec_options($recording_id); ?>
	</select>
	<?php
}

// Add the custom columns to the quiz post type:
add_filter( 'manage_quiz_posts_columns', 'cc_quizzes_set_custom_columns' );
function cc_quizzes_set_custom_columns($columns) {
	// save date column
    $date = $columns['date'];
    // unset the 'date' column
    unset( $columns['date'] ); 
    // add the recording column
    $columns['recording'] = 'Recording';
    // set the 'date' column again, after the custom column
    $columns['date'] = $date;
    return $columns;
}

// Add the data to the custom columns for the quiz post type:
add_action( 'manage_quiz_posts_custom_column' , 'cc_quizzes_custom_column', 10, 2 );
function cc_quizzes_custom_column( $column, $post_id ) {
    switch ( $column ) {
        case 'recording' :
            $recording_id = (int) get_post_meta( $post_id, '_quiz_recording_id', true );
            if ( $recording_id == 0 ){
                echo 'Not set yet';
            }else{
				echo get_the_title($recording_id);
            }
            break;
    }
}

// empty question
function cc_quizzes_empty_question(){
	return array(
		'question' => '',
		'answera' => '',
		'answerb' => '',
		'answerc' => '',
		'answerd' => '',
		'answere' => '',
		'correct' => '',
	);
}

// the backend question html
function cc_quizzes_backend_html($q_count, $qnum, $question){
	return '
		<label for="">Question Number</label><br>
		<input type="number" class="small-text" name="qnum['.$q_count.']" min="1" step="1" value="'.$qnum.'">
		<input type="hidden" id="qqdelete-'.$q_count.'" name="delete['.$q_count.']" value="no">
		<a href="javascript:void(0);" class="qqdel" data-qnum="'.$q_count.'"><i class="fa-solid fa-trash-can text-danger"></i></a>
		<span id="qqdmsg-'.$q_count.'" class="text-danger"></span>
		<br>
		<label for="">Question</label><br>
		<textarea class="widefat" name="question['.$q_count.']" cols="30" rows="2">'.$question['question'].'</textarea>
		<div class="quiz-question-col">
			<label for="">Answer A</label><br>
			<input type="text" class="widefat" name="answera['.$q_count.']" value="'.$question['answera'].'">
		</div>
		<div class="quiz-question-col">
			<label for="">Answer B</label><br>
			<input type="text" class="widefat" name="answerb['.$q_count.']" value="'.$question['answerb'].'">
		</div>
		<div class="quiz-question-col">
			<label for="">Answer C</label><br>
			<input type="text" class="widefat" name="answerc['.$q_count.']" value="'.$question['answerc'].'">
		</div>
		<div class="quiz-question-col">
			<label for="">Answer D</label><br>
			<input type="text" class="widefat" name="answerd['.$q_count.']" value="'.$question['answerd'].'">
		</div>
		<div class="quiz-question-col">
			<label for="">Answer E</label><br>
			<input type="text" class="widefat" name="answere['.$q_count.']" value="'.$question['answere'].'">
		</div>
		<div class="quiz-question-col">
			<label for="">Correct answer</label><br>
			<select name="correct['.$q_count.']">
				<option value="a" '.selected('a', $question['correct'], false).'>A</option>
				<option value="b" '.selected('b', $question['correct'], false).'>B</option>
				<option value="c" '.selected('c', $question['correct'], false).'>C</option>
				<option value="d" '.selected('d', $question['correct'], false).'>D</option>
				<option value="e" '.selected('e', $question['correct'], false).'>E</option>
			</select>
		</div>
		<div class="clear">&nbsp;</div>
		<hr>
	';
}

// saving metadata
add_action( 'save_post', 'cc_quizzes_save_questions' );
function cc_quizzes_save_questions( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if (!isset($_POST['post_type'])) return;
	if ($_POST['post_type'] <> 'quiz') return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	// questions
	$new_qs = array();
	foreach ($_POST['qnum'] as $key => $qnum) {
		if($key <> '##COUNT##'){
			if( $_POST['delete'][$key] <> 'yes' ){
				$question = array();
				$question['question'] = stripslashes( sanitize_textarea_field( $_POST['question'][$key] ) );
				$question['answera'] = stripslashes( sanitize_textarea_field( $_POST['answera'][$key] ) );
				$question['answerb'] = stripslashes( sanitize_textarea_field( $_POST['answerb'][$key] ) );
				$question['answerc'] = stripslashes( sanitize_textarea_field( $_POST['answerc'][$key] ) );
				$question['answerd'] = stripslashes( sanitize_textarea_field( $_POST['answerd'][$key] ) );
				$question['answere'] = stripslashes( sanitize_textarea_field( $_POST['answere'][$key] ) );
				$question['correct'] = stripslashes( sanitize_textarea_field( $_POST['correct'][$key] ) );
				$new_qs[$qnum] = $question;
			}
		}
	}
	// put them in order
	ksort($new_qs, SORT_NUMERIC);
	// and renumber to ensure there are no gaps in the sequence
	$renumbered = array_combine( range( 1, count($new_qs) ), array_values($new_qs) );
	update_post_meta($post_id, '_questions', $renumbered);

	// the recording the quiz relates to
	$recording_id = (int) $_POST['quiz-rec-id'];
	update_post_meta($post_id, '_quiz_recording_id', $recording_id);
}

// get the quiz id for a recording
// or NULL if not found
function cc_quizzes_quiz_id($recording_id){
	global $wpdb;
	$sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_quiz_recording_id' AND meta_value = '$recording_id' LIMIT 1";
	return $wpdb->get_var($sql);
}

// get the stuff needed for the quiz page
function cc_quizzes_question($user_id, $training_id, $qnum, $user_quiz_id){
	$response = array(
		'html' => '',
		'prev' => false,
		'next' => false,
	);
	$quiz_id = cc_quizzes_quiz_id($training_id);
	if($quiz_id !== NULL){
		$questions = get_post_meta( $quiz_id, '_questions', true );
		if($questions == ''){
			$response['html'] = '<p>Quiz not set for this training yet. Please try again later.';
		}else{
			if( isset( $questions[$qnum] ) ){
				// progress bar
				$tot_qs = count($questions);
				$progress = round(($qnum - 1) / $tot_qs * 100);
				$response['html'] .= '<div class="progress"><div class="progress-bar" role="progressbar" style="width: '.$progress.'%;" aria-valuenow="'.$progress.'" aria-valuemin="0" aria-valuemax="'.$tot_qs.'">'.$progress.'%</div></div>';
				$response['html'] .= '<h6 class="mb-0">Question '.$qnum.' of '.$tot_qs.':</h6>';
				$response['html'] .= '<h5 class="question">'.$questions[$qnum]['question'].'</h5>';
				$response['html'] .= '<div class="answers-wrap">';
				$answers = array('a', 'b', 'c', 'd', 'e');
				// we no longer want these in random order ...
				// shuffle($answers);
				$prev_answer = cc_quizzes_prev_answer($user_id, $user_quiz_id, $qnum);
				foreach ($answers as $answer) {
					if($questions[$qnum]['answer'.$answer] <> ''){
						if($prev_answer == $answer){
							$checked = 'checked';
						}else{
							$checked = '';
						}
						$response['html'] .= '
							<div class="form-check">
								<input class="form-check-input" type="radio" name="answer" id="answer'.$answer.'" value="'.$answer.'" required '.$checked.'>
								<label class="form-check-label" for="answer'.$answer.'">'.$questions[$qnum]['answer'.$answer].'</label>
							</div>';
					}
				}
				$response['html'] .= '</div>';
				$next = $qnum + 1;
				if( isset( $questions[$next] ) ){
					$response['next'] = $next;
				}else{
					// the end
					$response['next'] = 'end';
				}
				if( $qnum > 1 ){
					$response['prev'] = $qnum - 1;
				}
			}
		}
	}
	return $response;
}

// get a previously submitted answer
function cc_quizzes_prev_answer($user_id, $user_quiz_id, $qnum){
	$answers = get_user_meta($user_id, '_answers_'.$user_quiz_id, true);
	if(! is_array( $answers ) ){
		return '';
	}
	if( isset( $answers[$qnum] ) ){
		return $answers[$qnum];
	}
	return '';
}

// save a quiz answer
function cc_quizzes_save_answer($user_quiz_id, $user_id, $training_id, $qnum, $answer){
	$answers = get_user_meta($user_id, '_answers_'.$user_quiz_id, true);
	if(! is_array( $answers ) ){
		$answers = array();
	}
	$answers[$qnum] = $answer;
	update_user_meta($user_id, '_answers_'.$user_quiz_id, $answers);
}

// get the temporary answers and store them away (at the end of the quiz)
function cc_quizzes_store_answers($user_quiz_id, $user_id, $training_id){
	$stored_answers = array(
		'when' => date('Y-m-d H:i:s'),
		'answers' => array(),
		'correct' => 0,
		'incorrect' => 0,
		'score' => 0,
		'user_quiz_id' => $user_quiz_id,
	);
	$answers = get_user_meta($user_id, '_answers_'.$user_quiz_id, true);
	if(! is_array( $answers ) ){
		return false;
	}
	$quiz_id = cc_quizzes_quiz_id($training_id);
	if($quiz_id == NULL){
		return false;
	}
	$questions = get_post_meta( $quiz_id, '_questions', true );
	if(! is_array( $questions ) ){
		return false;
	}
	foreach ($questions as $qnum => $question) {
		if(isset($answers[$qnum])){
			$stored_answers['answers'][$qnum] = $answers[$qnum];
			if($answers[$qnum] == $question['correct']){
				$stored_answers['correct'] ++;
			}else{
				$stored_answers['incorrect'] ++;
			}
		}else{
			$stored_answers[$qnum] = '-';
			$stored_answers['incorrect'] ++;
		}
	}
	$stored_answers['score'] = round( $stored_answers['correct'] / count($questions) * 100 );
	add_user_meta($user_id, '_quiz_'.$training_id, $stored_answers);

	// delete the temporary answers so a reload does not save them again
	delete_user_meta( $user_id, '_answers_'.$user_quiz_id );

	return true;
}

// show the results to the user
function cc_quizzes_show_results($user_id, $training_id){
	$results = get_user_meta($user_id, '_quiz_'.$training_id, false);
	$latest = '';
	$latest_date = '2020-01-01 00:00:00';
	foreach ($results as $result) {
		if($result['when'] > $latest_date){
			$latest = $result;
			$latest_date = $result['when'];
		}
	}
	if($latest['score'] >= 70){
		$html = '<h4>Congratulations</h4>';
		$pass_fail = 'pass';
	}else{
		$html = '<h4>Unfortunately, you did not achieve a pass mark</h4>';
		$pass_fail = 'fail';
	}
	$html .= '<p>You answered '.$latest['correct'].' question';
	if($latest['correct'] <> 1){
		$html .= 's';
	}
	$html .= ' correctly and '.$latest['incorrect'].' question';
	if($latest['incorrect'] <> 1){
		$html .= 's';
	}
	$html .= ' incorrectly. Your score is '.$latest['score'].'% ('.$pass_fail.'). The pass mark is 70%.</p>';
	if(count($results) > 1){
		$html .= '<p>Your previous result';
		if(count($results) > 2){
			$html .= 's were ';
		}else{
			$html .= ' was ';
		}
		$and = '';
		foreach ($results as $result) {
			if($result['when'] <> $latest_date){
				$html .= $and.$result['score'].'% on ';
				$date = DateTime::createFromFormat('Y-m-d H:i:s', $result['when']);
				$html .= $date->format('d/m/Y');
				$and = ' and ';
			}
		}
		$html .= '.</p>';
	}
	if($pass_fail == 'fail'){
		switch ( count($results) ) {
			case 1:
				$html .= '<p>You can attempt the quiz a total of 3 times. You have two more attempts left.</p>';
				break;
			case 2:
				$html .= '<p>You can attempt the quiz a total of 3 times. You have one more attempt left. Please note, if you do not pass on this attempt, we will be unable to award you with your CE certificate.';
                $recording_access = ccrecw_user_can_view($training_id, $user_id);
                if($recording_access['access']){
                	$html .= ' You may wish to re-watch the recording before taking the test again.</p>';
                }
				break;
			case 3:
				$html .= '<p>Unfortunately you have no further attempts left. This means we are unable to award you with a CE certificate for this training event.</p>';
				break;
		}
	}else{
		if(cc_feedback_submitted($training_id, 0, $user_id)){
			$html .= '<p>Congratulations! You have successfully passed the test. Your CE certificate is now available to download from your account.</p>';
		}else{
			$html .= '<p>Congratulations! You have successfully passed the test. Next, please go to your account and submit feedback. Your CE certificate will then be available for download.</p>';
		}
	}
	return $html;
}

// previous results
function cc_quizzes_previous_results($user_id, $training_id){
	$response = array(
		'text' => '',
		'attempts' => 0,
		'pass_fail' => 'fail',
	);
	$results = get_user_meta($user_id, '_quiz_'.$training_id, false);
	$and = '';
	foreach ($results as $result) {
		$response['attempts'] ++;
		if($result['score'] >= 70){
			$response['pass_fail'] = 'pass';
		}
		$response['text'] .= $and.$result['score'].'% on ';
		$date = DateTime::createFromFormat('Y-m-d H:i:s', $result['when']);
		$response['text'] .= $date->format('d/m/Y');
		if(count($results) > 2 && $response['attempts'] == 1){
			$and = ', ';
		}else{
			$and = ' and ';
		}
	}
	return $response;
}

// check to see if a user_quiz_id has already been saved
// eg if the user uses the browser back btn
// returns bool
function cc_quizzes_already_saved($user_quiz_id, $user_id, $training_id){
	$answers = get_user_meta( $user_id, '_quiz_'.$training_id );
	foreach ($answers as $answer) {
		if( isset($answer['user_quiz_id']) && $answer['user_quiz_id'] == $user_quiz_id ){
			return true;
		}
	}
	return false;
}

// submission of a quiz question from the my account modal
add_action( 'wp_ajax_quiz_question_submit', 'quiz_question_submit' );
function quiz_question_submit(){
	// ccpa_write_log('function quiz_question_submit');
	global $rpm_theme_options;
	$response = array(
		'status' => 'error',
		'body' => '',
	);

	$raw_formdata = array();
	parse_str( $_POST['formData'], $raw_formdata );
	// Sanitize both keys and values
	$formdata = array_combine(
	    array_map('sanitize_key', array_keys($raw_formdata)),  // Sanitize keys ... changes them to LOWERCASE!!!!!
	    array_map('sanitize_text_field', array_values($raw_formdata))  // Sanitize values
	);

	$user_id = get_current_user_id();
	$next_q = isset( $_POST['nextQ'] ) ? sanitize_text_field( $_POST['nextQ'] ) : 0;

	// ccpa_write_log($formdata);

	if( isset( $formdata['userquizid'] ) && $formdata['userquizid'] <> ''
		&& isset( $formdata['trainingid'] ) && $formdata['trainingid'] > 0
		&& isset( $formdata['qnum'] ) ){
		// good data

		if( $formdata['qnum'] == 'end' ){
			// store the answers
            $result = cc_quizzes_store_answers( $formdata['userquizid'], $user_id, $formdata['trainingid'] );
        }elseif( $formdata['qnum'] == 'submit' ){
        	// show the results in a mo ...
		}elseif( $formdata['qnum'] > 0 ){
		    if( isset( $formdata['answer'] ) && in_array( $formdata['answer'], array( 'a', 'b', 'c', 'd', 'e' ) ) ){
		        cc_quizzes_save_answer( $formdata['userquizid'], $user_id, $formdata['trainingid'], $formdata['qnum'], $formdata['answer']);
		    }
		}

		if( $next_q == 'end' ){
		    $question = array(
		        'html' => apply_filters( 'the_content', $rpm_theme_options['quiz-outro-text'] ),
		        'prev' => $formdata['qnum'],
		        'next' => 'submit',
		    );
			$next_btn_txt = 'Submit answers';
		}elseif( $next_q == 'submit' ){
            $question = array(
                'html' => cc_quizzes_show_results( $user_id, $formdata['trainingid'] ),
                'prev' => false,
                'next' => false,
            );
		}else{
			// get the next question
			$question = cc_quizzes_question( $user_id, $formdata['trainingid'], $next_q, $formdata['userquizid'] );
			$next_btn_txt = 'Next';
		}

		// ccpa_write_log( $question );

	    $response['body'] = '<form method="POST" id="cc-quiz-form" class="needs-validation"><input type="hidden" name="trainingid" value="'.$formdata['trainingid'].'"><input type="hidden" name="eventID" value="0"><input type="hidden" name="userquizid" value="'.$formdata['userquizid'].'"><input type="hidden" name="qnum" value="'.$next_q.'"><div class="qa-wrap dark-bg p-3 mb-5">';

	    $response['body'] .= $question['html'];

	    $response['body'] .= '</div></form>';

	    $response['body'] .= '<div class="row"><div class="col">';
	    if( $question['prev'] ){
	    	$response['body'] .= '<button type="button" id="cc-quiz-form-back" class="btn btn-secondary btn-sm" data-prevQ="'.$question['prev'].'">Go back</button>';
	    }
	    $response['body'] .= '</div><div class="col text-end">';
	    if( $question['next'] ){
		    $response['body'] .= '<button type="submit" form="cc-quiz-form" id="cc-quiz-form-next" class="btn btn-primary" data-nextQ="'.$question['next'].'">'.$next_btn_txt.'</button>';
	    }
	    $response['body'] .= '</div></div>';

	    $response['body'] .= '<div id="quiz-msg" class="quiz-msg"></div>';

	    $response['status'] = 'ok';
	}
	// ccpa_write_log( $response );
    echo json_encode( $response );
    die();
}

// going back to the previous quiz question
add_action( 'wp_ajax_quiz_question_back', 'quiz_question_back' );
function quiz_question_back(){
	// ccpa_write_log('function quiz_question_back');
	$response = array(
		'status' => 'error',
		'body' => '',
	);

	$raw_formdata = array();
	parse_str( $_POST['formData'], $raw_formdata );
	// Sanitize both keys and values
	$formdata = array_combine(
	    array_map('sanitize_key', array_keys($raw_formdata)),  // Sanitize keys ... changes them to LOWERCASE!!!!!
	    array_map('sanitize_text_field', array_values($raw_formdata))  // Sanitize values
	);

	$user_id = get_current_user_id();
	$prev_q = isset( $_POST['prevQ'] ) ? absint( $_POST['prevQ'] ) : 0;

	if( isset( $formdata['userquizid'] ) && $formdata['userquizid'] <> ''
		&& isset( $formdata['trainingid'] ) && $formdata['trainingid'] > 0
		&& isset( $formdata['qnum'] ) ){
		// good data

		// no data is stored when you go back

	    $response['body'] = '<form method="POST" id="cc-quiz-form" class="needs-validation"><input type="hidden" name="trainingid" value="'.$formdata['trainingid'].'"><input type="hidden" name="eventID" value="0"><input type="hidden" name="userquizid" value="'.$formdata['userquizid'].'"><input type="hidden" name="qnum" value="'.$prev_q.'"><div class="qa-wrap dark-bg p-3 mb-5">';

		// get the previous question
		$question = cc_quizzes_question( $user_id, $formdata['trainingid'], $prev_q, $formdata['userquizid'] );
	    $response['body'] .= $question['html'];

	    $response['body'] .= '</div></form>';

	    $response['body'] .= '<div class="row"><div class="col">';
	    if( $question['prev'] ){
	    	$response['body'] .= '<button type="button" id="cc-quiz-form-back" class="btn btn-secondary btn-sm" data-prevQ="'.$question['prev'].'">Go back</button>';
	    }
	    $response['body'] .= '</div><div class="col text-end">';
	    if( $question['next'] ){
		    $response['body'] .= '<button type="submit" form="cc-quiz-form" id="cc-quiz-form-next" class="btn btn-primary" data-nextQ="'.$question['next'].'">Next</button>';
	    }
	    $response['body'] .= '</div></div>';

	    $response['body'] .= '<div id="quiz-msg" class="quiz-msg"></div>';

	    $response['status'] = 'ok';
	}
    echo json_encode($response);
    die();
}
