<?php
/**
 * training newsletter cards
 * these are the cards that show in the newsletters
 */

// freeconvert.com access token
function cc_tnc_freeconvert_access_token(){
	return 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiQ0MgSHRtbCB0byBQTkciLCJpZCI6IjY0MzUzMzNkZWYyODFhODMxMWZjNGU5MiIsImludGVyZmFjZSI6ImFwaSIsInJvbGUiOiJ1c2VyIiwiZW1haWwiOiJpYW5Acm9kZXJpY2twdWdobWFya2V0aW5nLmNvbSIsInBlcm1pc3Npb25zIjpbXSwiaWF0IjoxNjgxMjA4MTgxLCJleHAiOjIxNTQ1NzIxODF9.ZLqn1vV5HjkJxzGE1-paX39JhMKcA9zHtpFhCi-RebM';
}

// when saving workshops, generate a new card
// not using save_post_workshop as that fires earlier than save_post and will miss some updates (eg the post meta updates)
add_action('save_post', 'cc_tnc_save_post', 99);
// Fires once a post has been saved.
function cc_tnc_save_post($post_id){
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE){
        return;
    }
	if(isset($_POST['post_type']) 
		&& ( $_POST['post_type'] == 'workshop' || $_POST['post_type'] == 'course' ) 
		&& current_user_can('edit_post', $post_id)){
		// temporarily generating v1 and v2 cards
		$new_card_html = cc_tnc_create_card_html($post_id);
		$current_card_html = get_post_meta($post_id, '_news_card_html', true);
		if($new_card_html <> $current_card_html){
			$file_name = cc_tnc_convert_card_to_png( $new_card_html );
			if($file_name !== false){
				update_post_meta($post_id, '_news_card_img', $file_name);
				update_post_meta($post_id, '_news_card_html', $new_card_html);
			}
		}
		$new_card_html = cc_tnc_create_card_html_v2($post_id);
		$current_card_html = get_post_meta($post_id, '_news_card_v2_html', true);
		if($new_card_html <> $current_card_html){
			$file_name = cc_tnc_convert_card_to_png( $new_card_html, 600, 375 );
			if($file_name !== false){
				update_post_meta($post_id, '_news_card_v2_img', $file_name);
				update_post_meta($post_id, '_news_card_v2_html', $new_card_html);
			}
		}
	}
}

