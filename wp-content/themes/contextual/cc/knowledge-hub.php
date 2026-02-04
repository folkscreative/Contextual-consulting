<?php
/**
 * Knowledge Hub Stuff
 */

// Register Custom Post Type
function cc_knowledgehub_cpt() {

	$labels = array(
		'name'                  => 'Knowledge Hub Items',
		'singular_name'         => 'Knowledge Hub Item',
		'menu_name'             => 'Knowledge Hub Items',
		'name_admin_bar'        => 'Knowledge Hub Item',
		'archives'              => 'Knowledge Hub Item Archives',
		'attributes'            => 'Knowledge Hub Item Attributes',
		'parent_item_colon'     => 'Parent Knowledge Hub Item:',
		'all_items'             => 'All Knowledge Hub Items',
		'add_new_item'          => 'Add New Knowledge Hub Item',
		'add_new'               => 'Add New',
		'new_item'              => 'New Knowledge Hub Item',
		'edit_item'             => 'Edit Knowledge Hub Item',
		'update_item'           => 'Update Knowledge Hub Item',
		'view_item'             => 'View Knowledge Hub Item',
		'view_items'            => 'View Knowledge Hub Items',
		'search_items'          => 'Search Knowledge Hub Item',
		'not_found'             => 'Not found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Insert into Knowledge Hub Item',
		'uploaded_to_this_item' => 'Uploaded to this Knowledge Hub Item',
		'items_list'            => 'Knowledge Hub Items list',
		'items_list_navigation' => 'Knowledge Hub Items list navigation',
		'filter_items_list'     => 'Filter Knowledge Hub Items list',
	);
	$args = array(
		'label'                 => 'Knowledge Hub Item',
		'description'           => 'Knowledge Hub Item',
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'revisions' ),
		'taxonomies'            => array( 'category', 'tax_issues', 'tax_approaches', 'tax_rtypes', 'tax_others', 'tax_trainlevels' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 26,
		'menu_icon'             => 'dashicons-media-document',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => 'knowledge',
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
		'rewrite'				=> array('slug' => 'knowledge/%category%'),
	);
	register_post_type( 'knowledge_hub', $args );

}
add_action( 'init', 'cc_knowledgehub_cpt', 0 );


// add categories in to the KH urls
/**
 * Filters the permalink for a post of a custom post type.
 *
 * @since 3.0.0
 *
 * @param string  $post_link The post's permalink.
 * @param WP_Post $post      The post in question.
 * @param bool    $leavename Whether to keep the post name.
 * @param bool    $sample    Is it a sample permalink.
 */
add_filter ( 'post_type_link', 'cc_knowledge_hub_link', 1, 3 );
function cc_knowledge_hub_link( $post_link, $post ){
	$cat_slug = '';
	if ( is_object( $post )){
		if( $post->post_type == 'knowledge_hub' ){
			$term_id = false;
			if ( class_exists('WPSEO_Primary_Term') ) {
				$wpseo_primary_term = new WPSEO_Primary_Term( 'category', $post->ID );
				$term_id = $wpseo_primary_term->get_primary_term();
			}else{
				$term_list = wp_get_post_terms($post->ID, 'category', ['fields' => 'all']);
				foreach($term_list as $term) {
					if( get_post_meta($post->ID, '_yoast_wpseo_primary_category',true) == $term->term_id ) {
						// this is a primary category
						$term_id = $term->term_id;
						break;
					}
				}
				if( ! $term_id ){
					if( ! empty( $term_list ) ){
						$term_id = $term_list[0]->term_id;
					}
				}
			}
			if( $term_id ){
				$term = get_term( $term_id );
				$cat_slug = $term->slug;
			}else{
				$cat_slug = 'uncategorised';
			}
		}
	}
	return str_replace( '%category%' , $cat_slug , $post_link );
}


// the knowledge hub cards 
function cc_knowledge_hub_card( $category_id ){
	$category = get_category( $category_id );
	$html = '<div class="card mb-3 kh-cat-card">';
	$cat_img_url = get_term_meta( $category_id, 'term_image', true );
	if($cat_img_url == ''){
		$cat_img_url = get_the_post_thumbnail_url( get_option('page_on_front'), 'post-thumb' );
	}else{
		// the url will be the full size version, let's gat a smaller one
		$image_id = wms_media_get_id( $cat_img_url );
		if( $image_id ){
			$cat_img_url = wms_section_image_url( $image_id, 'post-thumb');
		}
	}
	$category_url = esc_url( add_query_arg( 'cat', $category->slug, '/knowledge' ) );
	$html .= '<a href="'.$category_url.'" class="image-zoom"><div class="wms-bg-img card-img-top kh-cat-image" style="background-image:url('.$cat_img_url.');"><div class="kh-colour-wash"><div class="row kh-overlay-row"><div class="col-9 kh-card-title">'.$category->name.'</div><div class="col-3 kh-card-link text-end align-self-end"><i class="fa-regular fa-circle-right"></i></div></div></div></div></a>';
	$html .= '<div class="card-body">';
	// $html .= '<h5 class="post-title card-title">'.$category->name.'</h5>';
	$html .= '<p class="card-text">'.$category->description.'</p>';
	// $html .= '<a href="'.$category_url.'" class="btn btn-primary btn-sm">See all</a>';
	$html .= '</div><!-- .card-body -->';
	$html .= '</div><!-- .card -->';
	return $html;
}