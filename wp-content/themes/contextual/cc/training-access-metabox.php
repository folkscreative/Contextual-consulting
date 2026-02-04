<?php
/**
 * Training Access Rules Meta Box
 * Allows admins to specify which trainings grant "Add to Account" access to other trainings
 * Works for: course, workshop, series post types
 */

// =================================================================
// ADD META BOX
// =================================================================

add_action('add_meta_boxes', 'cc_training_access_add_metabox');
function cc_training_access_add_metabox() {
    $post_types = array('course', 'workshop', 'series');
    
    foreach($post_types as $post_type) {
        add_meta_box(
            'cc_training_access_rules',
            '<i class="fa-solid fa-link"></i> Training Access Rules',
            'cc_training_access_render_metabox',
            $post_type,
            'side',
            'default'
        );
    }
}

// =================================================================
// RENDER META BOX
// =================================================================

function cc_training_access_render_metabox($post) {
    $training_id = $post->ID;
    $post_type = get_post_type($post);
    
    // Get current rules
    $supported = cc_training_access_get_supported_trainings($training_id);
    $primaries = cc_training_access_get_primary_trainings($training_id);
    
    // Nonce for security
    wp_nonce_field('cc_training_access_save', 'cc_training_access_nonce');
    
    ?>
    <style>
        .training-access-rules ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .training-access-rules ul li {
            margin: 5px 0;
            list-style: disc;
        }
        .training-access-rules .no-rules {
            list-style: none;
            color: #666;
            font-style: italic;
        }
        .training-access-rules .remove-rule {
            color: #b32d2e;
            cursor: pointer;
            text-decoration: none;
            margin-left: 5px;
        }
        .training-access-rules .remove-rule:hover {
            color: #dc3232;
            text-decoration: underline;
        }
        .training-access-rules hr {
            margin: 15px 0;
            border: none;
            border-top: 1px solid #ddd;
        }
        .training-access-rules .select2-container {
            width: 100% !important;
        }
        .training-access-rules .select2-container--default .select2-selection--single {
            border: 1px solid #8c8f94;
            border-radius: 4px;
            height: 30px;
        }
        .training-access-rules .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px;
            color: #2c3338;
        }
        .training-access-rules .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 28px;
        }
        .training-access-rules .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
        }
        .select2-dropdown {
            border: 1px solid #8c8f94;
            border-radius: 4px;
        }
        .select2-results__option--highlighted {
            background-color: #2271b1 !important;
        }
        .training-access-add-section {
            margin-top: 10px;
        }
        .training-access-rules .description {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .training-access-rules .section-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .training-access-loading {
            display: inline-block;
            margin-left: 5px;
            color: #666;
        }
    </style>
    
    <div class="training-access-rules">
        <!-- Section 1: Grants access TO -->
        <div class="section-title">
            <i class="fa-solid fa-arrow-right"></i> Grants "Add to Account" access to:
        </div>
        
        <ul id="supported-trainings-list">
            <?php if(!empty($supported)): ?>
                <?php foreach($supported as $supported_id): ?>
                    <li data-training-id="<?php echo $supported_id; ?>">
                        <?php echo esc_html(get_the_title($supported_id)); ?>
                        <span class="training-type-badge">(<?php echo get_post_type($supported_id); ?>)</span>
                        <a href="#" class="remove-rule" 
                           data-primary-id="<?php echo $training_id; ?>" 
                           data-supported-id="<?php echo $supported_id; ?>"
                           title="Remove this rule">
                            <i class="fa-solid fa-times"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="no-rules">No access rules set</li>
            <?php endif; ?>
        </ul>
        
        <!-- Add new rule -->
        <div class="training-access-add-section">
            <select id="add-supported-training" style="width: 100%;">
                <option value="">Type to search trainings...</option>
            </select>
            <button type="button" id="add-supported-btn" class="button button-small" style="margin-top: 5px; width: 100%;">
                <i class="fa-solid fa-plus"></i> Add Rule
            </button>
        </div>
        
        <hr>
        
        <!-- Section 2: Receives access FROM -->
        <div class="section-title">
            <i class="fa-solid fa-arrow-left"></i> Receives "Add to Account" access from:
        </div>
        
        <?php if(!empty($primaries)): ?>
            <ul>
                <?php foreach($primaries as $primary_id): ?>
                    <li>
                        <a href="<?php echo get_edit_post_link($primary_id); ?>" target="_blank">
                            <?php echo esc_html(get_the_title($primary_id)); ?>
                        </a>
                        <span class="training-type-badge">(<?php echo get_post_type($primary_id); ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="description">No primary trainings grant access to this.</p>
        <?php endif; ?>
        
        <p class="description" style="margin-top: 10px;">
            <i class="fa-solid fa-info-circle"></i> These rules are managed on the primary training's edit page.
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($){
        // Initialize Select2 with AJAX search
        $('#add-supported-training').select2({
            placeholder: 'Type to search trainings...',
            allowClear: true,
            minimumInputLength: 2, // Start searching after 2 characters
            ajax: {
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                delay: 250, // Wait 250ms after typing stops
                data: function(params) {
                    return {
                        action: 'cc_training_access_search_trainings',
                        search: params.term,
                        current_training_id: <?php echo $training_id; ?>,
                        excluded_ids: <?php echo json_encode(array_merge(array($training_id), $supported)); ?>,
                        nonce: '<?php echo wp_create_nonce('training_access_search'); ?>'
                    };
                },
                processResults: function(response) {
                    if(response.success) {
                        return {
                            results: response.data.results
                        };
                    }
                    return { results: [] };
                },
                cache: true
            }
        });
        
        // Add rule
        $('#add-supported-btn').on('click', function(){
            var $btn = $(this);
            var supportedId = $('#add-supported-training').val();
            
            if(!supportedId) {
                alert('Please select a training first');
                return;
            }
            
            // Disable button and show loading
            $btn.prop('disabled', true);
            $btn.html('<i class="fa-solid fa-spinner fa-spin"></i> Adding...');
            
            $.post(ajaxurl, {
                action: 'cc_training_access_add_rule_ajax',
                primary_id: <?php echo $training_id; ?>,
                supported_id: supportedId,
                nonce: '<?php echo wp_create_nonce('training_access_rules'); ?>'
            }, function(response){
                if(response.success){
                    // Reload to show updated list
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Failed to add rule'));
                    $btn.prop('disabled', false);
                    $btn.html('<i class="fa-solid fa-plus"></i> Add Rule');
                }
            }).fail(function(){
                alert('Network error. Please try again.');
                $btn.prop('disabled', false);
                $btn.html('<i class="fa-solid fa-plus"></i> Add Rule');
            });
        });
        
        // Remove rule
        $(document).on('click', '.remove-rule', function(e){
            e.preventDefault();
            
            var $link = $(this);
            var primaryId = $link.data('primary-id');
            var supportedId = $link.data('supported-id');
            
            if(!confirm('Remove this access rule?')) {
                return;
            }
            
            // Show loading state
            var $li = $link.closest('li');
            $li.css('opacity', '0.5');
            $link.html('<i class="fa-solid fa-spinner fa-spin"></i>');
            
            $.post(ajaxurl, {
                action: 'cc_training_access_remove_rule_ajax',
                primary_id: primaryId,
                supported_id: supportedId,
                nonce: '<?php echo wp_create_nonce('training_access_rules'); ?>'
            }, function(response){
                if(response.success){
                    // Remove from DOM
                    $li.fadeOut(300, function(){
                        $(this).remove();
                        // Check if list is now empty
                        if($('#supported-trainings-list li').length === 0){
                            $('#supported-trainings-list').html('<li class="no-rules">No access rules set</li>');
                        }
                    });
                } else {
                    alert('Error: ' + (response.data.message || 'Failed to remove rule'));
                    $li.css('opacity', '1');
                    $link.html('<i class="fa-solid fa-times"></i>');
                }
            }).fail(function(){
                alert('Network error. Please try again.');
                $li.css('opacity', '1');
                $link.html('<i class="fa-solid fa-times"></i>');
            });
        });
    });
    </script>
    <?php
}

