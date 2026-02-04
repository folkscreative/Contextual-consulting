<?php

// get the presenters images (if there are any)
/*
$presenter_image_html = '';
$post_thumbnail_id = get_post_thumbnail_id( get_the_ID() );
if ( !empty( $post_thumbnail_id ) ){
	$presenter_image_html = wms_media_image_html($post_thumbnail_id, 'post-thumb', true, '3:2', '', 'workshop-image');
}
*/

/*
$user_currency = cc_currency_get_user_currency();
$workshop_pricing = cc_workshop_price(get_the_ID(), $user_currency); // no prices retrieved if workshops_show_this_workshop() is false
$from_price = $workshop_pricing['price_text'];
$workshop_currency = $workshop_pricing['curr_found'];
$currency_icon = cc_currencies_icon($workshop_currency);

$user_id = get_current_user_id(); // 0 if not logged in
$user_timezone = cc_timezone_get_user_timezone($user_id);
$pretty_timezone = cc_timezone_get_user_timezone_pretty($user_id, $user_timezone);

$multi_event = workshop_is_multi_event(get_the_ID());
$show_workshop = workshops_show_this_workshop(get_the_ID()); // can return a recording_id now (v1.45) ... don't forget that true == 1 !!!!

$portal_user = get_user_meta( $user_id, 'portal_user', true );
// $block_nlft = get_post_meta( get_the_ID(), 'block_nlft', true );
// $block_cnwl = get_post_meta( get_the_ID(), 'block_cnwl', true );

$org_reg_status = org_register_status( $portal_user, get_the_ID() );
$reg_btn_disabled = '';
if( $org_reg_status['status'] == 'block' || $org_reg_status['status'] == 'expired' ){
	$reg_btn_disabled = 'disabled';
	$reg_btn = '<a href="#register" class="btn btn-reg btn-lge '.$reg_btn_disabled.'">Register now</a></p><p class="small lh-sm text-center text-bg-warning p-3"><i class="fa-solid fa-circle-exclamation"></i> '.$org_reg_status['message'];
	$reg_btn_footer = '<button type="button" id="training-footer-btn" class="btn btn-lge btn-reg" '.$reg_btn_disabled.'>Register<span class="d-none d-md-inline"> Now</span></button></p><p class="small lh-sm text-center text-bg-warning p-3"><i class="fa-solid fa-circle-exclamation"></i> '.$org_reg_status['message'];
}elseif( $multi_event){
	$reg_btn = '<a href="#register" class="btn btn-reg btn-lge">Register now</a>';
	// $reg_btn_footer = '<a href="#register" class="btn btn-reg btn-lge">Register<span class="d-none d-md-inline"> Now</span></a>';
	$reg_btn_footer = '<button type="button" id="training-footer-btn" class="btn btn-lge btn-reg">Register<span class="d-none d-md-inline"> Now</span></button>';
	$raw_price = 0;
	$all_events_discount = (float) get_post_meta(get_the_ID(), 'all_events_discount', true);
}else{
	$reg_btn = '<button class="btn btn-reg btn-lge" form="reg-form">Register now</button>';
	if($workshop_pricing['earlybird_msg'] <> '' || isset($workshop_pricing['student_price'])){
		$reg_btn_footer = '<button type="button" id="training-footer-btn" class="btn btn-lge btn-reg"><span class="d-none d-md-inline">Show </span>More</button>';
	}else{
		$reg_btn_footer = '<button class="btn btn-reg btn-lge" form="reg-form">Register<span class="d-none d-md-inline"> Now</span></button>';
	}
	if( $org_reg_status['message'] <> '' ){
		$reg_btn .= '</p><p class="small lh-sm text-center text-bg-warning p-3"><i class="fa-solid fa-circle-exclamation"></i> '.$org_reg_status['message'];
		$reg_btn_footer = '</p><p class="small lh-sm text-center text-bg-warning p-3"><i class="fa-solid fa-circle-exclamation"></i> '.$org_reg_status['message'];
	}
	$raw_price = $workshop_pricing['raw_price'];
	$all_events_discount = 0;
}
*/

// Get user and workshop info (still needed for display purposes)
$user_id = get_current_user_id(); // 0 if not logged in
$user_timezone = cc_timezone_get_user_timezone($user_id);
$pretty_timezone = cc_timezone_get_user_timezone_pretty($user_id, $user_timezone);

// Get workshop pricing for display (currency switcher, price display, etc.)
$user_currency = cc_currency_get_user_currency();
$workshop_pricing = cc_workshop_price(get_the_ID(), $user_currency);
$from_price = $workshop_pricing['price_text'];
$workshop_currency = $workshop_pricing['curr_found'];
$currency_icon = cc_currencies_icon($workshop_currency);

