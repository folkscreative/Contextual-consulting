<?php
/**
 * Training Accordions
 * Added v1.9
 */

// Register Custom Post Type
add_action( 'init', 'cc_train_acc_cpt_register', 0 );
function cc_train_acc_cpt_register() {
	$labels = array(
		'name'                  => 'Training Accordions',
		'singular_name'         => 'Training Accordion',
		'menu_name'             => 'Training Accordions',
		'name_admin_bar'        => 'Training Accordion',
		'archives'              => 'Item Archives',
		'attributes'            => 'Item Attributes',
		'parent_item_colon'     => 'Parent Item:',
		'all_items'             => 'All Items',
		'add_new_item'          => 'Add New Item',
		'add_new'               => 'Add New',
		'new_item'              => 'New Item',
		'edit_item'             => 'Edit Item',
		'update_item'           => 'Update Item',
		'view_item'             => 'View Item',
		'view_items'            => 'View Items',
		'search_items'          => 'Search Item',
		'not_found'             => 'Not found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Insert into item',
		'uploaded_to_this_item' => 'Uploaded to this item',
		'items_list'            => 'Items list',
		'items_list_navigation' => 'Items list navigation',
		'filter_items_list'     => 'Filter items list',
	);
	$args = array(
		'label'                 => 'Accordion',
		'description'           => 'Training accordion',
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 26,
		'menu_icon'             => 'dashicons-welcome-learn-more',
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
	);
	register_post_type( 'training_accordion', $args );
}

// get a training accordion
function cc_train_acc_get( $tta_id ){
	global $wpdb;
	$table_name = $wpdb->prefix.'train_train_accordions';
	$sql = "SELECT * FROM $table_name WHERE id = $tta_id";
	return $wpdb->get_row($sql, ARRAY_A);
}

// get all training accordions
// returns an array of post objects
function cc_train_acc_get_all(){
	$args = array(
		'numberposts' => -1,
		'post_type' => 'training_accordion',
		'orderby' => 'title',
		'order' => 'ASC',
	);
	return get_posts($args);
}

// create/update the training training accordions table
// ie the table of specific training accordions for all trainings
add_action('init', 'cc_train_acc_tta_table_update');
function cc_train_acc_tta_table_update(){
	global $wpdb;
	$tta_db_ver = 1;
	$installed_table_ver = get_option('tta_db_ver');
	if($installed_table_ver <> $tta_db_ver){
		$table_name = $wpdb->prefix.'train_train_accordions';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			training_id mediumint(9) NOT NULL,
			train_acc_id mediumint(9) NOT NULL,
			source varchar(20) NOT NULL,
			sequence mediumint(9) NOT NULL,
			hide varchar(10) NOT NULL,
			title varchar(255) NOT NULL,
			content text NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$response = dbDelta($sql);
		update_option('tta_db_ver', $tta_db_ver);
	}
}

// empty tta
function cc_train_acc_empty_tta(){
	return array(
		'id' => 0,
		'training_id' => 0,
		'train_acc_id' => 0,
		'source' => 'std',
		'sequence' => 0,
		'hide' => 'no',
		'title' => '',
		'content' => '',
	);
}

// update format
function cc_train_acc_update_format(){
	return array(
		'%d', // 'training_id' => 0,
		'%d', // 'train_acc_id' => 0,
		'%s', // 'source' => 'std',
		'%d', // 'sequence' => 0,
		'%s', // 'hide' => 'no',
		'%s', // 'title' => '',
		'%s', // 'content' => '',
	);
}

// get all training accordions for this training
// returns an array ... which maybe empty
// returns the default accordions if it's a new training with no accordions set yet
function cc_train_acc_tta_get_all($training_id, $include_hidden=true){
	global $wpdb;
	$table_name = $wpdb->prefix.'train_train_accordions';
	if(!$include_hidden){
		$where_and = "AND hide = 'no'";
	}else{
		$where_and = '';
	}
	$sql = "SELECT * FROM $table_name WHERE `training_id` = $training_id $where_and ORDER BY sequence ASC";
	$training_accordions = $wpdb->get_results($sql, ARRAY_A);
	if(empty($training_accordions) && empty(get_post_meta($training_id, '_training_accordions_set')) ){
		// set up the default training accordions
		if(course_training_type($training_id) == 'recording'){
			$meta_key = '_train_acc_default_rec';
		}else{
			$meta_key = '_train_acc_default_wks';
		}
		$args = array(
			'numberposts' => -1,
			'post_type' => 'training_accordion',
			'orderby' => 'title',
			'order' => 'ASC',
			'meta_key' => $meta_key,
			'meta_value' => 'yes',
		);
		$defaults = get_posts($args);
		// now format and save them as train_train_accordions
		$default_ttas = array();
		foreach ($defaults as $training_accordion) {
			$tta = cc_train_acc_empty_tta();
			$tta['training_id'] = $training_id;
			$tta['train_acc_id'] = $training_accordion->ID;
			$tta['id'] = cc_train_acc_update($tta);
			$default_ttas[] = $tta;
		}
		return $default_ttas;
	}
	return $training_accordions;
}

