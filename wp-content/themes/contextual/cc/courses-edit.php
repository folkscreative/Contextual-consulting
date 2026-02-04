<?php
/**
 * Course edit
 */

/**
 * things still to think about:
 * - venue
 */

// Add the Meta Boxes for subtitle and Modules & Sections
add_action('add_meta_boxes', 'add_course_modules_meta_boxes');
function add_course_modules_meta_boxes() {
    add_meta_box('course_subtitle_metabox', 'Subtitle', 'render_course_subtitle_metabox', 'course', 'extraordinary', 'high');
    add_meta_box('course_details_metabox', 'Course details', 'render_course_details_metabox', 'course', 'normal', 'high');
    add_meta_box(
        'course_resources_metabox',
        'Course Resources',
        'render_course_resources_metabox',
        'course', // your CPT slug
        'normal',
        'high'
    );
    add_meta_box(
        'course_modules_meta_box',          // id
        'Modules & Sections',               // title
        'render_course_modules_meta_box',   // callback
        'course',                           // screen
        'normal',                           // context
        'high'                              // priority
    );
    add_meta_box(
        'course_mailster_meta_box',          // id
        'Email trigger',               // title
        'render_course_mailster_meta_box',   // callback
        'course',                           // screen
        'normal',                           // context
        'high'                              // priority
    );
    add_meta_box( 'training_accordions', 'Training Accordions', 'cc_train_acc_add_training_accordion_metabox', 'course', 'advanced', 'default' );
    add_meta_box( 'course_feedback_metabox', 'Feedback Questions', 'cc_feedback_training_metabox', 'course', 'advanced', 'default' );
    add_meta_box( 'quiz_link', 'Quiz', array('Recording_Post_Type_Template', 'add_quiz_metabox'), 'course', 'side', 'default' );
}


// render the subtitle metabox
function render_course_subtitle_metabox( $post ){
    ?>
    <input class="large-text" type="text" id="subtitle" name="subtitle" value="<?php echo get_post_meta( $post->ID, 'subtitle', true ); ?>">
    <?php
}

// move the subtitle metabox after title ...
add_action('edit_form_after_title', 'reposition_course_subtitle_metabox');
function reposition_course_subtitle_metabox( $post ){
    global $wp_meta_boxes;
    if($post->post_type == 'course'){
        # Output the "extraordinary" meta boxes:
        do_meta_boxes( get_current_screen(), 'extraordinary', $post );
        # Remove the initial "extraordinary" meta boxes:
        unset($wp_meta_boxes['post']['extraordinary']);
    }
}

