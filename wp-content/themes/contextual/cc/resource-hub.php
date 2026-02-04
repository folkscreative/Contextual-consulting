<?php
/**
 * Resource Hub Stuff
 */

// Register Custom Post Type
function cc_resourcehub_cpt() {

	$labels = array(
		'name'                  => 'Resource Hub Items',
		'singular_name'         => 'Resource Hub Item',
		'menu_name'             => 'Resource Hub Items',
		'name_admin_bar'        => 'Resource Hub Item',
		'archives'              => 'Resource Hub Item Archives',
		'attributes'            => 'Resource Hub Item Attributes',
		'parent_item_colon'     => 'Parent Resource Hub Item:',
		'all_items'             => 'All Resource Hub Items',
		'add_new_item'          => 'Add New Resource Hub Item',
		'add_new'               => 'Add New',
		'new_item'              => 'New Resource Hub Item',
		'edit_item'             => 'Edit Resource Hub Item',
		'update_item'           => 'Update Resource Hub Item',
		'view_item'             => 'View Resource Hub Item',
		'view_items'            => 'View Resource Hub Items',
		'search_items'          => 'Search Resource Hub Item',
		'not_found'             => 'Not found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Insert into Resource Hub Item',
		'uploaded_to_this_item' => 'Uploaded to this Resource Hub Item',
		'items_list'            => 'Resource Hub Items list',
		'items_list_navigation' => 'Resource Hub Items list navigation',
		'filter_items_list'     => 'Filter Resource Hub Items list',
	);
	$args = array(
		'label'                 => 'Resource Hub Item',
		'description'           => 'Resource Hub Item',
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'revisions' ),
		'taxonomies'            => array( 'tax_issues', 'tax_approaches', 'tax_rtypes', 'tax_others', 'tax_trainlevels' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 26,
		'menu_icon'             => 'dashicons-media-document',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
		'rewrite'				=> array('slug' => 'resources'),
	);
	register_post_type( 'resource_hub', $args );

}
add_action( 'init', 'cc_resourcehub_cpt', 0 );

// Oct 2024
// we now also have a new table linking resource hub items to other things
add_action('init', 'cc_resourcehub_links_table');
function cc_resourcehub_links_table(){
	global $wpdb;
	// v1
	$cc_rhlt_db_ver = 1;
	$installed_table_ver = get_option('cc_rhlt_db_ver');
	if($installed_table_ver <> $cc_rhlt_db_ver){
		$rhl_table = $wpdb->prefix.'rh_links';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $rhl_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			rhub_id mediumint(9) NOT NULL,
			other_id mediumint(9) NOT NULL,
			other_type varchar(30) NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('cc_rhlt_db_ver', $cc_rhlt_db_ver);
	}
}

/*
// get the links
// $return must be a column name (or all)
// status can be 'all' or 'public'
function cc_resourcehub_get_links( $id, $type='resource_hub', $return='all', $order_by='id', $status='all' ){
	global $wpdb;
	$rhl_table = $wpdb->prefix.'rh_links';
	if( $type == 'resource_hub' ){
		$sql = "SELECT * FROM $rhl_table WHERE rhub_id = $id ORDER BY $order_by";
	}elseif( $type == 'other' ){
		$sql = "SELECT * FROM $rhl_table WHERE other_id = $id ORDER BY $order_by";
	}else{
		$sql = "SELECT * FROM $rhl_table WHERE other_id = $id AND other_type = '$type' ORDER BY $order_by";
	}
	$rows = $wpdb->get_results( $sql, ARRAY_A );
	if( $return <> 'all' ){
		$rows = array_column( $rows, $return );
	}
	return $rows;
}
*/

