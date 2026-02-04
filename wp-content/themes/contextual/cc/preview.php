<?php
/**
 * Previews
 * of unpublished pages so that non-logged in people can view the pages
 */

// More robust preview system that doesn't rely on WordPress nonces

// Generate a custom token that's not user-session dependent
function generate_preview_token($post_id) {
    $post = get_post($post_id);
    if (!$post) return false;
    
    // Create a hash based on post data that won't change
    $secret_key = 'your-secret-preview-key-change-this'; // Change this to something unique
    $post_data = $post_id . $post->post_date . $post->post_modified . $secret_key;
    
    return substr(hash('sha256', $post_data), 0, 16);
}

// Generate preview link with custom token
function generate_preview_link($post_id) {
    $token = generate_preview_token($post_id);
    if (!$token) return false;
    
    $preview_url = add_query_arg(array(
        'preview' => 'true',
        'post_id' => $post_id,
        'preview_token' => $token
    ), home_url());
    
    return $preview_url;
}

// Verify the custom token
function verify_preview_token($post_id, $provided_token) {
    $expected_token = generate_preview_token($post_id);
    return $expected_token && hash_equals($expected_token, $provided_token);
}

// Handle preview requests with improved validation
function handle_custom_preview_improved() {
    // Check if this is a preview request
    if (!isset($_GET['preview']) || $_GET['preview'] !== 'true') {
        return;
    }
    
    // If user is logged in and has edit capabilities, let WordPress handle the preview
    if (is_user_logged_in()) {
        // Check if this is a WordPress native preview (has preview_id, page_id, or p parameter)
        if (isset($_GET['preview_id']) || isset($_GET['page_id']) || isset($_GET['p'])) {
            $post_id = isset($_GET['preview_id']) ? intval($_GET['preview_id']) : 
                      (isset($_GET['page_id']) ? intval($_GET['page_id']) : intval($_GET['p']));
            $post = get_post($post_id);
            
            // If user can edit this post, let WordPress handle it
            if ($post && current_user_can('edit_post', $post_id)) {
                error_log('WordPress native preview detected for post ' . $post_id . ' - letting WP handle it');
                return; // Let WordPress handle the preview
            }
        }
    }
    
    // Check if post_id is provided for custom preview
    if (!isset($_GET['post_id'])) {
        wp_die('Invalid preview request');
    }
    
    $post_id = intval($_GET['post_id']);
    $token = isset($_GET['preview_token']) ? sanitize_text_field($_GET['preview_token']) : '';
    
    /*
    // Debug token validation
    error_log('Token validation debug:');
    error_log('Post ID: ' . $post_id);
    error_log('Provided token: ' . $token);
    error_log('Expected token: ' . generate_preview_token($post_id));
    error_log('Token verification result: ' . (verify_preview_token($post_id, $token) ? 'VALID' : 'INVALID'));
    */
    
    // Verify token for security
    if (!verify_preview_token($post_id, $token)) {
        wp_die('Invalid preview link - token verification failed');
    }
    
    // Get the post
    $post = get_post($post_id);
    
    // Check if post exists and is not published
    if (!$post || $post->post_status === 'publish') {
        wp_die('Content not found or already published');
    }
    
    // Set up global post data
    global $wp_query;
    $wp_query->is_single = ($post->post_type !== 'page');
    $wp_query->is_page = ($post->post_type === 'page');
    $wp_query->is_singular = true;
    $wp_query->is_home = false;
    $wp_query->is_archive = false;
    $wp_query->is_category = false;
    $wp_query->is_preview = true;
    $wp_query->queried_object = $post;
    $wp_query->queried_object_id = $post_id;
    $wp_query->post = $post;
    $wp_query->posts = array($post);
    $wp_query->post_count = 1;
    $wp_query->found_posts = 1;
    $wp_query->max_num_pages = 1;
    
    // Set up the global $post
    global $post;
    $post = get_post($post_id); // Ensure we're using the correct post
    setup_postdata($post);
    
    // Load the appropriate template
    if ($post->post_type === 'page') {
        $template = get_page_template();
    } else {
        $template = get_single_template();
    }
    
    // Add preview notice
    add_action('wp_head', 'add_preview_notice_styles');
    add_action('wp_footer', 'add_preview_notice');
    
    // Load the template
    if ($template) {
        include $template;
    } else {
        include get_template_directory() . '/index.php';
    }
    
    exit;
}