// course details
function render_course_details_metabox( $post ){
    ?>
    <div class="row">
        <div class="col-4">
            <?php
            /**
             * note that the meta field is _course_type
             */
            ?>
            <label for="course_type" class="form-label">Course Type</label><br>
            <select name="course_type" id="course_type" class="required form-control">
                <option value="on-demand" selected>On-demand</option>
                <!-- <option value="live">Live</option> -->
            </select>
        </div>
        <div class="col-4">
            <label for="course_status" class="form-label">Availability</label><br>
            <?php
            $course_status = get_post_meta( $post->ID, '_course_status', true );
            ?>
            <select name="course_status" id="course_status" class="form-control">
                <option value="">Available to purchase</option>
                <option value="closed" <?php selected('closed', $course_status); ?>>Closed for purchase</option>
                <option value="public" <?php selected('public', $course_status); ?>>No purchase necessary</option>
                <option value="unlisted" <?php selected('unlisted', $course_status); ?>>Available but unlisted</option>
            </select>
        </div>
        <div class="col-4">
            <label for="course_featured" class="form-label">Featured?</label><br>
            <?php
            $course_featured = get_post_meta( $post->ID, 'workshop_featured', true );
            ?>
            <select name="course_featured" id="course_featured" class="form-control">
                <option value="">No</option>
                <option value="yes" <?php selected('yes', $course_featured); ?>>Yes</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-4">
            <label for="course_timing" class="form-label"><i class="fa-solid fa-hourglass-half fa-fw"></i> Timing</label><br>
            <input type="text" class="form-control" id="course_timing" name="course_timing" value="<?php echo get_post_meta( $post->ID, '_course_timing', true ); ?>">
        </div>
        <div class="col-4">
            <label for="category" class="form-label"><i class="fa-solid fa-folder fa-fw"></i> Category</label><br>
            <input type="text" class="form-control" id="category" name="category" value="<?php echo get_post_meta( $post->ID, 'category', true ); ?>">
        </div>
        <div class="col-4">
            <label for="who_for" class="form-label"><i class="fa-solid fa-award fa-fw"></i> Audience</label><br>
            <input type="text" class="form-control" id="who_for" name="who_for" value="<?php echo get_post_meta( $post->ID, 'who_for', true ); ?>">
        </div>
    </div>
    <div class="row">
        <div class="col-4">
            <label for="block_nlft" class="form-label">Block NLFT registrations?</label><br>
            <?php
            $block_nlft = get_post_meta( $post->ID, 'block_nlft', true );
            ?>
            <select name="block_nlft" id="block_nlft" class="form-control">
                <option value="">No</option>
                <option value="yes" <?php selected('yes', $block_nlft); ?>>Yes</option>
            </select>
        </div>
        <div class="col-4">
            <label for="block_cnwl" class="form-label">Block CNWL registrations?</label><br>
            <?php
            $block_cnwl = get_post_meta( $post->ID, 'block_cnwl', true );
            ?>
            <select name="block_cnwl" id="block_cnwl" class="form-control">
                <option value="">No</option>
                <option value="yes" <?php selected('yes', $block_cnwl); ?>>Yes</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-3">
            <label for="recording_expiry_num" class="form-label">Recording expiry (number)</label><br>
            <?php
            $recording_expiry = rpm_cc_recordings_get_expiry( $post->ID, true );
            ?>
            <select name="recording_expiry_num" id="recording_expiry_num" class="form-control">
                <option value="">Unlimited</option>
                <?php
                for ($i=1; $i < 32; $i++) {
                    ?>
                    <option value="<?php echo $i; ?>" <?php selected( $i, $recording_expiry['num'] ); ?>><?php echo $i; ?></option>
                    <?php
                }
                ?>
            </select>
        </div>
        <div class="col-3">
            <label for="recording_expiry_unit" class="form-label">Recording expiry (units)</label><br>
            <select name="recording_expiry_unit" id="recording_expiry_unit" class="form-control">
                <option value="">Unlimited</option>
                <option value="days" <?php selected('days', $recording_expiry['unit']); ?>>Days</option>
                <option value="weeks" <?php selected('weeks', $recording_expiry['unit']); ?>>Weeks</option>
                <option value="months" <?php selected('months', $recording_expiry['unit']); ?>>Months</option>
            </select>
        </div>
    </div>

    <?php
    $pricing = course_pricing_get( $post->ID );
    ?>
    <hr>
    <h3>Pricing (excl VAT)</h3>
    <p>NOTE: Pricing is ignored if Availability is set to "no purchase necessary"</p>
    <div class="row">
        <?php foreach ( cc_valid_currencies() as $currency ) {
            $currency_lc = strtolower( $currency );
            ?>
            <div class="col-3">
                <label for="price_<?php echo $currency_lc; ?>" class="form-label"><?php echo ccpa_pretty_currency($currency); ?></label><br>
                <input type="number" id="price_<?php echo $currency_lc; ?>" name="price_<?php echo $currency_lc; ?>" min="0" step="0.01" value="<?php echo $pricing['price_'.$currency_lc]; ?>" class="form-control">
            </div>
        <?php } ?>
    </div>
    <div class="row">
        <div class="col-3">
            <label for="earlybird_discount" class="form-label">Early bird discount % (date must also be set)</label><br>
            <input type="number" id="earlybird_discount" name="earlybird_discount" class="form-control" min="0" step="0.0001" value="<?php echo $pricing['early_bird_discount']; ?>">
        </div>
        <div class="col-3">
            <?php
            if( $pricing['early_bird_expiry'] === NULL || $pricing['early_bird_expiry'] == '' ){
                $earlybird_expiry = '';
            }else{
                $datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $pricing['early_bird_expiry'], new DateTimeZone('Europe/London') );
                if ( $datetime ) {
                    $earlybird_expiry = $datetime->format( 'd/m/Y' );
                }
            }
            ?>
            <label for="earlybird_expiry" class="form-label">Early-bird expiry (at the end of the day)</label><br>
            <div class="input-group" id="earlybird_expiry_date_picker" data-td-target-input="nearest" data-td-target-toggle="nearest">
                <input type="text" name="earlybird_expiry" class="form-control" data-td-target="#earlybird_expiry_date_picker" value="<?php echo esc_attr($earlybird_expiry); ?>">
                <span class="input-group-text" data-td-target="#earlybird_expiry_date_picker" data-td-toggle="datetimepicker" title="Click to open the datepicker">
                    <i class="fa-solid fa-calendar-days"></i>
                </span>
            </div>
        </div>
        <div class="col-3">
            <label for="earlybird_name" class="form-label">Early-bird name (if not set will be "Early-bird")</label><br>
            <input type="text" id="earlybird_name" name="earlybird_name" class="form-control" value="<?php echo $pricing['early_bird_name']; ?>" placeholder="eg Discount" />
        </div>
    </div>
    <div class="row">
        <div class="col-3">
            <label for="student_discount" class="form-label">Student Discount %</label><br>
            <input type="number" id="student_discount" name="student_discount" class="form-control" min="0" step="0.0001" value="<?php echo $pricing['student_discount']; ?>">
        </div>
    </div>
    <div class="row">
        <?php
        $curr_upsell = cc_upsells_offer( $post->ID );
        if( $curr_upsell == null ){
            $upsell_workshop_disp = '';
            $upsell_workshop_id = '';
            $upsell_discount = 0;
            $upsell_expiry = '';
        }else{
            $upsell_workshop_disp = $curr_upsell['other_workshop_id'].': ('.course_training_type( $curr_upsell['other_workshop_id'] ).') '.get_the_title( $curr_upsell['other_workshop_id'] );
            $upsell_workshop_id = $curr_upsell['other_workshop_id'];
            $upsell_discount = $curr_upsell['discount'];
            if( $curr_upsell['expiry'] == '0000-00-00 00:00:00' ){
                $upsell_expiry = '';
            }else{
                $upsell_expiry = date( 'd/m/Y H:i', strtotime( $curr_upsell['expiry'] ) );
            }
        }
        ?>
        <div class="col-4">
            <label for="upsell_workshop" class="form-label">Upsell training</label><br>
            <div class="upsell-workshop-wrap">
                <input type="text" id="upsell-workshop-search" class="form-control" placeholder="Search for training ..." value="<?php echo $upsell_workshop_disp; ?>" data-training_id="<?php echo $post->ID; ?>" data-was="<?php echo $upsell_workshop_disp; ?>">
            </div>
        </div>
        <div class="col-3">
            <label for="upsell_discount" class="form-label">Upsell Discount %</label><br>
            <input type="number" id="upsell_discount" name="upsell_discount" class="form-control" min="0" step="0.0001" max="100" value="<?php echo $upsell_discount; ?>">
        </div>
        <div class="col-3">
            <label for="upsell_expiry" class="form-label">Upsell expiry</label><br>
            <div class="input-group" id="upsell_expiry_picker" data-td-target-input="nearest" data-td-target-toggle="nearest">
                <input type="text" name="upsell_expiry" class="form-control" data-td-target="#upsell_expiry_picker" value="<?php echo $upsell_expiry; ?>">
                <span class="input-group-text" data-td-target="#upsell_expiry_picker" data-td-toggle="datetimepicker" title="Click to open the datepicker">
                    <i class="fa-solid fa-calendar-days"></i>
                </span>
            </div>
        </div>
        <div class="col-2 text-end">
            <label class="form-label">&nbsp;</label><br>
            <a href="javascript:void(0);" id="upsell-workshop-del" class="button text-danger">
                <i class="fa-solid fa-trash-can"></i> Delete upsell
            </a>
            <input type="hidden" id="upsell-workshop-id" name="upsell_workshop" value="<?php echo $upsell_workshop_id; ?>">
        </div>
        <?php // echo cc_upsells_backend_field( $post->ID ); ?>
    </div>

    <hr>
    <h3>Credits</h3>
    <div class="row">
        <div class="col-2">
            <label for="ce_credits" class="form-label"><i class="fa-solid fa-graduation-cap"></i> CE Credits:</label><br>
            <input type="number" id="ce_credits" name="ce_credits" class="form-control" value="<?php echo (float) get_post_meta( $post->ID, 'ce_credits', true ); ?>" min="0">
        </div>
        <div class="col-6">
            <label for="workshop_dates" class="form-label">Original workshop date(s) for certificate</label><br>
            <input type="text" name="workshop_dates" id="workshop_dates" class="form-control" value="<?php echo get_post_meta( $post->ID, 'workshop_dates', true ); ?>">
            <p class="description">Any format you like. If left blank, will display "by Contextual Consulting" instead</p>
        </div>
        <div class="col-4">
            <label for="" class="form-label">Original workshop:</label><br>
            <?php
            $original_workshop = recording_get_matching_workshop_id( $post->ID );
            if( $original_workshop <> null ){
                echo '<div>'.$original_workshop.': '.get_the_title( $original_workshop ).'</div>';
            }else{
                echo '<div>Not set</div>';
            }
            ?>
            <p>Edit the workshop page to set this.
                <?php
                if( $original_workshop <> null ){
                    echo '<a href="'.get_edit_post_link( $original_workshop ).'" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>';
                }
                ?>
            </p>
        </div>
    </div>
    <?php
    $cert_cc = cc_certs_setting( $post->ID, 0, 'cc' );
    $cert_apa = cc_certs_setting( $post->ID, 0, 'apa' );
    $cert_bacb = cc_certs_setting( $post->ID, 0, 'bacb' );
    $cert_nbcc = cc_certs_setting( $post->ID, 0, 'nbcc' );
    $cert_icf = cc_certs_setting( $post->ID, 0, 'icf' );
    ?>
    <div class="row">
        <div class="col-2">
            <label for="cert_cc" class="form-label">CC Cert?</label><br>
            <select name="cert_cc" id="cert_cc" class="form-control">
                <option value="no" <?php selected( 'no', $cert_cc ); ?>>No</option>
                <option value="yes" <?php selected( 'yes', $cert_cc ); ?>>Yes</option>
            </select>
        </div>
        <div class="col-2">
            <label for="cert_apa" class="form-label">APA Cert?</label><br>
            <select name="cert_apa" id="cert_apa" class="form-control">
                <option value="no" <?php selected( 'no', $cert_apa ); ?>>No</option>
                <option value="yes" <?php selected( 'yes', $cert_apa ); ?>>Yes</option>
            </select>
        </div>
        <div class="col-2">
            <label for="cert_bacb" class="form-label">BACB Cert?</label><br>
            <select name="cert_bacb" id="cert_bacb" class="form-control">
                <option value="no" <?php selected( 'no', $cert_bacb ); ?>>No</option>
                <option value="yes" <?php selected( 'yes', $cert_bacb ); ?>>Yes</option>
            </select>
        </div>
        <div class="col-2">
            <label for="cert_nbcc" class="form-label">NBCC Cert?</label><br>
            <select name="cert_nbcc" id="cert_nbcc" class="form-control">
                <option value="no" <?php selected( 'no', $cert_nbcc ); ?>>No</option>
                <option value="yes" <?php selected( 'yes', $cert_nbcc ); ?>>Yes</option>
            </select>
        </div>
        <div class="col-2">
            <label for="cert_icf" class="form-label">ICF Cert?</label><br>
            <select name="cert_icf" id="cert_icf" class="form-control">
                <option value="no" <?php selected( 'no', $cert_icf ); ?>>No</option>
                <option value="yes" <?php selected( 'yes', $cert_icf ); ?>>Yes</option>
            </select>
        </div>
    </div>

    <hr>
    <div class="row">
        <div class="col-6">
            <label for="mailster_list" class="form-label">Newsletter list:</label><br>
            <?php
            $mailster_list = get_post_meta( $post->ID, 'mailster_list', true );
            $lists = mailster( 'lists' )->get();
            ?>
            <select name="mailster_list" id="mailster_list" class="form-control">
                <option value="">Will be set up when the first person registers</option>
                <?php foreach ($lists as $list) { ?>
                    <option value="<?php echo $list->ID; ?>" <?php selected($list->ID, $mailster_list); ?>><?php echo $list->name; ?></option>
                <?php } ?>
            </select>
            <p class="description">Automatically generated but you can override it here</p>
        </div>
        <div class="col-6">
            <label for="" class="form-label">Xero tracking category (automatically generated)</label><br>
            <input type="text" class="form-control" value="<?php echo get_post_meta( $post->ID, '_xero_tracking_code', true ); ?>" disabled>
        </div>
    </div>

    <hr>
    <h3>Content to be included in the registration email (if needed):</h3>
    <p><strong>IMPORTANT!</strong> Leave ONE blank line after the message so that it separates it from the other text in the email.</p>
    <?php
    $registration_message = get_post_meta($post->ID, 'registration_message', true);
    wp_editor($registration_message, 'registration_message', array('textarea_rows' => 4));
    ?>
    <h3>Joining info</h3>
    <textarea name="workshop_joining" id="workshop_joining" class="form-control" cols="100" rows="4"><?php echo get_post_meta($post->ID, 'workshop_joining', true); ?></textarea>
    <?php
}