// update/insert a tta
function cc_train_acc_update($tta){
	global $wpdb;
	$table_name = $wpdb->prefix.'train_train_accordions';
	$id = $tta['id'];
	unset($tta['id']);
	if($id > 0){
		// update
		$wpdb->update( $table_name, $tta, array('id' => $id), cc_train_acc_update_format() );
		return $id;
	}else{
		// insert
		$wpdb->insert( $table_name, $tta, cc_train_acc_update_format() );
		return $wpdb->insert_id;
	}
}

// delete a tta
// It returns the number of rows updated, or false on error
function cc_train_acc_delete_row($id){
	global $wpdb;
	$table_name = $wpdb->prefix.'train_train_accordions';
	return $wpdb->delete( $table_name, array('id' => $id) );
}

// returns all training accordions as options for a select clause
function cc_train_acc_all_options($selected_id){
	$html = '';
	if($selected_id == 0){
		$html .= '<option value="">Select if required ...</option>';
	}
	foreach (cc_train_acc_get_all() as $train_acc) {
		$html .= '<option value="'.$train_acc->ID.'" '.selected($train_acc->ID, $selected_id, false).'>'.$train_acc->post_title.'</option>';
	}
	// and a custom one
	$html .= '<option value="999999" '.selected('999999', $selected_id, false).'>Custom</option>';
	return $html;
}

// returns the selected training accordion title
function cc_train_acc_sel_title( $selected_id ){
	if( $selected_id == '' || $selected_id == 0 ){
		return '';
	}
	if( $selected_id == 999999 ){
		return 'Custom';
	}
	foreach (cc_train_acc_get_all() as $train_acc) {
		if( $train_acc->ID == $selected_id ){
			return $train_acc->post_title;
		}
	}
	return 'Unknown: '.$selected_id;
}

// return appropriate options for a select clause to offer source
function cc_train_acc_source_options($source){
	$html = '';
	if($source == ''){
		$source = 'std';
	}
	$html .= '<option value="std" '.selected('std', $source, false).'>Standard text</option>';
	$html .= '<option value="cust" '.selected('cust', $source, false).'>Custom text</option>';
	return $html;
}

// return a tidy source
function cc_train_acc_source_tidy( $source ){
	switch ($source) {
		case 'std':
			return 'Standard text';
			break;
		case 'cust':
			return 'Custom text';
			break;
		default:
			return 'Unknown';
			break;
	}
}

// return appropriate options for a select clause to offer hide/show
function cc_train_acc_hide_options($hide){
	$html = '';
	if($hide == ''){
		$hide = 'no';
	}
	$html .= '<option value="no" '.selected('no', $hide, false).'>Show</option>';
	$html .= '<option value="yes" '.selected('yes', $hide, false).'>Hide</option>';
	return $html;
}

// return a tidy hide
function cc_train_acc_hide_tidy( $hide ){
	switch ($hide) {
		case '':
		case 'no':
			return 'Show';
			break;
		case 'yes':
			return 'Hide';
			break;
		default:
			return $hide;
			break;
	}
}


// get the content of a training accordion for the admin pages
add_action('wp_ajax_get_training_accordion', 'cc_train_acc_get_admin_ajax');
function cc_train_acc_get_admin_ajax(){
	$response = array(
		'status' => 'error',
		'title' => '',
		'content' => '',
	);
	$training_id = absint( $_POST['training_id'] );
	$train_acc_id = absint( $_POST['train_acc_id'] );
	$tta_id = absint( $_POST['tta_id'] );
	$result = cc_train_acc_get_content($training_id, $train_acc_id, $tta_id);
	$response['status'] = 'ok';
	$response['title'] = $result['title'];
	$response['content'] = $result['content'];
	// wp_send_json_success( $response );
   	echo json_encode($response);
	die();
}