// creates the card as html
function cc_tnc_create_card_html($training_id){
	// outside the site, padding is extra to the size
	$html = '<div style="width:244px; height:156px; padding:10px; font-family:\'sofia-pro\'; color:#fff; ';
	$bg_image = get_the_post_thumbnail_url( $training_id, 'post-thumb' );
	if($bg_image == ''){
		$html = '<div style="width:244px; height:156px; padding:10px; font-family:\'sofia-pro\'; color:#fff; background:#000;">';
	}else{
		$html = '<div style="width:244px; height:156px; padding:10px; font-family:\'sofia-pro\'; color:#fff; background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('.$bg_image.'); background-repeat:no-repeat; background-size:cover;">';
	}

	$title = get_the_title($training_id);
	if($title == ''){
		$title = 'Title not found';
	}

	$presenter_image = '';
	$presenters = cc_presenters_training_get_ids($training_id);
	if(is_array($presenters) && count($presenters) > 0){
		$presenter_image_id = cc_presenters_image_id( $presenters[0]['id'], $presenters[0]['image'] );
		if(!empty($presenter_image_id)){
			// wms_media_image_html($image, $size='post-thumb', $bg=true, $ratio='3:2', $link='', $xclass='', $add_bg_styles=false)
			$presenter_image = wms_media_image_html($presenter_image_id, 'presenter-profile', true, '1:1', '', '', true);
		}
	}

	if($presenter_image == ''){
		$max_title_width = 244;
		$float = '';
	}else{
		// 244 - 65 (img) - 10 (padd)
		$max_title_width = 169;
		$float = ' float:left;';
	}

	$title_font = cc_tnc_text_size($max_title_width, 65, $title);

	$html .= '<div style="width:'.$max_title_width.'px; height:65px; font-size:'.$title_font['font_size'].'px; line-height:'.$title_font['line_height'].'; '.$float.'">'.$title.'</div>';

	if($presenter_image <> ''){
		$html .= '<div style="margin-left:10px; width:65px; height:65px; float:left;">'.$presenter_image.'</div>';
		$html .= '<div style="clear:both;"></div>';
	}

	$subtitle = get_post_meta( $training_id, 'subtitle', true);
	if($subtitle <> ''){
		$subtitle_font = cc_tnc_text_size(244, 30, $subtitle);
		$html .= '<div style="margin-top:10px; margin-bottom:10px; height:30px; font-size:'.$subtitle_font['font_size'].'px; line-height:'.$subtitle_font['line_height'].'; ">'.$subtitle.'</div>';
	}else{
		$html .= '<div style="height:50px;">&nbsp;</div>';
	}

	$presenter_names = cc_presenters_names($training_id, 'none');
	if($presenter_names <> ''){
		// width = 244 - 12 (FA) - 10 (padd), line-height set to 12 * 1.3
		$presenter_font = cc_tnc_text_size(222, 16, $presenter_names);
		$html .= '<div style="line-height:14px; font-size:12px;"><i class="fa-solid fa-user fa-fw" style="margin-right:5px;"></i><span style="font-size:'.$presenter_font['font_size'].'px;">'.$presenter_names.'</span></div>';
	}else{
		$html .= '<div style="height:14px;">&nbsp;</div>';
	}

	$text_dates = get_post_meta( $training_id, 'prettydates', true );
	if($text_dates <> ''){
		// width = 244 - 12 (FA) - 10 (padd), line-height set to 12 * 1.3
		$prettydates_font = cc_tnc_text_size(222, 16, $text_dates);
		$html .= '<div style="line-height:14px; font-size:12px;"><i class="fa-solid fa-hourglass-half fa-fw" style="margin-right:5px;"></i><span style="font-size:'.$prettydates_font['font_size'].'px;">'.$text_dates.'</span></div>';
	}else{
		$html .= '<div style="height:14px;">&nbsp;</div>';
	}

	$pretty_dates = workshop_calculated_prettydates($training_id, 'Europe/London');
	$sessions = '';
	$num_sess = workshop_number_sessions($training_id);
	if($num_sess > 1){
		$sessions = $num_sess.' sessions: ';
	}
	$html .= '<div style="line-height:14px; font-size:12px;"><div style="width:180px; float:left;">';
	if($pretty_dates['london_date'] <> ''){
		$dates_font = cc_tnc_text_size(160, 16, $sessions.$pretty_dates['london_date']);
		$html .= '<i class="fa-solid fa-calendar-days fa-fw" style="margin-right:5px;"></i><span style="font-size:'.$dates_font['font_size'].'px;">'.$sessions.$pretty_dates['london_date'].'</span>';
	}else{
		$html .= '&nbsp';
	}
	$html .= '</div><div style="width:64px; float:left; text-align:right; color:#f6921e;"><u>Details ></u></div><div style="clear:both;"></div></div>';

	$html .= '</div>';
	return $html;
}

