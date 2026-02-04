<?php
/**
 * WMS
 * Home Page - formats the home page layout for the backend and the frontend
 */

// the possible sections that we allow for so far
function wms_home_sections_accommodated(){
	return array(
		'Standard' => '',
		'Image on Left' => 'left',
		'Image on Right' => 'right',
		'Two columns' => '2-columns',
		'Three columns' => '3-columns',
		'Thumbnail on Left' => 'thumb-left',
		'News/Blog Posts' => 'posts',
		'Logos' => 'logos',
		// added for CC:
		'Next Training' => 'training_next',
		'Upcoming Training' => 'training_upcoming',
	);
}

// returns empty section args array
function wms_home_empty_section_args(){
	global $rpm_theme_options;
	return array(
		'visibility' => '', // '' or 'hide'
	    'type' => '',
	    'image' => '',
	    'image_xs' => '',
	    'parallax' => '',
	    'bg_colour' => '',
	    'opacity' => 0.8,
	    'text_colour' => '',
	    'text_size' => 'normal',
	    'text_align' => WMS_SECTION_ALIGN,
	    'youtube' => '',
	    'video_quality' => '720p',
	    'num_items' => '6',
	    'title' => '',
	    'heading' => 'h2',
	    'width' => '12',
	    'image_size' => '',
	    'xclass' => '',
	    'content' => '',
	    'content-1' => '',
	    'content-2' => '',
	    'padding_top' => '',
	    'padding_bot' => '',
	    'focal_point' => 'center',
	    'subhead' => '',
	    // logos section:
	    'ids' => '',
	    'rows_xs' => '1',
	    'rows_sm' => '1',
	    'rows_md' => '1',
	    'rows_lg' => '1',
	    'rows_xl' => '1',
	    'logo_size' => 'sm',
	);
}

// return a pretty section type
function wms_home_section_type_pretty($type){
	foreach (wms_home_sections_accommodated() as $key => $value) {
		if($type == $value){
			return $key;
		}
	}
	return 'Unknown';
}

// remove the editor section from the home page in the backend
add_action('admin_head', 'wms_home_remove_editor');
function wms_home_remove_editor(){
    if((int) get_option('page_on_front') == get_the_ID()){
        remove_post_type_support('page', 'editor');
    }
}

// move the Yoast meta box down to the bottom (affects the whole site)
add_filter( 'wpseo_metabox_prio', 'wms_home_change_yoast_metabox_priority');
function wms_home_change_yoast_metabox_priority() {
    return 'low';
}

// add the appropriate number of meta boxes to the home page, one for each section
add_action('add_meta_boxes_page', 'wms_home_add_metaboxes');
function wms_home_add_metaboxes($post){
	global $rpm_theme_options;
    if((int) get_option('page_on_front') == $post->ID){
    	$num_metas = absint($rpm_theme_options['home-sections']);
    	for ($i=0; $i < $num_metas; $i++) { 
	    	// add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
	    	add_meta_box( 'home-meta-'.$i, 'Section: '.wms_home_metabox_title($i), 'wms_home_metabox_callback', 'page', 'normal', 'high', $i );
    	}
    }
}

// gets the metabox/section title
function wms_home_metabox_title($sect_id){
	global $post;
	$sect_args = get_post_meta($post->ID, '_wms_home_sect_'.$sect_id.'_args', true);
	if(is_array($sect_args) && isset($sect_args['title'])){
		$title = $sect_args['title'];
		if(isset($sect_args['visibility']) && $sect_args['visibility'] == 'hide'){
			$title .= ' - Hidden';
		}
		return $title;
	}
	return '';
}