// get the appropriate training accordion content
function cc_train_acc_get_content($training_id, $train_acc_id, $tta){
	global $wpdb;
	$response = array(
		'title' => '',
		'content' => '',
	);
	$tta_table = $wpdb->prefix.'train_train_accordions';
	// look for this tta first
	if($tta > 0){
		$sql = "SELECT * FROM $tta_table WHERE id = $tta AND training_id = $training_id AND train_acc_id = $train_acc_id LIMIT 1";
		$tta_row = $wpdb->get_row($sql, ARRAY_A);
		if($tta_row !== NULL){
			if($tta_row['source'] == 'cust'){
				$response['title'] = $tta_row['title'];
				$response['content'] = $tta_row['content'];
				return $response;
			}
		}
	}
	// if it's custom, return blanks
	if($train_acc_id == '999999'){
		return $response;
	}
	/*
	// is this training accordion used for this training?
	$sql = "SELECT * FROM $tta_table WHERE training_id = $training_id AND train_acc_id = $train_acc_id";
	$tta_rows = $wpdb->get_results($sql, ARRAY_A);
	// if multiple rows, we will not know which to use, so don't use any!
	if( count($tta_rows) == 1 ){
		if($tta_rows[0]['source'] == 'cust'){
			$response['title'] = $tta_rows[0]['title'];
			$response['content'] = $tta_rows[0]['content'];
			return $response;
		}
	}
	*/
	// let's get the original 
	$response['title'] = get_the_title($train_acc_id);
	$response['content'] = get_the_content( null, false,  $train_acc_id );
	return $response;
}

// for the modal on the training pages we need to include the editor
// switched from recording to course
add_action('admin_enqueue_scripts', 'cc_train_acc_admin_enqueues');
function cc_train_acc_admin_enqueues(){
	// Check that we are on the right screen
	$screen_id = get_current_screen()->id;
	if ( $screen_id == 'workshop' || $screen_id == 'course' ) {
		// Enqueue the editor so that we can create a wp_editor thingy
		wp_enqueue_editor();
	}
}

// we'll put the thickbox content form into the admin footer
// Fired on post edit page
// add_filter('admin_footer-post.php', 'cc_train_acc_modal_content');
/*
function cc_train_acc_modal_content(){ ?> 
    <div id="tta-content-edit-modal" class="tta-content-edit-modal" style="display:none;" >
    	<div class="tta-content-edit-modal-outer">
	    	<div class="tta-content-edit-modal-inner">
	    		<h2 class="tta-modal-header">Edit Content</h2>
	    		<form action="">
	    			<input type="hidden" id="tta-modal-row">
	    			<div class="tta-field-wrap">
	    				<label for="tta-modal-title" class="tta-modal-label">Title</label><br>
	    				<input type="text" id="tta-modal-title" class="tta-modal-title">
	    			</div>
	    			<div class="tta-field-wrap tta-modal-text-wrap">
	    				<textarea cols="30" rows="25" id="tta-modal-text"></textarea>
	    				<?php // this will be replaced by an editor in JS ?>
	    			</div>
	    			<div class="tta-field-wrap">
	    				<div class="tta-half-col">
	    					<a href="javascript:void(0);" id="tta-modal-close">Close</a>
	    				</div>
	    				<div class="tta-half-col tta-save-wrap">
	    					<a href="javascript:void(0);" id="tta-save-btn" class="button-primary tta-save-btn">Save</a>
	    				</div>
	    			</div>
	    		</form>
	    	</div>
    	</div>
	</div>
	<?php     
}
*/

// show the trainiig accordions on the training page
function cc_train_acc_accordions($training_id){
	$html = '';
	$ttas = cc_train_acc_tta_get_all($training_id, false);
	if( count($ttas) > 0 ){
		$html .= '<div class="training-accordions-wrap">';
		$html .= '<div class="accordion" id="train-acc-'.$training_id.'">';
		$acc_item = 0;
		foreach ($ttas as $tta) {
			if($tta['source'] == 'std'){
				$title = get_the_title($tta['train_acc_id']);
				$body = wpautop( do_shortcode( get_the_content(null, false, $tta['train_acc_id']) ) );
			}else{
				$title = $tta['title'];
				$body = wpautop(do_shortcode($tta['content']));
			}
			$html .= '<div class="accordion-item">';
			$html .= '<h2 class="accordion-header" id="train-acc-head-'.$acc_item.'">';
			$html .= '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#train-acc-body-'.$acc_item.'" aria-expanded="false" aria-controls="train-acc-body-'.$acc_item.'">'.$title.'</button>';
			$html .= '</h2>';
			$html .= '<div id="train-acc-body-'.$acc_item.'" class="accordion-collapse collapse" aria-labelledby="train-acc-head-'.$acc_item.'" data-bs-parent="#train-acc-'.$training_id.'">';
			$html .= '<div class="accordion-body">'.$body.'</div>';
			$html .= '</div>';
			$html .= '</div>';
			$acc_item ++;
		}
		$html .= '</div>';
		$html .= '</div>';
	}
	return $html;
}