// creates the card as html
// v2 June 2025 - larger cards with a different layout
function cc_tnc_create_card_html_v2($training_id){
	// outside the site, padding is extra to the size
	// overall size is 600 x 375
	$html = '<div style="width:520px; height:295px; padding:40px; font-family:\'sofia-pro\'; color:#fff; ';

	// main gackground image
	$bg_image = get_the_post_thumbnail_url( $training_id, 'post-thumb' );
	if($bg_image == ''){
		$html = '<div style="width:520px; height:295px; padding:40px; font-family:\'sofia-pro\'; color:#fff; background:#000;">';
	}else{
		$html = '<div style="width:520px; height:295px; padding:40px; font-family:\'sofia-pro\'; color:#fff; background-image: linear-gradient(rgba(130,138,145,0.5), rgba(130,138,145,0.5)), url('.$bg_image.'); background-repeat:no-repeat; background-size:cover;">';
	}

	// overlay background - internal width 460px 
	$html .= '<div style="background:rgba(34,41,59,0.7); padding:30px; width="460px; height:235px;>';

	$presenter_image = '';
	$presenters = cc_presenters_training_get_ids($training_id);
	if(is_array($presenters) && count($presenters) > 0){
		$presenter_image_id = cc_presenters_image_id( $presenters[0]['id'], $presenters[0]['image'] );
		if(!empty($presenter_image_id)){
			// wms_media_image_html($image, $size='post-thumb', $bg=true, $ratio='3:2', $link='', $xclass='', $add_bg_styles=false)
			$presenter_image = wms_media_image_html($presenter_image_id, 'presenter-profile', true, '1:1', '', '', true);
		}
	}

	// background width - bg padding (520 - 60 = 460)
	if($presenter_image == ''){
		$left_col_width = 460;
		$left_col_float = '';
	}else{
		// 460 - 100 (img) - 10 (image margin)
		$left_col_width = 350;
		$left_col_float = ' float:left;';
	}

	// top half of the text
	$html .= '<div style="width:100%; height:190px;">';

	// left hand column (or full width if no image)
	$html .= '<div style="width:'.$left_col_width.'px; '.$left_col_float.'">';

	// title
	$title = get_the_title($training_id);
	if($title == ''){
		$title = 'Title not found';
	}
	$title_font = cc_tnc_text_size( $left_col_width, 80, $title );
	$html .= '<div style="width:100%; font-size:'.$title_font['font_size'].'px; line-height:'.$title_font['line_height'].'; ">'.$title.'</div>';

	// presenter(s)
	$presenter_names = cc_presenters_names($training_id, 'none');
	if($presenter_names <> ''){
		$presenter_font = cc_tnc_text_size( $left_col_width, 37, $presenter_names, 16 );
		// $html .= '<div style="margin-top:10px; line-height:14px; font-size:12px;"><i class="fa-solid fa-user fa-fw" style="margin-right:5px;"></i><span style="font-size:'.$presenter_font['font_size'].'px;">'.$presenter_names.'</span></div>';
		$html .= '<div style="width:100%; margin-top:15px; line-height:'.$presenter_font['line_height'].'; font-size:'.$presenter_font['font_size'].'px;">'.$presenter_names.'</div>';
	}else{
		// $html .= '<div style="height:14px;">&nbsp;</div>';
	}

	// subtitle
	$subtitle = get_post_meta( $training_id, 'subtitle', true);
	if($subtitle <> ''){
		$subtitle_font = cc_tnc_text_size( $left_col_width, 37, $subtitle, 36 );
		$html .= '<div style="width:100%; margin-top:15px; font-size:'.$subtitle_font['font_size'].'px; line-height:'.$subtitle_font['line_height'].';">'.$subtitle.'</div>';
	}else{
		// $html .= '<div style="height:50px;">&nbsp;</div>';
	}

	// end of left column
	$html .= '</div>';

	// and the right column ...
	if($presenter_image <> ''){
		$html .= '<div style="margin-left:10px; width:100px; height:100px; float:right; border-radius:50px; overflow:hidden;">'.$presenter_image.'</div>';
	}

	// clear both columns
	$html .= '<div style="clear:both;"></div>';

	// end of the top half
	$html .= '</div>';

	// bottom half

	// left col
	// 460 - 132 = 328
	$html .= '<div style="width:328px; float:left;">';

	if( course_training_type( $training_id ) == 'workshop' ){

		$text_dates = get_post_meta( $training_id, 'prettydates', true );
		if($text_dates <> ''){
			$prettydates_font = cc_tnc_text_size( 328, 21, $text_dates, 16 );
			$html .= '<div style="line-height:'.$prettydates_font['line_height'].'; font-size:'.$prettydates_font['font_size'].'px;">'.$text_dates.'</div>';
		}else{
			// $html .= '<div style="height:14px;">&nbsp;</div>';
		}

		$pretty_dates = workshop_calculated_prettydates($training_id, 'Europe/London');
		$sessions = '';
		$num_sess = workshop_number_sessions($training_id);
		if($num_sess > 1){
			$sessions = $num_sess.' sessions: ';
		}
		if($pretty_dates['london_date'] <> ''){
			$dates_font = cc_tnc_text_size( 328, 21, $sessions.$pretty_dates['london_date'], 16 );
			$html .= '<div style="line-height:'.$dates_font['line_height'].'; font-size:'.$dates_font['font_size'].'px;">'.$sessions.$pretty_dates['london_date'].'</div>';
		}

	}else{

		$ce_credits = (float) get_post_meta( $training_id, 'ce_credits', true );
		if( $ce_credits > 0 ){
			$html .= '<div style="font-size:16px; line-height:1.3;">'.$ce_credits.' CE credits</div>';
		}

		$recording_expiry = rpm_cc_recordings_get_expiry( $training_id, true );
        if( $recording_expiry['expiry_text'] <> '' ){
			$html .= '<div style="font-size:16px; line-height:1.3;">Access for '.$recording_expiry['expiry_text'].'</div>';
        }

	}

	// end of the left col
	$html .= '</div>';

	// right col
	$html .= '<div style="width:122px; float:right; text-align:right;">'; // 10px gap
	$html .= '<div style="background:#f6921e; padding:8px 0; border-radius:5px; color:#fff; text-align:center;">Book now</div>';
	$html .= '</div>';

	// clear both columns
	$html .= '<div style="clear:both;"></div>';

	// end of text
	$html .= '</div>';
	// end of background
	$html .= '</div>';
	// end of card
	$html .= '</div>';

	return $html;
}


