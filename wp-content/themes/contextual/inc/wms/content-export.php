<?php
/**
 * Page content import and export
 */

// export
add_action('wp_ajax_page_content_export', 'page_content_export_function');
function page_content_export_function(){
	$response = array(
		'status' => 'error',
	);
	$page_id = 0;
	if(isset($_POST['pageid'])){
		$page_id = (int) $_POST['pageid'];
	}
	if($page_id > 0){
		$page = get_post($page_id);
		if($page){
			$export_data = array();
			$post_fields = array('post_content', 'post_title', 'post_excerpt');
			foreach ($post_fields as $post_field) {
				$export_data[$post_field] = $page->$post_field;
			}
			$export_data['metas'] = get_post_meta($page_id);
		}
		$json = json_encode(array('data' => $export_data));
		if (file_put_contents(ABSPATH.'wp-content/export/page_'.$page_id.'_content.json', $json)){
			$response['status'] = 'ok';
		}
	}
    echo json_encode($response);
    die();
}