function cc_train_acc_add_training_accordion_metabox($post){
    echo '<div id="tta-rows-wrap" data-cptid="'.$post->ID.'">';
    echo '<div id="tta-rows-loader" style="text-align:center; margin:50px;"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div>';
    echo '</div>';

    /* was ..........
    // get all possible training accordions (title order)
    $training_accordions = cc_train_acc_get_all();
    // get all training accordions used for this training (sequence order)
    $ttas = cc_train_acc_tta_get_all($post->ID);
    echo '<div id="tta-rows-wrap">';
    echo '<input type="hidden" id="tta-row-count" value="'.( count( $ttas ) + 1).'">'; // count the extra unused (so far) row too but not the hidden ##row## row
    $row = 0;
    foreach ($ttas as $tta) {
        echo cc_train_acc_training_accordion_row( $tta, $row, $post->ID );
        echo '<hr>';
        $row ++;
    }
    // show one extra row
    echo cc_train_acc_training_accordion_row( cc_train_acc_empty_tta(), $row, $post->ID );
    echo '</div>';
    echo '<a href="javascript:void(0);" id="tta-add-btn" class="tta-add button button-primary">Add accordion</a>';
    // the hidden row
    echo '<div id="tta-new-row" style="display:none;">';
    echo cc_train_acc_training_accordion_row( cc_train_acc_empty_tta(), '##row##', $post->ID );
    echo '</div>';
    */
}

// get the content for the training metabox
add_action( 'wp_ajax_cc_train_acc_metabox_load', function(){
	if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error('Unauthorized', 403);
    }
    $training_id = intval($_POST['training_id']);
    // get all training accordions used for this training (sequence order)
    $ttas = cc_train_acc_tta_get_all( $training_id );
    $html = '';
    foreach ($ttas as $tta) {
        $html .= cc_train_acc_metabox_row( $tta, $training_id );
        $html .= '<hr>';
    }
    $html .= '<a href="javascript:void(0);" id="tta-add-btn" class="tta-add button button-primary" data-cptid="'.$training_id.'">Add accordion</a>';
	wp_send_json_success($html);
});

// a row for the metabox
function cc_train_acc_metabox_row( $tta, $training_id ){
    if($tta['source'] == 'std' && $tta['train_acc_id'] > 0){
        $title = get_the_title($tta['train_acc_id']);
        $content = get_the_content(null, false, $tta['train_acc_id']);
    }else{
        $title = $tta['title'];
        $content = wpautop(do_shortcode($tta['content']));
    }
    $html = '
        <div class="row tta-row">
            <div class="col-3">
                <div class="mb-3">
                    <label class="form-label">Training Accordion</label><br>
                    <input type="text" class="form-control" value="'.cc_train_acc_sel_title( $tta['train_acc_id'] ).'" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">What to show</label><br>
                    <input type="text" class="form-control" value="'.cc_train_acc_source_tidy( $tta['source'] ).'" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Order</label><br>
                    <input type="number" class="form-control" value="'.$tta['sequence'].'" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Show?</label><br>
                    <input type="text" class="form-control" value="'.cc_train_acc_hide_tidy( $tta['hide'] ).'" disabled>
                </div>
                <div class="mb-3 text-end">
                    <br>
                    <a href="#" class="tta-edt button button-primary" data-id="'.$tta['id'].'">Edit</a>
                </div>
                <div class="mb-3">
                    <div class="tta-msg"></div>
                </div>
            </div>
            <div class="col-9">
				<h3 class="tta-tit-wrap">'.$title.'</h3>
                <div class="tta-txt-wrap">'.$content.'</div>
            </div>
        </div>';
    return $html;
}

