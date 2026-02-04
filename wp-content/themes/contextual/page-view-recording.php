<?php
/**
* Template Name: View Recording Page
* - Vimeo script is enqueued for this page template
* - NO LONGER USED (OR KEPT UP TO DATE!!!)
*/

// should this person be here???
$recording_id = 0;
if(isset($_GET['id'])){
	$recording_id = absint($_GET['id']);
}
if($recording_id == 0){
	wp_redirect( '/my-account' );
    exit;
}

$current_user = wp_get_current_user();

$recording_for_sale = get_post_meta($recording_id, 'recording_for_sale', true);
if($recording_for_sale == 'public'){
	// ok to view by anybody
}else{
	if(!cc_users_is_valid_user_logged_in()){
		wp_redirect( '/member-login' );
	    exit;
	}
	$recording_access = ccrecw_user_can_view($recording_id);
	if(!$recording_access['access']){
		wp_redirect( '/my-account' );
	    exit;
	}
}

date_default_timezone_set('Europe/London');

get_header();
while ( have_posts() ) : the_post(); ?>
	<div id="workshop-header-<?php echo $recording_id; ?>" class="workshop-header-wrap wms-section wms-section-std sml-padd-top sml-padd-bot">
		<div class="wms-sect-bg wms-bg-img px-xxl-5">
			<div class="container">
				<div class="workshop-header">
					<div class="row workshop-title-row">
						<div class="col text-center">
							<h1 class="workshop-title"><?php echo get_the_title($recording_id); ?></h1>
							<?php
							$workshop_subtitle = get_post_meta( $recording_id, 'subtitle', true);
							if($workshop_subtitle <> ''){ ?>
								<h3 class="workshop-subtitle"><?php echo $workshop_subtitle; ?></h3>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
	$feat_img_id = get_post_thumbnail_id( $recording_id );
	if($feat_img_id > 0){
		echo wms_section_css('workshop-header-'.$recording_id, '#eef1f4', '', '#ffffff', 'rgba(0,0,0,0.5)', $feat_img_id, '', '', 'center', '', 'sect');
	}

	/*
	$modules_html = '';
	for ($i=0; $i < 10; $i++) { 
		$mod_num = $i + 1;
		$module_name = get_post_meta($recording_id, 'module_name_'.$i, true);
		$module_vimeo = get_post_meta($recording_id, 'module_vimeo_'.$i, true);
		if($module_name <> '' && $module_vimeo <> ''){
			$modules_html .= '
				<div class="accordion-item">
					<h2 class="accordion-header" id="module-'.$mod_num.'">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#module-body-'.$mod_num.'" aria-expanded="true" aria-controls="module-body-'.$mod_num.'">'.$module_name.'</button>
					</h2>
					<div id="module-body-'.$mod_num.'" class="accordion-collapse collapse" aria-labelledby="module-'.$mod_num.'" data-bs-parent="#training-modules">
						<div class="accordion-body">';
			$num_views = 0;
			if(isset($recording_meta['modules'][$i]['num_views'])){
				$num_views = $recording_meta['modules'][$i]['num_views'];
			}
			$viewed_end = 'no';
			if(isset($recording_meta['modules'][$i]['viewed_end'])){
				$viewed_end = $recording_meta['modules'][$i]['viewed_end'];
			}
			$viewing_time = 0;
			if(isset($recording_meta['modules'][$i]['viewing_time'])){
				$viewing_time = $recording_meta['modules'][$i]['viewing_time'];
			}
			$modules_html .= '
					        <div id="rec-video-'.$i.'" class="hd-video-container HD1080 rec-video" data-module="'.$mod_num.'" data-source="vimeo" data-recid="'.$recording_id.'-'.$i.'" data-lastviewed="'.date('Y-m-d H:i:d').'" data-numviews="'.$num_views.'" data-viewedend="'.$viewed_end.'" data-viewingtime="'.$viewing_time.'">
						        <iframe class="rec-iframe" width="1920" height="1080" src="https://player.vimeo.com/video/'.$module_vimeo.'" frameborder="0" allowfullscreen></iframe>
					        </div>
				        </div>
			        </div>
		        </div>';
		}
	}
	*/
	?>

	<div class="wms-section wms-section-std sml-padd-top sml-padd-bot">
		<div class="wms-sect-bg">
			<div class="container">

				<div class="row mb-5">

					<div class="col-lg-4">
						<div class="bullet-wrapper">
							<h3>Training details:</h3>
							<?php
							if($recording_for_sale <> 'public' && $recording_access['expiry_date'] <> ''){
								echo '<div class="bullet"><i class="fa-solid fa-lock-open fa-fw"></i>Your access expires '.$recording_access['expiry_date'].'</div>';
							}
							$who = cc_presenters_names( $recording_id);
							if($who <> ''){
								echo '<div class="bullet"><i class="fa-solid fa-user fa-fw"></i>'.$who.'</div>';
							}
							$duration = get_post_meta( $recording_id, 'duration', true);
							if($duration <> ''){
								echo '<div class="bullet"><i class="fa-solid fa-hourglass-half fa-fw"></i>'.$duration.'</div>';
							}
							/*
							$cat = get_post_meta($recording_id, 'category', true);
							if($cat <> ''){
								echo '<div class="bullet"><i class="fa-solid fa-folder fa-fw"></i>'.$cat.'</div>';
							}
							*/
							$ce_credits = (float) get_post_meta( $recording_id, 'ce_credits', true );
							if( $ce_credits > 0 ){
								echo '<div class="bullet"><i class="fa-solid fa-graduation-cap"></i>'.$ce_credits.' CE credits</div>';
							}
							$who_for = get_post_meta($recording_id, 'who_for', true);
							if($who_for <> ''){
								echo '<div class="bullet"><i class="fa-solid fa-award fa-fw"></i>'.do_shortcode($who_for).'</div>';
							}
							$viewer_content = get_post_meta($recording_id, 'viewer_content', true);
							if($viewer_content <> ''){ ?>
								<div class="viewer-content">
									<?php echo wpautop(do_shortcode($viewer_content)); ?>
								</div>
							<?php }
				            $recording_feedback = get_post_meta($recording_id, 'workshop_feedback', true);
							if($recording_feedback <> ''){ ?>
			                    <p><a class="btn btn-primary btn-sm feedback-btn" href="<?php echo $recording_feedback; ?>" target="_blank"><i class="fa-solid fa-comments"></i> Give feedback</a></p>
							<?php } ?>
							
						</div>
					</div>

					<div class="col-lg-8">

						<?php
						$show_click_msg = true;
						$vimeo_id = get_post_meta( $recording_id, 'vimeo_id', true );
						// $recording_meta = get_user_meta($current_user->ID, 'cc_rec_wshop_'.$recording_id, true);
						$recording_meta = get_recording_meta( $current_user->ID, $recording_id );
						$recording_url = get_post_meta($recording_id, 'recording_url', true);
						$accordions = false;
						for ($i=0; $i < 10; $i++) { 
							$module_name = get_post_meta($recording_id, 'module_name_'.$i, true);
							if($module_name <> ''){
								$accordions = true;
								echo '<div class="accordion accordion-flush training-accordion" id="training-modules">';
								break;
							}
						}

						$collapsed = false;
						$mod_num = 0;

						$num_views = 0;
						if(isset($recording_meta['num_views'])){
							$num_views = $recording_meta['num_views'];
						}
						$viewed_end = 'no';
						if(isset($recording_meta['viewed_end'])){
							$viewed_end = $recording_meta['viewed_end'];
						}
						$viewing_time = 0;
						if(isset($recording_meta['viewing_time'])){
							$viewing_time = $recording_meta['viewing_time'];
						}

						if($vimeo_id <> ''){
							$mod_title = get_the_title($recording_id);
							$chat_module = 9999;

							echo cc_recordings_module_html($accordions, $collapsed, $mod_num, $mod_title, $recording_id, $num_views, $viewed_end, $viewing_time, $vimeo_id, $chat_module);

							$recording_files = resources_show_list($recording_id);
							if($recording_files <> ''){ ?>
								<h4 class="mt-4">Resources</h4>
								<?php echo $recording_files;
								$show_click_msg = false;
							}

							if($accordions){
								// done this way to include the files in the accordion
								echo '</div></div></div>';
							}

							$collapsed = true;
							$mod_num ++;
						}

						for ($i=0; $i < 10; $i++) { 
							$module_name = get_post_meta($recording_id, 'module_name_'.$i, true);
							$module_vimeo = get_post_meta($recording_id, 'module_vimeo_'.$i, true);
							if($module_name <> '' && $module_vimeo <> ''){
								$num_views = 0;
								if(isset($recording_meta['modules'][$i]['num_views'])){
									$num_views = $recording_meta['modules'][$i]['num_views'];
								}
								$viewed_end = 'no';
								if(isset($recording_meta['modules'][$i]['viewed_end'])){
									$viewed_end = $recording_meta['modules'][$i]['viewed_end'];
								}
								$viewing_time = 0;
								if(isset($recording_meta['modules'][$i]['viewing_time'])){
									$viewing_time = $recording_meta['modules'][$i]['viewing_time'];
								}

								// if there is a main vid, mod_num will start at 1, not 0
								echo cc_recordings_module_html($accordions, $collapsed, $mod_num, $module_name, $recording_id.'-'.$i, $num_views, $viewed_end, $viewing_time, $module_vimeo, $i);

								$recording_files = resources_show_list($recording_id, $i, $show_click_msg);
								if($recording_files <> ''){ ?>
									<h4 class="mt-4"><?php echo get_post_meta($recording_id, 'module_name_'.$i, true); ?></h4>
									<?php echo $recording_files;
									$show_click_msg = false;
								}

								if($accordions){
									// done this way to include the files in the accordion
									echo '</div></div></div>';
								}

								$collapsed = true;
								$mod_num ++;
							}
						}

						if($accordions){
							echo '</div>';
						}

						if($mod_num == 0 && $recording_url <> ''){
							?>
							<div class='flex-video' style='padding-bottom:56.25%'>
								<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->
								<video id="rec-video" playsinline controls class="wp-video-shortcode rec-video" data-module="0" width="1920" height="1080" preload="metadata" data-controls="controls" data-source="cc" data-recid="<?php echo $recording_id; ?>" data-lastviewed="<?php echo date('d/m/Y H:i:d'); ?>" data-numviews="<?php echo $num_views; ?>" data-viewedend="<?php echo $viewed_end; ?>" data-viewingtime="<?php echo $viewing_time; ?>">
									<source type="video/mp4" src="<?php echo $recording_url; ?>" />
									<a href="<?php echo $recording_url; ?>"><?php echo $recording_url; ?></a>
								</video>
							</div>
							<?php
						} ?>

					</div>

				</div>
			</div>
		</div>
	</div>

	<?php // the presenter modal ?>
	<div id="presenter-modal" class="modal cc-modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body"><div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div></div>
			</div>
		</div>
	</div>

<?php
endwhile; // end of the loop.
get_footer();


