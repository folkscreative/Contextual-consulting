<?php
/**
 * Training (not site) FAQs
 */

// Register Custom Post Type
function cc_faqs_register_post_type() {

	$labels = array(
		'name'                  => 'Training FAQs',
		'singular_name'         => 'FAQ',
		'menu_name'             => 'Training FAQs',
		'name_admin_bar'        => 'FAQs',
		'archives'              => 'FAQ Archives',
		'attributes'            => 'FAQ Attributes',
		'parent_item_colon'     => 'Parent Item:',
		'all_items'             => 'All FAQs',
		'add_new_item'          => 'Add New FAQ',
		'add_new'               => 'Add New',
		'new_item'              => 'New FAQ',
		'edit_item'             => 'Edit FAQ',
		'update_item'           => 'Update FAQ',
		'view_item'             => 'View FAQ',
		'view_items'            => 'View FAQs',
		'search_items'          => 'Search FAQ',
		'not_found'             => 'Not found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Insert into FAQ',
		'uploaded_to_this_item' => 'Uploaded to this FAQ',
		'items_list'            => 'FAQs list',
		'items_list_navigation' => 'FAQs list navigation',
		'filter_items_list'     => 'Filter FAQs list',
	);
	$args = array(
		'label'                 => 'FAQ',
		'description'           => 'FAQs',
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 26,
		'menu_icon'             => 'dashicons-lightbulb',
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
	);
	register_post_type( 'faq', $args );

}
add_action( 'init', 'cc_faqs_register_post_type', 0 );

// the FAQ metabox on workshop and recording edit pages
add_action( 'add_meta_boxes', 'cc_faqs_register_metabox' );
function cc_faqs_register_metabox(){
	// add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
	$post_types = array( 'workshop', 'recording', 'course' );
	add_meta_box( 'faq_metabox', 'FAQs', 'cc_faqs_render_metabox', $post_types, 'side', 'default' );
}
function cc_faqs_render_metabox($post){
	wp_nonce_field( 'cc_faqs_metabox_nonce_action', 'cc_faqs_metabox_nonce' );
	?>
	<p>Which FAQs would you like shown for this training?</p>
	<?php
	$training_faqs = cc_faqs_training_get_ids($post->ID);
	foreach (cc_faqs_get_all() as $faq) {
		echo '<p><input type="checkbox" id="faq-'.$faq->ID.'" name="faq-'.$faq->ID.'" value="yes"';
		if(in_array($faq->ID, $training_faqs) ){
			echo ' checked';
		}
		echo '> <label for="faq-'.$faq->ID.'" title="'.substr($faq->post_content, 0, 100).'...">'.$faq->post_title.'</label></p>';
	}
}

// save the faq metabox
add_action('save_post', 'cc_faqs_save_metabox', 10, 2);
function cc_faqs_save_metabox($post_id, $post){
	if ( !isset( $_POST['cc_faqs_metabox_nonce'] ) || !wp_verify_nonce( $_POST['cc_faqs_metabox_nonce'], 'cc_faqs_metabox_nonce_action' ) ){
		return $post_id;
	}
	$post_type = get_post_type_object( $post->post_type );
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ){
		return $post_id;
	}
	$training_faqs = array();
	foreach (cc_faqs_get_all() as $faq) {
		if(isset($_POST['faq-'.$faq->ID])){
			$training_faqs[] = $faq->ID;
		}
	}
	update_post_meta($post_id, '_training_faqs', $training_faqs);
}

// get all FAQs
function cc_faqs_get_all(){
	$args = array(
		'numberposts' => -1,
		'post_type' => 'faq',
	);
	return get_posts($args);
}

// get the faq IDs for a specific training
// always returns an array
function cc_faqs_training_get_ids($post_id){
	$training_faqs = get_post_meta($post_id, '_training_faqs', true);
	if($training_faqs == ''){
		$training_faqs = array();
	}
	return $training_faqs;
}