// Add preview notice styling
function add_preview_notice_styles() {
    ?>
    <style>
        .preview-notice {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #ff6b35;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            z-index: 999999;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        body.has-preview-notice {
            padding-top: 50px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('has-preview-notice');
        });
    </script>
    <?php
}

// Add preview notice banner
function add_preview_notice() {
    ?>
    <div class="preview-notice">
        🔍 PREVIEW MODE - This content is not yet published
    </div>
    <?php
}

// Replace the old function
remove_action('template_redirect', 'handle_custom_preview');

// Disable canonical redirects for preview requests
add_action('init', function() {
    if (isset($_GET['preview']) && isset($_GET['post_id']) && isset($_GET['preview_token'])) {
        remove_action('template_redirect', 'redirect_canonical');
    }
});

// Add our handler at a higher priority to run before other redirects
add_action('template_redirect', 'handle_custom_preview_improved', 5);

// Update the meta box to use the new token system
function preview_link_meta_box_callback($post) {
    if ($post->post_status !== 'publish') {
        $preview_link = generate_preview_link($post->ID);
        if ($preview_link) {
            echo '<p>Share this link for public preview:</p>';
            echo '<div style="display: flex; align-items: center; gap: 8px;">';
            echo '<input type="text" id="preview-link-input" value="' . esc_attr($preview_link) . '" readonly style="width: 100%; flex: 1;" onclick="this.select();" />';
            echo '<button type="button" id="copy-preview-link" style="padding: 6px 10px; cursor: pointer; border: 1px solid #ccc; background: #f7f7f7; border-radius: 3px;" title="Copy to clipboard">📋</button>';
            echo '</div>';
            echo '<p><small>This link allows non-logged-in users to preview this content.</small></p>';
            echo '<div id="copy-success" style="color: green; font-size: 12px; margin-top: 5px; display: none;">✓ Link copied to clipboard!</div>';
            
            // Add JavaScript for copy functionality
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const copyButton = document.getElementById('copy-preview-link');
                const input = document.getElementById('preview-link-input');
                const successMessage = document.getElementById('copy-success');
                
                if (copyButton && input) {
                    copyButton.addEventListener('click', function() {
                        // Select and copy the text
                        input.select();
                        input.setSelectionRange(0, 99999); // For mobile devices
                        
                        try {
                            // Try using the modern Clipboard API first
                            if (navigator.clipboard && window.isSecureContext) {
                                navigator.clipboard.writeText(input.value).then(function() {
                                    showSuccessMessage();
                                });
                            } else {
                                // Fallback to execCommand for older browsers
                                const successful = document.execCommand('copy');
                                if (successful) {
                                    showSuccessMessage();
                                }
                            }
                        } catch (err) {
                            // If all else fails, just select the text so user can manually copy
                            input.select();
                        }
                    });
                }
                
                function showSuccessMessage() {
                    successMessage.style.display = 'block';
                    setTimeout(function() {
                        successMessage.style.display = 'none';
                    }, 2000);
                }
            });
            </script>
            <?php
        } else {
            echo '<p>Unable to generate preview link.</p>';
        }
    } else {
        echo '<p>This content is already published.</p>';
    }
}

// Update admin row actions to use new token system
function add_preview_link_to_admin($actions, $post) {
    if ($post->post_status !== 'publish') {
        $preview_link = generate_preview_link($post->ID);
        if ($preview_link) {
            $actions['custom_preview'] = '<a href="' . $preview_link . '" target="_blank">Preview (Public)</a>';
        }
    }
    return $actions;
}

// Add preview link to post list in admin
add_filter('post_row_actions', 'add_preview_link_to_admin', 10, 2);
add_filter('page_row_actions', 'add_preview_link_to_admin', 10, 2);

// Add meta box to edit screen for easy copy/paste
add_action('add_meta_boxes', 'add_preview_link_meta_box');

function add_preview_link_meta_box() {
    add_meta_box(
        'preview_link_meta_box',
        'Public Preview Link',
        'preview_link_meta_box_callback',
        ['post', 'page', 'workshop', 'course', 'series'],
        'side',
        'high'
    );
}
?>