// get the links
// $return must be a column name (or 'all')
// $status can be 'all' or 'public'
function cc_resourcehub_get_links( $id, $type = 'resource_hub', $return = 'all', $order_by = 'id', $status = 'public' ) {
	global $wpdb;
	$rhl_table = $wpdb->prefix . 'rh_links';

	// Build base SQL query safely
	if ( $type == 'resource_hub' ) {
		$sql = $wpdb->prepare("SELECT * FROM $rhl_table WHERE rhub_id = %d ORDER BY $order_by", $id);
	} elseif ( $type == 'other' ) {
		$sql = $wpdb->prepare("SELECT * FROM $rhl_table WHERE other_id = %d ORDER BY $order_by", $id);
	} else {
		$sql = $wpdb->prepare("SELECT * FROM $rhl_table WHERE other_id = %d AND other_type = %s ORDER BY $order_by", $id, $type);
	}

	$rows = $wpdb->get_results( $sql, ARRAY_A );

	// Filter only if public posts are required
	if ( $status === 'public' ) {
		$rows = array_filter( $rows, function( $row ) {
			$rhub_post  = get_post( $row['rhub_id'] );
			$other_post = get_post( $row['other_id'] );

			return ( 
				$rhub_post && $rhub_post->post_status === 'publish' &&
				$other_post && $other_post->post_status === 'publish'
			);
		});
	}

	// Return a specific column if requested
	if ( $return !== 'all' ) {
		$rows = array_column( $rows, $return );
	}

	return $rows;
}


// insert
// $row excludes id
function cc_resourcehub_insert_link( $row ){
	global $wpdb;
	$rhl_table = $wpdb->prefix.'rh_links';
	$wpdb->insert( $rhl_table, $row, array( '%d', '%d', '%s' ) );
	return $wpdb->insert_id;
}

// delete all rows for this rhub_id
function cc_resourcehub_delete_rhub_links( $rhub_id ){
	global $wpdb;
	$rhl_table = $wpdb->prefix.'rh_links';
	$where = array( 'rhub_id' => $rhub_id );
	return $wpdb->delete( $rhl_table, $where );
}

function resource_hub_meta_box() {
    add_meta_box(
        'resource_hub_links',
        'Resource Hub Links',
        'render_resource_hub_meta_box',
        'resource_hub',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'resource_hub_meta_box');

// NEW: Add member access meta box
function resource_hub_member_access_meta_box() {
    add_meta_box(
        'resource_hub_member_access',
        'Member Access Settings',
        'render_resource_hub_member_access_meta_box',
        'resource_hub',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'resource_hub_member_access_meta_box');

// NEW: Render member access meta box
function render_resource_hub_member_access_meta_box($post) {
    wp_nonce_field('save_resource_hub_member_access', 'resource_hub_member_access_nonce');
    
    $member_only = get_post_meta($post->ID, '_member_only', true);
    $non_member_intro = get_post_meta($post->ID, '_non_member_intro', true);
    ?>
    <div class="cc-rhub-member-settings">
        <p>
            <label for="member_only">
                <input type="checkbox" id="member_only" name="member_only" value="1" <?php checked($member_only, '1'); ?> />
                <strong>Member Only Resource</strong>
            </label>
        </p>
        <p class="description">Check this box to restrict this resource to paid members only.</p>
        
        <div id="ccRhubNonMemberIntroWrapper" class="cc-rhub-non-member-intro-wrapper" style="<?php echo $member_only ? '' : 'display:none;'; ?>">
            <h4>Non-Member Preview</h4>
            <p>
                <label for="non_member_intro">Custom preview content for non-members:</label>
            </p>
            <?php
            wp_editor($non_member_intro, 'non_member_intro', array(
                'textarea_name' => 'non_member_intro',
                'media_buttons' => false,
                'textarea_rows' => 8,
                'teeny' => true,
                'quicktags' => true
            ));
            ?>
            <p class="description">If left empty, the first portion of the main content will be shown instead (with links/videos disabled).</p>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#member_only').on('change', function() {
            if ($(this).is(':checked')) {
                $('#ccRhubNonMemberIntroWrapper').slideDown();
            } else {
                $('#ccRhubNonMemberIntroWrapper').slideUp();
            }
        });
    });
    </script>
    <?php
}

