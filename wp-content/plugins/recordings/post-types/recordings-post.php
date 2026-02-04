<?php
if(!class_exists('Recording_Post_Type_Template')) {

	class Recording_Post_Type_Template {
		const POST_TYPE	= "recording";
		private $_meta	= array(
            'registration_link',
            'registration_link_id',
            'recording_url',
			'vimeo_id',
            'recording_price',
            'recording_price_aud',
            'recording_price_usd',
            'recording_price_eur',
            'recording_for_sale',
            'registration_message',
            'workshop_slides',
            'workshop_resources',
            'workshop_certificate', // replaced by individual certs
            'workshop_feedback',
            'workshop_linkedin',
            'workshop_joining',
            'workshop_dates',
            // 'presenter',
            'duration',
            'student_discount',
            'earlybird_discount',
            'earlybird_expiry_date',
            'earlybird_name',
            'category',
            'who_for',
            'availability',
            'mailster_list',
            'ce_credits',
            // added v1.67
            'cert_cc',
            'cert_apa',
            'cert_bacb',
            'cert_nbcc',
            'cert_icf',
            'quiz_override',
            'block_nlft',
            'block_cnwl',
		);
		
    	public function __construct() {
    		// register actions
    		add_action('init', array(&$this, 'init'));
    		add_action('admin_init', array(&$this, 'admin_init'));
            // position the subtitle metabox where i want it
            add_action('edit_form_after_title', array(&$this, 'reposition_subtitle_metabox'));
    	} // END public function __construct()

    	public function init() {
    		// Initialize Post Type
    		$this->create_post_type();
    		add_action('save_post', array(&$this, 'save_post'));
    	} // END public function init()

    	public function create_post_type() {
    		register_post_type(self::POST_TYPE,
    			array(
    				'labels' => array(
    					'name' => __(sprintf('%ss', ucwords(str_replace("_", " ", self::POST_TYPE)))),
    					'singular_name' => __(ucwords(str_replace("_", " ", self::POST_TYPE)))
    				),
    				'public' => true,
    				'has_archive' => true,
    				'description' => __("Contextual Consulting Recordings"),
    				'supports' => array(
    					'title', 'editor', 'excerpt', 'thumbnail', 
    				),
                    'taxonomies' => array( 'tax_issues', 'tax_approaches', 'tax_rtypes', 'tax_others', 'tax_trainlevels' ),
                    'rewrite' => array('slug' => 'online-training'),
    			)
    		);
    	}
	
    	public function save_post($post_id) {
            // verify if this is an auto save routine. 
            // If it is our form has not been submitted, so we dont want to do anything
            if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
                return;
            }
            
    		if(isset($_POST['post_type']) && $_POST['post_type'] == self::POST_TYPE && current_user_can('edit_post', $post_id)){
    			// empty out all the checkboxes as, if they have been unset, there will be no post for them
                foreach($this->_meta as $field_name){
    				// Update the post's meta field
                    update_post_meta($post_id, $field_name, $_POST[$field_name]);
    			}
                if(isset($_POST['view_chg_sub'])){
                    if(isset($_POST['view_order']) && $_POST['view_order'] == 'ad'){
                        $view_chg['view_order'] = 'ad';
                    }else{
                        $view_chg['view_order'] = '';
                    }
                    if(isset($_POST['stdt']) && $_POST['stdt'] <> ''){
                        $date = date_create_from_format('d/m/Y', $_POST['stdt']);
                        if($date){
                            $view_chg['stdt'] = date_format($date, 'd/m/Y');
                        }else{
                            $view_chg['stdt'] = '';
                        }
                    }else{
                        $view_chg['stdt'] = '';
                    }
                    if(isset($_POST['endt']) && $_POST['endt'] <> ''){
                        $date = date_create_from_format('d/m/Y', $_POST['endt']);
                        if($date){
                            $view_chg['endt'] = date_format($date, 'd/m/Y');
                        }else{
                            $view_chg['endt'] = '';
                        }
                    }else{
                        $view_chg['endt'] = '';
                    }
                    update_post_meta($post_id, '_view_chg', $view_chg);
                }
                if(isset($_POST['viewer_content'])){
                    update_post_meta($post_id, 'viewer_content', $_POST['viewer_content']);
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
                // now the upsells
                if( isset( $_POST['upsell_workshop'] ) ){
                    if( $_POST['upsell_workshop'] <> '' ){
                        $discount = 0;
                        if(isset($_POST['upsell_discount']) && $_POST['upsell_discount'] <> '' && is_numeric($_POST['upsell_discount'])){
                            $discount = $_POST['upsell_discount'];
                        }
                        $expiry = '0000-00-00 00:00:00';
                        if( isset( $_POST['upsell_expiry_date'] ) && $_POST['upsell_expiry_date'] <> '' ){
                            if( isset( $_POST['upsell_expiry_time'] ) && $_POST['upsell_expiry_time'] <> '' ){
                                $date_time_string = $_POST['upsell_expiry_date'].' '.$_POST['upsell_expiry_time'];
                            }else{
                                $date_time_string = $_POST['upsell_expiry_date'].' 00:00';
                            }
                            $datetime = DateTime::createFromFormat( "d/m/Y H:i", $date_time_string, new DateTimeZone( 'Europe/London' ));
                            if( $datetime ){
                                $expiry = $datetime->format( 'Y-m-d H:i' ).':00';
                            }
                        }
                        $result = cc_upsells_update_upsell($post_id, absint($_POST['upsell_workshop']), $discount, $expiry);
                    }else{
                        $result = cc_upsells_remove_upsell($post_id);
                    }
                }
                if(isset($_POST['subtitle'])){
                    update_post_meta($post_id, 'subtitle', sanitize_text_field($_POST['subtitle']));
                }
                // and now the resource files folder
                resources_save_folder($post_id);
                // module data
                for ($i=0; $i < 10; $i++) { 
                    $module_name = (isset($_POST['module_name_'.$i])) ? stripslashes( sanitize_text_field( $_POST['module_name_'.$i] ) ) : '';
                    update_post_meta($post_id, 'module_name_'.$i, $module_name);
                    $module_vimeo = (isset($_POST['module_vimeo_'.$i])) ? stripslashes( sanitize_text_field( $_POST['module_vimeo_'.$i] ) ) : '';
                    update_post_meta($post_id, 'module_vimeo_'.$i, $module_vimeo);
                    $module_docs = (isset($_POST['module_docs_'.$i])) ? stripslashes( sanitize_url( $_POST['module_docs_'.$i] ) ) : '';
                    update_post_meta($post_id, 'module_docs_'.$i, $module_docs);
                }
                // and now the training accordions
                /*
                $empty_rows = 0;
                $maybe_more = true;
                $row = 0;
                while ($maybe_more) {
                    if(!isset($_POST['tta-ta-'.$row])){
                        $empty_rows ++;
                        if($empty_rows > 5){
                            $maybe_more = false;
                        }
                    }else{
                        $tta = cc_train_acc_empty_tta();
                        $tta['id'] = absint( $_POST['tta-id-'.$row] );
                        if( $_POST['tta-del-'.$row] == 'yes' ){
                            if($tta['id'] > 0){
                                cc_train_acc_delete_row($tta['id']);
                            }
                        }else{
                            $tta['training_id'] = $post_id;
                            $tta['train_acc_id'] = absint( $_POST['tta-ta-'.$row] );
                            $tta['source'] = sanitize_text_field( $_POST['tta-src-'.$row] );
                            $tta['sequence'] = absint( $_POST['tta-ord-'.$row] );
                            $tta['hide'] = sanitize_text_field( $_POST['tta-hid-'.$row] );
                            if($tta['source'] == 'cust'){
                                $tta['title'] = sanitize_text_field( $_POST['tta-tit-'.$row] );
                                $tta['content'] = wp_kses_post( stripslashes( $_POST['tta-txt-'.$row] ) );
                            }
                            if($tta['train_acc_id'] > 0){
                                $tta_id = cc_train_acc_update($tta);
                            }
                        }
                    }
                    $row ++;
                }
                // and ensure that fresh defaults are not set up for this training
                update_post_meta($post_id, '_training_accordions_set', 'yes');
                */
    		}
    		else
    		{
    			return;
    		} // if($_POST['post_type'] == self::POST_TYPE && current_user_can('edit_post', $post_id))
    	} // END public function save_post($post_id)

    	public function admin_init() {			
    		// Add metaboxes
    		add_action('add_meta_boxes_'.self::POST_TYPE, array(&$this, 'add_meta_boxes'));
    	} // END public function admin_init()
			
    	public function add_meta_boxes() {
    		// Add this metabox to every selected post
            add_meta_box( 
                sprintf('recordings_modules_%s_section', self::POST_TYPE),
                sprintf('%s Modules', ucwords(str_replace("_", " ", self::POST_TYPE))),
                array(&$this, 'add_modules_meta_boxes'),
                self::POST_TYPE
            );
    		add_meta_box( 
    			sprintf('recordings_plugin_%s_section', self::POST_TYPE),
    			sprintf('%s Information', ucwords(str_replace("_", " ", self::POST_TYPE))),
    			array(&$this, 'add_inner_meta_boxes'),
    			self::POST_TYPE
    	    );
            add_meta_box( 'recording_plugin_training_accordions', 'Training Accordions', 'cc_train_acc_add_training_accordion_metabox', self::POST_TYPE, 'advanced', 'default' );
            add_meta_box( 'workshops_plugin_feedback', 'Feedback Questions', 'cc_feedback_training_metabox', self::POST_TYPE, 'advanced', 'default' );
            add_meta_box( 
                sprintf('recordings_viewers_%s_section', self::POST_TYPE),
                sprintf('%s Viewers', ucwords(str_replace("_", " ", self::POST_TYPE))),
                array(&$this, 'add_viewer_meta_boxes'),
                self::POST_TYPE
            );
            add_meta_box('recordings_plugin_subtitle_metabox', 'Recording Subtitle', array(&$this, 'add_subtitle_metabox'), self::POST_TYPE, 'extraordinary', 'high');
            add_meta_box( 'quiz_link', 'Quiz', array(&$this, 'add_quiz_metabox'), self::POST_TYPE, 'side', 'default' );
    	} // END public function add_meta_boxes()

        // after title ...
        public function reposition_subtitle_metabox(){
            # Get the globals:
            global $post, $wp_meta_boxes;
            if($post->post_type == 'recording'){
                # Output the "extraordinary" meta boxes:
                do_meta_boxes( get_current_screen(), 'extraordinary', $post );
                # Remove the initial "extraordinary" meta boxes:
                unset($wp_meta_boxes['post']['extraordinary']);
            }
        }

        public function add_subtitle_metabox($post){ ?>
            <input class="large-text" type="text" id="subtitle" name="subtitle" value="<?php echo @get_post_meta($post->ID, 'subtitle', true); ?>" />
        <?php }
	
        // mar 2025 changed to add static so that I can use this for courses too
        public static function add_quiz_metabox($post){
            $quiz_id = cc_quizzes_quiz_id($post->ID);
            if($quiz_id === NULL){
                echo '<p>Quiz not set for this recording yet.</p>';
            }else{
                $quiz_title = get_the_title($quiz_id);
                $url = get_edit_post_link($quiz_id);
                echo '<p><a href="'.esc_url($url).'">Edit '.$quiz_title.'</a>.</p>';
            }

            $quiz_override = get_post_meta( $post->ID, 'quiz_override', true );
            echo '
            <p>
                <label for="quiz_override">Quiz Override (will a certificate be issued without completing the quiz?):</label><br>
                <select name="quiz_override" id="quiz_override">
                    <option value="">No</option>
                    <option value="yes" '.selected( 'yes', $quiz_override, false ).'>Yes</option>
                </select>
            </p>';
        }

        public function add_modules_meta_boxes($post){ ?>
            <!-- <table> -->
                <?php for ($i=0; $i < 10; $i++) {
                    if( $i == 0 ){
                        $placeholder = "Sections of video removed in the edit in the format&#10;start time cut/add duration&#10;e.g.&#10;00:00:00 add 00:00:13 (adding 13 secs at the start)&#10;00:00:00 cut 00:49:07 (removing 49mins 7 secs at the start)&#10;00:56:38 cut 01:06:26 (1h 6m 26s removed at the 56m 38s point in the edited video)&#10;Etc.";
                    }else{
                        $placeholder = "";
                    }
                    $zoom_chat = cc_zoom_chat_get( $post->ID, $i );
                    if( $zoom_chat === NULL ){
                        $zoom_chat = cc_zoom_chat_empty();
                    }
                    ?>
                    <h4>Module <?php echo ($i + 1); ?></h4>
                    <div class="row">
                        <div class="col-4">
                            <label for="module_name_<?php echo $i; ?>">Module name</label><br>
                            <input type="text" class="widefat" id="module_name_<?php echo $i; ?>" name="module_name_<?php echo $i; ?>" value="<?php echo get_post_meta($post->ID, 'module_name_'.$i, true); ?>"><br>
                            <label for="module_vimeo_<?php echo $i; ?>">Vimeo ID</label><br>
                            <input type="text" class="widefat" id="module_vimeo_<?php echo $i; ?>" name="module_vimeo_<?php echo $i; ?>" value="<?php echo get_post_meta($post->ID, 'module_vimeo_'.$i, true); ?>"><br>
                            <?php echo resources_metabox_fields( $post->ID, $i, false ); ?>
                        </div>
                        <div class="col-4">
                            <label for=""><strong>Zoom chat file 1</strong></label><br>
                            <input type="file" id="zoom_chat_<?php echo $i; ?>"><br>
                            <label for=""><strong>Zoom chat file 2 (if needed)</strong></label><br>
                            <input type="file" id="zoom_chat2_<?php echo $i; ?>"><br>
                            <label for=""><strong>Uncut length of 1st video</strong> (if 2nd chat file)</label><br>
                            <input type="text" id="uncut_vid1_<?php echo $i; ?>" placeholder="hh:mm:ss"><br>
                            <label for=""><strong>Time gaps</strong></label><br>
                            <textarea class="w-100" id="zoom_gaps_<?php echo $i; ?>" placeholder="<?php echo $placeholder; ?>"><?php echo $zoom_chat['raw_gaps']; ?></textarea><br>
                            <a href="javascript:void(0)" class="zoom-chat-upload button button-secondary" data-recid="<?php echo $post->ID; ?>" data-module="<?php echo $i; ?>">Upload now</a><br>
                            <div id="zcu_msg_<?php echo $i; ?>" class="zoom-chat-upload-msg"></div>
                        </div>
                        <div class="col-4">
                            <?php if( $zoom_chat['chat'] <> '' ){ ?>
                                <label>Chat</label>
                                <div class="zoom-chat-show">
                                    <?php
                                    $chats = maybe_unserialize( $zoom_chat['chat'] );
                                    foreach ($chats as $chat) { ?>
                                        <p><?php echo $chat['time'].' '.$chat['who'].' '.$chat['msg']; ?></p>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <?php /*

                    <tr valign="top">
                        <th colspan="2">
                            <h4>Module <?php echo ($i + 1); ?></h4>
                        </th>
                        <th colspan="3"></th>
                    </tr>
                    <tr valign="top">
                        <th class="metabox_label_column">
                            <label for="module_name_<?php echo $i; ?>">Module name</label>
                        </th>
                        <td>
                            <input type="text" class="widefat" id="module_name_<?php echo $i; ?>" name="module_name_<?php echo $i; ?>" value="<?php echo get_post_meta($post->ID, 'module_name_'.$i, true); ?>">
                        </td>
                        <td rowspan="3">
                            <label for=""><strong>Zoom chat file</strong></label><br>
                            <input type="file" id="zoom_chat_<?php echo $i; ?>"><br>
                            <label for=""><strong>Time gaps</strong></label><br>
                            <textarea class="w-100" id="zoom_gaps_<?php echo $i; ?>" placeholder="<?php echo $placeholder; ?>"><?php echo $zoom_chat['raw_gaps']; ?></textarea><br>
                            <a href="javascript:void(0)" class="zoom-chat-upload button button-secondary" data-recid="<?php echo $post->ID; ?>" data-module="<?php echo $i; ?>">Upload now</a><br>
                            <div id="zcu_msg_<?php echo $i; ?>" class="zoom-chat-upload-msg"></div>
                        </td>
                        <td rowspan="3">
                            <?php if( $zoom_chat['chat'] <> '' ){ ?>
                                <div class="zoom-chat-show">
                                    <?php echo $zoom_chat['chat']; ?>
                                </div>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th class="metabox_label_column">
                            <label for="module_vimeo_<?php echo $i; ?>">Vimeo ID</label>
                        </th>
                        <td>
                            <input type="text" class="widefat" id="module_vimeo_<?php echo $i; ?>" name="module_vimeo_<?php echo $i; ?>" value="<?php echo get_post_meta($post->ID, 'module_vimeo_'.$i, true); ?>">
                        </td>
                    </tr>

                    <?php echo resources_metabox_fields($post->ID, $i); ?>

                    <!-- 
                    <tr valign="top">
                        <th class="metabox_label_column">
                            <label for="module_docs_<?php echo $i; ?>">Document URL</label>
                        </th>
                        <td>
                            <input type="text" class="widefat" id="module_docs_<?php echo $i; ?>" name="module_docs_<?php echo $i; ?>" value="<?php echo get_post_meta($post->ID, 'module_docs_'.$i, true); ?>">
                        </td>
                    </tr>
                    -->

                    */ ?>

                <?php } ?>
            </table>
        <?php }

		public function add_inner_meta_boxes($post)	{ ?>
            <table>
                <!--
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="presenter">Presenter</label>
                    </th>
                    <td>
                        <i class="fa-solid fa-user fa-fw"></i>
                        <input type="text" id="presenter" name="presenter" value="<?php echo @get_post_meta($post->ID, 'presenter', true); ?>" />
                    </td>
                </tr>
                -->
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="duration">Duration</label>
                    </th>
                    <td>
                        <i class="fa-solid fa-hourglass-half fa-fw"></i>
                        <input type="text" id="duration" name="duration" value="<?php echo @get_post_meta($post->ID, 'duration', true); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="availability">Availability</label>
                    </th>
                    <td>
                        <i class="fa-solid fa-video fa-fw"></i>
                        <input type="text" id="availability" name="availability" value="<?php echo @get_post_meta($post->ID, 'availability', true); ?>" placeholder="eg recording available for 6 months" />
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="category">Category</label>
                    </th>
                    <td>
                        <i class="fa-solid fa-folder fa-fw"></i>
                        <input type="text" id="category" name="category" value="<?php echo @get_post_meta($post->ID, 'category', true); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="who_for">Audience (who's it for?)</label>
                    </th>
                    <td>
                        <i class="fa-solid fa-award fa-fw"></i>
                        <input type="text" id="who_for" name="who_for" class="regular-text" value="<?php echo @get_post_meta($post->ID, 'who_for', true); ?>"><br>
                        Add small text with ... "Normal text [small]small text[/small] more normal text"
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="block_nlft">Block NLFT registrations?</label>
                    </th>
                    <td>
                        <select name="block_nlft" id="block_nlft" class="">
                            <option value="">No</option>
                            <option value="yes" <?php selected( 'yes', get_post_meta( $post->ID, 'block_nlft', true ) ); ?>>Yes</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="block_cnwl">Block CNWL registrations?</label>
                    </th>
                    <td>
                        <select name="block_cnwl" id="block_cnwl" class="">
                            <option value="">No</option>
                            <option value="yes" <?php selected( 'yes', get_post_meta( $post->ID, 'block_cnwl', true ) ); ?>>Yes</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label>The recording</label>
                    </th>
                    <td>
                        <div class="row">
                            <div class="col-4">
                                <label for="registration_link_id">OLD: Recording ID (Oli's Recordings)</label>
                                <input class="widefat" type="text" id="registration_link_id" name="registration_link_id" value="<?php echo @get_post_meta($post->ID, 'registration_link_id', true); ?>" />
                                <label for="registration_link">OLD: Recording/registration URL</label>
                                <input class="widefat" type="text" id="registration_link" name="registration_link" value="<?php echo @get_post_meta($post->ID, 'registration_link', true); ?>" />
                                <label for="recording_url">OLD: The URL of the recording (the mp4 file)</label>
                                <input class="widefat" type="text" id="recording_url" name="recording_url" value="<?php echo @get_post_meta($post->ID, 'recording_url', true); ?>" />
                                <label for="vimeo_id">The Vimeo ID:</label>
                                <input class="widefat" type="text" id="vimeo_id" name="vimeo_id" value="<?php echo @get_post_meta($post->ID, 'vimeo_id', true); ?>" />
                            </div>
                            <div class="col-4">
                                <?php
                                $zoom_chat = cc_zoom_chat_get( $post->ID, 9999 );
                                if( $zoom_chat === NULL ){
                                    $zoom_chat = cc_zoom_chat_empty();
                                }
                                ?>
                                <label for=""><strong>Zoom chat file 1</strong></label><br>
                                <input type="file" id="zoom_chat_9999"><br>
                                <label for=""><strong>Zoom chat file 2 (if needed)</strong></label><br>
                                <input type="file" id="zoom_chat2_9999"><br>
                                <label for=""><strong>Uncut length of 1st video</strong> (if 2nd chat file)</label><br>
                                <input type="text" id="uncut_vid1_9999" placeholder="hh:mm:ss"><br>
                                <label for=""><strong>Time gaps</strong></label><br>
                                <textarea class="w-100" id="zoom_gaps_9999" placeholder="Sections of video removed in the edit in the format&#10;start time cut/add duration&#10;e.g.&#10;00:00:00 add 00:00:13 (adding 13 secs at the start)&#10;00:00:00 cut 00:49:07 (removing 49mins 7 secs at the start)&#10;00:56:38 cut 01:06:26 (1h 6m 26s removed at the 56m 38s point in the edited video)&#10;Etc."><?php echo $zoom_chat['raw_gaps']; ?></textarea><br>
                                <a href="javascript:void(0)" class="zoom-chat-upload button button-secondary" data-recid="<?php echo $post->ID; ?>" data-module="9999">Upload now</a><br>
                                <div id="zcu_msg_9999" class="zoom-chat-upload-msg"></div>
                            </div>
                            <div class="col-4">
                                <?php if( $zoom_chat['chat'] <> '' ){ ?>
                                    <label>Chat</label>
                                    <div class="zoom-chat-show">
                                        <?php
                                        $chats = maybe_unserialize( $zoom_chat['chat'] );
                                        foreach ($chats as $chat) { ?>
                                            <p><?php echo $chat['time'].' '.$chat['who'].' '.$chat['msg']; ?></p>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>&nbsp;</th>
                    <td><hr></td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label>Pricing (excl VAT)</label>
                    </th>
                    <td>
                        <table>
                            <tr>
                                <td>
                                    <label for="recording_price">&pound; GBP</label>
                                    <input class="" type="text" id="recording_price" name="recording_price" value="<?php echo @get_post_meta($post->ID, 'recording_price', true); ?>" />
                                </td>
                                <td>
                                    <label for="recording_price_aud">$ AUD</label>
                                    <input class="" type="text" id="recording_price_aud" name="recording_price_aud" value="<?php echo @get_post_meta($post->ID, 'recording_price_aud', true); ?>" />
                                </td>
                                <td>
                                    <label for="recording_price_usd">$ USD</label>
                                    <input class="" type="text" id="recording_price_usd" name="recording_price_usd" value="<?php echo @get_post_meta($post->ID, 'recording_price_usd', true); ?>" />
                                </td>
                                <td>
                                    <label for="recording_price_eur">€ Euro</label>
                                    <input class="" type="text" id="recording_price_eur" name="recording_price_eur" value="<?php echo @get_post_meta($post->ID, 'recording_price_eur', true); ?>" />
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <br>
                        <label>Early-bird discount</label>
                    </th>
                    <td>
                        <table>
                            <tr>
                                <td>
                                    <label for="earlybird_discount">Discount % (date must also be set)</label><br>
                                    <input type="number" id="earlybird_discount" name="earlybird_discount" class="regular-text code" min="0" step="0.0001" value="<?php echo @get_post_meta($post->ID, 'earlybird_discount', true); ?>" /> %
                                </td>
                                <td>
                                    <label for="earlybird_expiry_date">Early-bird expiry (at the end of the day)</label><br>
                                    <input type="text" id="earlybird_expiry_date" name="earlybird_expiry_date" class="regular-text code" value="<?php echo @get_post_meta($post->ID, 'earlybird_expiry_date', true); ?>" placeholder="dd/mm/yyyy" />
                                </td>
                                <td>
                                    <label for="earlybird_name">Early-bird name (if not set will be "Early-bird")</label><br>
                                    <input type="text" id="earlybird_name" name="earlybird_name" class="regular-text code" value="<?php echo @get_post_meta($post->ID, 'earlybird_name', true); ?>" placeholder="eg Discount" />
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="student_discount">Student discount %</label>
                    </th>
                    <td>
                        <input type="number" id="student_discount" name="student_discount" class="regular-text code" min="0" step="0.0001" value="<?php echo @get_post_meta($post->ID, 'student_discount', true); ?>" /> %
                    </td>
                </tr>
                <tr valign="top">
                    <?php echo cc_upsells_backend_field( $post->ID ); ?>
                </tr>
                <tr>
                    <th>Xero</th>
                    <td>
                        <label for="">Xero tracking category (automatically generated)</label>
                        <input type="text" class="regular-text" value="<?php echo get_post_meta( $post->ID, '_xero_tracking_code', true ); ?>" disabled>
                    </td>
                </tr>
                <tr>
                    <th>&nbsp;</th>
                    <td><hr></td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="recording_for_sale">Available for purchase?</label>
                    </th>
                    <td>
                        <select name="recording_for_sale" id="recording_for_sale" class="widefat">
                            <option value="">Available to purchase</option>
                            <option value="closed" <?php selected('closed', get_post_meta($post->ID, 'recording_for_sale', true)); ?>>Closed for purchase</option>
                            <option value="public" <?php selected('public', get_post_meta($post->ID, 'recording_for_sale', true)); ?>>No purchase necessary</option>
                            <option value="unlisted" <?php selected('unlisted', get_post_meta($post->ID, 'recording_for_sale', true)); ?>>Available but unlisted</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="mailster_list">Newsletter list:</label>
                    </th>
                    <td>
                        <?php
                        $mailster_list = get_post_meta($post->ID, 'mailster_list', true);
                        $lists = mailster( 'lists' )->get();
                        ?>
                        <select name="mailster_list" id="mailster_list">
                            <option value="">Will be set up when the first person registers</option>
                            <?php foreach ($lists as $list) { ?>
                                <option value="<?php echo $list->ID; ?>" <?php selected($list->ID, $mailster_list); ?>><?php echo $list->name; ?></option>
                            <?php } ?>
                        </select>
                        <br>Automatically generated but you can override it here
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="">Recording expiry:</label>
                    </th>
                    <td>
                        <?php $recording_expiry = rpm_cc_recordings_get_expiry($post->ID, true); ?>
                        <select name="recording_expiry_num" id="recording_expiry_num" class="recording-expiry-settings">
                            <option value="">Unlimited</option>
                            <?php
                            for ($i=1; $i < 32; $i++) { ?>
                                <option value="<?php echo $i; ?>" <?php selected($i, $recording_expiry['num']); ?>><?php echo $i; ?></option>
                            <?php } ?>
                        </select>
                        <select name="recording_expiry_unit" id="recording_expiry_unit" class="recording-expiry-settings">
                            <option value="">Unlimited</option>
                            <option value="days" <?php selected('days', $recording_expiry['unit']); ?>>Days</option>
                            <option value="weeks" <?php selected('weeks', $recording_expiry['unit']); ?>>Weeks</option>
                            <option value="months" <?php selected('months', $recording_expiry['unit']); ?>>Months</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="recording_expiry_update">Reset recording expiry times</label>
                    </th>
                    <td>
                        <?php
                        // JS for recording expiry stuff is in the js/recordings-admin.js file
                        ?>
                        <div class="recording-expiry-wrap">
                            <p class="recording-expiry-msg">
                                Recording expiry last set to: <strong><?php echo $recording_expiry['expiry_text'].'</strong>, set on: <strong>'.$recording_expiry['when_set_text'].'</strong>, last reset: <strong>'.$recording_expiry['last_reset_text']; ?></strong>
                            </p>
                            <p><strong>Recording expiry reset:</strong> Start and end dates relate to the date that somebody was given access to a recording (the access date). Both dates are inclusive and both dates are optional. If set, the new expiry date will be used instead of adding the expiry time period above to a person's access date. Viewers where the expiry time has been manually overridden will be excluded from the update. After you click the button, the expiry time update will be started in the background. Update progress will be shown here.</p>
                            <p><strong>Access date range:</strong><br>
                                <label for="rec-exp-upd-startdate">Start date (optional):</label>
                                <input type="text" id="rec-exp-upd-startdate" placeholder="dd/mm/yyyy">
                                <label for="rec-exp-upd-enddate">End date (optional):</label>
                                <input type="text" id="rec-exp-upd-enddate" placeholder="dd/mm/yyyy">
                            </p>
                            <p><strong>Expiry actions:</strong><br>
                                <label for="rec-exp-upd-expirydate">Set new expiry date (optional):</label>
                                <input type="text" id="rec-exp-upd-expirydate" placeholder="dd/mm/yyyy">
                                <label for="rec-exp-upd-email">Send Expiry Update email?
                                    <input type="checkbox" id="rec-exp-upd-email" value="yes">
                                </label>
                            </p>
                            <p class="recording-expiry-btn-wrap">
                                <?php if($recording_expiry['reset_status'] == '' || $recording_expiry['reset_status'] == 'complete'){ ?>
                                    <a href="javascript:void(0)" id="recording-expiry-update" class="recording-expiry-update button button-primary" data-recid="<?php echo $post->ID; ?>" data-confirm="<?php echo $recording_expiry['expiry_text']; ?>" data-status="<?php echo $recording_expiry['reset_status']; ?>">Start update</a>
                                <?php }else{ ?>
                                    <a href="javascript:void(0)" id="recording-expiry-update" class="recording-expiry-update button button-primary disabled" data-recid="<?php echo $post->ID; ?>" data-confirm="<?php echo $recording_expiry['expiry_text']; ?>" data-status="<?php echo $recording_expiry['reset_status']; ?>"><i class="fa fa-spinner fa-spin"></i></a>
                                <?php } ?>
                                <span id="recording-expiry-update-msg" class="recording-expiry-update-msg"></span>
                            </p>
                            <div id="recording-expiry-progress" class="recording-expiry-progress">
                                <div id="recording-expiry-progress-bar" class="recording-expiry-progress-bar"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            <h3>Content to be shown on the page below the recording:</h3>
            <?php
            $viewer_content = get_post_meta($post->ID, 'viewer_content', true);
            wp_editor($viewer_content, 'viewer_content', array('media_buttons' => true));
            ?>
            <h3>Content to be included in the registration email (if needed):</h3>
            <p><strong>IMPORTANT!</strong> Leave ONE blank line after the message so that it separates it from the other text in the email.</p>
            <?php
            $registration_message = get_post_meta($post->ID, 'registration_message', true);
            wp_editor($registration_message, 'registration_message', array('textarea_rows' => 4));
            ?>
            <h3>Resources</h3>
            <table>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="">Slides</label>
                    </th>
                    <td>
                        <input type="text" name="workshop_slides" id="workshop_slides" class="regular-text" value="<?php echo @get_post_meta($post->ID, 'workshop_slides', true); ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="">Resources</label>
                    </th>
                    <td>
                        <input type="text" name="workshop_resources" id="workshop_resources" class="regular-text" value="<?php echo @get_post_meta($post->ID, 'workshop_resources', true); ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="">Certificates</label>
                    </th>
                    <td style="width:100%;">
                        <div class="row">
                            <div class="col-2">
                                <label for="ce_credits"><i class="fa-solid fa-graduation-cap"></i> CE Credits:</label>
                                <input type="number" id="ce_credits" name="ce_credits" class="form-control" value="<?php echo (float) @get_post_meta($post->ID, 'ce_credits', true); ?>" min="0">
                            </div>
                            <div class="col-6">
                                <label for="workshop_dates">Original workshop date(s) for certificate</label>
                                <input type="text" name="workshop_dates" id="workshop_dates" class="form-control" value="<?php echo @get_post_meta($post->ID, 'workshop_dates', true); ?>">
                                Any format you like. If left blank, will display "by Contextual Consulting" instead
                            </div>
                            <div class="col-4">
                                <label>Original workshop:</label>
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
                                <input type="hidden" name="workshop_certificate" id="workshop_certificate" class="form-control" value="<?php echo @get_post_meta($post->ID, 'workshop_certificate', true); ?>">
                                <label for="cert_cc">CC Cert?</label>
                                <select name="cert_cc" id="cert_cc" class="form-control">
                                    <option value="no" <?php selected( 'no', $cert_cc ); ?>>No</option>
                                    <option value="yes" <?php selected( 'yes', $cert_cc ); ?>>Yes</option>
                                </select>
                            </div>
                            <div class="col-2">
                                <label for="cert_apa">APA Cert?</label>
                                <select name="cert_apa" id="cert_apa" class="form-control">
                                    <option value="no" <?php selected( 'no', $cert_apa ); ?>>No</option>
                                    <option value="yes" <?php selected( 'yes', $cert_apa ); ?>>Yes</option>
                                </select>
                            </div>
                            <div class="col-2">
                                <label for="cert_bacb">BACB Cert?</label>
                                <select name="cert_bacb" id="cert_bacb" class="form-control">
                                    <option value="no" <?php selected( 'no', $cert_bacb ); ?>>No</option>
                                    <option value="yes" <?php selected( 'yes', $cert_bacb ); ?>>Yes</option>
                                </select>
                            </div>
                            <div class="col-2">
                                <label for="cert_nbcc">NBCC Cert?</label>
                                <select name="cert_nbcc" id="cert_nbcc" class="form-control">
                                    <option value="no" <?php selected( 'no', $cert_nbcc ); ?>>No</option>
                                    <option value="yes" <?php selected( 'yes', $cert_nbcc ); ?>>Yes</option>
                                </select>
                            </div>
                            <div class="col-2">
                                <label for="cert_icf">ICF Cert?</label>
                                <select name="cert_icf" id="cert_icf" class="form-control">
                                    <option value="no" <?php selected( 'no', $cert_icf ); ?>>No</option>
                                    <option value="yes" <?php selected( 'yes', $cert_icf ); ?>>Yes</option>
                                </select>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="">Feedback</label>
                    </th>
                    <td>
                        <input type="text" name="workshop_feedback" id="workshop_feedback" class="regular-text" value="<?php echo @get_post_meta($post->ID, 'workshop_feedback', true); ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="">LinkedIn</label>
                    </th>
                    <td>
                        <input type="text" name="workshop_linkedin" id="workshop_linkedin" class="regular-text" value="<?php echo @get_post_meta($post->ID, 'workshop_linkedin', true); ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th class="metabox_label_column">
                        <label for="">Joining info</label>
                    </th>
                    <td>
                        <textarea name="workshop_joining" id="workshop_joining" cols="100" rows="4"><?php echo @get_post_meta($post->ID, 'workshop_joining', true); ?></textarea>
                    </td>
                </tr>

                <?php echo resources_metabox_fields($post->ID); ?>
                
            </table>
            <?php
        } // END public function add_inner_meta_boxes($post)

        public function add_viewer_meta_boxes($post){ ?>
            <h3>Recent viewers</h3>
            <?php $view_chg = get_post_meta($post->ID, '_view_chg', true);
            if(!is_array($view_chg)){
                $view_chg = array(
                    'view_order' => '',
                    'stdt' => '',
                    'endt' => '',
                );
            }
            ?>
            <form action="">
                <table>
                    <tr>
                        <td>
                            <label for="view_order">Order:</label><br>
                            <select name="view_order" id="view_order">
                                <option value="">Latest viewers first</option>
                                <option value="ad" <?php selected('ad', $view_chg['view_order']); ?>>Latest Access date first</option>
                            </select>
                        </td>
                        <td>
                            <label for="stdt">First access date to show</label><br>
                            <input type="text" name="stdt" id="stdt" value="<?php echo $view_chg['stdt']; ?>" placeholder= "dd/mm/yyyy">
                        </td>
                        <td>
                            <label for="endt">Last access date to show</label><br>
                            <input type="text" name="endt" id="endt" value="<?php echo $view_chg['endt']; ?>" placeholder= "dd/mm/yyyy">
                        </td>
                        <td>
                            <br>
                            <button type="submit" class="button button-secondary" id="view_chg_sub" name="view_chg_sub">Reload</button>
                        </td>
                    </tr>
                </table>
            </form>
            <br>
            <?php
            $args = array(
                'meta_key' => 'cc_rec_wshop_'.$post->ID,
            );
            $viewers = get_users($args);
            $recent_viewers = array();
            if($view_chg['stdt'] == ''){
                $check_start_date = false;
                $start_date = 0;
            }else{
                $check_start_date = true;
                $date = date_create_from_format('d/m/Y', $view_chg['stdt']);
                $start_date = date_format($date, 'Y-m-d').' 00:00:00';
            }
            if($view_chg['endt'] == ''){
                $check_end_date = false;
                $end_date = 0;
            }else{
                $check_end_date = true;
                $date = date_create_from_format('d/m/Y', $view_chg['endt']);
                $end_date = date_format($date, 'Y-m-d').' 23:59:59';
            }
            foreach ($viewers as $user) {
                // $recording_meta = get_user_meta($user->ID, 'cc_rec_wshop_'.$post->ID, true);
                $recording_meta = get_recording_meta( $user->ID, $post->ID );
                if($recording_meta){
                    if((!$check_start_date || $start_date < $recording_meta['access_time']) && (!$check_end_date || $end_date > $recording_meta['access_time']) ){
                        if($recording_meta['last_viewed'] == ''){
                            $last_viewed = 0;
                        }else{
                            $last_viewed = $recording_meta['last_viewed'];
                        }
                        if(isset($recording_meta['amount'])){
                            $amount = (float) $recording_meta['amount'];
                        }else{
                            $amount = 0;
                        }
                        if(isset($recording_meta['refund_amount'])){
                            $amount = $amount - $recording_meta['amount'];
                        }
                        $recent_viewers[] = array(
                            'access_time' => $recording_meta['access_time'],
                            'closed_time' => $recording_meta['closed_time'],
                            'last_viewed' => $last_viewed,
                            'email' => $user->user_email,
                            'access_type' => $recording_meta['access_type'],
                            'amount' => $amount,
                            'currency' => (isset($recording_meta['currency'])) ? $recording_meta['currency'] : 'GBP',
                        );
                    }
                }
            }
            if($view_chg['view_order'] == ''){
                usort($recent_viewers, function($a, $b) {
                    if($a['last_viewed'] == $b['last_viewed']) return 0;
                    if($a['last_viewed'] > $b['last_viewed']) return -1;
                    return 1;
                });
            }else{
                usort($recent_viewers, function($a, $b) {
                    if($a['access_time'] == $b['access_time']) return 0;
                    if($a['access_time'] > $b['access_time']) return -1;
                    return 1;
                });
            }
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Access from</th>
                        <th>Access to</th>
                        <th>Last Viewed</th>
                        <th>Access Type</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $viewers_count = 0;
                    $viewers_paid = 0;
                    $viewers_paid_aud = 0;
                    $viewers_paid_usd = 0;
                    foreach ($recent_viewers as $viewer) {
                        $viewers_count ++;
                        ?>
                        <tr>
                            <td>
                                <?php if($viewer['access_time'] == 0){
                                    echo 'never';
                                }else{
                                    echo date('d/m/Y H:i:s', strtotime($viewer['access_time']));
                                } ?>
                            </td>
                            <td>
                                <?php if($viewer['closed_time'] == 0){
                                    echo '-';
                                }else{
                                    echo date('d/m/Y H:i:s', strtotime($viewer['closed_time']));
                                } ?>
                            </td>
                            <td>
                                <?php if($viewer['last_viewed'] == 0){
                                    echo 'never';
                                }else{
                                    echo date('d/m/Y H:i:s', strtotime($viewer['last_viewed']));
                                } ?>
                            </td>
                            <td>
                                <?php if($viewer['access_type'] == 'paid'){
                                    if($viewer['currency'] == 'AUD'){
                                        echo 'AU$';
                                        $viewers_paid_aud = $viewers_paid_aud + $viewer['amount'];
                                    }elseif($viewer['currency'] == 'USD'){
                                        echo 'US$';
                                        $viewers_paid_usd = $viewers_paid_usd + $viewer['amount'];
                                    }else{
                                        echo '&pound;';
                                        $viewers_paid = $viewers_paid + $viewer['amount'];
                                    }
                                    echo number_format($viewer['amount'], 2, '.', '');
                                }else{
                                    echo $viewer['access_type'];
                                } ?>
                            </td>
                            <td>
                                <?php echo $viewer['email']; ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">
                            <?php echo $viewers_count.' viewers'; ?>
                        </th>
                        <th>
                            <?php
                            echo '&pound;'.number_format($viewers_paid, 2, '.', '').'<br>';
                            echo 'AU$'.number_format($viewers_paid_aud, 2, '.', '').'<br>';
                            echo 'US$'.number_format($viewers_paid_usd, 2, '.', '');
                            ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        <?php }

	} // END class Recording_Post_Type_Template
} // END if(!class_exists('Recording_Post_Type_Template'))