// Generate registration button using unified function
// This handles: Add to Account, standard registration, blocked users, multi-event, etc.
$button_data = cc_training_register_button( get_the_ID() );
$reg_btn = $button_data['main'];
$reg_btn_footer = $button_data['footer'];
$registration_form = $button_data['form'];

// Set variables needed for multi-event logic and form
if( isset( $button_data['data']['is_multi_event'] ) && $button_data['data']['is_multi_event'] ){
	$raw_price = 0;
	$all_events_discount = (float) get_post_meta(get_the_ID(), 'all_events_discount', true);
}else{
	$raw_price = $button_data['data']['price'];
	$all_events_discount = 0;
}

// These are used in the multi-event section
$num_events = 1;
$num_free = 0;

// Legacy variables (in case other code references them)
$multi_event = isset( $button_data['data']['is_multi_event'] ) ? $button_data['data']['is_multi_event'] : false;
$show_workshop = workshops_show_this_workshop(get_the_ID());

$reg_permalink = '/registration';
$num_events = 1;
$num_free = 0;

$now = time();

get_header();
while ( have_posts() ) : the_post(); ?>
	<div id="workshop-header-<?php echo get_the_ID(); ?>" class="workshop-header-wrap wms-section wms-section-std sml-padd-top sml-padd-bot">
		<div class="wms-sect-bg wms-bg-img px-xxl-5 d-flex align-items-center">
			<div class="container">
				<div class="workshop-header">
					<div class="row workshop-title-row">
						<div class="col text-center">
							<h1 class="workshop-title"><?php the_title(); ?></h1>
							<?php
							$workshop_subtitle = get_post_meta(get_the_ID(), 'subtitle', true);
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
	$feat_img_id = get_post_thumbnail_id( get_the_ID() );
	if($feat_img_id > 0){
		// echo wms_section_css('workshop-header-'.get_the_ID(), '#eef1f4', '', '#ffffff', 'rgba(0,0,0,0.5)', $feat_img_id, '', '', 'center', '', 'sect');
		echo '<style>'.wms_page_slider_slide_css(
			$feat_img_id, 
			'#workshop-header-'.get_the_ID().' .wms-sect-bg', 
			get_post_meta(get_the_ID(), '_featured_bg_position', true), 
			get_post_meta(get_the_ID(), '_featured_height', true), 
			get_post_meta(get_the_ID(), '_featured_wash_opacity', true)
		).'</style>';
	}
	?>

	<div class="workshop-body-wrap wms-section wms-section-std px-xxl-5 sml-padd-top sml-padd-bot">
		<div class="wms-sect-bg">
			<div class="container">
				<div class="workshop-body">
					<div class="row">
						<div class="col-lg-9">
							<div class="row bullet-row">
								<div class="col-8 offset-2 offset-lg-0 col-lg-4 image-col">
									<?php echo cc_presenters_images(get_the_ID()) ?>
								</div>
								<div class="col-12 col-md-8 offset-md-2 col-lg-8 offset-lg-0 bullet-col">
									<div class="bullet-wrapper">
										<?php
										// echo cc_topics_show(get_the_ID(), 'xs');
										$who = cc_presenters_names(get_the_ID());
										if($who <> ''){
											echo '<div class="bullet"><i class="fa-solid fa-user fa-fw"></i>'.$who.'</div>';
										}
										$where = get_post_meta(get_the_ID(), 'event_1_venue_name', true);
										if($where <> ''){
											echo '<div class="bullet"><i class="fa-solid fa-location-dot fa-fw"></i>In person: ';
											$where_link = get_post_meta( get_the_ID(), 'meta_c', true );
											if( $where_link <> '' ){
												echo '<a href="'.esc_url($where_link).'" target="_blank">';
											}
											echo $where;
											if( $where_link <> '' ){
												echo '</a>';
											}
											echo '</div>';
										}
										$text_dates = get_post_meta( get_the_ID(), 'prettydates', true );
										if($text_dates <> ''){
											echo '<div class="bullet"><i class="fa-solid fa-hourglass-half fa-fw"></i>'.$text_dates.'</div>';
										}
										/*
										$cat = get_post_meta(get_the_ID(), 'category', true);
										if($cat <> ''){
											echo '<div class="bullet"><i class="fa-solid fa-folder fa-fw"></i>'.$cat.'</div>';
										}
										*/
										$ce_credits = (float) get_post_meta( get_the_ID(), 'ce_credits', true );
										if( $ce_credits > 0 ){
											echo '<div class="bullet"><i class="fa-solid fa-graduation-cap"></i>'.$ce_credits.' CE credits</div>';
										}
										$training_levels = cc_topics_training_levels( get_the_ID() );
										if( $training_levels <> '' ){
											echo '<div class="bullet"><i class="fa-solid fa-star fa-fw"></i>'.$training_levels.'</div>';
											// echo '<div class="bullet"><i class="fa-solid fa-star fa-fw"></i><a href="#" data-bs-toggle="tooltip" data-bs-title="Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque sodales suscipit ligula eu laoreet. Donec tristique, erat sed accumsan pulvinar">'.$training_levels.'</a></div>';
										}
										$rec_avail = get_post_meta(get_the_ID(), 'rec_avail', true);
										if($rec_avail <> ''){
											echo '<div class="bullet"><i class="fa-solid fa-video fa-fw"></i>'.$rec_avail.'</div>';
										}
										$who_for = get_post_meta(get_the_ID(), 'who_for', true);
										if($who_for <> ''){
											echo '<div class="bullet"><i class="fa-solid fa-award fa-fw"></i>'.do_shortcode($who_for).'</div>';
										}
										?>
									</div>
								</div>
							</div>

							<hr class="border-2 d-lg-none">

							<?php
							// the redirect message panel
							$msg_panel = get_post_meta( get_the_ID(), '_redir_msg_panel', true );
							if( $msg_panel == 'yes' ){
								$msg_panel_heading = get_option( 'cc-redir-panel-form-heading', '' );
								$msg_panel_text = get_option( 'cc-redir-panel-form-text', '' );
								if( $msg_panel_heading <> '' || $msg_panel_text <> '' ){ ?>
									<div class="mb-3 mt-5 grad-bg p-5">
										<?php if( $msg_panel_heading <> '' ){ ?>
											<h3><?php echo $msg_panel_heading; ?></h3>
										<?php } ?>
										<?php if( $msg_panel_text <> '' ){ ?>
											<div><?php echo $msg_panel_text; ?></div>
										<?php }
										echo cc_custom_training_buttons_core();
										?>
									</div>
								<?php }
							}

							/*
							$series_id = series_id_for_course( get_the_ID() );
							if( $series_id ){
								echo course_series_panel( $series_id, get_the_ID() );
							}
							*/
							?>

							<div class="">
								<?php
								$voucher_banner = cc_voucher_offer_training_banner('w', get_the_ID(), $workshop_currency, $raw_price);
								echo $voucher_banner;
								?>
							</div>

							<div class="">
								<?php
								echo cc_upsell_training_banner( get_the_ID() );
								?>
							</div>

							<div class="row description-row">
								<?php /*
								<div class="col-12 col-lg-4">
									<h2>Course Description</h2>
								</div>
								<div class="col-12 col-lg-8 content-wrapper">
								*/ ?>
								<div class="col-12 content-wrapper">
									<?php
									the_content();
									/*
									<div class="content-limited faded">
										<?php the_content(); ?>
									</div>
									<div class="content-delimiter text-center">
										<a href="javascript:void(0)" class="btn btn-sm btn-primary content-delimiter-btn">Show More</a>
									</div>
									*/ ?>
								</div>
							</div>

							<?php echo cc_train_acc_accordions( get_the_ID() ); ?>

						</div>
						<div class="d-none d-lg-block col-lg-3 floater-wrapper">
							<div class="floater">

								<div class="floater-inner">
									<h6 class="workshop-title"><?php the_title(); ?></h6>
									<?php
							        $venue = get_post_meta( get_the_ID(), 'event_1_venue_name', true );
									$pretty_dates = workshop_calculated_prettydates(get_the_ID(), $user_timezone);
									$sessions = '';
									$num_sess = workshop_number_sessions(get_the_ID());
									if($num_sess > 1){
										$sessions = $num_sess.' sessions: ';
									}
									if( $venue <> '' && $pretty_dates['london_date'] <> '' ){
										echo '<div class="bullet"><i class="fa-solid fa-calendar-days fa-fw"></i><span class="locale-date">'.$sessions.$pretty_dates['london_date'].'</span></div>';
									}elseif( $venue == '' && $pretty_dates['locale_date'] <> '' ){
										echo '<div class="bullet"><i class="fa-solid fa-calendar-days fa-fw"></i><span class="locale-date">'.$sessions.$pretty_dates['locale_date'].'</span></div>';
									}
									if(!$multi_event && $pretty_dates['locale_start_time'] <> ''){
										if( $num_sess > 1){
											if( $venue <> '' ){
									            // face to face workshop
												echo '<div class="bullet face-to-face venue"><i class="fa-solid fa-location-dot fa-fw"></i>In person: ';
												$venue_link = get_post_meta( get_the_ID(), 'meta_c', true );
												if( $venue_link <> '' ){
													echo '<a href="'.esc_url($venue_link).'" target="_blank">';
												}
												echo $venue;
												if( $venue_link <> '' ){
													echo '</a>';
												}
												echo '</div>';
									            echo '<div class="bullet"><i class="fa-solid fa-clock fa-fw"></i>Starts: <span class="start-time">'.$pretty_dates['london_start_time'].'</span> <small>(London time)</small><br><a class="cc-full-schedule" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#workshop-times-modal" data-type="w" data-id="'.get_the_ID().'">Course schedule</a></div>';
											}else{
												echo '<div class="bullet"><i class="fa-solid fa-clock fa-fw"></i>Starts: <span class="start-time">'.$pretty_dates['locale_start_time'].'</span> <small>(<span class="user-timezone">'.$pretty_timezone.'</span> time)</small><br><a class="cc-full-schedule" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#workshop-times-modal" data-type="w" data-id="'.get_the_ID().'">Course schedule</a><br><small><a class="cc-timezone-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-time-modal" data-type="w" data-id="'.get_the_ID().'">change timezone</a></small></div>';
											}
										}else{
									        if( $venue <> '' ){
									            echo '<div class="bullet face-to-face venue"><i class="fa-solid fa-location-dot fa-fw"></i>In person: ';
									            $venue_link = get_post_meta( get_the_ID(), 'meta_c', true );
									            if( $venue_link <> '' ){
													echo '<a href="'.esc_url($venue_link).'" target="_blank">';
									            }
									            echo $venue;
												if( $venue_link <> '' ){
													echo '</a>';
												}
												echo '</div>';
									            echo '<div class="bullet"><i class="fa-solid fa-clock fa-fw"></i> London: '.$pretty_dates['london_datetime'].'</div>';
									        }else{
												echo '<div class="bullet"><i class="fa-solid fa-clock fa-fw"></i><span class="locale-times">'.$pretty_dates['locale_times'].'</span> <small>(<span class="user-timezone">'.$pretty_timezone.'</span> time) <a class="cc-timezone-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-time-modal" data-type="w" data-id="'.get_the_ID().'">change timezone</a></small></div>';
											}
										}
									}
									/*
									if($pretty_dates['locale_date'] <> ''){
										if(!$multi_event && $pretty_dates['locale_start_time'] <> ''){
											echo '<div class="bullet"><i class="fa-solid fa-clock fa-fw"></i>Starts: <span class="start-time">'.$pretty_dates['locale_start_time'].'</span> <small>(<span class="user-timezone">'.$pretty_timezone.'</span> time) <a class="cc-timezone-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-time-modal" data-type="w" data-id="'.get_the_ID().'">change timezone</a></small></div>';
										}
									}
									*/
									if($from_price <> ''){ ?>
											<div class="bullet">
												<span class="currency-icon"><?php echo $currency_icon; ?></span><span class="from-price"><?php echo $from_price; ?></span>.
												<?php echo $workshop_pricing['earlybird_msg'];
										if(isset($workshop_pricing['student_price'])){ ?>
											</div>
											<div class="bullet">
												<i class="fa-solid fa-graduation-cap fa-fw"></i>
												Student price
												<span class="student-price">
													<?php echo $workshop_pricing['student_price_formatted']; ?>
												</span>
												<a href="#" class="" data-bs-toggle="modal" data-bs-target="#student-modal"><i class="fa-solid fa-circle-info m-0"></i></a>
												<?php /* <a href="#" data-bs-container="body" data-bs-toggle="popover" data-bs-placement="top" data-bs-content="<?php echo cc_phrases_student_discount_terms(); ?>" data-bs-trigger="focus"><i class="fa-solid fa-circle-info m-0"></i></a> */ ?>
												&nbsp;&nbsp;Select:
												<div class="form-switch d-inline"><input class="form-check-input" type="checkbox" role="switch" id="reg-student" value="yes"></div>
											</div>
											<div class="bullet">
												<i class="fa-solid fa-arrow-right-arrow-left"></i>
										<?php }else{ ?>
												<small>
										<?php }
										if( $from_price <> 'Free' ){ ?>
													<a class="cc-currency-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-curr-modal" data-type="w" data-id="<?php echo get_the_ID(); ?>">change currency</a>
										<?php }
										if(!isset($workshop_pricing['student_price'])){ ?>
												</small>
										<?php } ?>
											</div>
										<?php if( $show_workshop > 1 ){ ?>
											<div class="wksp-full"><strong>IMPORTANT!</strong><br>This live training is now full. Click to register for the recording (available after the live training).</div>
										<?php } ?>
											<p class="text-center mt-3"><?php echo $reg_btn; ?></p>
									<?php } ?>
								</div>

								<?php // echo $voucher_banner; ?>

								<?php
								// NEW: Check if this workshop is part of a series and display the training groups selection card
								$series_id = series_id_for_course(get_the_ID());
								if($series_id){
								    echo training_groups_selection_card($series_id);
								}
								?>

							</div><!-- .floater -->

						</div><!-- .floater-wrapper -->

					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
	if($show_workshop && $multi_event){ ?>
		<div class="d-none d-lg-block">
			<a id="register"></a>
			<hr class="border-2">
			<div class="workshop-register-wrap wms-section wms-section-std sml-padd-top sml-padd-bot px-xxl-5">
				<div class="wms-sect-bg">
					<div class="container-fluid">
						<div class="row">
							<div class="col-12 col-lg-3">
								<h2>Register now:</h2>
								<p>Select the events you will attend:</p>
							</div>
							<div class="col-12 col-lg-9">
								<div class="events-wrap px-4 px-lg-0">
									<?php
									$events_passed = false;
									$num_events = 0;
									$all_events = 0;
									$num_free = 0;
									$hide_dates = get_post_meta(get_the_ID(), 'hide_dates', true);
									for($i = 1; $i<16; $i++){
										$event_name = get_post_meta( get_the_ID(), 'event_'.$i.'_name', true );
										if($event_name <> ''){
											$all_events ++;
											$event_dt = workshop_event_display_date_time(get_the_ID(), $i, $user_timezone);
											$show_date = false;
											if($event_dt['timestamp'] <> ''){
												if($now > $event_dt['timestamp']){
													$events_passed = true; // so we do not offer the "buy them all" discount
													continue;
												}else{
													$show_date = true;
												}
											}
											$num_events ++;
											$event_free = get_post_meta( get_the_ID(), 'event_'.$i.'_free', true );
											if($event_free == 'yes'){
												$num_free ++;
											}
											?>
											<div class="animated-card">
												<div class="wkshp-event wms-background animated-card-inner">
													<div class="row">
														<div class="col-12 col-md-5 wkshp-name">
															<h5><?php echo $event_name; ?></h5>
														</div>
														<div class="col-12 col-md-7">
															<div class="row">
																<div class="col-12 col-xl-5 wkshp-date">
																	<?php
																	if($show_date && $hide_dates <> 'yes'){
																		echo '<div class="bullet"><i class="fa-solid fa-calendar-days fa-fw"></i><span class="event-time-'.$i.'">'.$event_dt['date'].' '.$event_dt['time'].'</span></div>';
																	}
																	?>
																</div>
																<div class="col-9 col-md-9 col-xl-5 wkshp-price">
																	<?php
																	if($event_free == 'yes'){
																		echo 'FREE';
																		$event_price = 0;
																	}else{
																		$event_price = workshops_event_price(get_the_ID(), $i, $workshop_currency);
																		echo '<div class="bullet"><span class="event-price event-price-'.$i.'">'.workshops_pretty_price($event_price, $workshop_currency).'</span></div>';
																	}
																	?>
																</div>
																<div class="col-3 col-xl-2 wkshp-select">
																	<?php if(get_post_meta(get_the_ID(), 'event_'.$i.'_closed', true) == 'closed'){ ?>
																		<p class="event-full">FULL</p>
																	<?php }else{ ?>
																		<label for="event-chooser-<?php echo $i; ?>" class="event-chooser-wrapper">
																			<input id="event-chooser-<?php echo $i; ?>" class="event-chooser" type="checkbox" name="event-chooser-<?php echo $i; ?>" data-free="<?php echo $event_free; ?>" data-eventid="<?php echo $i; ?>" data-price="<?php echo $event_price; ?>">
																			<div class="event-chooser-slider">
																				<div class="event-chooser-knob"></div>
																			</div>
																		</label>
																	<?php } ?>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										<?php
										}
									}
									?>
								</div>
								<div class="row">
									<div class="col-12 col-md-4 order-md-2">
										<div class="events-reg-wrap px-4 px-lg-0">
											<div class="animated-card">
												<div class="events-reg wms-background animated-card-inner">
													<h6 class="mb-2">Total: <span id="tot-price" class="tot-price" data-discount="<?php echo $all_events_discount; ?>">0.00</span> <small>(+ VAT if applicable)</small></h6>
													<?php if(!$events_passed && $all_events_discount > 0){ ?>
														<p class="discounting"><?php echo $all_events_discount; ?>% discount applies when you book all events</p>
													<?php } ?>
													<p class="text-end">
														<button class="comp-reg btn btn-reg btn-sm disabled" data-link="#" title="Select an event first" form="reg-form">Complete registration</button>
													</p>
												</div>
											</div>
										</div>
									</div>
									<div class="col-12 col-md-8 order-md-1">
										<p class="events-note">* Note: All event dates/times are shown in <span class="user-timezone"><?php echo $pretty_timezone; ?></span> time (<a class="cc-timezone-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-time-modal" data-type="w" data-id="<?php echo get_the_ID(); ?>">change timezone</a>). You can also <a class="cc-currency-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-curr-modal" data-type="w" data-id="<?php echo get_the_ID(); ?>">change currency</a>.</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php }

	$faqs = cc_faqs_training_get_ids( get_the_ID() );
	if(!empty($faqs)){ ?>
		<div class="workshop-faqs-wrap wms-section wms-section-std sml-padd-top sml-padd-bot">
			<div class="wms-sect-bg wms-bg-img parallax">
				<div class="container-fluid">
					<div class="row">
						<div class="col-lg-10 offset-lg-1 col-xl-8 offset-xl-2 col-xxl-6 offset-xxl-3">
							<div class="wms-background-outer">
								<div class="animated-card">
									<div class="wms-background bg-white animated-card-inner">
										<div class="inner">
											<h2>FAQs</h2>
											<div id="faq-accordion" class="accordion accordion-flush">
												<?php
												$faq_num = 0;
												// $btn_class = '';
												// $body_class = 'show';
												$btn_class = 'collapsed';
												$body_class = '';
												foreach ($faqs as $faq_id) { ?>
													<div class="accordion-item">
														<h4 id="faq-head-<?php echo $faq_num; ?>" class="accordion-header">
															<button class="accordion-button <?php echo $btn_class; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq-<?php echo $faq_num; ?>" aria-expanded="false" aria-controls="faq-<?php echo $faq_num; ?>"><?php echo get_the_title($faq_id); ?></button>
														</h4>
														<div id="faq-<?php echo $faq_num; ?>" class="accordion-collapse collapse <?php echo $body_class; ?>" aria-labelledby="faq-head-<?php echo $faq_num; ?>" data-bs-parent="#faq-accordion">
															<div class="accordion-body"><?php echo do_shortcode(get_the_content(null, false, $faq_id)); ?></div>
														</div>
													</div>
													<?php
													/*
													if($faq_num == 0){
														$btn_class = 'collapsed';
														$body_class = '';
													}
													*/
													$faq_num ++;
												} ?>
											</div>
											<?php if($from_price <> ''){ ?>
												<p class="text-center mt-3"><?php echo $reg_btn; ?></p>
											<?php } ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<?php // for mobiles ... ?>
	<div class="training-footer-wrap">
		<div class="container-fluid">
			<div id="training-footer">
				<?php if( $show_workshop > 1 ){ ?>
					<div class="wksp-full my-2"><strong>IMPORTANT!</strong><br>This live training is now full. Click to register for the recording (available after the live training).</div>
				<?php } ?>
				<div class="row align-items-center">
					<div class="col-7 col-sm-5 offset-sm-1 col-md-4 offset-md-2">
						<?php
						if( $venue <> '' && $pretty_dates['london_date'] <> '' ){ ?>
							<div class="bullet"><i class="fa-solid fa-calendar-days fa-fw"></i><?php
							echo $pretty_dates['london_date'];
							if($num_sess > 1){
								echo '<small><br><a class="cc-full-schedule" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#workshop-times-modal" data-type="w" data-id="'.get_the_ID().'">Course schedule</a></small>';
							} ?>
							</div>
						<?php }elseif( $venue == '' && $pretty_dates['locale_date'] <> '' ){ ?>
							<div class="bullet"><i class="fa-solid fa-calendar-days fa-fw"></i><?php
							echo $pretty_dates['locale_date'];
							if($num_sess > 1){
								echo '<small><br><a class="cc-full-schedule" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#workshop-times-modal" data-type="w" data-id="'.get_the_ID().'">Course schedule</a></small>';
							} ?>
							</div>
						<?php }
						if($from_price <> ''){ ?>
							<div class="bullet">
								<span class="currency-icon"><?php echo $currency_icon; ?></span><span class="from-price"><?php echo $from_price; ?></span>
								<?php if(isset($workshop_pricing['student_price'])){ ?>
									(student discount available)
								<?php }
								if( $from_price <> 'Free' ){ ?>
									<br>
									<small><a class="cc-currency-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-curr-modal" data-type="w" data-id="<?php echo get_the_ID(); ?>">change currency</a></small>
								<?php } ?>
							</div>
							<?php // echo '<span class="from-price">'.$from_price.'</span> <small>(<a class="cc-currency-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-curr-modal" data-type="w" data-id="'.get_the_ID().'">change currency</a>)</small></div>';
						}
						?>
					</div>
					<div class="col-5 col-sm-5 col-md-4">
						<?php
						if($from_price <> ''){
							echo '<p class="text-center mt-3">'.$reg_btn_footer.'</p>';
						}
						?>
					</div>
				</div>
			</div>
			<div id="training-footer-expanded" class="training-footer-expanded">
				<div class="row">
					<div class="col">
						<div class="training-footer-header">
							<h6 class="workshop-title"><?php the_title(); ?></h6>
							<button type="button" id="training-footer-closer" class="btn-close" aria-label="Close"></button>
						</div>
						<?php if($pretty_dates['locale_date'] <> ''){ ?>
							<div class="bullet"><i class="fa-solid fa-calendar-days fa-fw"></i>	<?php
								echo $pretty_dates['locale_date'];
								if($num_sess > 1){
									echo ' <small><a class="cc-full-schedule" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#workshop-times-modal" data-type="w" data-id="'.get_the_ID().'">Course schedule</a></small>';
								}
								?>
							</div>
							<?php if(!$multi_event && $pretty_dates['locale_start_time'] <> ''){ ?>
								<div class="bullet"><i class="fa-solid fa-clock fa-fw"></i>Starts: <span class="start-time"><?php echo $pretty_dates['locale_start_time']; ?></span> <small>(<span class="user-timezone"><?php echo $pretty_timezone; ?></span> time) <a class="cc-timezone-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-time-modal" data-type="w" data-id="<?php echo get_the_ID(); ?>">change timezone</a></small></div>
							<?php }
						}
						if($from_price <> ''){
							if($multi_event){ ?>
								<div class="training-footer-events-wrap">
									<p class="footer-reg-text mb-0"><strong>Register now:</strong></p>
									<p class="footer-sel-text">Select the events you will attend:</p>
									<?php
									$events_passed = false;
									$num_events = 0;
									$all_events = 0;
									$num_free = 0;
									$hide_dates = get_post_meta(get_the_ID(), 'hide_dates', true);
									for($i = 1; $i<16; $i++){
										$event_name = get_post_meta( get_the_ID(), 'event_'.$i.'_name', true );
										if($event_name <> ''){
											$all_events ++;
											$event_dt = workshop_event_display_date_time(get_the_ID(), $i, $user_timezone);
											$show_date = false;
											if($event_dt['timestamp'] <> ''){
												if($now > $event_dt['timestamp']){
													$events_passed = true; // so we do not offer the "buy them all" discount
													continue;
												}else{
													$show_date = true;
												}
											}
											$num_events ++;
											$event_free = get_post_meta( get_the_ID(), 'event_'.$i.'_free', true );
											if($event_free == 'yes'){
												$num_free ++;
											}
											?>
											<div class="footer-event">
												<div class="row">
													<div class="col-9">
														<h6 class="footer-event-name"><?php echo $event_name; ?></h6>
														<?php if($show_date && $hide_dates <> 'yes'){ ?>
															<div class="event-time-wrap"><span class="event-time-<?php echo $i; ?>"><?php echo $event_dt['date'].' '.$event_dt['time']; ?></span></div>
														<?php }
														if($event_free == 'yes'){
															echo 'FREE';
															$event_price = 0;
														}else{
															$event_price = workshops_event_price(get_the_ID(), $i, $workshop_currency); ?>
															<div class="event-price-wrap"><span class="event-price event-price-'.$i.'"><?php echo workshops_pretty_price($event_price, $workshop_currency); ?></span></div>
														<?php } ?>
													</div>
													<div class="col-3">
														<div class="wkshp-select">
															<?php if(get_post_meta(get_the_ID(), 'event_'.$i.'_closed', true) == 'closed'){ ?>
																<p class="event-full">FULL</p>
															<?php }else{ ?>
																<label for="footer-event-chooser-<?php echo $i; ?>" class="event-chooser-wrapper">
																	<input id="footer-event-chooser-<?php echo $i; ?>" class="event-chooser event-chooser-<?php echo $i; ?>" type="checkbox" name="event-chooser-<?php echo $i; ?>" data-free="<?php echo $event_free; ?>" data-eventid="<?php echo $i; ?>" data-price="<?php echo $event_price; ?>">
																	<div class="event-chooser-slider">
																		<div class="event-chooser-knob"></div>
																	</div>
																</label>
															<?php } ?>
														</div>
													</div>
												</div>
											</div>
										<?php }
									} ?>
								</div>
								<div class="row">
									<div class="col-7">
										<div class="events-reg-wrap">
											<div class="events-reg">
												<h6 class="footer-tot-price-wrap mb-2">Total: <span class="tot-price" data-discount="<?php echo $all_events_discount; ?>">0.00</span><br><small>(+ VAT if applicable)</small></h6>
												<?php if(!$events_passed && $all_events_discount > 0){ ?>
													<p class="discounting"><?php echo $all_events_discount; ?>% discount applies when you book all events</p>
												<?php } ?>
											</div>
										</div>
									</div>
									<div class="col-5 text-end">
										<button class="comp-reg btn btn-reg btn-lg disabled" data-link="#" title="Select an event first" form="reg-form">Complete registration</button>
									</div>
									<div class="col-12">
										<p class="events-note">* Note: All event dates/times are shown in <span class="user-timezone"><?php echo $pretty_timezone; ?></span> time (<a class="cc-timezone-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-time-modal" data-type="w" data-id="<?php echo get_the_ID(); ?>">change timezone</a>). You can also <a class="cc-currency-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-curr-modal" data-type="w" data-id="<?php echo get_the_ID(); ?>">change currency</a>.</p>
									</div>
								</div>
							<?php }else{ ?>
								<div class="bullet">
									<span class="currency-icon"><?php echo $currency_icon; ?></span><span class="from-price"><?php echo $from_price; ?></span>.
									<?php echo $workshop_pricing['earlybird_msg'];
								if(isset($workshop_pricing['student_price'])){ ?>
								</div>
								<div class="bullet">
									<i class="fa-solid fa-graduation-cap fa-fw"></i>
									Student price
									<span class="student-price">
										<?php echo $workshop_pricing['student_price_formatted']; ?>
									</span>
									<a href="#" class="" data-bs-toggle="modal" data-bs-target="#student-modal"><i class="fa-solid fa-circle-info m-0"></i></a>
									&nbsp;&nbsp;Select:
									<div class="form-switch d-inline"><input class="form-check-input" type="checkbox" role="switch" id="reg-student" value="yes"></div>
								</div>
								<div class="bullet">
									<i class="fa-solid fa-arrow-right-arrow-left"></i>
								<?php }else{ ?>
									<small>
								<?php }
								if( $from_price <> 'Free' ){ ?>
										<a class="cc-currency-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-curr-modal" data-type="w" data-id="<?php echo get_the_ID(); ?>">change currency</a>
								<?php }
								if(!isset($workshop_pricing['student_price'])){ ?>
									</small>
								<?php } ?>
								</div>
								<?php if( $show_workshop > 1 ){ ?>
									<div class="wksp-full"><strong>IMPORTANT!</strong><br>This live training is now full. Click to register for the recording (available after the live training).</div>
								<?php } ?>
								<p class="text-center mt-3"><?php echo $reg_btn; ?></p>
							<?php }
						} ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
	// the registration form
	echo $registration_form;

	/*
	if($show_workshop > 1){
		// workshop is closed ... register for the recording instead
		$training_type = 'r';
		$workshop_id = $show_workshop;
	}else{
		$training_type = 'w';
		$workshop_id = get_the_ID();
	}
	?>
	<div class="d-none">
		<form id="reg-form" action="<?php echo $reg_permalink; ?>" method="POST">
			<input type="hidden" id="training-type" name="training-type" value="<?php echo $training_type; ?>">
			<input type="hidden" id="training-id" name="workshop-id" value="<?php echo $workshop_id; ?>">
			<input type="hidden" id="eventID" name="eventID" value="">
			<input type="hidden" id="num-events" name="num-events" value="<?php echo $num_events; ?>">
			<input type="hidden" id="num-free" name="num-free" value="<?php echo $num_free; ?>">
			<input type="hidden" id="currency" name="currency" value="<?php echo $workshop_currency; ?>">
			<input type="hidden" id="raw-price" name="raw-price" value="<?php echo $raw_price; ?>">
			<input type="hidden" id="user-timezone" name="user-timezone" value="<?php echo $user_timezone; ?>">
			<input type="hidden" id="user-prettytime" name="user-prettytime" value="<?php echo $pretty_timezone; ?>">
			<input type="hidden" id="student" name="student" value="no">
			<input type="hidden" id="student-price" name="student-price" value="<?php echo $workshop_pricing['student_price']; ?>">
			<!-- tracking -->
			<input type="hidden" name="source_page" value="<?php echo get_the_title(); ?>">
		    <input type="hidden" name="utm_source" value="<?php echo $_GET['utm_source'] ?? ''; ?>">
		    <input type="hidden" name="utm_campaign" value="<?php echo $_GET['utm_campaign'] ?? ''; ?>">
		</form>
	</div>
	*/ ?>

	<?php // student discount terms modal ?>
	<div id="student-modal" class="modal cc-modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Student Discount</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<?php echo cc_phrases_student_discount_terms(); ?>
				</div>
			</div>
		</div>
	</div>

	<?php // voucher offer t&cs modal ?>
	<div id="voucher-tandcs" class="modal cc-modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Gift Voucher Terms &amp; Conditions</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<?php echo cc_phrases_gift_voucher_terms(); ?>
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

	<?php // the workshop times modal ?>
	<div id="workshop-times-modal" class="modal session-modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body"><div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div></div>
			</div>
		</div>
	</div>

<?php endwhile; // end of the loop.
get_footer();
