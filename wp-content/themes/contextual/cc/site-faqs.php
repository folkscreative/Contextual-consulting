<?php
/**
 * Site (not training) FAQs
 */

// Register Custom Post Type
function cc_sitefaqs_register_post_type() {

	$labels = array(
		'name'                  => 'Site FAQs',
		'singular_name'         => 'FAQ',
		'menu_name'             => 'Site FAQs',
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
		'label'                 => 'Site FAQ',
		'description'           => 'Site FAQs',
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'page-attributes' ),
		'taxonomies'			=> array( 'sitefaqcat' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 26,
		'menu_icon'             => 'dashicons-lightbulb',
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
	);
	register_post_type( 'sitefaq', $args );

}
add_action( 'init', 'cc_sitefaqs_register_post_type', 0 );

// Register Custom Taxonomy
function cc_sitefaqs_register_taxonomy() {

	$labels = array(
		'name'                       => 'FAQ Categories',
		'singular_name'              => 'FAQ Category',
		'menu_name'                  => 'FAQ Category',
		'all_items'                  => 'All Items',
		'parent_item'                => 'Parent Item',
		'parent_item_colon'          => 'Parent Item:',
		'new_item_name'              => 'New Item Name',
		'add_new_item'               => 'Add New Item',
		'edit_item'                  => 'Edit Item',
		'update_item'                => 'Update Item',
		'view_item'                  => 'View Item',
		'separate_items_with_commas' => 'Separate items with commas',
		'add_or_remove_items'        => 'Add or remove items',
		'choose_from_most_used'      => 'Choose from the most used',
		'popular_items'              => 'Popular Items',
		'search_items'               => 'Search Items',
		'not_found'                  => 'Not Found',
		'no_terms'                   => 'No items',
		'items_list'                 => 'Items list',
		'items_list_navigation'      => 'Items list navigation',
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => false,
		'show_tagcloud'              => false,
	);
	register_taxonomy( 'sitefaqcat', array( 'sitefaq' ), $args );

}
add_action( 'init', 'cc_sitefaqs_register_taxonomy', 0 );