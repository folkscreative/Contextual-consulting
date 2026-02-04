<?php
/***
 * Professions
 * - expanded from the CNWL jobs stuff
 */

// the professions
function the_professions(){
	return array(
		array(
			'slug' => 'asst_psych',
			'pretty' => 'Assistant psychologist',
			'users' => array('cnwl', 'std', 'nlft'),
			'cnwl_conf' => 'y',
		),
		array(
			'slug' => 'well_pract',
			'pretty' => 'Mental health well-being practitioner',
			'users' => array('cnwl', 'std', 'nlft'),
			'cnwl_conf' => 'y',
		),
		array(
			'slug' => 'well_pract_t',
			'pretty' => 'Mental health well-being practitioner in training',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'psych_pract',
			'pretty' => 'Psychological wellbeing practitioner',
			'users' => array('cnwl', 'std', 'nlft'),
			'cnwl_conf' => 'y',
		),
		array(
			'slug' => 'psych_pract_t',
			'pretty' => 'Psychological wellbeing practitioner in training',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'child_well',
			'pretty' => "Children's wellbeing practitioner",
			'users' => array('nlft'),
		),
		array(
			'slug' => 'child_well_t',
			'pretty' => "Children's wellbeing practitioner in training",
			'users' => array('nlft'),
		),
		array(
			'slug' => 'adult_psych',
			'pretty' => 'Adult psychotherapist',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'adult_psych_t',
			'pretty' => 'Adult psychotherapist in training',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'family_psych',
			'pretty' => 'Family and systemic psychotherapist',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'family_psych_t',
			'pretty' => 'Family and systemic psychotherapist in training',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'child_psych',
			'pretty' => 'Child and adolescent psychotherapist',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'child_psych_t',
			'pretty' => 'Child and adolescent psychotherapist in training',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'cap',
			'pretty' => 'Clinical associate in psychology (CAP)',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'cap_t',
			'pretty' => 'Clinical associate in psychology (CAP) in training',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'arts',
			'pretty' => 'Arts therapist',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'arts_t',
			'pretty' => 'Arts therapist in training',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'appl_psych',
			'pretty' => 'Applied psychologist',
			'users' => array('cnwl', 'nlft'),
		),
		array(
			'slug' => 'appl_psych_t',
			'pretty' => 'Applied psychologist in training',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'psychother',
			'pretty' => 'Psychotherapist',
			'users' => array('cnwl', 'std'),
		),
		array(
			'slug' => 'ment_pract',
			'pretty' => 'Mental health practitioner',
			'users' => array('cnwl', 'std', 'nlft'),
		),
		array(
			'slug' => 'behav_anal',
			'pretty' => 'Behaviour analyst',
			'users' => array('std', 'nlft'),
		),
		array(
			'slug' => 'coach',
			'pretty' => 'Coach',
			'users' => array('std', 'nlft'),
		),
		array(
			'slug' => 'cbt_ther',
			'pretty' => 'CBT therapist',
			'users' => array('std', 'nlft'),
		),
		array(
			'slug' => 'cbt_ther_t',
			'pretty' => 'CBT therapist in training',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'counsellor',
			'pretty' => 'Counsellor',
			'users' => array('std', 'nlft'),
		),
		array(
			'slug' => 'counsellor_t',
			'pretty' => 'Counsellor in training',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'mft',
			'pretty' => 'Marriage and family therapist (MFT)',
			'users' => array('std'),
		),
		array(
			'slug' => 'physician',
			'pretty' => 'Physician',
			'users' => array('std'),
		),
		array(
			'slug' => 'doctor',
			'pretty' => 'Doctor',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'doctor_t',
			'pretty' => 'Doctor in training',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'psychologist',
			'pretty' => 'Psychologist',
			'users' => array('std'),
		),
		array(
			'slug' => 'peer_support',
			'pretty' => 'Peer support worker',
			'users' => array('nlft'),
		),
		array(
			'slug' => 'other',
			'pretty' => 'Other',
			'users' => array('cnwl',),
		),
		array(
			'slug' => 'other_non',
			'pretty' => 'Other or Non-professional',
			'users' => array('std'),
		),
	);
}

// get selected professions
function professions_get_selected($user){
	$selected = array();
	foreach (the_professions() as $profession) {
		if( in_array($user, $profession['users']) ){
			$selected[] = $profession;
		}
	}
	return $selected;
}

// options for a select clause
function professions_options($type, $selected=''){
	$html = '';
	$other = professions_other( $type, $selected );
	foreach ( professions_get_selected( $type ) as $job ) {
		if(isset($job['cnwl_conf']) && $job['cnwl_conf'] == 'y'){
			$cnwl_conf = 'y';
		}else{
			$cnwl_conf = 'n';
		}
		if( $other && $job['slug'] == 'other' ){
			$html .= '<option value="other" data-conf="'.$cnwl_conf.'" selected>Other</option>';
		}else{
			$html .= '<option value="'.$job['slug'].'" data-conf="'.$cnwl_conf.'" '.selected($job['slug'], $selected, false).'>'.$job['pretty'].'</option>';
		}
	}
	return $html;
}

// do we need to capture anything at the time of registration?
// for std people we need a job
// for cnwl people we may also need email confirmation
// returns 'job', 'email' or ''
function professions_reg_capture($user_id){
	$user_job = get_user_meta( $user_id, 'job', true);
	if($user_job == '') return 'job';

	$portal_user = get_user_meta($user_id, 'portal_user', true);
	if($portal_user <> 'cnwl') return '';

	foreach (professions_get_selected('cnwl') as $job){
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

// returns a pretty job
function professions_pretty($slug){
	foreach (the_professions() as $profession) {
		if($profession['slug'] == $slug){
			return $profession['pretty'];
		}
	}
	return $slug; // useful for "other" professions
}

// is this an "other" profession?
function professions_other( $type, $slug ){
	if( $slug == '' ){
		return false;
	}
	if( $slug == 'other' ){ // shouldn't happen!
		return true;
	}
	foreach ( professions_get_selected( $type ) as $job ) {
		if( $job['slug'] == $slug ){
			return false;
		}
	}
	return true;
}


// lookup jobs
add_action('wp_ajax_fetch_professions', 'professions_autocomplete_fetch');
add_action('wp_ajax_nopriv_fetch_professions', 'professions_autocomplete_fetch'); // For non-logged-in users
function professions_autocomplete_fetch() {
	$user_id = get_current_user_id();
	$portal_user = '';
	if( $user_id > 0 ){
		$portal_user = get_user_meta($user_id, 'portal_user', true);
	}
	if( $portal_user == '' ){
		$portal_user = 'std';
	}

    $term = isset($_GET['term']) ? strtolower(sanitize_text_field($_GET['term'])) : '';

    $matches = [];

    foreach (professions_get_selected( $portal_user ) as $entry) {
        if (strpos(strtolower($entry['pretty']), $term) !== false) {
            $matches[] = array(
                'label' => $entry['pretty'],
                'value' => $entry['pretty'],
                'id'    => $entry['slug']
            );
        }
    }

    // Slice to a max of 5 results
    $matches = array_slice($matches, 0, 5);

    wp_send_json($matches);
}