function render_resource_hub_meta_box($post) {
    wp_nonce_field('save_resource_hub_links', 'resource_hub_links_nonce');
    $links = cc_resourcehub_get_links( $post->ID, 'resource_hub', 'all', 'id' );
    ?>
    <p>Use this section to link this resource hub item to knowledge hub items, presenters and/or blog posts.</p>
    <div id="resource-hub-links-container">
    	<?php foreach ($links as $link) { ?>
    		<div class="resource-hub-link mx-2">
    			<input type="text" class="w-90" value="<?php echo get_the_title($link['other_id']).' ('.$link['other_type'].')'; ?>" readonly>
    			<a href="javascript:void(0);" class="text-end resource-hub-link-del text-danger"><i class="fa-solid fa-trash-can"></i></a>
	            <input type="hidden" class="resource-hub-post-id" name="resource_hub_links[]" value="<?php echo $link['other_id']; ?>">
    		</div>
    	<?php } ?>
        <div class="resource-hub-link mx-2">
            <input type="text" class="resource-hub-search w-90" placeholder="Search for content...">
			<a href="javascript:void(0);" class="text-end resource-hub-link-del text-danger"><i class="fa-solid fa-trash-can"></i></a>
            <input type="hidden" class="resource-hub-post-id" name="resource_hub_links[]">
        </div>
    </div>
    <div class="mx-2">
    	<button type="button" id="add-more-links" class="button">Add Another Link</button>
    </div>
    <?php
}

