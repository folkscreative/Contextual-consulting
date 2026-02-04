<?php
/**
 * Phrases
 */

// formats the terms and conds for display
function cc_phrases_tandcs($training_type, $training_id){
	global $rpm_theme_options;
	if($training_type == 'w'){
		$workshop_type = get_post_meta( $training_id, 'type', true );
		if($workshop_type == 'webinar'){
			$fee = '10% of the training fee';
		}else{
			$fee = '10% of the training fee';
		}
		$tandcs = str_replace('[cancel_fee]', $fee, $rpm_theme_options['terms']);
	}else{
		$tandcs = '';
	}
	return nl2br(esc_attr($tandcs));
}

// student discount T&Cs
function cc_phrases_student_discount_terms(){
	global $rpm_theme_options;
	return nl2br(esc_attr($rpm_theme_options['student']));
}

// gift voucher t&cs
function cc_phrases_gift_voucher_terms(){
	global $rpm_theme_options;
	return wpautop($rpm_theme_options['voucher-terms']);
}