// get the optimum font size so that text nicely fills the space available
function cc_tnc_text_size($max_width, $max_height, $text, $max_font=40){
	$text_words = explode(' ', $text);
	$num_words = count($text_words);
	// uses imagettfbbox to give the bounding box of a text using TrueType fonts
	// so we had to convert the font file to ttf and this is now in the cc folder
	// Set the environment variable for GD
	putenv('GDFONTPATH=' . get_template_directory() . '/cc/' );
	// Name the font to be used (note the lack of the .ttf extension)
	$font = 'SofiaProBold';
	// imagettfbbox seems to overstate the width by about 34%
	$max_width_adjusted = $max_width * 1.34;
	if($max_height < $max_font){
		$starting_font = $max_height;
	}else{
		$starting_font = $max_font;
	}
	// now let's find the font size, starting at 40px and getting smaller to a min of 5px
	for( $size=$starting_font; $size>5; $size--){
		// different line heights based on font size
		if($size > 39){
			$line_height = 1;
		}elseif($size > 29){
			$line_height = 1.1;
		}elseif($size > 19){
			$line_height = 1.2;
		}else{
			$line_height = 1.3;
		}
		$text_line = '';
		$num_lines = 1;
		for ($i=0; $i < $num_words; $i++) { 
			if($text_line <> ''){
				$text_line .= ' ';
			}
			$text_line .= $text_words[$i];
			// now let's see how big the text_line is
			// imagettfbbox( $fontsizeinpoints, $angle, $fontfile, $text)
			$sizearray = imagettfbbox ( $size , 0 , $font , $text_line );
			// $html .= 'Sizearray:'.print_r($sizearray, true);
		    // calculate actual text width (according to imagettfbbox)
		    $width = abs($sizearray[2] - $sizearray[0]);
		    if($sizearray[0] < -1) {
		        $width = abs($sizearray[2]) + abs($sizearray[0]) - 1;
		    }
			if( $width > $max_width_adjusted ){
				// line too wide ... move the last word to the next line
				$num_lines ++;
				$text_line = $text_words[$i];
			}
		}
		$height = $num_lines * $line_height * $size;
		if($height <= $max_height){
			// fits
			break;
		}
	}
	$result = array(
		'font_size' => $size,
		'line_height' => $line_height,
	);
	return $result;
}