add_action('save_post', 'save_resource_hub_links');
function save_resource_hub_links($post_id) {
    if (!isset($_POST['resource_hub_links_nonce']) || !wp_verify_nonce($_POST['resource_hub_links_nonce'], 'save_resource_hub_links')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    $curr_rows = cc_resourcehub_get_links( $post_id, 'resource_hub', 'other_id', 'other_id' );
    $links = array_filter( array_map( 'intval', $_POST['resource_hub_links'] ?? [] ) ); // ?? = the null coalescing operator same as isset( $_POST['resource_hub_links'] ) ? $_POST['resource_hub_links'] : []
    sort( $links );
    if( $curr_rows <> $links ){
    	cc_resourcehub_delete_rhub_links( $post_id );
		foreach ( $links as $link_id ) {
			$row = array(
				'rhub_id' => $post_id,
				'other_id' => $link_id,
				'other_type' => get_post_type( $link_id ),
			);
			$inserted_id = cc_resourcehub_insert_link( $row );
		}
    }
}

// NEW: Save member access settings
add_action('save_post', 'save_resource_hub_member_access');
function save_resource_hub_member_access($post_id) {
    // Check if this is a resource_hub post
    if (get_post_type($post_id) !== 'resource_hub') {
        return;
    }
    
    if (!isset($_POST['resource_hub_member_access_nonce']) || !wp_verify_nonce($_POST['resource_hub_member_access_nonce'], 'save_resource_hub_member_access')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save member only setting
    $member_only = isset($_POST['member_only']) ? '1' : '';
    update_post_meta($post_id, '_member_only', $member_only);
    
    // Save non-member intro content
    $non_member_intro = isset($_POST['non_member_intro']) ? wp_kses_post($_POST['non_member_intro']) : '';
    update_post_meta($post_id, '_non_member_intro', $non_member_intro);
}

add_action('wp_ajax_resource_hub_search', 'resource_hub_ajax_search');
function resource_hub_ajax_search() {
    check_ajax_referer('resource_hub_nonce', 'security');

    $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';

    $query = new WP_Query([
        's' => $term,
        'post_type' => ['presenter', 'post', 'knowledge_hub'],
        'posts_per_page' => 10,
    ]);

    $results = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'post_type' => get_post_type(),
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json($results);
}


// add the metabox to show any RH links onto the posts, presenters and knowledge hub
// Hook into 'add_meta_boxes' to register the custom metabox
add_action('add_meta_boxes', 'add_resource_hub_metabox');
function add_resource_hub_metabox() {
    // Define the post types where the metabox should appear
    $post_types = ['post', 'presenter', 'knowledge_hub'];
    
    foreach ($post_types as $post_type) {
        add_meta_box(
            'resource_hub_metabox',          // Unique ID of the metabox
            'Resource Hub Links',             // Title of the metabox
            'display_resource_hub_metabox',  // Callback function to display the content
            $post_type,                      // Post type where the metabox will appear
            'side',                          // Context where the box will appear (side, normal, advanced)
            'default'                        // Priority (high, core, default, low)
        );
    }
}

// Callback function to display the metabox content
function display_resource_hub_metabox($post) {
    $resource_hub_links = cc_resourcehub_get_links( $post->ID, $post->post_type, 'rhub_id' );
    if( count( $resource_hub_links ) == 0 ){ ?>
    	<p>No resource hub links</p>
    <?php }else{
    	foreach ($resource_hub_links as $rhub_id) { ?>
    		<p><a href="<?php echo get_edit_post_link( $rhub_id ); ?>" target="_blank"><?php echo get_the_title( $rhub_id ); ?></a></p>
    	<?php }
    }
}

// NEW: Check if user has access to a resource
function cc_resourcehub_user_has_access($post_id, $user_id = null) {
    // Get current user if none specified
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }
    
    // Check if resource is member-only
    $member_only = get_post_meta($post_id, '_member_only', true);
    
    // If not member-only, everyone has access
    if (!$member_only) {
        return true;
    }
    
    // If not logged in, no access
    if (!$user_id) {
        return false;
    }
    
    // Check if user has paid membership using the subscription system
    if (class_exists('ContextualUserCapabilities')) {
        // $user_capabilities = new ContextualUserCapabilities();
        // return $user_capabilities->check_resource_access($user_id, $post_id);
        return ContextualUserCapabilities::get_instance()->check_resource_access($user_id, $post_id);
    }
    
    // Fallback: check for basic member capabilities
    return user_can($user_id, 'access_member_resources');
}

// NEW: Get preview content for non-members
function cc_resourcehub_get_preview_content($post_id) {
    // Check for custom non-member intro
    $non_member_intro = get_post_meta($post_id, '_non_member_intro', true);
    
    if (!empty($non_member_intro)) {
        return $non_member_intro;
    }
    
    // Fall back to truncated main content with disabled links/videos
    $post = get_post($post_id);
    $content = $post->post_content;
    
    // Handle shortcodes before applying content filters
    $content = cc_resourcehub_disable_shortcodes($content);
    
    // Strip any remaining shortcodes to prevent WordPress from processing them
    $content = strip_shortcodes($content);
    
    // Apply WordPress content filters (but shortcodes are already processed)
    $content = apply_filters('the_content', $content);
    
    // Truncate to first 300 words or first paragraph, whichever is longer
    $preview_content = cc_resourcehub_truncate_content($content, 300);
    
    // Disable links and other interactive elements
    $preview_content = cc_resourcehub_disable_interactive_elements($preview_content);
    
    return $preview_content;
}

// NEW: Handle shortcodes in preview content - disable before WordPress processes them
function cc_resourcehub_disable_shortcodes($content) {
    // Handle [hd_video] shortcode specifically
    $content = preg_replace_callback(
        '/\[hd_video([^\]]*)\]/i',
        function($matches) {
            // Extract attributes to show what video would be there
            $atts_string = $matches[1];
            $video_info = 'Video content';
            $thumbnail_url = '';
            $video_title = '';
            
            // Try to extract youtube ID and get thumbnail
            if (preg_match('/youtube=["\']?([^"\'\s\]]+)["\']?/i', $atts_string, $yt_matches)) {
                $youtube_id = $yt_matches[1];
                $video_info = 'YouTube video';
                $thumbnail_url = cc_resourcehub_get_youtube_thumbnail($youtube_id);
                $video_title = cc_resourcehub_get_youtube_title($youtube_id);
            } 
            // Try to extract vimeo ID and get thumbnail
            elseif (preg_match('/vimeo=["\']?([^"\'\s\]]+)["\']?/i', $atts_string, $vimeo_matches)) {
                $vimeo_id = $vimeo_matches[1];
                $video_info = 'Vimeo video';
                $thumbnail_url = cc_resourcehub_get_vimeo_thumbnail($vimeo_id);
                $video_title = 'Vimeo video';
            }
            
            return cc_resourcehub_render_disabled_video($video_info, $thumbnail_url, $video_title);
        },
        $content
    );
    
    // Handle other common video shortcodes
    $video_shortcodes = ['video', 'youtube', 'vimeo', 'embed'];
    foreach ($video_shortcodes as $shortcode) {
        $content = preg_replace('/\[' . $shortcode . '([^\]]*)\]/i', cc_resourcehub_render_disabled_video('Video content', '', ''), $content);
    }
    
    return $content;
}

// NEW: Get YouTube thumbnail URL
function cc_resourcehub_get_youtube_thumbnail($youtube_id) {
    // YouTube provides several thumbnail sizes - use maxresdefault for best quality
    $thumbnail_urls = [
        "https://img.youtube.com/vi/{$youtube_id}/maxresdefault.jpg",
        "https://img.youtube.com/vi/{$youtube_id}/hqdefault.jpg",
        "https://img.youtube.com/vi/{$youtube_id}/mqdefault.jpg"
    ];
    
    // Return the first available thumbnail (maxresdefault if available)
    foreach ($thumbnail_urls as $url) {
        $headers = @get_headers($url);
        if ($headers && strpos($headers[0], '200') !== false) {
            return $url;
        }
    }
    
    // Fallback to default thumbnail
    return "https://img.youtube.com/vi/{$youtube_id}/hqdefault.jpg";
}

// NEW: Get YouTube video title (optional - requires API key for best results)
function cc_resourcehub_get_youtube_title($youtube_id) {
    // For now, return a generic title
    // You could enhance this with YouTube API integration if needed
    return 'YouTube Video';
}

// NEW: Get Vimeo thumbnail URL - clean version without debugging
function cc_resourcehub_get_vimeo_thumbnail($vimeo_id) {
    // Clean the Vimeo ID
    $vimeo_id = trim($vimeo_id);
    
    // Try direct CDN patterns for Vimeo
    $possible_urls = [
        "https://i.vimeocdn.com/video/{$vimeo_id}_640x360.jpg",
        "https://i.vimeocdn.com/video/{$vimeo_id}_295x166.jpg",
        "https://i.vimeocdn.com/video/{$vimeo_id}.jpg"
    ];
    
    foreach ($possible_urls as $url) {
        // Quick check if thumbnail exists (with short timeout)
        $response = wp_remote_head($url, array('timeout' => 3));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            return $url;
        }
    }
    
    // If direct URLs don't work, try Vimeo's v2 API as fallback
    $oembed_url = "https://vimeo.com/api/v2/video/{$vimeo_id}.json";
    
    $response = wp_remote_get($oembed_url, array('timeout' => 5));
    
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (is_array($data) && !empty($data[0]['thumbnail_large'])) {
            return $data[0]['thumbnail_large'];
        } elseif (is_array($data) && !empty($data[0]['thumbnail_medium'])) {
            return $data[0]['thumbnail_medium'];
        }
    }
    
    // If all methods fail, return empty (will show fallback)
    return '';
} 

