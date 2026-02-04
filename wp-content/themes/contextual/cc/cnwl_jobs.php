<?php
/***
 * CNWL Job Titles
 * Used at myacct details and registration
 * now uses professions
 **/

// the job titles
function cnwl_job_titles(){
	return professions_get_selected('cnwl');
	/*
	return array(
		array(
			'slug' => 'asst_psych',
			'pretty' => 'Assistant psychologist',
			'needs_conf' => 'y',
		),
		array(
			'slug' => 'well_pract',
			'pretty' => 'Mental health well-being practitioner',
			'needs_conf' => 'y',
		),
		array(
			'slug' => 'psych_pract',
			'pretty' => 'Psychological wellbeing practitioner',
			'needs_conf' => 'y',
		),
		array(
			'slug' => 'appl_psych',
			'pretty' => 'Applied psychologist',
			'needs_conf' => 'n',
		),
		array(
			'slug' => 'psychother',
			'pretty' => 'Psychotherapist',
			'needs_conf' => 'n',
		),
		array(
			'slug' => 'ment_pract',
			'pretty' => 'Mental health practitioner',
			'needs_conf' => 'n',
		),
		array(
			'slug' => 'other',
			'pretty' => 'Other',
			'needs_conf' => 'n',
		),
	);
	*/
}

// options for a select clause
/*
function cnwl_job_options($selected=''){
	$html = '';
	foreach (cnwl_job_titles() as $job) {
		if(isset($job['cnwl_conf']) && $job['cnwl_conf'] == 'y'){
			$cnwl_conf = 'y';
		}else{
			$cnwl_conf = 'n';
		}
		$html .= '<option value="'.$job['slug'].'" data-conf="'.$cnwl_conf.'" '.selected($job['slug'], $selected, false).'>'.$job['pretty'].'</option>';
	}
	return $html;
}
*/

// do we need to capture anything at the time of registration?
// returns 'job', 'email' or ''
/*
function cnwl_job_to_capture($user_id){
	$portal_user = get_user_meta($user_id, 'portal_user', true);
	if($portal_user <> 'cnwl') return '';
	$user_job = get_user_meta( $user_id, 'job', true);
	if($user_job == '') return 'job';
	foreach (cnwl_job_titles() as $job){
		if($job['slug'] == $user_job){
			if(isset($job['cnwl_conf']) && $job['cnwl_conf'] == 'y'){
				return 'email';
			}else{
				return '';
			}
		}
	}
	return 'job';
}
*/

// returns the cnwl job approval panel
// this asks for a job title if we don't know it and asks for manager conf for selected jobs
/*
function cnwl_job_conf_panel($user_id){
	$user_job = get_user_meta( $user_id, 'job', true);
	$get_job = true;
	$cnwl_conf = true;
	if($user_job <> ''){
		foreach (cnwl_job_titles() as $job) {
			if($job['slug'] == $user_job){
				$get_job = false;
				if(!isset($job['cnwl_conf']) || $job['cnwl_conf'] == 'n'){
					$cnwl_conf = false;
				}
				break;
			}
		}
	}
	if(!$get_job && !$cnwl_conf) return '';
	$html = '
		<div class="animated-card">
			<div class="reg-cnwl-panel reg-panel wms-background animated-card-inner pale-bg">
				<div class="job-conf">
					<h2>2. CNWL Requirement:</h2>';
	if($get_job){
		$html .= '
					<div class="mb-3">
						<label for="job" class="form-label">Job Title</label>
						<select id="job" class="form-select form-select-lg">
							<option value="">Please select ...</option>
							'.cnwl_job_options().'
						</select>
						<div id="job-msg" class="form-text"></div>
					</div>
					<div id="conf-email-wrap" class="conf-email-wrap hide">
						<div class="mb-3">
							<label for="conf_email" class="form-label">Please confirm your manager\'s approval to register for this training by entering their email address:</label>
							<input type="email" id="conf_email" class="form-control form-control-lg" name="conf_email">
							<div id="conf-email-msg" class="form-text invalid-feedback">Your manager\'s email address is required</div>
						</div>
					</div>';
	}else{
		$html .= '
					<div id="conf-email-wrap" class="conf-email-wrap">
						<div class="mb-3">
							<label for="conf_email" class="form-label">Please confirm your manager\'s approval to register for this training by entering their email address:</label>
							<input type="email" id="conf_email" class="form-control form-control-lg" name="conf_email" required>
							<div id="conf-email-msg" class="form-text invalid-feedback">Your manager\'s email address is required</div>
						</div>
					</div>';
	}
	$html .= '
				</div>
			</div>
		</div>';
	return $html;
}
*/

// saving a job title during the registration process
/*
add_action('wp_ajax_cnwl_job_save', 'cnwl_job_save');
add_action('wp_ajax_nopriv_cnwl_job_save', 'cnwl_job_save');
function cnwl_job_save(){
	$response = array(
		'status' => 'error',
		'append' => '',
	);
	$user_job = '';
	if(isset($_POST['job'])){
		$user_job = stripslashes( sanitize_text_field( $_POST['job'] ) );
	}
	if($user_job <> ''){
		foreach (cnwl_job_titles() as $job){
			if($job['slug'] == $user_job){
				update_user_meta( get_current_user_id(), 'job', $user_job );
				$response['status'] = 'ok';
				if(isset($_POST['append']) && $_POST['append'] == 'y'){
					$response['append'] = cc_registration_attend_next_panels('', array(), array(), '3');
				}
				break;
			}
		}
	}
	echo json_encode($response);
	die;
}
*/