// replaced by the above function
// admin_url('admin.php?page=tta_modal_page&TB_iframe=true&width=600&height=550&modal_window=true')
/*
function cc_train_acc_training_accordion_row($tta, $row, $training_id){
    $html = '
        <div class="tta-wrap clearfix" data-row="'.$row.'" data-tta="'.$tta['id'].'" data-train="'.$training_id.'" data-accord="'.$tta['train_acc_id'].'">
            <input type="hidden" name="tta_id-'.$row.'" value="'.$tta['id'].'">
            <div class="tta-left">
                <div class="tta-row clearfix">
                    <div class="tta_ta-wrap">
                        <label for="tta_ta-'.$row.'">Training Accordion</label><br>
                        <select name="tta_ta-'.$row.'" id="tta_ta-'.$row.'" class="tta_ta tta-refresh">
                            '.cc_train_acc_all_options($tta['train_acc_id']).'
                        </select>
                    </div>
                    <div class="tta_src-wrap">
                        <label for="tta_src-'.$row.'">What to show</label><br>
                        <select name="tta_src-'.$row.'" id="tta_src-'.$row.'" class="tta_src tta-refresh">
                            '.cc_train_acc_source_options($tta['source']).'
                        </select>
                    </div>
                </div>
                <div class="tta-row clearfix">
                    <div class="tta_ord-wrap">
                        <label for="tta_ord-'.$row.'">Order</label><br>
                        <input type="number" id="tta_ord-'.$row.'" name="tta_ord-'.$row.'" class="" value="'.$tta['sequence'].'">
                    </div>
                    <div class="tta_hid-wrap">
                        <label for="tta_hid-'.$row.'">Show?</label><br>
                        <select name="tta_hid-'.$row.'" id="tta_hid-'.$row.'" class="">
                            '.cc_train_acc_hide_options($tta['source']).'
                        </select>
                    </div>
                    <div class="tta-edt-wrap">
                        <br>
                        <a href="#" id="tta-edt-'.$row.'" class="tta-edt button button-primary'.(($tta['source'] == 'std') ? ' disabled' : '').'" data-title="Edit content" data-row="'.$row.'">Edit content</a>
                    </div>
                    <div class="tta-del-wrap">
                        <br>
                        <a href="javascript:void(0);" id="tta-del-'.$row.'" class="tta-del" data-row="'.$row.'" disabled><i class="fa-solid fa-trash-can"></i></a>
                        <input type="hidden" class="tta-del-switch" name="tta-del-'.$row.'" value="no">
                    </div>
                </div>
                <div class="tta-row clearfix">
                    <div class="tta-msg"></div>
                </div>
            </div>
            <div class="tta-right">';
    if($tta['source'] == 'std' && $tta['train_acc_id'] > 0){
        $title = get_the_title($tta['train_acc_id']);
        $content = get_the_content(null, false, $tta['train_acc_id']);
    }else{
        $title = $tta['title'];
        $content = wpautop(do_shortcode($tta['content']));
    }
    $html .= '  <h3 id="tta-tit-'.$row.'" class="tta-tit-wrap">'.$title.'</h3>
                <input type="hidden" id="tta-tith-'.$row.'" class="tta-tith-wrap" name="tta-tit-'.$row.'" value="'.esc_html($title).'">
                <div id="tta-txt-'.$row.'" class="tta-txt-wrap">'.$content.'</div>
                <input type="hidden" id="tta-txth-'.$row.'" class="tta-txth-wrap" name="tta-txt-'.$row.'" value="'.esc_html($content).'">
            </div>
        </div>';
    return $html;
}
*/

// add new tta
// note this does not save it to the db, hence it has an ID of 0
add_action('wp_ajax_cc_tta_add_tta', 'cc_tta_add_tta');
function cc_tta_add_tta() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $tta = cc_train_acc_empty_tta();
    $tta['training_id'] = intval($_POST['cptid']);
    $tta['train_acc_id'] = 999999;
    $tta['source'] = 'cust';
    $tta['title'] = 'Enter title here';
    $tta['content'] = 'Enter content here';

    $html = cc_tta_popup_html( $tta, $tta['title'], $tta['content'] );

    wp_send_json_success( array( 'html' => $html, 'content' => 'Enter content here' ) );
}

// the html for the training acciordion popup used in the training metabox'
function cc_tta_popup_html( $tta, $title, $content ){
    ob_start();
    ?>
	<h2 class="tta-modal-header">Edit Training Accordion</h2>
	<hr>
	<form id="tta-form" action="">
		<input type="hidden" id="tta_id" name="tta_id" value="<?php echo $tta['id']; ?>">
		<input type="hidden" id="tta_tid" name="tta_tid" value="<?php echo $tta['training_id']; ?>">
		<div class="row">
			<div class="col-6">
                <label for="tta_ta" class="tta-modal-label">Training Accordion</label><br>
                <select name="tta_ta" id="tta_ta" class="tta_ta tta-refresh form-select">
                    <?php echo cc_train_acc_all_options( $tta['train_acc_id'] ); ?>
                </select>
			</div>
			<div class="col-6">
                <label for="tta_src" class="tta-modal-label">What to show</label><br>
                <select name="tta_src" id="tta_src" class="tta_src tta-refresh form-select"<?php echo ( $tta['train_acc_id'] == 999999 && $tta['source'] == 'cust' ) ? 'disabled' : ''; ?>>
                    <?php echo cc_train_acc_source_options( $tta['source'] ); ?>
                </select>
			</div>
		</div>
		<div class="row">
			<div class="col-6">
                <label for="tta_ord" class="tta-modal-label">Order</label><br>
                <input type="number" id="tta_ord" name="tta_ord" class="form-control" value="<?php echo $tta['sequence']; ?>">
			</div>
			<div class="col-6">
                <label for="tta_hid" class="tta-modal-label">Show?</label><br>
                <select name="tta_hid" id="tta_hid" class="form-select">
                    <?php echo cc_train_acc_hide_options( $tta['hide'] ); ?>
                </select>
			</div>
		</div>
		<div class="row">
			<div class="col-12">
				<label for="tta_tit" class="tta-modal-label">Title</label><br>
				<input type="text" id="tta_tit" name="tta_tit" class="tta_tit" value="<?php echo $title; ?>">
			</div>
		</div>
		<div class="row tta_text-wrap">
			<div class="col-12">
				<?php wp_editor( $content, 'tta_text', array(
		            'textarea_name' => 'tta_text', // The name of the textarea
		            'textarea_rows' => 10,
		            'editor_height' => 200,
		            'media_buttons' => false,
		            'quicktags' => false,
		        ) ); ?>
			</div>
		</div>
		<div class="row">
			<div class="col-4">
				<a href="javascript:void(0);" id="tta-del-btn" class="button btn-danger tta-del-btn">Delete</a>
			</div>
			<div class="col-4 text-center">
				<a href="javascript:void(0);" class="tta-edit-popup-close">Cancel</a>
			</div>
			<div class="col-4 text-end">
				<a href="javascript:void(0);" id="tta-save-btn" class="button-primary tta-save-btn">Save</a>
			</div>
		</div>
	</form>
    <?php
    $html = ob_get_clean();
    return $html;
}