// NEW: Render disabled video element with thumbnail
function cc_resourcehub_render_disabled_video($video_info, $thumbnail_url = '', $video_title = '') {
    $html = '<div class="cc-rhub-disabled-video-shortcode">';
    
    if (!empty($thumbnail_url)) {
        $html .= '<div class="cc-rhub-video-thumbnail-wrapper">';
        $html .= '<img src="' . esc_url($thumbnail_url) . '" alt="Video thumbnail" class="cc-rhub-video-thumbnail">';
        $html .= '<div class="cc-rhub-video-overlay">';
        $html .= '<i class="fas fa-play-circle cc-rhub-play-icon"></i>';
        $html .= '<div class="cc-rhub-video-overlay-text">';
        $html .= '<span class="cc-rhub-video-login-text">Login required to watch this video</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
    } else {
        // Fallback when no thumbnail available
        $html .= '<div class="cc-rhub-video-fallback">';
        $html .= '<i class="fas fa-play-circle"></i>';
        $html .= '<span>Video content - Login required</span>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

// NEW: Truncate content intelligently
function cc_resourcehub_truncate_content($content, $word_limit = 300) {
    // Strip shortcodes first
    $content = strip_shortcodes($content);
    
    // Convert to plain text temporarily to count words
    $plain_text = wp_strip_all_tags($content);
    $words = explode(' ', $plain_text);
    
    if (count($words) <= $word_limit) {
        return $content;
    }
    
    // Find a good breaking point in the HTML
    $doc = new DOMDocument();
    @$doc->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    
    $word_count = 0;
    $truncated = '';
    
    // Simple fallback: truncate at word boundary and add ellipsis
    $truncated_words = array_slice($words, 0, $word_limit);
    $truncated_text = implode(' ', $truncated_words);
    
    // Try to maintain some HTML structure
    $truncated_content = wp_trim_words($content, $word_limit, '...');
    
    return $truncated_content;
}

// NEW: Disable interactive elements in preview content
function cc_resourcehub_disable_interactive_elements($content) {
    // Remove or disable links - fixed regex to handle attributes properly
    $content = preg_replace('/<a\s+([^>]*?)href=(["\'])(.*?)\2([^>]*?)>(.*?)<\/a>/i', '<span class="cc-rhub-disabled-link" data-original-href="$3" title="Login required to access links">$5</span>', $content);
    
    // Disable video embeds
    $content = preg_replace('/<video[^>]*>/i', '<div class="cc-rhub-disabled-video"><i class="fa fa-video"></i><br><span>Video preview - Login required</span></div><div style="display: none;">', $content);
    $content = str_replace('</video>', '</div>', $content);
    
    // Disable iframes (for embedded videos, etc.)
    $content = preg_replace('/<iframe[^>]*>/i', '<div class="cc-rhub-disabled-embed"><i class="fa fa-play-circle"></i><br><span>Embedded content - Login required</span></div><div style="display: none;">', $content);
    $content = str_replace('</iframe>', '</div>', $content);
    
    // Disable form elements
    $content = preg_replace('/<(input|textarea|select|button)[^>]*>/i', '<$1 disabled style="opacity: 0.5;" title="Login required">', $content);
    
    return $content;
}

// NEW: Add login modal AJAX handler
add_action('wp_ajax_nopriv_resource_hub_login', 'resource_hub_ajax_login');
function resource_hub_ajax_login() {
    check_ajax_referer('resource_hub_login_nonce', 'nonce');
    
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    $redirect_url = esc_url_raw($_POST['redirect_url']);
    
    $credentials = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => true,
    );
    
    $user = wp_signon($credentials, false);
    
    if (is_wp_error($user)) {
        wp_send_json_error(array(
            'message' => $user->get_error_message()
        ));
    } else {
        wp_send_json_success(array(
            'message' => 'Login successful',
            'redirect_url' => $redirect_url
        ));
    }
}

// NEW: Enqueue scripts for login modal
add_action('wp_enqueue_scripts', 'resource_hub_enqueue_scripts');
function resource_hub_enqueue_scripts() {
    if (is_singular('resource_hub')) {
        wp_localize_script('jquery', 'resource_hub_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'login_nonce' => wp_create_nonce('resource_hub_login_nonce'),
            'membership_url' => home_url('/membership/') // Update this to your membership page URL
        ));
    }
}