// build the meta boxes
function wms_home_metabox_callback($post, $args){
	global $rpm_theme_options;
	// var_dump( $args );
	// array(4) {["id"]=>"home-meta-2", ["title"]=>"Section", ["callback"]=>"wms_home_metabox_callback", ["args"]=>int(2)}
	$sect = $args['args'];
	$sect_args = get_post_meta($post->ID, '_wms_home_sect_'.$sect.'_args', true);
	if($sect_args == ''){
		$sect_args = wms_home_empty_section_args();
	}
	wp_nonce_field( 'home_metabox_'.$sect, 'home_metabox_nonce_'.$sect );
	?>
	<div id="<?php echo $args['id']; ?>-metabox" class="wms-home-section-metabox" data-secttype="<?php echo $sect_args['type']; ?>">
		<div class="home-meta-row">
			<div class="home-meta-col half">
				<div class="onoffswitch">
					<?php
					if($sect_args['visibility'] == ''){
						$checked = 'checked';
					}else{
						$checked = '';
					}
					?>
					<label class="toggle" for="visibility-<?php echo $sect; ?>">
						Show this section:
					    <input class="toggle__input" name="visibility[<?php echo $sect; ?>]" type="checkbox" id="visibility-<?php echo $sect; ?>" value="show" <?php echo $checked; ?>>
					    <div class="toggle__fill"></div>
					</label>
				</div>
			</div>
			<div class="home-meta-col half text-right">
				<label for="order-<?php echo $sect; ?>">Order</label><br>
				<?php $order = ($sect + 1) * 10; ?>
				<input type="number" name="order[<?php echo $sect; ?>]" value="<?php echo $order; ?>" class="narrow">
			</div>
		</div>
		<div class="home-meta-row">
			<div class="home-meta-col">
				<label for="type[<?php echo $sect; ?>]">Section Type:</label><br>
				<select name="type[<?php echo $sect; ?>]">
					<?php
					foreach (wms_home_sections_accommodated() as $value => $key) { ?>
						<option value="<?php echo $key; ?>" <?php selected($key, $sect_args['type']); ?>><?php echo $value; ?></option>
					<?php } ?>
				</select>
			</div>
			<div class="home-meta-col">
				<label for="title[<?php echo $sect; ?>]">Section Title:</label><br>
				<input type="text" name="title[<?php echo $sect; ?>]" value="<?php echo $sect_args['title']; ?>">
			</div>
			<div class="home-meta-col">
				<label for="width[<?php echo $sect; ?>]">Width</label><br>
				<select name="width[<?php echo $sect; ?>]">
					<option value="wide" <?php selected('wide', $sect_args['width']); ?>>Full width</option>
					<?php for ($i=12; $i > 1; $i--) { ?>
						<option value="<?php echo $i; ?>" <?php selected($i, $sect_args['width']); ?>><?php echo $i.' cols'; ?></option>
					<?php } ?>
				</select>
			</div>
			<div class="home-meta-col">
				<label for="heading[<?php echo $sect; ?>]">Title Heading</label><br>
				<select name="heading[<?php echo $sect; ?>]">
					<option value="h1" <?php selected('h1', $sect_args['heading']); ?>>H1</option>
					<option value="h2" <?php selected('h2', $sect_args['heading']); ?>>H2</option>
					<option value="h3" <?php selected('h3', $sect_args['heading']); ?>>H3</option>
					<option value="h4" <?php selected('h4', $sect_args['heading']); ?>>H4</option>
					<option value="h5" <?php selected('h5', $sect_args['heading']); ?>>H5</option>
					<option value="h6" <?php selected('h6', $sect_args['heading']); ?>>H6</option>
				</select>
			</div>
		</div>
		<div class="home-meta-row">
			<div class="home-meta-col">
				<label for="image[<?php echo $sect; ?>]">Image:</label><br>
				<div class="section-img-container-std">
					<?php
					$sect_img_found = false;
					if($sect_args['image'] <> ''){
						$sect_img_src = wp_get_attachment_image_src( $sect_args['image'], 'xs' );
						$sect_img_found = is_array($sect_img_src);
						if($sect_img_found){ ?>
							<img src="<?php echo $sect_img_src[0] ?>" alt="" style="max-width:100%;">
						<?php }
					} ?>
				</div>
			    <a class="upload-section-img upload-section-img-std button button-secondary <?php if ( $sect_img_found  ) { echo 'hidden'; } ?>" 
					href="<?php echo esc_url( get_upload_iframe_src( 'image', $post->ID ) ); ?>"
					data-sectid="<?php echo $sect; ?>"
					data-imgtype="std">
					Set section image
			    </a>
			    <a class="delete-section-img delete-section-img-std button button-secondary <?php if ( ! $sect_img_found  ) { echo 'hidden'; } ?>" 
					href="#"
					data-sectid="<?php echo $sect; ?>"
					data-imgtype="std">
					Remove this image
			    </a>
			    <input class="section-img-id-std" name="image[<?php echo $sect; ?>]" type="hidden" value="<?php echo esc_attr( $sect_args['image'] ); ?>">
			</div>
			<div class="home-meta-col">
				<label for="image_xs[<?php echo $sect; ?>]">Image on mobile:</label><br>
				<div class="section-img-container-mob">
					<?php
					$sect_img_found = false;
					if($sect_args['image_xs'] <> ''){
						$sect_img_src = wp_get_attachment_image_src( $sect_args['image_xs'], 'xs' );
						$sect_img_found = is_array($sect_img_src);
						if($sect_img_found){ ?>
							<img src="<?php echo $sect_img_src[0] ?>" alt="" style="max-width:100%;">
						<?php }
					} ?>
				</div>
			    <a class="upload-section-img upload-section-img-mob button button-secondary <?php if ( $sect_img_found  ) { echo 'hidden'; } ?>" 
					href="<?php echo esc_url( get_upload_iframe_src( 'image', $post->ID ) ); ?>"
					data-sectid="<?php echo $sect; ?>"
					data-imgtype="mob">
					Set mobile image
			    </a>
			    <a class="delete-section-img delete-section-img-mob button button-secondary <?php if ( ! $sect_img_found  ) { echo 'hidden'; } ?>" 
					href="#"
					data-sectid="<?php echo $sect; ?>"
					data-imgtype="mob">
					Remove mobile image
			    </a>
			    <input class="section-img-id-mob" name="image_xs[<?php echo $sect; ?>]" type="hidden" value="<?php echo esc_attr( $sect_args['image_xs'] ); ?>">
			</div>
			<div class="home-meta-col">
				<label for="parallax[<?php echo $sect; ?>]">Parallax scrolling?</label><br>
				<select name="parallax[<?php echo $sect; ?>]">
					<option value="no" <?php selected('', $sect_args['parallax']); ?>>No</option>
					<option value="yes" <?php selected('yes', $sect_args['parallax']); ?>>Yes</option>
				</select>
			</div>
			<div class="home-meta-col">
				<label for="focal_point[<?php echo $sect; ?>]">Focal point</label>
				<input type="text" name="focal_point[<?php echo $sect; ?>]" value="<?php echo $sect_args['focal_point']; ?>" class="">
			</div>
		</div>
		<div class="home-meta-row">
			<div class="home-meta-col">
				<label for="bg_colour[<?php echo $sect; ?>]">Background Colour</label><br>
				<input class="section-colour-field" type="text" name="bg_colour[<?php echo $sect; ?>]" value="<?php esc_attr_e( $sect_args['bg_colour'] ); ?>">
			</div>
			<div class="home-meta-col">
				<label for="opacity[<?php echo $sect; ?>]">Opacity</label>
				<input type="range" min="0" max="1" step="0.1" name="opacity[<?php echo $sect; ?>]" value="<?php echo $sect_args['opacity']; ?>" class="">
			</div>
			<div class="home-meta-col">
				<label for="padding_top[<?php echo $sect; ?>]">Top spacing (padding)</label><br>
				<select name="padding_top[<?php echo $sect; ?>]">
					<option value="" <?php selected('', $sect_args['padding_top']); ?>>Normal</option>
					<option value="none" <?php selected('none', $sect_args['padding_top']); ?>>None</option>
					<option value="small" <?php selected('small', $sect_args['padding_top']); ?>>Small</option>
					<option value="large" <?php selected('large', $sect_args['padding_top']); ?>>Large</option>
				</select>
			</div>
			<div class="home-meta-col">
				<label for="padding_bot[<?php echo $sect; ?>]">Bottom spacing (padding)</label><br>
				<select name="padding_bot[<?php echo $sect; ?>]">
					<option value="" <?php selected('', $sect_args['padding_bot']); ?>>Normal</option>
					<option value="none" <?php selected('none', $sect_args['padding_bot']); ?>>None</option>
					<option value="small" <?php selected('small', $sect_args['padding_bot']); ?>>Small</option>
					<option value="large" <?php selected('large', $sect_args['padding_bot']); ?>>Large</option>
				</select>
			</div>
		</div>
		<div class="home-meta-row">
			<div class="home-meta-col">
				<label for="text_colour[<?php echo $sect; ?>]">Text Colour</label><br>
				<input class="section-colour-field" type="text" name="text_colour[<?php echo $sect; ?>]" value="<?php esc_attr_e( $sect_args['text_colour'] ); ?>">
			</div>
			<div class="home-meta-col">
				<label for="text_size[<?php echo $sect; ?>]">Text Size</label><br>
				<select name="text_size[<?php echo $sect; ?>]">
					<option value="" <?php selected('', $sect_args['text_size']); ?>>Normal</option>
					<option value="small" <?php selected('small', $sect_args['text_size']); ?>>Small</option>
					<option value="large" <?php selected('large', $sect_args['text_size']); ?>>Large</option>
				</select>
			</div>
			<div class="home-meta-col">
				<label for="text_align[<?php echo $sect; ?>]">Text Alignment</label><br>
				<select name="text_align[<?php echo $sect; ?>]">
					<option value="left" <?php selected('left', $sect_args['text_align']); ?>>Left</option>
					<option value="right" <?php selected('right', $sect_args['text_align']); ?>>Right</option>
					<option value="center" <?php selected('center', $sect_args['text_align']); ?>>Centre</option>
					<option value="justify" <?php selected('justify', $sect_args['text_align']); ?>>Justify</option>
					<option value="nowrap" <?php selected('nowrap', $sect_args['text_align']); ?>>No-wrap</option>
				</select>
			</div>
			<div class="home-meta-col">
				<label for="xclass[<?php echo $sect; ?>]">Extra classes</label>
				<input type="text" name="xclass[<?php echo $sect; ?>]" value="<?php echo $sect_args['xclass']; ?>" class="">
			</div>
		</div>
		<hr>

		<h4>Settings for the <?php echo wms_home_section_type_pretty($sect_args['type']); ?> section. NOTE: if you just changed the section type above, update the page first and then change the settings here.</h4>

		<?php if($sect_args['type'] == 'logos'){ ?>
			<div class="home-meta-row">
				<div class="home-meta-col">
					<label for="rows_xs[<?php echo $sect; ?>]">Number of rows (XS screns)</label>
					<select name="rows_xs[<?php echo $sect; ?>]">
						<?php for ($i=1; $i < 13; $i++) { ?>
							<option value="<?php echo $i; ?>" <?php selected($i, $sect_args['rows_xs']); ?>><?php echo $i; ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="home-meta-col">
					<label for="rows_sm[<?php echo $sect; ?>]">Number of rows (SM screns)</label>
					<select name="rows_sm[<?php echo $sect; ?>]">
						<?php for ($i=1; $i < 13; $i++) { ?>
							<option value="<?php echo $i; ?>" <?php selected($i, $sect_args['rows_sm']); ?>><?php echo $i; ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="home-meta-col">
					<label for="rows_md[<?php echo $sect; ?>]">Number of rows (MD screns)</label>
					<select name="rows_md[<?php echo $sect; ?>]">
						<?php for ($i=1; $i < 13; $i++) { ?>
							<option value="<?php echo $i; ?>" <?php selected($i, $sect_args['rows_md']); ?>><?php echo $i; ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="home-meta-col">
					<label for="rows_lg[<?php echo $sect; ?>]">Number of rows (LG screns)</label>
					<select name="rows_lg[<?php echo $sect; ?>]">
						<?php for ($i=1; $i < 13; $i++) { ?>
							<option value="<?php echo $i; ?>" <?php selected($i, $sect_args['rows_lg']); ?>><?php echo $i; ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<div class="home-meta-row">
				<div class="home-meta-col">
					<label for="rows_xl[<?php echo $sect; ?>]">Number of rows (XL screns)</label>
					<select name="rows_xl[<?php echo $sect; ?>]">
						<?php for ($i=1; $i < 13; $i++) { ?>
							<option value="<?php echo $i; ?>" <?php selected($i, $sect_args['rows_xl']); ?>><?php echo $i; ?></option>
						<?php } ?>
					</select>
				</div>
				<div class="home-meta-col">
					<label for="logo_size[<?php echo $sect; ?>]">Logo size</label>
					<select name="logo_size[<?php echo $sect; ?>]">
						<option value="xxxs" <?php selected('xxxs', $sect_args['logo_size']); ?>>XXX Small (160x100)</option>
						<option value="xxs" <?php selected('xxs', $sect_args['logo_size']); ?>>XX Small (240x150)</option>
						<option value="xs" <?php selected('xs', $sect_args['logo_size']); ?>>X Small (320x200)</option>
						<option value="sm" <?php selected('sm', $sect_args['logo_size']); ?>>Small (640x400)</option>
						<option value="post-thumb" <?php selected('post-thumb', $sect_args['logo_size']); ?>>Medium (720x480)</option>
						<option value="lg" <?php selected('lg', $sect_args['logo_size']); ?>>Large (1366x854)</option>
					</select>
				</div>
			</div>
			<div class="home-meta-row">
				<?php
				$logo_ids = explode(',', $sect_args['ids']);
				for ($i=0; $i < 12; $i++) {
					if($i == 6){ ?>
						<div class="home-meta-row">
					<?php } ?>
					<div id="<?php echo $args['id'].'-'.$i; ?>" class="home-meta-col logo-col">
						<label for="logo-image[<?php echo $sect; ?>][<?php echo $i; ?>]">Logo:</label><br>
						<div class="section-img-container">
							<?php
							$logo_id = 0;
							$sect_img_found = false;
							if(isset($logo_ids[$i])){
								$logo_id = absint(trim($logo_ids[$i]));
								if($logo_id > 0){
									$sect_img_src = wp_get_attachment_image_src( $logo_id, 'xs' );
									$sect_img_found = is_array($sect_img_src);
									if($sect_img_found){ ?>
										<img src="<?php echo $sect_img_src[0] ?>" alt="" style="max-width:100%;">
									<?php }else{
										$logo_id = 0;
									}
								}
							} ?>
						</div>
					    <a class="upload-section-img button button-secondary <?php if ( $sect_img_found  ) { echo 'hidden'; } ?>" 
							href="<?php echo esc_url( get_upload_iframe_src( 'image', $post->ID ) ); ?>"
							data-sectid="<?php echo $sect; ?>"
							data-logoid="<?php echo $i; ?>">
							Set Logo
					    </a>
					    <a class="delete-section-img button button-secondary <?php if ( ! $sect_img_found  ) { echo 'hidden'; } ?>" 
							href="#"
							data-sectid="<?php echo $sect; ?>"
							data-logoid="<?php echo $i; ?>">
							Delete logo
					    </a>
					    <input class="section-img-id" name="logo-image[<?php echo $sect; ?>][<?php echo $i; ?>]" type="hidden" value="<?php echo esc_attr( $logo_id ); ?>">
					</div>
					<?php if($i == 5){ ?>
						</div>
					<?php }
				} ?>
			</div>
			<input type="hidden" name="<?php echo $args['id'].'-content'; ?>" value="<?php echo $sect_args['content']; ?>">
			<input type="hidden" name="<?php echo $args['id'].'-content-1'; ?>" value="<?php echo $sect_args['content-1']; ?>">
			<input type="hidden" name="<?php echo $args['id'].'-content-2'; ?>" value="<?php echo $sect_args['content-2']; ?>">
			<input type="hidden" name="image_size[<?php echo $sect; ?>]" value="<?php echo $sect_args['image_size']; ?>">
			<input type="hidden" name="subhead[<?php echo $sect; ?>]" value="<?php echo $sect_args['subhead']; ?>">

		<?php }elseif($sect_args['type'] == 'thumb-left'){ ?>
			<div class="home-meta-row">
				<div class="home-meta-col">
					<label for="image_size[<?php echo $sect; ?>]">Image Size</label><br>
					<select name="image_size[<?php echo $sect; ?>]">
						<option value="" <?php selected('', $sect_args['image_size']); ?>>Default</option>
						<option value="small" <?php selected('small', $sect_args['image_size']); ?>>Small</option>
						<option value="large" <?php selected('large', $sect_args['image_size']); ?>>Large</option>
					</select>
				</div>
			</div>
			<label for="">Section Content</label>
			<?php wp_editor( $sect_args['content'] , $args['id'].'-content', array() ); ?>
			<input type="hidden" name="<?php echo $args['id'].'-content-1'; ?>" value="<?php echo $sect_args['content-1']; ?>">
			<input type="hidden" name="<?php echo $args['id'].'-content-2'; ?>" value="<?php echo $sect_args['content-2']; ?>">
			<input type="hidden" name="ids[<?php echo $sect; ?>]" value="<?php echo $sect_args['ids']; ?>">
			<input type="hidden" name="rows_xs[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_xs']; ?>">
			<input type="hidden" name="rows_sm[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_sm']; ?>">
			<input type="hidden" name="rows_md[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_md']; ?>">
			<input type="hidden" name="rows_lg[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_lg']; ?>">
			<input type="hidden" name="rows_xl[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_xl']; ?>">
			<input type="hidden" name="logo_size[<?php echo $sect; ?>]" value="<?php echo $sect_args['logo_size']; ?>">
			<input type="hidden" name="subhead[<?php echo $sect; ?>]" value="<?php echo $sect_args['subhead']; ?>">

		<?php }elseif($sect_args['type'] == '2-columns'){ ?>
			<div class="home-meta-row">
				<div class="home-meta-col half">
					<label for="">Column One</label>
					<?php 
					// wp_editor( string $content, string $editor_id, array $settings = array() )
					wp_editor( $sect_args['content'] , $args['id'].'-content', array() ); ?>
				</div>
				<div class="home-meta-col half">
					<label for="">Column Two</label>
					<?php wp_editor( $sect_args['content-1'] , $args['id'].'-content-1', array() ); ?>
				</div>
			</div>
			<input type="hidden" name="<?php echo $args['id'].'-content-2'; ?>" value="<?php echo $sect_args['content-2']; ?>">
			<input type="hidden" name="ids[<?php echo $sect; ?>]" value="<?php echo $sect_args['ids']; ?>">
			<input type="hidden" name="rows_xs[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_xs']; ?>">
			<input type="hidden" name="rows_sm[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_sm']; ?>">
			<input type="hidden" name="rows_md[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_md']; ?>">
			<input type="hidden" name="rows_lg[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_lg']; ?>">
			<input type="hidden" name="rows_xl[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_xl']; ?>">
			<input type="hidden" name="logo_size[<?php echo $sect; ?>]" value="<?php echo $sect_args['logo_size']; ?>">
			<input type="hidden" name="subhead[<?php echo $sect; ?>]" value="<?php echo $sect_args['subhead']; ?>">

		<?php }elseif($sect_args['type'] == '3-columns'){ ?>
			<div class="home-meta-row">
				<div class="home-meta-col third">
					<label for="">Column One</label>
					<?php 
					// wp_editor( string $content, string $editor_id, array $settings = array() )
					wp_editor( $sect_args['content'] , $args['id'].'-content', array() ); ?>
				</div>
				<div class="home-meta-col third">
					<label for="">Column Two</label>
					<?php wp_editor( $sect_args['content-1'] , $args['id'].'-content-1', array() ); ?>
				</div>
				<div class="home-meta-col third">
					<label for="">Column Three</label>
					<?php wp_editor( $sect_args['content-2'] , $args['id'].'-content-2', array() ); ?>
				</div>
			</div>
			<input type="hidden" name="ids[<?php echo $sect; ?>]" value="<?php echo $sect_args['ids']; ?>">
			<input type="hidden" name="rows_xs[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_xs']; ?>">
			<input type="hidden" name="rows_sm[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_sm']; ?>">
			<input type="hidden" name="rows_md[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_md']; ?>">
			<input type="hidden" name="rows_lg[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_lg']; ?>">
			<input type="hidden" name="rows_xl[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_xl']; ?>">
			<input type="hidden" name="logo_size[<?php echo $sect; ?>]" value="<?php echo $sect_args['logo_size']; ?>">
			<input type="hidden" name="subhead[<?php echo $sect; ?>]" value="<?php echo $sect_args['subhead']; ?>">

		<?php }elseif($sect_args['type'] == 'posts'){ ?>
			<p>No settings needed</p>
			<input type="hidden" name="<?php echo $args['id'].'-content'; ?>" value="<?php echo $sect_args['content']; ?>">
			<input type="hidden" name="<?php echo $args['id'].'-content-1'; ?>" value="<?php echo $sect_args['content-1']; ?>">
			<input type="hidden" name="<?php echo $args['id'].'-content-2'; ?>" value="<?php echo $sect_args['content-2']; ?>">
			<input type="hidden" name="image_size[<?php echo $sect; ?>]" value="<?php echo $sect_args['image_size']; ?>">
			<input type="hidden" name="ids[<?php echo $sect; ?>]" value="<?php echo $sect_args['ids']; ?>">
			<input type="hidden" name="rows_xs[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_xs']; ?>">
			<input type="hidden" name="rows_sm[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_sm']; ?>">
			<input type="hidden" name="rows_md[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_md']; ?>">
			<input type="hidden" name="rows_lg[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_lg']; ?>">
			<input type="hidden" name="rows_xl[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_xl']; ?>">
			<input type="hidden" name="logo_size[<?php echo $sect; ?>]" value="<?php echo $sect_args['logo_size']; ?>">
			<input type="hidden" name="subhead[<?php echo $sect; ?>]" value="<?php echo $sect_args['subhead']; ?>">

		<?php
		// training added for CC
		}elseif($sect_args['type'] == '' || $sect_args['type'] == 'left' || $sect_args['type'] == 'right' || $sect_args['type'] == 'training'){ ?>
			<div class="home-meta-row">
				<div class="home-meta-col">
					<label for="subhead[<?php echo $sect; ?>]">Subhead</label><br>
					<input type="text" name="subhead[<?php echo $sect; ?>]" value="<?php echo $sect_args['subhead']; ?>" class="">
				</div>
			</div>
			<label for="">Section Content</label>
			<?php wp_editor( $sect_args['content'] , $args['id'].'-content', array() ); ?>
			<input type="hidden" name="<?php echo $args['id'].'-content-1'; ?>" value="<?php echo $sect_args['content-1']; ?>">
			<input type="hidden" name="<?php echo $args['id'].'-content-2'; ?>" value="<?php echo $sect_args['content-2']; ?>">
			<input type="hidden" name="image_size[<?php echo $sect; ?>]" value="<?php echo $sect_args['image_size']; ?>">
			<input type="hidden" name="ids[<?php echo $sect; ?>]" value="<?php echo $sect_args['ids']; ?>">
			<input type="hidden" name="rows_xs[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_xs']; ?>">
			<input type="hidden" name="rows_sm[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_sm']; ?>">
			<input type="hidden" name="rows_md[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_md']; ?>">
			<input type="hidden" name="rows_lg[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_lg']; ?>">
			<input type="hidden" name="rows_xl[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_xl']; ?>">
			<input type="hidden" name="logo_size[<?php echo $sect; ?>]" value="<?php echo $sect_args['logo_size']; ?>">

		<?php }else{ ?>
			<label for="">Section Content</label>
			<?php wp_editor( $sect_args['content'] , $args['id'].'-content', array() ); ?>
			<input type="hidden" name="<?php echo $args['id'].'-content-1'; ?>" value="<?php echo $sect_args['content-1']; ?>">
			<input type="hidden" name="<?php echo $args['id'].'-content-2'; ?>" value="<?php echo $sect_args['content-2']; ?>">
			<input type="hidden" name="image_size[<?php echo $sect; ?>]" value="<?php echo $sect_args['image_size']; ?>">
			<input type="hidden" name="ids[<?php echo $sect; ?>]" value="<?php echo $sect_args['ids']; ?>">
			<input type="hidden" name="rows_xs[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_xs']; ?>">
			<input type="hidden" name="rows_sm[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_sm']; ?>">
			<input type="hidden" name="rows_md[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_md']; ?>">
			<input type="hidden" name="rows_lg[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_lg']; ?>">
			<input type="hidden" name="rows_xl[<?php echo $sect; ?>]" value="<?php echo $sect_args['rows_xl']; ?>">
			<input type="hidden" name="logo_size[<?php echo $sect; ?>]" value="<?php echo $sect_args['logo_size']; ?>">
			<input type="hidden" name="subhead[<?php echo $sect; ?>]" value="<?php echo $sect_args['subhead']; ?>">
		<?php } ?>

		<input type="hidden" name="youtube[<?php echo $sect; ?>]" value="<?php echo $sect_args['youtube']; ?>">
		<input type="hidden" name="video_quality[<?php echo $sect; ?>]" value="<?php echo $sect_args['video_quality']; ?>">
		<input type="hidden" name="num_items[<?php echo $sect; ?>]" value="<?php echo $sect_args['num_items']; ?>">
	</div>
	<?php
}

// save the section data
add_action('save_post', 'wms_home_save_metaboxes');
function wms_home_save_metaboxes($post_id){
	global $rpm_theme_options;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
    }
    if((int) get_option('page_on_front') <> $post_id){
        return $post_id;
    }
	$num_metas = absint($rpm_theme_options['home-sections']);

	/*
	error_log('wms_home_save_metaboxes');
	if ( is_array( $_POST ) || is_object( $_POST ) ) {
		error_log( print_r( $_POST, true ) );
	} else {
		error_log( $_POST );
	}
	*/
	$reordred_sections = array();
	for ($sect=0; $sect < $num_metas; $sect++) {
		if ( wp_verify_nonce( $_POST['home_metabox_nonce_'.$sect], 'home_metabox_'.$sect ) ){
			$sect_args = wms_home_empty_section_args();
			foreach ($sect_args as $key => $value) {
				switch ($key) {
					case 'content':
					case 'content-1':
					case 'content-2':
						// wp_editor uses a different format for the name tag
						if(isset($_POST['home-meta-'.$sect.'-'.$key])){
							$sect_args[$key] = wp_kses_post($_POST['home-meta-'.$sect.'-'.$key]);
						}
						break;

					case 'ids':
						if(isset($_POST['type'][$sect]) && $_POST['type'][$sect] == 'logos'){
							$sect_args[$key] = implode(',', $_POST['logo-image'][$sect]);
						}else{
							$sect_args[$key] = stripslashes(sanitize_text_field($_POST[$key][$sect]));
						}
						break;

					case 'visibility':
						if(isset($_POST['visibility'][$sect]) && $_POST['visibility'][$sect] == 'show'){
							$sect_args[$key] = '';
						}else{
							$sect_args[$key] = 'hide';
						}
						break;

					default:
						if(isset($_POST[$key][$sect])){
							$sect_args[$key] = stripslashes(sanitize_text_field($_POST[$key][$sect]));
						}
						break;
				}
			}
			$sect_seq = absint( $_POST['order'][$sect] ) * 10 + $sect;
			$key = 's-'.sprintf("%09d", $sect_seq);
			// wms_write_log('sect:'.$sect.' key:'.$key.' seq:'.$sect_seq.' post:'.$_POST['order'][$sect]);
			$reordred_sections[$key] = $sect_args;
		}
	}

	ksort($reordred_sections);
	$sect = 0;
	foreach ($reordred_sections as $key => $sect_args) {
		update_post_meta($post_id, '_wms_home_sect_'.$sect.'_args', $sect_args);
		$sect ++;
	}
}

// convert home meta into section html
function wms_home_meta_section($sect){
	global $post;
	$html = '';
	$sect_args = get_post_meta($post->ID, '_wms_home_sect_'.$sect.'_args', true);
	if(is_array($sect_args) && $sect_args['visibility'] == ''){
		$shortcode = '[section ';
		$std_args = array('type', 'image', 'parallax', 'bg_colour', 'opacity', 'text_colour', 'text_align', 'youtube', 'video_quality', 'num_items', 'title', 'width', 'image_size', 'ids', 'rows_xs', 'rows_sm', 'rows_md', 'rows_lg', 'rows_xl', 'logo_size', 'focal_point', 'image_xs', 'heading', 'subhead');
		// excludes 'text_size', 'xclass', 'content', 'content-1', 'content-2', 'padding_top', 'padding_bot' 
		foreach ($std_args as $std_arg) {
			if(isset($sect_args[$std_arg])){
				$shortcode .= $std_arg.'="'.$sect_args[$std_arg].'" ';
			}
		}
		$xclass = '';
		if(isset($sect_args['xclass'])){
			$xclass = $sect_args['xclass'].' ';
		}
		if(isset($sect_args['padding_top'])){
			switch ($sect_args['padding_top']) {
				case '':
					// normal
					break;
				case 'none':
					$xclass .= 'no-padd-top ';
					break;
				case 'small':
					$xclass .= 'sml-padd-top ';
					break;
				case 'large':
					$xclass .= 'big-padd-top ';
					break;
			}
		}
		if(isset($sect_args['padding_bot'])){
			switch ($sect_args['padding_bot']) {
				case '':
					// normal
					break;
				case 'none':
					$xclass .= 'no-padd-bot ';
					break;
				case 'small':
					$xclass .= 'sml-padd-bot ';
					break;
				case 'large':
					$xclass .= 'big-padd-bot ';
					break;
			}
		}
		if($sect_args['text_size'] <> ''){
			$xclass .= $sect_args['text_size'].' ';
		}
		$shortcode .= 'xclass="'.$xclass.'" ';
		$shortcode .= ']';
		if($sect_args['type'] == '2-columns'){
			$shortcode .= '<div class="row eq-height-backgrounds">';
			$shortcode .= '<div class="col-12 col-md-6 wms-section-content wms-col-0">';
			if(isset($sect_args['content'])){
				$shortcode .= wms_shortcode_empty_paragraph_fix(wpautop($sect_args['content']));
			}
			$shortcode .= '</div><!-- .col -->';
			$shortcode .= '<div class="col-12 col-md-6 wms-section-content wms-col-1">';
			if(isset($sect_args['content-1'])){
				$shortcode .= wms_shortcode_empty_paragraph_fix(wpautop($sect_args['content-1']));
			}
			$shortcode .= '</div><!-- .col -->';
			$shortcode .= '</div><!-- .row -->';
		}elseif($sect_args['type'] == '3-columns'){
			$shortcode .= '<div class="row eq-height-backgrounds">';
			$shortcode .= '<div class="col-12 col-md-4 wms-section-content wms-col-0">';
			if(isset($sect_args['content'])){
				$shortcode .= wms_shortcode_empty_paragraph_fix(wpautop($sect_args['content']));
			}
			$shortcode .= '</div><!-- .col -->';
			$shortcode .= '<div class="col-12 col-md-4 wms-section-content wms-col-1">';
			if(isset($sect_args['content-1'])){
				$shortcode .= wms_shortcode_empty_paragraph_fix(wpautop($sect_args['content-1']));
			}
			$shortcode .= '</div><!-- .col -->';
			$shortcode .= '<div class="col-12 col-md-4 wms-section-content wms-col-2">';
			if(isset($sect_args['content-2'])){
				$shortcode .= wms_shortcode_empty_paragraph_fix(wpautop($sect_args['content-2']));
			}
			$shortcode .= '</div><!-- .col -->';
			$shortcode .= '</div><!-- .row -->';
		}else{
			if(isset($sect_args['content'])){
				$shortcode .= wms_shortcode_empty_paragraph_fix(wpautop($sect_args['content']));
			}
		}
		$shortcode .= '[/section]';
		$html = do_shortcode($shortcode);
	}
	return $html;
}