// get the content for the tta edit popup 
add_action('wp_ajax_cc_tta_fetch_popup_data', 'cc_tta_fetch_popup_data');
function cc_tta_fetch_popup_data() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $tta_id = intval($_POST['ttaID']);
    $tta = cc_train_acc_get( $tta_id );

    if($tta['source'] == 'std' && $tta['train_acc_id'] > 0){
        $title = get_the_title($tta['train_acc_id']);
        $content = get_the_content(null, false, $tta['train_acc_id']);
    }else{
        $title = $tta['title'];
        $content = wpautop(do_shortcode($tta['content']));
    }

    $html = cc_tta_popup_html( $tta, $title, $content );

    wp_send_json_success( array( 'html' => $html, 'content' => $content ) );
}

// get the content for the tta edit popup when the source (the training accordion) has been changed or what to show has been changed to "std"
add_action('wp_ajax_cc_tta_refresh_popup_data', 'cc_tta_refresh_popup_data');
function cc_tta_refresh_popup_data() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized', 403);
    }

    parse_str( $_POST['formData'], $form_data );
    $tta_text = wp_kses_post( stripslashes( $_POST['ttaText'] ) );

    if($form_data['tta_src'] == 'std' && $form_data['tta_ta'] > 0){
        $title = get_the_title($form_data['tta_ta']);
        $content = get_the_content(null, false, $form_data['tta_ta']);
    }else{
    	if( $form_data['tta_ta'] == 999999 ){
    		$title = 'Enter title here';
    		$content = 'Enter content here';
    	}else{
	        $title = stripslashes( $form_data['tta_tit'] );
	        $content = $tta_text;
    	}
    }

    $tta = array(
		'id' => $form_data['tta_id'],
		'training_id' => $form_data['tta_tid'],
		'train_acc_id' => $form_data['tta_ta'],
		'source' => $form_data['tta_src'],
		'sequence' => $form_data['tta_ord'],
		'hide' => $form_data['tta_hid'],
		'title' => $title,
		'content' => $content,
    );

    $html = cc_tta_popup_html( $tta, $title, $content );

    wp_send_json_success( array( 'html' => $html, 'content' => $content ) );
}

// save the updated tta data
add_action('wp_ajax_cc_tta_save_popup_data', 'cc_tta_save_popup_data');
function cc_tta_save_popup_data() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized', 403);
    }

    parse_str( $_POST['formData'], $form_data );
    $tta_text = wp_kses_post( stripslashes( $_POST['ttaText'] ) );

    $tta = array(
    	'id' => $form_data['tta_id'],
    	'training_id' => $form_data['tta_tid'],
    	'train_acc_id' => $form_data['tta_ta'],
    	'source' => $form_data['tta_src'],
    	'sequence' => $form_data['tta_ord'],
    	'hide' => $form_data['tta_hid'],
    );

    if($form_data['tta_src'] == 'std' || $form_data['tta_ta'] == 0){
        $tta['title'] = '';
        $tta['content'] = '';
    }else{
        $tta['title'] = stripslashes( $form_data['tta_tit'] );
        $tta['content'] = $tta_text;
    }

    cc_train_acc_update($tta);

    wp_send_json_success( 'Saved!' );
}

// delete a tta
add_action('wp_ajax_cc_tta_delete', 'cc_tta_delete');
function cc_tta_delete() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $tta_id = intval($_POST['ttaID']);
    if( $tta_id == 0 ){
        wp_send_json_error('Invalid ID', 400);
    }

    $result = cc_train_acc_delete_row( $tta_id );
    if( $result == 1 ){
    	wp_send_json_success( 'Deleted!' );
    }

    wp_send_json_error('Unexpected result: '.$result, 400);
}