// the code needed to show related resource hub content on presenter, knowledge hub and posts pages
function cc_resourcehub_linked ( $post_id ){
	$html = '';
	$resource_hub_links = cc_resourcehub_get_links( $post_id, 'other', 'rhub_id' );
	if( count( $resource_hub_links ) > 0 ){

		$doing_carousel = false;
		if( count( $resource_hub_links ) > 3 ){
			$doing_carousel = true;
		}

		$html .= '<h4 class="mt-5">Resources related to '.get_the_title( $post_id ).'</h4>';
		$html .= '<div class="cc-train-panel">';

		$html .= '<div class="row mx-auto my-auto';
		if( $doing_carousel ){
			$html .= ' justify-content-center';
		}
		$html .= '">';

		if( $doing_carousel ){
			$html .= '<div id="cc-train-panel-carousel-rhub-links" class="carousel carousel-dark slide" data-bs-ride="carousel" data-bs-interval="7000">';
			$html .= '<div class="carousel-inner cc-train-panel-carousel-inner" role="listbox">';
		}

		$item_class = 'active';
		foreach ($resource_hub_links as $rhub_id) {
			$html .= cc_topics_cpt_card_col( $rhub_id, false, $doing_carousel, $item_class );
			$item_class = '';
		}

		if( $doing_carousel ){
			$html .= '</div><!-- .carousel-inner -->';
			// controls
			$html .= '<button class="carousel-control-prev" type="button" data-bs-target="#cc-train-panel-carousel-rhub-links" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button><button class="carousel-control-next" type="button" data-bs-target="#cc-train-panel-carousel-rhub-links" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>';
			$html .= '</div><!-- .carousel -->';
		}

		$html .= '</div><!-- .row -->';
		$html .= '</div><!-- .cc-train-panel -->';
	}

	return $html;
}