// Render the Modules & Sections UI inside the Course Edit Page
function render_course_modules_meta_box($post) {
    global $wpdb;
    $course_id = $post->ID;
    $table_modules = $wpdb->prefix . 'course_modules';
    $table_sections = $wpdb->prefix . 'course_sections';
    
    // Fetch modules linked to this course
    $modules = $wpdb->get_results("SELECT * FROM $table_modules WHERE course_id = $course_id ORDER BY position ASC");
    ?>
    <p>There must be at least one Module and at least one Section!</p>
    <div class="row">
        <div class="col-4">
            <label for="course_components">Course components title</label><br>
            <input type="text" id="course_components" class="form-control" name="course_components" placeholder="Course components" value="<?php echo get_post_meta( $course_id, 'course_components', true ); ?>">
            <p class="description">If left blank, will be "Course components"</p>
        </div>
        <div class="col-4">&nbsp;</div>
        <div class="col-4">
            <?php $show_modules = get_post_meta( $course_id, '_show_modules', true ); ?>
            <label for="show_modules">Show modules and sections on the training page?</label><br>
            <select name="show_modules" id="show_modules" class="form-control">
                <option value="">Automatic (shows if multiple mods/sects)</option>
                <option value="hide" <?php selected( $show_modules, 'hide' ); ?>>Always hidden</option>
                <option value="show" <?php selected( $show_modules, 'show' ); ?>>Always shown</option>
            </select>
        </div>
    </div>
    <ul id="modules-list" class="modules-list">
        <?php foreach ($modules as $module): ?>
            <li class="module-item" data-module-id="<?php echo $module->id; ?>">
                <a href="javascript:void(0);" class="module-sorter" title="drag to re-order"><i class="fa-solid fa-arrows-up-down"></i></a>
                <span class='module-title edit-module' data-module-id="<?php echo $module->id; ?>"><?php echo esc_html($module->title); ?></span>
                <span class="button-wrap">
                    <?php echo resources_edit_button( 'module', $module->id ); ?>
                    <button class='edit-module button button-sml' data-module-id="<?php echo $module->id; ?>" title="Edit"><i class="fa-solid fa-pencil"></i></button>
                    <button class='delete-module button button-sml text-danger' data-module-id="<?php echo $module->id; ?>" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                    <button class='add-section button' data-module-id="<?php echo $module->id; ?>" title="Add a new section">+ Section</button>
                </span>
                <?php
                $sections = $wpdb->get_results("SELECT * FROM $table_sections WHERE module_id = $module->id ORDER BY position ASC");
                $section_list_class = '';
                if( ! empty( $sections ) ){
                    $section_list_class = ' has-items';
                }
                ?>
                <ul class='sections-list<?php echo $section_list_class; ?>' data-module-id="<?php echo $module->id; ?>">
                    <?php foreach ($sections as $section): ?>
                        <li class="section-item" data-section-id='<?php echo $section->id; ?>'>
                            <a href="javascript:void(0);" class="section-sorter" title="drag to re-order"><i class="fa-solid fa-arrows-up-down"></i></a>
                            <span class='section-title edit-section' data-section-id='<?php echo $section->id; ?>'><?php echo esc_html($section->title); ?></span>
                            <span class="button-wrap">
                                <?php echo resources_edit_button( 'section', $section->id ); ?>
                                <button class='edit-section button button-sml' data-section-id='<?php echo $section->id; ?>' title="Edit"><i class="fa-solid fa-pencil"></i></button>
                                <button class='delete-section button button-sml text-danger' data-section-id='<?php echo $section->id; ?>' title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <?php /*
            <div class="module-item" data-id="<?php echo $module->id; ?>">
                <strong><?php echo esc_html($module->title); ?></strong>
                <button class="edit-module" data-id="<?php echo $module->id; ?>">Edit</button>
                <button class="delete-module" data-id="<?php echo $module->id; ?>">Delete</button>
                <div class="sections-list">
                    <?php 
                    $sections = $wpdb->get_results("SELECT * FROM $table_sections WHERE module_id = $module->id ORDER BY position ASC");
                    foreach ($sections as $section): ?>
                        <div class="section-item" data-id="<?php echo $section->id; ?>">
                            <?php echo esc_html($section->title); ?>
                            <button class="edit-section" data-id="<?php echo $section->id; ?>">Edit</button>
                            <button class="delete-section" data-id="<?php echo $section->id; ?>">Delete</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="add-section" data-module="<?php echo $module->id; ?>">+ Section</button>
            </div>
            */ ?>
        <?php endforeach; ?>
    </ul>
    <button id="add-module" class="button-primary button-large">+ Module</button>

    <!-- Module Modal -->
    <div id="module-modal" class="course-modal-overlay">
        <div class="course-modal-content">
            <span class="course-modal-close">&times;</span>
            <h3 id="module-modal-title" class="course-modal-heading">Edit Module</h3>
            <input type="hidden" id="module-id">
            <label class="form-label">Title:</label><br>
            <input type="text" id="module-title" class="form-control">
            <div id="module-modal-loading" class="text-center" style="display:none;"><i class="fa-solid fa-spinner fa-spin-pulse"></i> Loading, please wait ...</div>
            <label for="module-timing" class="form-label"><i class="fa-regular fa-clock fa-fw"></i> Timing</label><br>
            <input type="text" class="form-control" id="module-timing" name="module-timing">
            <p style="text-align:right;"><button id="save-module" class="button button-primary">Save</button></p>
        </div>
    </div>

    <!-- Section Modal -->
    <div id="section-modal" class="course-modal-overlay">
        <div class="course-modal-content">
            <span class="course-modal-close">&times;</span>
            <h3 id="section-modal-title" class="course-modal-heading">Edit Section</h3>
            <input type="hidden" id="section-id">
            <label class="form-label">Title:</label><br>
            <input type="text" id="section-title" class="form-control">

            <div id="section-modal-loading" class="text-center" style="display:none;"><i class="fa-solid fa-spinner fa-spin-pulse"></i> Loading, please wait ...</div>

            <h3 class="course-modal-subhead">On-demand training (ignored for live)</h3>
            <div class="row">
                <div class="col-6">
                    <label for="recording_type" class="form-label">Recording type</label><br>
                    <select name="recording_type" id="recording_type" class="form-control">
                        <option value="vimeo">Vimeo ID</option>
                        <option value="rec_url">[old] MP4 file (URL)</option>
                        <option value="reg_url">[old] Recording registration URL</option>
                        <option value="reg_id">[old] Recording ID (Oli's)</option>
                    </select>
                </div>
                <div class="col-6">
                    <label for="recording_id" class="form-label">Recording ID</label>
                    <input type="text" id="recording_id" name="recording_id" class="form-control">
                </div>
            </div>

            <div id="chat_noupload_row" class="row">
                <p>Zoom chat upload will be available here after this section has been saved.</p>
            </div>

            <div id="chat_upload_row" class="row" style="display: none;">
                <div class="col-6">
                    <label for="zoom_chat_1" class="form-label">Zoom chat file 1</label><br>
                    <input type="file" id="zoom_chat_1" class="form-control"><br>
                    <label for="zoom_chat_2" class="form-label">Zoom chat file 2 (if needed)</label><br>
                    <input type="file" id="zoom_chat_2" class="form-control"><br>
                    <label for="uncut_vid1" class="form-label">Uncut length of 1st video (if 2nd chat file)</label><br>
                    <input type="text" id="uncut_vid1" class="form-control" placeholder="hh:mm:ss"><br>
                </div>
                <div class="col-6">
                    <label for="zoom_gaps" class="form-label">Time gaps</label><br>
                    <textarea class="form-control" id="zoom_gaps" placeholder="Sections of video removed in the edit in the format&#10;start time cut/add duration&#10;e.g.&#10;00:00:00 add 00:00:13 (adding 13 secs at the start)&#10;00:00:00 cut 00:49:07 (removing 49mins 7 secs at the start)&#10;00:56:38 cut 01:06:26 (1h 6m 26s removed at the 56m 38s point in the edited video)&#10;Etc."></textarea>
                    <p style="text-align:right;"><a href="javascript:void(0)" class="zoom-chat-upload button button-secondary" data-sectionid="">Upload now</a></p>
                    <div id="zcu_msg" class="zoom-chat-upload-msg"></div>
                </div>
            </div>

            <div id="chat_row" class="row" style="display: none;">
                <div class="col-12">
                    <label for="zoom_chat" class="form-label">Chat</label><br>
                    <div id="zoom_chat" class="zoom-chat-show"></div>
                </div>
            </div>

            <h3 class="course-modal-subhead">Live training (ignored for on-demand)</h3>
            <div class="row">
                <div class="col-6">
                    <label for="section_start_date" class="form-label">Start date & time *</label><br>
                    <div class="input-group" id="section_start_date_picker" data-td-target-input="nearest" data-td-target-toggle="nearest">
                        <input type="text" id="section_start_date" name="section_start_date" class="form-control" data-td-target="#section_start_date_picker" value="">
                        <span class="input-group-text" data-td-target="#section_start_date_picker" data-td-toggle="datetimepicker" title="Click to open the datepicker">
                            <i class="fa-solid fa-calendar-days"></i>
                        </span>
                    </div>
                </div>
                <div class="col-6">
                    <label for="section_end_date" class="form-label">End date & time *</label><br>
                    <div class="input-group" id="section_end_date_picker" data-td-target-input="nearest" data-td-target-toggle="nearest">
                        <input type="text" id="section_end_date" name="section_end_date" class="form-control" data-td-target="#section_end_date_picker" value="">
                        <span class="input-group-text" data-td-target="#section_end_date_picker" data-td-toggle="datetimepicker" title="Click to open the datepicker">
                            <i class="fa-solid fa-calendar-days"></i>
                        </span>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-3">
                    <button class="button course-modal-close-btn">Close/cancel</button>
                </div>
                <div id="section-modal-msg" class="col-6 text-center">
                    <small>* Enter all dates/times as London times</small>
                </div>
                <div class="col-3 text-end">
                    <button id="save-section" class="button button-primary">Save</button>
                </div>
            </div>

        </div>
    </div>

    <!-- Resources Modal -->
    <div id="resources-modal" class="course-modal-overlay" style="display: none;">
        <div class="course-modal-content">
            <span class="course-modal-close">&times;</span>
            <h3 class="course-modal-heading">Manage Resources</h3>

            <input type="hidden" id="resource-context-type">
            <input type="hidden" id="resource-context-id">

            <div id="resources-table">
                <div class="row">
                    <div class="col-5">
                        <strong>Resource Name</strong>
                    </div>
                    <div class="col-6">
                        <strong>URL</strong>
                    </div>
                    <div class="col-1">
                        &nbsp;
                    </div>
                </div>
                <div id="resources-content">
                    <!-- Filled by JS -->
                </div>
            </div>

            <button class="button button-secondary" id="add-resource-row">Add Resource</button>

            <hr>

            <div class="row mt-3">
                <div class="col-6">
                    <button class="button course-modal-close-btn">Cancel</button>
                </div>
                <div class="col-6 text-end">
                    <button class="button button-primary" id="save-resources-btn">Save</button>
                </div>
            </div>

        </div>
    </div>
    <?php
}

// Render the Mailster connection data meta box
function render_course_mailster_meta_box($post) {
    global $wpdb;
    $course = course_get_all( $post->ID );
    ?>
    <p>Optionally trigger a person to be added to a newsletter list ...</p>
    <div class="row">
        <div class="col-6">
            <label for="newsletter_trigger_section">When the following training is started</label><br>
            <select name="newsletter_trigger_section" id="newsletter_trigger_section" class="form-control">
                <option value="">-- no trigger selected --</option>
                <?php
                $newsletter_trigger_section = course_single_meta( $course, 'newsletter_trigger_section' );

                foreach ( $course['modules'] as $module) { ?>
                    <optgroup label="<?php echo $module['title']; ?>">
                        <?php foreach ( $module['sections'] as $section) { ?>
                            <option value="<?php echo $section['id']; ?>" <?php selected( $section['id'], $newsletter_trigger_section ); ?>><?php echo $section['title']; ?></option>
                        <?php } ?>
                    </optgroup>
                <?php } ?>
            </select>
        </div>
        <div class="col-6">
            <label for="newsletter_trigger_list">Then add the person to the following list</label>
            <?php
            $newsletter_trigger_list = course_single_meta( $course, 'newsletter_trigger_list' );
            $lists = mailster( 'lists' )->get();
            ?>
            <select name="newsletter_trigger_list" id="newsletter_trigger_list" class="form-control">
                <option value="">-- no list selected --</option>
                <?php foreach ($lists as $list) { ?>
                    <option value="<?php echo $list->ID; ?>" <?php selected( $list->ID, $newsletter_trigger_list ); ?>><?php echo $list->name; ?></option>
                <?php } ?>
            </select>
        </div>
    </div>
    <?php
}




// AJAX Handlers for Adding/Editing Modules & Sections
// Case 2: Adding a REAL module
function add_module() {
    global $wpdb;

    $title = sanitize_text_field($_POST['title']);
    $timing = sanitize_text_field($_POST['timing']);
    $course_id = intval($_POST['course_id']);

    if (!$title || !$course_id) {
        wp_send_json_error(['message' => 'Module title and course ID are required.']);
    }

    $wpdb->insert("{$wpdb->prefix}course_modules", [
        'course_id' => $course_id,
        'title' => $title,
        'timing' => $timing,
    ]);
    $module_id = $wpdb->insert_id;

    wp_send_json_success(['module_id' => $module_id]);
}
add_action('wp_ajax_add_module', 'add_module');

// get a module
function get_module(){
    global $wpdb;
    $module_id = intval($_POST['module_id']);
    $modules_table = $wpdb->prefix.'course_modules';
    $sql = $wpdb->prepare("SELECT * FROM $modules_table WHERE id = %d", $module_id);
    $module = $wpdb->get_row($sql, ARRAY_A);
    if ($module) {
        wp_send_json_success($module);
    } else {
        wp_send_json_error(['message' => 'module not found.']);
    }
}
add_action('wp_ajax_get_module', 'get_module');


// Case 1: Adding a TEMP module
function add_temp_module() {
    $title = sanitize_text_field($_POST['title']);
    $timing = sanitize_text_field($_POST['timing']);
    $course_id = intval($_POST['course_id']); // temp post_id
    $module_id = sanitize_text_field($_POST['module_id']); // JS-generated temp ID

    if (!$title || !$course_id) {
        wp_send_json_error(['message' => 'Module title and course ID are required.']);
    }

    // Store temp module in wp_options
    $temp_modules = get_option('_temp_modules', []);
    $temp_modules[$course_id][$module_id] = [
        'module_id' => $module_id,
        'title' => $title,
        'timing' => $timing,
    ];
    update_option('_temp_modules', $temp_modules);

    wp_send_json_success(['module_id' => $module_id]);
}
add_action('wp_ajax_add_temp_module', 'add_temp_module');

function update_module() {
    global $wpdb;

    $module_id = sanitize_text_field($_POST['module_id']);
    $title = sanitize_text_field($_POST['title']);
    $timing = sanitize_text_field($_POST['timing']);
    $course_id = intval($_POST['course_id']);

    if (!$title || !$course_id || !$module_id) {
        wp_send_json_error(['message' => 'Module ID, title, and course ID are required.']);
    }
    if( strlen( $title ) > 255 ){
        wp_send_json_error(['message' => 'Max length of title is 255 characters. That title is '.strlen( $title ).' characters.']);
    }

    if (strpos($module_id, 'temp_') === 0) {
        // Update temp module in wp_options
        $temp_modules = get_option('_temp_modules', []);
        if (isset($temp_modules[$course_id][$module_id])) {
            $temp_modules[$course_id][$module_id]['title'] = $title;
            $temp_modules[$course_id][$module_id]['timing'] = $timing;
            update_option('_temp_modules', $temp_modules);
            wp_send_json_success();
        }
    } else {
        // Update real module in database
        $updated = $wpdb->update("{$wpdb->prefix}course_modules", 
            [
                'title' => $title,
                'timing' => $timing,
            ], 
            ['id' => $module_id]
        );

        if ($updated !== false) {
            wp_send_json_success(['module_id' => $module_id]);
        } else {
            wp_send_json_error(['message' => 'Failed to update module.']);
        }
    }
}
add_action('wp_ajax_update_module', 'update_module');

// Delete a TEMP module
function delete_temp_module() {
    $module_id = sanitize_text_field($_POST['module_id']);
    $course_id = intval($_POST['course_id']);

    $temp_modules = get_option('_temp_modules', []);
    unset($temp_modules[$course_id][$module_id]); // Remove module
    update_option('_temp_modules', $temp_modules);

    wp_send_json_success();
}
add_action('wp_ajax_delete_temp_module', 'delete_temp_module');

// Delete a REAL module
function delete_module() {
    global $wpdb;
    $module_id = intval($_POST['module_id']);

    $wpdb->delete("{$wpdb->prefix}course_modules", ['id' => $module_id]);
    $wpdb->delete("{$wpdb->prefix}course_sections", ['module_id' => $module_id]); // Remove its sections

    wp_send_json_success();
}
add_action('wp_ajax_delete_module', 'delete_module');

// Reorder TEMP modules before course is saved
function reorder_temp_modules() {
    $course_id = intval($_POST['course_id']);
    $order = $_POST['order'];

    $temp_order = get_option('_temp_modules_order', []);
    $temp_order[$course_id] = $order;
    update_option('_temp_modules_order', $temp_order);

    wp_send_json_success();
}
add_action('wp_ajax_reorder_temp_modules', 'reorder_temp_modules');

// Reorder REAL modules after course is saved
function reorder_modules() {
    global $wpdb;
    $course_id = intval($_POST['course_id']);
    $order = $_POST['order'];

    foreach ($order as $position => $module_id) {
        $wpdb->update("{$wpdb->prefix}course_modules", 
            ['position' => $position], 
            ['id' => $module_id]
        );
    }

    wp_send_json_success();
}
add_action('wp_ajax_reorder_modules', 'reorder_modules');








function get_temp_modules() {
    $temp_id = sanitize_text_field($_POST['temp_id']);
    $temp_modules = get_option('_temp_modules', []);

    $modules = isset($temp_modules[$temp_id]) ? $temp_modules[$temp_id] : [];

    wp_send_json_success(['modules' => $modules]); // This avoids the extra "data" wrapping
}
add_action('wp_ajax_get_temp_modules', 'get_temp_modules');

function store_temp_course_id() {
    $post_id = intval($_POST['post_id']);
    $temp_id = sanitize_text_field($_POST['temp_id']);

    if (!$post_id || !$temp_id) {
        wp_send_json_error(['message' => 'Invalid data']);
    }

    update_post_meta($post_id, '_temp_course_id', $temp_id);
    wp_send_json_success(['message' => 'Temp ID stored']);
}
add_action('wp_ajax_store_temp_course_id', 'store_temp_course_id');

// Case 1: Adding a TEMP section
function add_temp_section() {
    $title = sanitize_text_field($_POST['title']);
    $module_id = sanitize_text_field($_POST['module_id']);
    $course_id = intval($_POST['course_id']);
    $section_id = sanitize_text_field($_POST['section_id']); // JS-generated temp ID

    if (!$title || !$module_id || !$course_id) {
        wp_send_json_error(['message' => 'Section title, module ID, and course ID are required.']);
    }

    // Store temp section in wp_options
    $temp_sections = get_option('_temp_sections', []);
    $temp_sections[$course_id][$module_id][$section_id] = [
        'section_id' => $section_id,
        'title' => $title
    ];
    update_option('_temp_sections', $temp_sections);

    wp_send_json_success(['section_id' => $section_id]);
}
add_action('wp_ajax_add_temp_section', 'add_temp_section');

// Case 2: Adding a REAL section
function add_section() {
    global $wpdb;

    $title = sanitize_text_field($_POST['title']);
    $module_id = sanitize_text_field($_POST['module_id']);
    // $course_id = intval($_POST['course_id']);
    $recording_type = sanitize_text_field( $_POST['recordingType'] );
    $recording_id = sanitize_text_field( $_POST['recordingId'] );
    $start_date_time = sanitize_text_field( $_POST['startDateTime'] );
    $end_date_time = sanitize_text_field( $_POST['endDateTime'] );

    $start_time = safe_date_time( $start_date_time );
    $end_time = safe_date_time( $end_date_time );

    if (!$title || !$module_id) {
        wp_send_json_error(['message' => 'Section title and module ID are required.']);
    }

    $wpdb->insert("{$wpdb->prefix}course_sections", [
        'module_id' => $module_id,
        'position' => 0,
        'title' => $title,
        'description' => '',
        'start_time' => $start_time,
        'end_time' => $end_time,
        'recording_type' => $recording_type,
        'recording_id' => $recording_id,
    ]);
    $section_id = $wpdb->insert_id;

    wp_send_json_success(['section_id' => $section_id]);
}
add_action('wp_ajax_add_section', 'add_section');

// returns a safe date/time or null
function safe_date_time( $input ){
    $output = NULL;
    if( $input <> '' ){
        $date_obj = DateTime::createFromFormat('d/m/Y H:i', $input);
        if ($date_obj !== false){
            $output = $date_obj->format('Y-m-d H:i:s');
        }
    }
    return $output;
}

// Case 3: Updating a section (temp or real)
function update_section() {
    global $wpdb;

    $section_id = sanitize_text_field($_POST['section_id']);
    $title = sanitize_text_field($_POST['title']);
    $module_id = sanitize_text_field($_POST['module_id']);
    $course_id = intval($_POST['course_id']);
    $recording_type = sanitize_text_field( $_POST['recordingType'] );
    $recording_id = sanitize_text_field( $_POST['recordingId'] );
    $start_date_time = sanitize_text_field( $_POST['startDateTime'] );
    $end_date_time = sanitize_text_field( $_POST['endDateTime'] );

    $start_time = safe_date_time( $start_date_time );
    $end_time = safe_date_time( $end_date_time );

    if (!$title || !$course_id || !$module_id || !$section_id) {
        wp_send_json_error(['message' => 'Section ID, title, module ID, and course ID are required.']);
    }

    if (strpos($section_id, 'temp_') === 0) {
        // Update temp section in wp_options
        $temp_sections = get_option('_temp_sections', []);
        if (isset($temp_sections[$course_id][$module_id][$section_id])) {
            $temp_sections[$course_id][$module_id][$section_id]['title'] = $title;
            update_option('_temp_sections', $temp_sections);
            wp_send_json_success();
        }
    } else {
        // Update real section in database
        $section = array(
            'module_id' => $module_id,
            'title' => $title,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'recording_type' => $recording_type,
            'recording_id' => $recording_id,

        );
        $updated = $wpdb->update("{$wpdb->prefix}course_sections", 
            $section, 
            ['id' => $section_id]
        );

        if ($updated !== false) {
            wp_send_json_success(['section_id' => $section_id]);
        } else {
            wp_send_json_error(['message' => 'Failed to update section.']);
        }
    }
}
add_action('wp_ajax_update_section', 'update_section');

// Reorder TEMP sections before course is saved
function reorder_temp_sections() {
    $course_id = intval($_POST['course_id']);
    $order = $_POST['order'];

    $temp_order = get_option('_temp_sections_order', []);
    $temp_order[$course_id] = $order;
    update_option('_temp_sections_order', $temp_order);

    wp_send_json_success();
}
add_action('wp_ajax_reorder_temp_sections', 'reorder_temp_sections');

// Reorder REAL sections after course is saved
function reorder_sections() {
    global $wpdb;
    $course_id = intval($_POST['course_id']);
    $module_id = intval($_POST['module_id']);
    $order = $_POST['order'];

    foreach ($order as $position => $section_id) {
        $wpdb->update("{$wpdb->prefix}course_sections", 
            ['position' => $position], 
            ['id' => $section_id]
        );
    }

    wp_send_json_success();
}
add_action('wp_ajax_reorder_sections', 'reorder_sections');


// Delete a TEMP section
function delete_temp_section() {
    $section_id = sanitize_text_field($_POST['section_id']);
    $course_id = intval($_POST['course_id']);

    $temp_sections = get_option('_temp_sections', []);
    unset($temp_sections[$course_id][$section_id]); // Remove section
    update_option('_temp_sections', $temp_sections);

    wp_send_json_success();
}
add_action('wp_ajax_delete_temp_section', 'delete_temp_section');

// Delete a REAL section
function delete_section() {
    global $wpdb;
    $section_id = intval($_POST['section_id']);

    $wpdb->delete("{$wpdb->prefix}course_sections", ['id' => $section_id]);

    wp_send_json_success();
}
add_action('wp_ajax_delete_section', 'delete_section');



// get a section
function get_section(){
    global $wpdb;
    $section_id = intval($_POST['section_id']);
    $sections_table = $wpdb->prefix.'course_sections';
    $sql = $wpdb->prepare("SELECT * FROM $sections_table WHERE id = %d", $section_id);
    $section = $wpdb->get_row($sql, ARRAY_A);
    if ($section) {
        // Format start_time and end_time to "dd/mm/yyyy hh:mm" or return an empty string if null
        $section['start_time'] = $section['start_time'] ? date('d/m/Y H:i', strtotime($section['start_time'])) : '';
        $section['end_time']   = $section['end_time']   ? date('d/m/Y H:i', strtotime($section['end_time']))   : '';

        // if it's a Vimeo recording, also send back Zoom chat stuff
        $zoom_chat = courses_zoom_chat_get( $section_id );
        $section['zoom_chat_id'] = $zoom_chat['id'];

        $section['zoom_chat_chat'] = '';
        $chats = maybe_unserialize( $zoom_chat['chat'] );
        foreach ($chats as $chat) {
            $section['zoom_chat_chat'] .= '<p>'.$chat['time'].' '.$chat['who'].' '.$chat['msg'].'</p>';
        }

        $section['zoom_chat_gaps'] = '';
        $gaps = maybe_unserialize( $zoom_chat['gaps'] );
        foreach ($gaps as $gap) {
            $section['zoom_chat_gaps'] .= '<p>'.$gap.'</p>';
        }

        $section['zoom_chat_raw'] = $zoom_chat['raw_gaps'];
        wp_send_json_success($section);
    } else {
        wp_send_json_error(['message' => 'Section not found.']);
    }
}
add_action('wp_ajax_get_section', 'get_section');


// save the course
// also converts temp modules to real ones if needed
add_action('save_post', 'course_edit_save_post', 10, 3);
function course_edit_save_post( $post_id, $post, $update ) {
    global $wpdb;
    if ($post->post_type !== 'course') {
        return;
    }

    $metas = array(
        'subtitle' => 'subtitle',
        'course_type' => '_course_type',
        'course_status' => '_course_status',
        'course_featured' => 'workshop_featured',
        'course_timing' => '_course_timing',
        'category' => 'category',
        'who_for' => 'who_for',
        'block_nlft' => 'block_nlft',
        'block_cnwl' => 'block_cnwl',
        'ce_credits' => 'ce_credits',
        'workshop_dates' => 'workshop_dates',
        'cert_cc' => 'cert_cc',
        'cert_apa' => 'cert_apa',
        'cert_bacb' => 'cert_bacb',
        'cert_nbcc' => 'cert_nbcc',
        'cert_icf' => 'cert_icf',
        'mailster_list' => 'mailster_list',
        'registration_message' => 'registration_message',
        'workshop_joining' => 'workshop_joining',
        'quiz_override' => 'quiz_override',
        'course_components' => 'course_components',
        'newsletter_trigger_section' => 'newsletter_trigger_section',
        'newsletter_trigger_list' => 'newsletter_trigger_list',
        'show_modules' => '_show_modules',
    );
    foreach ($metas as $post_name => $meta_name) {
        if( isset( $_POST[$post_name] ) ){
            update_post_meta( $post_id, $meta_name, $_POST[$post_name] );
        }
    }

    if(isset($_POST['recording_expiry_num']) && isset($_POST['recording_expiry_unit'])){
        $recording_expiry = rpm_cc_recordings_get_expiry($post_id);
        if($recording_expiry['num'] == $_POST['recording_expiry_num'] && $recording_expiry['unit'] == $_POST['recording_expiry_unit']){
            // nothing to do
        }else{
            if($recording_expiry['reset_status'] == '' || $recording_expiry['reset_status'] == 'complete'){
                // ok to update
                $recording_expiry['num'] = $_POST['recording_expiry_num'];
                $recording_expiry['unit'] = $_POST['recording_expiry_unit'];
                $recording_expiry['when_set'] = time();
                update_post_meta($post_id, 'recording_expiry', $recording_expiry);
            }else{
                // not ok to update
            }
        }
    }
    
    // original_workshop is not saved at the moment but this will need to change when we include workshops here

    $pricing = course_pricing_get( $post_id );
    $pricing['pricing_type'] = '';
    $pricing['price_gbp'] = isset( $_POST['price_gbp'] ) ? (float) $_POST['price_gbp'] : 0;
    $pricing['price_usd'] = isset( $_POST['price_usd'] ) ? (float) $_POST['price_usd'] : 0;
    $pricing['price_eur'] = isset( $_POST['price_eur'] ) ? (float) $_POST['price_eur'] : 0;
    $pricing['price_aud'] = isset( $_POST['price_aud'] ) ? (float) $_POST['price_aud'] : 0;
    $pricing['student_discount'] = isset( $_POST['student_discount'] ) ? (float) $_POST['student_discount'] : 0;
    $pricing['early_bird_discount'] = isset( $_POST['earlybird_discount'] ) ? (float) $_POST['earlybird_discount'] : 0;
    if( isset( $_POST['earlybird_expiry'] ) && $_POST['earlybird_expiry'] !== '' ){
        $datetime = DateTime::createFromFormat( "d/m/Y H:i:s", $_POST['earlybird_expiry'].' 23:59:59', new DateTimeZone('Europe/London'));
        if( $datetime ){
            $pricing['early_bird_expiry'] = $datetime->format( "Y-m-d H:i:s" );
        }
    }
    $pricing['early_bird_name'] = isset( $_POST['earlybird_name'] ) ? stripslashes( sanitize_text_field( $_POST['earlybird_name'] ) ) : '';
    $pricing_id = course_pricing_db_update( $pricing );

    // now the upsells
    if( isset( $_POST['upsell_workshop'] ) ){
        if( $_POST['upsell_workshop'] <> '' && absint( $_POST['upsell_workshop'] ) > 0 ){
            $other_training = absint( $_POST['upsell_workshop'] );

            $discount = 0;
            if(isset($_POST['upsell_discount']) && $_POST['upsell_discount'] <> '' && is_numeric($_POST['upsell_discount'])){
                $discount = (float) $_POST['upsell_discount'];
            }

            $expiry = '0000-00-00 00:00:00';
            if( isset( $_POST['upsell_expiry'] ) && $_POST['upsell_expiry'] <> '' ){ // dd/mm/yyyy hh:mm
                $datetime = DateTime::createFromFormat( "d/m/Y H:i", $_POST['upsell_expiry'], new DateTimeZone( 'Europe/London' ));
                if( $datetime ){
                    $expiry = $datetime->format( 'Y-m-d H:i' ).':00';
                }
            }

            $result = cc_upsells_update_upsell($post_id, $other_training, $discount, $expiry);
        }else{
            $result = cc_upsells_remove_upsell($post_id);
        }
    }

    // was this, until a nanosecond ago, simply a temp post with some modules and sections?
    // Get the stored temp ID for this course (only exists when first saved) ... cos we delete it in a mo
    $temp_id = get_post_meta($post_id, '_temp_course_id', true);
    if ( $temp_id ) {
        // Fetch all temp modules
        $temp_modules = get_option('_temp_modules', []);

        // Move only the relevant modules to the real database
        if (isset($temp_modules[$temp_id])) {
            foreach ($temp_modules[$temp_id] as $module) {
                $wpdb->insert("{$wpdb->prefix}course_modules", [
                    'title'     => sanitize_text_field($module['title']),
                    'course_id' => $post_id,
                ]);
            }
            // Remove transferred modules from temporary storage
            unset($temp_modules[$temp_id]);
            update_option('_temp_modules', $temp_modules);
        }
        // Remove the temp ID metadata since it's no longer needed
        delete_post_meta($post_id, '_temp_course_id');
    }
}


// course resources
function render_course_resources_metabox($post) {
    global $wpdb;
    wp_nonce_field('save_course_resources', 'course_resources_nonce');

    echo '<p>Click the button below to manage resources for this course. Changes will be saved here (not when you click the save button above).</p>';

    $resources = $wpdb->get_results($wpdb->prepare(
        "SELECT resource_name, resource_url FROM {$wpdb->prefix}course_resources WHERE course_id = %d", $post->ID
    ));

    $btn_class = ($resources) ? 'full' : 'empty';

    echo '<button type="button" class="button edit-resources '.$btn_class.'" data-type="course" data-id="' . esc_attr($post->ID) . '">Manage Resources</button>';

    if ($resources) {
        echo '<ul style="margin-top:1em;">';
        foreach ($resources as $res) {
            $name = esc_html($res->resource_name);
            $url = esc_url($res->resource_url);
            echo "<li><a href=\"$url\" target=\"_blank\">$name</a></li>";
        }
        echo '</ul>';
    } else {
        echo '<p><em>No resources added yet.</em></p>';
    }
}

// Prevent Saving During Post Save (Handled via AJAX)
add_action('save_post_training', 'noop_course_resources_save', 10, 2);
function noop_course_resources_save($post_id, $post) {
    // No-op to ensure autosave doesn’t interfere
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
}


add_action('add_meta_boxes', 'course_edit_add_series_reference_metabox');
function course_edit_add_series_reference_metabox() {
    add_meta_box(
        'course_series_reference',
        'Series Assignment',
        'course_edit_render_series_reference_metabox',
        array( 'course', 'workshop' ),
        'side',
        'default'
    );
}

function course_edit_render_series_reference_metabox($post) {
    $assigned_series = series_id_for_course( $post->ID, true, false ); // verbose results including hidden series

    if (empty($assigned_series)) {
        echo '<p>This course is <strong>not part of any series</strong>.</p>';
    } else {
        echo '<p>This course is part of the following series:</p>';
        echo '<ul>';
        foreach ($assigned_series as $series) {
            echo '<li><a href="' . esc_url($series['edit_link']) . '">' . esc_html($series['title']) . '</a></li>';
        }
        echo '</ul>';

        if (count($assigned_series) > 1) {
            echo '<p style="color: red;"><strong>Warning:</strong> This course is assigned to more than one series. A course should only belong to <em>one</em> series.</p>';
        }
    }
}