// the metabox for the Training Accordions post type
add_action('admin_init', 'cc_train_acc_admin_init');
function cc_train_acc_admin_init(){
	add_action('add_meta_boxes_training_accordion', 'cc_train_acc_add_meta_boxes');
}
function cc_train_acc_add_meta_boxes(){
    // add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
    add_meta_box( 'cc_train_acc_metabox', 'Training accordion settings', 'cc_train_acc_metabox_content', 'training_accordion', 'side' );
}
function cc_train_acc_metabox_content($post){
	$train_acc_default_wks = get_post_meta($post->ID, '_train_acc_default_wks', true);
	$train_acc_default_rec = get_post_meta($post->ID, '_train_acc_default_rec', true);
	?>
	<label>Default for new workshops?</label><br>
	<select class="widefat" name="train_acc_default_wks">
		<option value="">No</option>
		<option value="yes" <?php selected($train_acc_default_wks, 'yes'); ?>>Yes</option>
	</select>
	<br><br>
	<label>Default for new recordings?</label><br>
	<select class="widefat" name="train_acc_default_rec">
		<option value="">No</option>
		<option value="yes" <?php selected($train_acc_default_rec, 'yes'); ?>>Yes</option>
	</select>
	<?php
}

add_action('save_post', 'cc_train_acc_save_post');
function cc_train_acc_save_post($post_id){
	if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if(isset($_POST['post_type']) && $_POST['post_type'] == 'training_accordion' && current_user_can('edit_post', $post_id)){
		if(isset($_POST['train_acc_default_wks']) && $_POST['train_acc_default_wks'] == 'yes'){
			$train_acc_default_wks = 'yes';
		}else{
			$train_acc_default_wks = '';
		}
		if(isset($_POST['train_acc_default_rec']) && $_POST['train_acc_default_rec'] == 'yes'){
			$train_acc_default_rec = 'yes';
		}else{
			$train_acc_default_rec = '';
		}
		update_post_meta($post_id, '_train_acc_default_wks', $train_acc_default_wks);
		update_post_meta($post_id, '_train_acc_default_rec', $train_acc_default_rec);
	}
}