add_shortcode('cc_tnc_card', 'cc_tnc_card_shortcode');
function cc_tnc_card_shortcode(){
	$training_id = 5092;
	$html = cc_tnc_create_card_html_v2($training_id);
	// cc_tnc_convert_card_to_png($html);
	return $html;
}

// The use keyword must be declared in the outermost scope of a file
// require get_template_directory().'/cc/vendor/autoload.php';
// use \ConvertApi\ConvertApi;

// convert from html to png
function cc_tnc_convert_card_to_png( $card_html, $width=264, $height=176 ){

	// wrap the card html
	$page_html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width='.$width.', initial-scale=1.0, user-scalable=0"><title>Document</title><link rel="stylesheet" id="contextual-fonts-css" href="https://use.typekit.net/nyw0zqj.css" type="text/css" media="all"><link rel="stylesheet" id="font-awesome-css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css?ver=6.1.1" type="text/css" media="all" /></head><body style="margin:0; padding:0; width:'.$width.'px; height:'.$height.'px;">'.$card_html.'</body></html>';
	// we'll save this as a file
	$file_name = uniqid();
	$file_path = get_template_directory().'/news-cards/';
	file_put_contents( $file_path.$file_name.'.html', $page_html);
	$file_url = get_template_directory_uri().'/news-cards/'.$file_name.'.html';

	/**
	 * Now (Sep 2024) using Cloudmersive
	 * Added the folder to the cc folder ... so remove it if you're not using Cloudmersive any more
	 */

	$cloudmersive_api_key = '37e1ae7c-c874-47b8-83ca-442896709560';
	require_once get_template_directory().'/cc/cloudmersive/vendor/autoload.php';
	// Configure API key authorization: Apikey
	$config = Swagger\Client\Configuration::getDefaultConfiguration()->setApiKey('Apikey', $cloudmersive_api_key);
	$apiInstance = new Swagger\Client\Api\ConvertWebApi(
	    new GuzzleHttp\Client(),
	    $config
	);
	$input = array(
		'Html' => $page_html,
		"ExtraLoadingWait" => 0,
		"ScreenshotWidth" => $width,
		"ScreenshotHeight" => $height,
	);
	try {
	    $result = $apiInstance->convertWebHtmlToPng($input);
		// Save the output to a file
	    file_put_contents($file_path.$file_name.'.png', $result);
		return $file_name.'.png';
	} catch (Exception $e) {
		ccpa_write_log('Function cc_tnc_convert_card_to_png: Cloudmersive: Exception when calling ConvertDocumentApi->convertWebHtmlToPng: ', $e->getMessage(), PHP_EOL);
	}

}

// show the card on the training edit page
add_action( 'add_meta_boxes', 'cc_tnc_workshop_metabox' );
function cc_tnc_workshop_metabox(){
	// add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
	$post_types = array( 'workshop', 'recording', 'course' );
	add_meta_box( 'train_news_cards_metabox', 'Newsletter card', 'cc_tnc_render_tnc_metabox', $post_types, 'side', 'low' );
}
function cc_tnc_render_tnc_metabox($post){
	$file_name = get_post_meta($post->ID, '_news_card_img', true);
	echo '<p><strong>The small card:</strong></p>';
	if($file_name == ''){
		echo '<p>The card will be shown here after the training has been saved.</p>';
	}else{
		echo '<img src="'.get_stylesheet_directory_uri().'/news-cards/'.$file_name.'" style="max-width:100%;">';
	}
	$file_name = get_post_meta($post->ID, '_news_card_v2_img', true);
	echo '<p><strong>The new, larger card:</strong></p>';
	if($file_name == ''){
		echo '<p>The card will be shown here after the training has been saved.</p>';
	}else{
		echo '<img src="'.get_stylesheet_directory_uri().'/news-cards/'.$file_name.'" style="max-width:100%;">';
	}
}