// show related content on the resource hub page
function cc_resourcehub_linked_rhub( $rhub_id ){

	$html = '';
	$resource_hub_links = cc_resourcehub_get_links( $rhub_id, 'resource_hub', 'other_id', 'id' );
	if( count( $resource_hub_links ) > 0 ){

		$doing_carousel = false;
		if( count( $resource_hub_links ) > 3 ){
			$doing_carousel = true;
		}

		$html .= '<div class="cc-train-panel resource-hub-links';
		if( $doing_carousel ){
			$html .= ' px-5';
		}
		$html .= '">';
		$html .= '<h4 class="mt-5">Related content for this resource:</h4>';

		$html .= '<div class="row mx-auto my-auto';
		if( $doing_carousel ){
			$html .= ' justify-content-center';
		}
		$html .= '">';

		if( $doing_carousel ){
			$html .= '<div id="cc-train-panel-carousel-rhub-links" class="carousel carousel-dark slide" data-bs-ride="carousel" data-bs-interval="7000">';
			$html .= '<div class="carousel-inner cc-train-panel-carousel-inner" role="listbox">';
		}

		$item_class = 'active';
		foreach ($resource_hub_links as $other_id) {
			$html .= cc_topics_cpt_card_col( $other_id, false, $doing_carousel, $item_class, array(), '3' );
			$item_class = '';
		}

		if( $doing_carousel ){
			$html .= '</div><!-- .carousel-inner -->';
			// controls
			$html .= '<button class="carousel-control-prev" type="button" data-bs-target="#cc-train-panel-carousel-rhub-links" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button><button class="carousel-control-next" type="button" data-bs-target="#cc-train-panel-carousel-rhub-links" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>';
			$html .= '</div><!-- .carousel -->';
		}

		$html .= '</div><!-- .row -->';
		$html .= '</div><!-- .cc-train-panel -->';
	}

	return $html;
}

// the ids of all published resiurce_hub items
function cc_resourcehub_get_ids(){
	$args = array(
		'post_type' => 'resource_hub',
		'numberposts' => -1,
		'fields' => 'ids',
	);
	return get_posts( $args );
}