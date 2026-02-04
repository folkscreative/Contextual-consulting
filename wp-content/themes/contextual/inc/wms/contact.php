<?php
/**
 * Contact page stuff
 */

if(!defined('ABSPATH')) exit;

// adds a meta box to the contact page to select which location it's for ... if there are two locations
add_action('add_meta_boxes', 'wms_contact_page_add_metaboxes');
function wms_contact_page_add_metaboxes(){
	/*
	global $post;
	global $rpm_theme_options;
	// are there two locations?
	if($rpm_theme_options['second-locn']){
		// are we on the contact page?
		if ( 'page-contact-page.php' == get_post_meta( $post->ID, '_wp_page_template', true ) ){
			// add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
			add_meta_box('wms_contact_page_control_metabox', 'Location To Show', 'wms_contact_page_control_callback','page', 'side');
		}
	}
	*/
}

// and here's the metabox ...
function wms_contact_page_control_callback($post){
	global $rpm_theme_options;
	wp_nonce_field( 'contact_page_control_metabox', 'contact_page_control_nonce' );
	$contact_page_control_location = get_post_meta($post->ID, '_contact_page_control_location', true);
	echo '<p>Which location do you want this contact page to be for?</p>';
	echo '<p><label for="wms_contact_page_control_location">What to show:';
	echo '<select class="widefat" id="wms_contact_page_control_location" name="wms_contact_page_control_location">';
	echo '<option value="1">Location 1 '.esc_attr($rpm_theme_options['location-name']).'</option>';
	echo '<option value="2" '.selected('2', $contact_page_control_location).'>Location 2 '.esc_attr($rpm_theme_options['location-name-2']).'</option>';
	echo '</select></label></p>';
}

// and save the data
add_action('save_post', 'wms_contact_page_control_save');
function wms_contact_page_control_save($post_id){
	if ( ! isset( $_POST['contact_page_control_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['contact_page_control_nonce'], 'contact_page_control_metabox' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
	} else {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}
	if(!isset($_POST['wms_contact_page_control_location'])){
		return;
	}
	update_post_meta( $post_id, '_contact_page_control_location', $_POST['wms_contact_page_control_location'] );
}