// =================================================================
// AJAX HANDLERS
// =================================================================

/**
 * AJAX handler for searching trainings (Select2 AJAX)
 */
add_action('wp_ajax_cc_training_access_search_trainings', 'cc_training_access_search_trainings_ajax');
function cc_training_access_search_trainings_ajax() {
    // Verify nonce
    if(!check_ajax_referer('training_access_search', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    // Check permissions
    if(!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $current_training_id = isset($_POST['current_training_id']) ? absint($_POST['current_training_id']) : 0;
    $excluded_ids = isset($_POST['excluded_ids']) ? array_map('absint', (array)$_POST['excluded_ids']) : array();

    // Remove any zeros and ensure array is clean
    $excluded_ids = array_filter($excluded_ids);
    
    // Ensure current training is excluded
    if($current_training_id > 0) {
        $excluded_ids[] = $current_training_id;
        // Remove duplicates
        $excluded_ids = array_unique($excluded_ids);
    }

    // Search for trainings
    $args = array(
        'post_type' => array('workshop', 'course', 'series'),
        'posts_per_page' => 20,
        'post_status' => 'publish',
        'suppress_filters' => false,
    );
    
    // Add search parameter and sorting if provided
    if(!empty($search)) {
        $args['s'] = $search;
        // Sort by newest first
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
    } else {
        // Show newest first when browsing
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
    }

    // Exclude already linked trainings
    if(!empty($excluded_ids)) {
        $args['post__not_in'] = $excluded_ids;
    }

    // Filter to prioritize title matches
    if(!empty($search)) {
        add_filter('posts_search', 'cc_training_access_prioritize_title_search', 10, 2);
    }

    $trainings = get_posts($args);

    // Remove the filter after query
    if(!empty($search)) {
        remove_filter('posts_search', 'cc_training_access_prioritize_title_search', 10);
    }
   
    // Manually filter out excluded IDs (in case post__not_in didn't work)
    if(!empty($excluded_ids)) {
        $trainings = array_filter($trainings, function($training) use ($excluded_ids) {
            return !in_array($training->ID, $excluded_ids);
        });
    }

    // Format results for Select2
    $results = array();
    foreach($trainings as $training) {
        // Double-check exclusion
        if(!empty($excluded_ids) && in_array($training->ID, $excluded_ids)) {
            continue; // Skip this one
        }

        $results[] = array(
            'id' => $training->ID,
            'text' => $training->post_title . ' (' . ucfirst($training->post_type) . ')'
        );
    }
    
    wp_send_json_success(array(
        'results' => $results
    ));
}

add_action('wp_ajax_cc_training_access_add_rule_ajax', 'cc_training_access_add_rule_ajax');
function cc_training_access_add_rule_ajax() {
    // Verify nonce
    if(!check_ajax_referer('training_access_rules', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    // Check permissions
    if(!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    $primary_id = absint($_POST['primary_id']);
    $supported_id = absint($_POST['supported_id']);
    
    // Validate IDs
    if(!$primary_id || !$supported_id) {
        wp_send_json_error(array('message' => 'Invalid training IDs'));
    }
    
    // Check that both posts exist
    if(!get_post($primary_id) || !get_post($supported_id)) {
        wp_send_json_error(array('message' => 'One or both trainings do not exist'));
    }
    
    // Add the rule
    $result = cc_training_access_add_rule($primary_id, $supported_id);
    
    if($result) {
        wp_send_json_success(array(
            'message' => 'Rule added successfully',
            'rule_id' => $result
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to add rule (may already exist)'));
    }
}

add_action('wp_ajax_cc_training_access_remove_rule_ajax', 'cc_training_access_remove_rule_ajax');
function cc_training_access_remove_rule_ajax() {
    // Verify nonce
    if(!check_ajax_referer('training_access_rules', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }
    
    // Check permissions
    if(!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }
    
    global $wpdb;
    $table = $wpdb->prefix.'cc_training_access_rules';
    
    $primary_id = absint($_POST['primary_id']);
    $supported_id = absint($_POST['supported_id']);
    
    // Validate IDs
    if(!$primary_id || !$supported_id) {
        wp_send_json_error(array('message' => 'Invalid training IDs'));
    }
    
    // Remove the rule
    $result = $wpdb->delete($table, array(
        'primary_training_id' => $primary_id,
        'supported_training_id' => $supported_id
    ), array('%d', '%d'));
    
    if($result) {
        wp_send_json_success(array('message' => 'Rule removed successfully'));
    } else {
        wp_send_json_error(array('message' => 'Failed to remove rule'));
    }
}

// =================================================================
// HELPER: Display training type badge styling & Enqueue Select2
// =================================================================

add_action('admin_enqueue_scripts', 'cc_training_access_enqueue_scripts');
function cc_training_access_enqueue_scripts($hook) {
    // Only load on post edit screens
    if($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    
    $screen = get_current_screen();
    if(!$screen || !in_array($screen->post_type, array('course', 'workshop', 'series'))) {
        return;
    }
    
    // Enqueue Select2 (check if already enqueued by something else first)
    if(!wp_script_is('select2', 'enqueued')) {
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
    }
}

add_action('admin_head', 'cc_training_access_admin_styles');
function cc_training_access_admin_styles() {
    $screen = get_current_screen();
    if(!$screen || !in_array($screen->post_type, array('course', 'workshop', 'series'))) {
        return;
    }
    ?>
    <style>
        .training-type-badge {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            font-weight: 500;
        }
    </style>
    <?php
}

// =================================================================
// SEARCH HELPER: Title-Only Search
// =================================================================

/**
 * Filter to search ONLY in post titles (ignores post content completely)
 * This makes searches much faster and more accurate for large databases
 */
function cc_training_access_prioritize_title_search($search, $query) {
    global $wpdb;
    
    // Check for search term - don't rely on is_search() since get_posts() doesn't set it
    $search_term = $query->get('s');
    
    if(empty($search) || empty($search_term)) {
        return $search;
    }
    
    // Get individual words from search term
    $search_words = explode(' ', $search_term);
    
    // Build custom search SQL for TITLE ONLY
    $search = ' AND (';
    
    $title_conditions = array();
    foreach($search_words as $word) {
        $word = trim($word);
        if(empty($word)) continue;
        
        $word = $wpdb->esc_like($word);
        $title_conditions[] = "({$wpdb->posts}.post_title LIKE '%{$word}%')";
    }
    
    if(!empty($title_conditions)) {
        $search .= implode(' AND ', $title_conditions);
    }
    
    $search .= ') ';
    
    return $search;
}