// one-off accordion setup
// add_shortcode('Training_accordion_setup', 'cc_train_acc_one_off_setup');
function cc_train_acc_one_off_setup(){
	global $wpdb;
	$errors = 0;
	$tta_table = $wpdb->prefix.'train_train_accordions';
	$html = '';
	// let's start with workshops
	$args = array(
		'numberposts' => -1,
		'post_type' => 'workshop',
	);
	$workshops = get_posts($args);
	$html .= count($workshops).' workshops found<br>';
	// let's find the defaults for workshops
	$args = array(
		'numberposts' => -1,
		'post_type' => 'training_accordion',
		'orderby' => 'title',
		'order' => 'ASC',
		'meta_key' => '_train_acc_default_wks',
		'meta_value' => 'yes',
	);
	$defaults = get_posts($args);
	$html .= 'The default accordions for workshops are:<br>';
	foreach ($defaults as $default) {
		$html .= $default->ID.' '.$default->post_title.'<br>';
	}
	// now we process the workshops
	$html .= '<h4>Workshops:</h4>';
	foreach ($workshops as $workshop) {
		$html .= $workshop->ID.' '.$workshop->post_title.'<br>';
		$sql = "SELECT * FROM $tta_table WHERE `training_id` = $workshop->ID ORDER BY sequence ASC";
		$training_tas = $wpdb->get_results($sql, ARRAY_A);
		$training_accordions_set = get_post_meta($workshop->ID, '_training_accordions_set', true);
		$html .= 'training_accordions_set = '.$training_accordions_set.'. ';
		$html .= count($training_tas).' accordions found. ';
		$accordions_set = array();
		foreach ($training_tas as $tta) {
			$html .= $tta['train_acc_id'].', ';
			$accordions_set[] = $tta['train_acc_id'];
		}
		$html .= '<br>';
		if($training_accordions_set <> 'yes'){
			// now add the defaults, if not already set
			foreach ($defaults as $default){
				if(in_array($default->ID, $accordions_set)){
					$html .= $default->ID.' already set<br>';
				}else{
					$html .= 'Setting '.$default->ID;
					$new_tta = cc_train_acc_empty_tta();
					$new_tta['training_id'] = $workshop->ID;
					$new_tta['train_acc_id'] = $default->ID;
					$result = cc_train_acc_update($new_tta);
					// $result = false;
					if($result){
						$html .= ' done<br>';
					}else{
						$html .= ' ERROR!<br>';
						$errors ++;
					}
				}
			}
			// set the workshop as having had accordions set
	        update_post_meta($workshop->ID, '_training_accordions_set', 'yes');
	        $html .= 'Workshop flagged to prevent future defaults being set<br><br>';
		}else{
			$html .= 'Nothing to do here<br>';
		}
	}

	// and recordings ....
	$html .= '<br><br>';
	$args = array(
		'numberposts' => -1,
		'post_type' => 'recording',
	);
	$recordings = get_posts($args);
	$html .= count($recordings).' recordings found<br>';
	// let's find the defaults for recordings
	$args = array(
		'numberposts' => -1,
		'post_type' => 'training_accordion',
		'orderby' => 'title',
		'order' => 'ASC',
		'meta_key' => '_train_acc_default_rec',
		'meta_value' => 'yes',
	);
	$defaults = get_posts($args);
	$html .= 'The default accordions for recordings are:<br>';
	foreach ($defaults as $default) {
		$html .= $default->ID.' '.$default->post_title.'<br>';
	}
	// now we process the recordings
	$html .= '<h4>Recordings:</h4>';
	foreach ($recordings as $recording) {
		$html .= $recording->ID.' '.$recording->post_title.'<br>';
		$sql = "SELECT * FROM $tta_table WHERE `training_id` = $recording->ID ORDER BY sequence ASC";
		$training_tas = $wpdb->get_results($sql, ARRAY_A);
		$training_accordions_set = get_post_meta($recording->ID, '_training_accordions_set', true);
		$html .= 'training_accordions_set = '.$training_accordions_set.'. ';
		$html .= count($training_tas).' accordions found. ';
		$accordions_set = array();
		foreach ($training_tas as $tta) {
			$html .= $tta['train_acc_id'].', ';
			$accordions_set[] = $tta['train_acc_id'];
		}
		$html .= '<br>';
		if($training_accordions_set <> 'yes'){
			// now add the defaults, if not already set
			foreach ($defaults as $default){
				if(in_array($default->ID, $accordions_set)){
					$html .= $default->ID.' already set<br>';
				}else{
					$html .= 'Setting '.$default->ID;
					$new_tta = cc_train_acc_empty_tta();
					$new_tta['training_id'] = $recording->ID;
					$new_tta['train_acc_id'] = $default->ID;
					$result = cc_train_acc_update($new_tta);
					// $result = false;
					if($result){
						$html .= ' done<br>';
					}else{
						$html .= ' ERROR!<br>';
						$errors ++;
					}
				}
			}
			// set the recording as having had accordions set
	        update_post_meta($recording->ID, '_training_accordions_set', 'yes');
	        $html .= 'Recording flagged to prevent future defaults being set<br><br>';
		}else{
			$html .= 'Nothing to do here<br>';
		}
	}

	$html .= '<br><br>'.$errors.' errors';
	return $html;
}

// when the Duplicate Post plugin is used to duplicate training, it does not automatically duplicate the training accordions, so we'll do it now
/**
 * Fires after duplicating a post.
 *
 * @param int|WP_Error $new_post_id The new post id or WP_Error object on error.
 * @param WP_Post      $post        The original post object.
 * @param bool         $status      The intended destination status.
 * @param int          $parent_id   The parent post ID if we are calling this recursively.
 */
add_action( 'duplicate_post_post_copy', 'cc_train_acc_duplicate_post', 10, 4 );
function cc_train_acc_duplicate_post( $new_post_id, $post, $status, $parent_id ){
	global $wpdb;
	$table_name = $wpdb->prefix.'train_train_accordions';
	if( $new_post_id !== 0 && ! is_wp_error( $new_post_id ) ){
		if( $post->post_type == 'workshop' || $post->post_type == 'recording' ){
			$sql = "SELECT * FROM $table_name WHERE `training_id` = $post->ID ORDER BY sequence ASC";
			$training_accordions = $wpdb->get_results($sql, ARRAY_A);
			foreach ($training_accordions as $new_row) {
				unset( $new_row['id'] );
				$new_row['training_id'] = $new_post_id;
				$wpdb->insert( $table_name, $new_row, cc_train_acc_update_format() );
			}
		}
	}
}

// the popup to allow editing of the training accordion in the backend
add_action('admin_footer', 'cc_train_acc_add_popup_html');
function cc_train_acc_add_popup_html() {
    ?>
    <div id="tta-edit-popup" style="display:none;">
        <div class="tta-edit-popup-content">
            <span id="tta-edit-popup-close" class="tta-edit-popup-close">&times;</span>
            <div id="tta-edit-popup-data" data-flag=""><i class="fa-solid fa-spinner fa-spin-pulse"></i></div>
            <div id="tta-edit-popup-msg"></div>
        </div>
    </div>
    <?php
}
