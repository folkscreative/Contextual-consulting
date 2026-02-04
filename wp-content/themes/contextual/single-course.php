<?php
/**
 * Single training course
 * only built to handle recordings!!!!!!
 */

$course_id = get_the_id();
$course = course_get_all( $course_id );

$user_id = 0;
$portal_user = '';
if( cc_users_is_valid_user_logged_in() ){
	$user_id = get_current_user_id();
	$portal_user = get_user_meta( $user_id, 'portal_user', true );
}

// watch now?
$allow_watch_now = false;
$recording_access = ccrecw_user_can_view( $course_id, $user_id );
if( course_single_meta( $course, '_course_status' ) == 'public' || $recording_access['access'] ){
	$allow_watch_now = true;
}

// Check organisation registration status
$org_reg_status = org_register_status($portal_user, $course_id);
$reg_btn_disabled = '';
if($org_reg_status['status'] == 'block' || $org_reg_status['status'] == 'expired'){
    $reg_btn_disabled = ' disabled';
}

// pricing & currency
$user_currency = cc_currency_get_user_currency( $user_id );
$raw_price = $undiscounted_price = $early_bird_discount = 0;
$early_bird_expiry = false;
if( ! $allow_watch_now && ( course_single_meta( $course, '_course_status' ) == '' || course_single_meta( $course, '_course_status' ) == 'unlisted' ) ){
	// work out the pricing
	if( $user_currency == 'GBP' ){
		$raw_price = $course['pricing']['price_gbp'];
	}else{
		$raw_price = $course['pricing']['price_'.strtolower( $user_currency )];
		if( $raw_price == 0 ){
			$raw_price = $course['pricing']['price_gbp'];
			$user_currency = 'GBP';
		}
	}
	$undiscounted_price = $raw_price;
	// do we have to apply an early bird discount?
	$early_bird_discount = (float) $course['pricing']['early_bird_discount'];
	if( $early_bird_discount > 0 && $early_bird_discount <= 100 && $course['pricing']['early_bird_expiry'] <> NULL){
		$early_bird_expiry = DateTime::createFromFormat( 'Y-m-d H:i:s', $course['pricing']['early_bird_expiry'] );
		if( $early_bird_expiry ){
			if( $early_bird_expiry->getTimestamp() > time() ){
				$discount_pence = round( $raw_price * $early_bird_discount ); // eg £100 * 20% = 2000p
				$discount_price = $raw_price - ( $discount_pence / 100 ); // eg £100 - ( 2000p / 100 ) = £80
				/**
				 * we were rounding this up to the next multiple of 5, but no longer
				 *
				// however, we want this rounded up to the next multiple of 5
				$raw_price = ceil($discount_price / 5) * 5;
				*/
				$raw_price = $discount_price;
			}
		}
	}
}
$currency_icon = cc_currencies_icon( $user_currency );
if($raw_price == 0){
	$pretty_price = 'Free';
}else{
	$pretty_price = workshops_pretty_price( $raw_price, $user_currency );
}
$student_pricing = course_student_pricing( $course, $user_currency );


/*
// registration/watch now button
$reg_btn = $reg_btn_footer = '';
if( course_single_meta( $course, '_course_status' ) <> 'closed'){
	if( ! empty( course_single_meta( $course, 'registration_link' ) ) ){
		// the online workshop's link ... no longer used
		$reg_btn = '<a href="'.esc_url( course_single_meta( $course, 'registration_link' ) ).'" class="btn btn-reg btn-lg'.$reg_btn_disabled.'">Access training</a>';
		$reg_btn_footer = '<a href="'.esc_url( course_single_meta( $course, 'registration_link' ) ).'" class="btn btn-reg btn-lg'.$reg_btn_disabled.'"><span class="d-none d-md-inline">Access </span>training</a>';
		$reg_type = 1;
	}
	if( ! empty( course_single_meta( $course, 'registration_link_id' ) ) ){
		// Oli's ID
		$registration_link = 'https://app-4.globalpodium.com/watch/'.course_single_meta( $course, 'registration_link_id' );
		$reg_btn = '<a href="'.esc_url($registration_link).'" class="btn btn-reg btn-lg'.$reg_btn_disabled.'" target="_blank">Access training</a>';
		$reg_btn_footer = '<a href="'.esc_url($registration_link).'" class="btn btn-reg btn-lg'.$reg_btn_disabled.'"><span class="d-none d-md-inline">Access </span>training</a>';
		$reg_type = 2;
	}
	if($allow_watch_now){
		// new training delivery page
		$nonce = wp_create_nonce( 'course_id' . $course_id );
		$reg_btn = '
            <form action="'.esc_url( site_url( '/training-delivery/' ) ).'" method="post" class="mb-3">
                <input type="hidden" name="id" value="'.esc_attr( $course_id ).'">
                <input type="hidden" name="training_delivery_nonce" value="'.esc_attr( $nonce ).'">
                <button type="submit" class="btn btn-reg btn-lg'.$reg_btn_disabled.'">Watch now</button>
            </form>';
		$reg_btn_footer = '
            <form action="'.esc_url( site_url( '/training-delivery/' ) ).'" method="post" class="mb-3">
                <input type="hidden" name="id" value="'.esc_attr( $course_id ).'">
                <input type="hidden" name="training_delivery_nonce" value="'.esc_attr( $nonce ).'">
                <button type="submit" class="btn btn-reg btn-lg'.$reg_btn_disabled.'">Watch<span class="d-none d-md-inline"> Now</span></button>
            </form>';
	}else{
		if( ! empty( course_single_meta( $course, 'recording_url' ) ) ){
			// media library file
			$reg_btn = '<button class="btn btn-reg btn-lg" form="reg-form"'.$reg_btn_disabled.'>Register now</button>';
			$reg_btn_footer = '<button form="reg-form" class="btn btn-lg btn-reg"'.$reg_btn_disabled.'>Register<span class="d-none d-md-inline"> Now</span></button>';
			$reg_type = 3;
		}else{
			// we'll assume it's Vimeo .....
			$reg_btn = '<button class="btn btn-reg btn-lg" form="reg-form"'.$reg_btn_disabled.'>Register now</button>';

			// early bird?
			$early_bird_msg = '';
			if( $early_bird_discount > 0 && $early_bird_expiry && $early_bird_expiry->getTimestamp() > time() ){
			    $early_bird_name = $course['pricing']['early_bird_name'] == '' ? 'Early-bird' : $course['pricing']['early_bird_name'];
			    $early_bird_msg = $early_bird_name.' rate valid until '.$early_bird_expiry->format('jS M Y').' and then the normal price of <span class="non-early-price">'.workshops_price_prefix($user_currency).number_format($undiscounted_price,2).'</span> + VAT will apply.';
			}
			if( $early_bird_msg <> '' || $course['pricing']['student_discount'] > 0 ){
				$reg_btn_footer = '<button type="button" id="training-footer-btn" class="btn btn-lge btn-reg"'.$reg_btn_disabled.'><span class="d-none d-md-inline">Show </span>More</button>';
			}else{
				$reg_btn_footer = '<button class="btn btn-reg btn-lge" form="reg-form"'.$reg_btn_disabled.'>Register<span class="d-none d-md-inline"> Now</span></button>';
			}
			$reg_type = 4;
		}
		if($org_reg_status['message'] != ''){
		    $reg_btn .= '</p><p class="small lh-sm text-center text-bg-warning p-3"><i class="fa-solid fa-circle-exclamation"></i> '.$org_reg_status['message'];
		    $reg_btn_footer .= '</p><p class="small lh-sm text-center text-bg-warning p-3"><i class="fa-solid fa-circle-exclamation"></i> '.$org_reg_status['message'];
		}

	}
}
*/

// Check for external registration links first (these bypass normal registration)
$has_external_link = false;
if( ! empty( course_single_meta( $course, 'registration_link' ) ) ){
	// External registration link
	$has_external_link = true;
	$registration_link = course_single_meta( $course, 'registration_link' );
	$reg_btn = '<a href="'.esc_url($registration_link).'" class="btn btn-reg btn-lg">Access training</a>';
	$reg_btn_footer = '<a href="'.esc_url($registration_link).'" class="btn btn-reg btn-lg"><span class="d-none d-md-inline">Access </span>training</a>';
	$reg_form = ''; // No form needed for external links
}elseif( ! empty( course_single_meta( $course, 'registration_link_id' ) ) ){
	// Oli's globalpodium link
	$has_external_link = true;
	$registration_link = 'https://app-4.globalpodium.com/watch/'.course_single_meta( $course, 'registration_link_id' );
	$reg_btn = '<a href="'.esc_url($registration_link).'" class="btn btn-reg btn-lg" target="_blank">Access training</a>';
	$reg_btn_footer = '<a href="'.esc_url($registration_link).'" class="btn btn-reg btn-lg"><span class="d-none d-md-inline">Access </span>training</a>';
	$reg_form = ''; // No form needed for external links
}else{
	// Use the new centralized button generation function
	$button_data = cc_training_register_button($course_id, array(
		'post_type' => 'course',
		'user_id' => $user_id
	));
	
	$reg_btn = $button_data['main'];
	$reg_btn_footer = $button_data['footer'];
	$reg_form = $button_data['form'];
	
	// Get pricing data from function (may override earlier calculations)
	$raw_price = $button_data['data']['price'];
	$user_currency = $button_data['data']['currency'];
	
	// Recalculate display values if needed
	$currency_icon = cc_currencies_icon($user_currency);
	if($raw_price == 0){
		$pretty_price = 'Free';
	}else{
		$pretty_price = workshops_pretty_price($raw_price, $user_currency);
	}
	
	// Refresh student pricing with new currency
	$student_pricing = course_student_pricing($course, $user_currency);
}

// Calculate early bird message for display (used in expanded footer)
$early_bird_msg = '';
if(!$has_external_link && $early_bird_discount > 0 && $early_bird_expiry && $early_bird_expiry->getTimestamp() > time() ){
	$early_bird_name = $course['pricing']['early_bird_name'] == '' ? 'Early-bird' : $course['pricing']['early_bird_name'];
	$early_bird_msg = $early_bird_name.' rate valid until '.$early_bird_expiry->format('jS M Y').' and then the normal price of <span class="non-early-price">'.workshops_price_prefix($user_currency).number_format($undiscounted_price,2).'</span> + VAT will apply.';
}


$user_timezone = cc_timezone_get_user_timezone( $user_id );
$pretty_timezone = cc_timezone_get_user_timezone_pretty( $user_id, $user_timezone );


get_header();
while ( have_posts() ) : the_post(); ?>
	<div id="workshop-header-<?php echo $course_id; ?>" class="workshop-header-wrap wms-section wms-section-std sml-padd-top sml-padd-bot">
		<div class="wms-sect-bg wms-bg-img px-xxl-5 d-flex align-items-center">
			<div class="container">
				<div class="workshop-header">
					<div class="row workshop-title-row">
						<div class="col text-center">
							<h1 class="workshop-title"><?php the_title(); ?></h1>
							<?php
							if( ! empty( course_single_meta( $course, 'subtitle' ) ) ){ ?>
								<h3 class="workshop-subtitle"><?php echo course_single_meta( $course, 'subtitle' ); ?></h3>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php
	if( isset( $course['meta']['_thumbnail_id'] ) && $course['meta']['_thumbnail_id']  > 0){
		echo '<style>'.wms_page_slider_slide_css(
			course_single_meta( $course, '_thumbnail_id' ),
			'#workshop-header-'.$course_id.' .wms-sect-bg',
			course_single_meta( $course, '_featured_bg_position' ),
			course_single_meta( $course, '_featured_height' ),
			course_single_meta( $course, '_featured_wash_opacity' ),
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
									<?php echo cc_presenters_images( $course_id ) ?>
								</div>
								<div class="col-12 col-md-8 offset-md-2 col-lg-8 offset-lg-0 bullet-col">
									<div class="bullet-wrapper">
										<?php
										
										$who = cc_presenters_names( $course_id);
										if($who <> ''){
											echo '<div class="bullet"><i class="fa-solid fa-user fa-fw"></i>'.$who.'</div>';
										}

							            if( $course['recording_expiry']['num'] > 0 && $course['recording_expiry']['unit'] <> '' ){
											echo '<div class="bullet"><i class="fa-solid fa-video fa-fw"></i>Access is for '.$course['recording_expiry']['num'].' '.$course['recording_expiry']['unit'].' after purchase</div>';
							            }

										if( ! empty( course_single_meta( $course, '_course_timing' ) ) ){
											echo '<div class="bullet"><i class="fa-solid fa-hourglass-half fa-fw"></i>'.course_single_meta( $course, '_course_timing' ).'</div>';
										}

										$ce_credits = (float) course_single_meta( $course, 'ce_credits' );
										if( $ce_credits > 0 ){
											echo '<div class="bullet"><i class="fa-solid fa-graduation-cap"></i>'.$ce_credits.' CE credits</div>';
										}

										$training_levels = cc_topics_training_levels( $course_id );
										if( $training_levels <> '' ){
											echo '<div class="bullet"><i class="fa-solid fa-star fa-fw"></i>'.$training_levels.'</div>';
										}
										if( ! empty( course_single_meta( $course, 'who_for' ) ) ){
											echo '<div class="bullet"><i class="fa-solid fa-award fa-fw"></i>'.do_shortcode( course_single_meta( $course, 'who_for' ) ).'</div>';
										}

										?>
									</div>
								</div>
							</div>
							<hr class="border-2 d-lg-none">

							<?php
							// the redirect message panel
							if( course_single_meta( $course, '_redir_msg_panel' ) == 'yes' ){
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
							$series_id = series_id_for_course( $course_id );
							if( $series_id ){
								echo course_series_panel( $series_id, $course_id );
							}
							*/

							if( ! $recording_access['access'] ){ ?>
								<div class="">
									<?php
									$voucher_banner = cc_voucher_offer_training_banner('r', $course_id, $user_currency, $raw_price);
									echo $voucher_banner;
									?>
								</div>

								<div class="">
									<?php
									echo cc_upsell_training_banner( $course_id );
									?>
								</div>

							<?php } ?>

							<div class="row description-row">
								<div class="col-12 content-wrapper">
									<?php the_content(); ?>
								</div>
							</div>

							<?php if( course_single_meta( $course, '_show_modules' ) <> 'hide' && ( course_single_meta( $course, '_show_modules' ) == 'show' || $course['module_counts']['module_count'] > 1 || $course['module_counts']['all_sections_count'] > 1 ) ){ ?>

								<div class="row modules-row">

									<?php
									if( $course['module_counts']['module_count'] > 1 && $course['module_counts']['all_sections_count'] > 1 ){
										$use_accordions = true;
									}else{
										$use_accordions = false;
									}
									?>

									<div class="col-12 modules-wrapper mb-4">
										<h4 class="mb-2">
											<?php
											$course_components = course_single_meta( $course, 'course_components' );
											echo $course_components == '' ? 'Course components' : $course_components;
											?>
										</h4>
										<?php if( $use_accordions ){ ?>
								            <div class="accordion modules-accordion" id="courseAccordion">
										<?php }

										// the modules and sections
										foreach ($course['modules'] as $index => $module) {
											if( $course['module_counts']['module_count'] > 1 ){
												if( $use_accordions ){
								                    // Generate unique IDs for each module accordion
								                    $moduleId = 'moduleAccordion' . $index;
								                    $headingId = 'heading' . $index;
								                    $collapseId = 'collapse' . $index;
								                    ?>
								                    <div class="accordion-item">
								                        <h2 class="accordion-header" id="<?php echo $headingId; ?>">
								                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="false" aria-controls="<?php echo $collapseId; ?>">
								                            	<div class="container-fluid">
																	<div class="row w-100">
																		<div class="col-9 col-lg-10 text-start p-0 lh-sm">
											                                <?php echo esc_html($module['title']); ?>
																		</div>
																		<div class="col-3 col-lg-2 text-end small p-0 lh-sm">
											                                <?php
											                                echo $module['timing'] == '' ? '' : '<i class="fa-regular fa-clock fa-fw"></i> '.esc_html($module['timing']);
											                                ?>
																		</div>
																	</div>
																</div>
								                            </button>
								                        </h2>
								                        <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse" aria-labelledby="<?php echo $headingId; ?>" data-bs-parent="#courseAccordion">
								                            <div class="accordion-body">
												<?php }else{ ?>
													<div class="module-wrapper mb-2">
														<h6 class="mb-1"><?php echo esc_html( $module['title'] ); ?></h6>
												<?php }
											}
			                                if( $module['sections_count'] > 0 ) : ?>
			                                    <ul class="list-unstyled mb-0 module-sections-wrapper">
			                                        <?php foreach ($module['sections'] as $section) : ?>
			                                            <li class="mb-1"><?php echo esc_html($section['title']); ?></li>
			                                        <?php endforeach; ?>
			                                    </ul>
			                                <?php else : ?>
			                                    <p><?php echo esc_html($module['title']); ?></p>
			                                <?php endif;
			                                /*
											if( $module['sections_count'] > 1 ){ ?>
												<div class="sections-wrapper">
												<?php foreach ($module['sections'] as $section) { ?>
													<div class="section-wrapper">
														<p class="mb-1"><?php echo $section['title']; ?></p>
													</div>
												<?php } ?>
												</div>
											<?php }
											*/
											if( $course['module_counts']['module_count'] > 1 ){
												if( $use_accordions ){ ?>
															</div><!-- .accordion-body -->
														</div><!-- .collapse -->
													</div><!-- .accordion-item -->
												<?php }else{ ?>
													</div><!-- .module-wrapper -->
												<?php }
											}
										}

										if( $use_accordions ){ ?>
											</div><!-- .accordion -->
										<?php } ?>

									</div><!-- .modules-wrapper -->
								</div><!-- .modules-row -->

							<?php } ?>
							
							<?php echo cc_train_acc_accordions( $course_id ); ?>

						</div>
						<div class="d-none d-lg-block col-lg-3 floater-wrapper">
							<div class="floater">
								<div class="floater-inner">
									<h6 class="workshop-title"><?php the_title(); ?></h6>
									<?php if( ! $allow_watch_now && course_single_meta( $course, '_course_status' ) == 'closed' ){ ?>
										<p class="mt-3">Registration closed</p>
									<?php }else{
										if( !$allow_watch_now ){ ?>
											<div class="bullet">
												<span class="currency-icon"><?php echo $currency_icon; ?></span><span class="from-price"><?php echo $pretty_price; ?></span>.
												<?php
												// echo (isset($recording_pricing['earlybird_msg'])) ? $recording_pricing['earlybird_msg'] : '';
												echo $early_bird_msg;
												if(isset($student_pricing['student_price'])){ ?>
													</div>
													<div class="bullet">
														<i class="fa-solid fa-graduation-cap fa-fw"></i>
														Student price
														<span class="student-price">
															<?php echo $student_pricing['student_price_formatted']; ?>
														</span>
														<a href="#" class="" data-bs-toggle="modal" data-bs-target="#student-modal"><i class="fa-solid fa-circle-info m-0"></i></a>&nbsp;&nbsp;Select:
														<div class="form-switch d-inline"><input class="form-check-input" type="checkbox" role="switch" id="reg-student" value="yes"></div>
													</div>
													<div class="bullet">
														<i class="fa-solid fa-arrow-right-arrow-left"></i>
												<?php }else{ ?>
													<small>
												<?php }
												if( $pretty_price <> 'Free' ){ ?>
													<a class="cc-currency-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-curr-modal" data-type="w" data-id="<?php echo $course_id; ?>">change currency</a>
												<?php }
												if(!isset($student_pricing['student_price'])){ ?>
													</small>
												<?php } ?>
											</div>
										<?php } ?>
										<p class="text-center mt-3"><?php echo $reg_btn; ?></p>
									<?php } ?>
								</div>

								<?php
								// NEW: Check if this course is part of a series and display the training groups selection card
								$series_id = series_id_for_course(get_the_ID());
								if($series_id){
								    echo training_groups_selection_card($series_id);
								}
								?>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php // for mobiles ... ?>
	<div class="training-footer-wrap">
		<div class="container-fluid">
			<div id="training-footer">
				<div class="row align-items-center">
					<?php if($allow_watch_now){ ?>
						<div class="col-12">
							<p class="text-center mt-3"><?php echo $reg_btn_footer; ?></p>
						</div>
					<?php }elseif( course_single_meta( $course, '_course_status' ) == 'closed' ){ ?>
						<div class="col-12">
							<p class="mt-3">Registration closed</p>
						</div>
					<?php }else{ ?>
						<div class="col-7 col-sm-5 offset-sm-1 col-md-4 offset-md-2">
							<?php
							if($pretty_price <> ''){ ?>
								<div class="bullet">
									<span class="currency-icon"><?php echo $currency_icon; ?></span><span class="from-price"><?php echo $pretty_price; ?></span>
									<?php if(isset($student_pricing['student_price'])){ ?>
										(student discount available)
									<?php }
									if( $pretty_price <> 'Free' ){ ?>
										<br>
										<small><a class="cc-currency-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-curr-modal" data-type="w" data-id="<?php echo $course_id; ?>">change currency</a></small>
									<?php } ?>
								</div>
							<?php } ?>
						</div>
						<div class="col-5 col-sm-5 col-md-4">
							<p class="text-center mt-3"><?php echo $reg_btn_footer; ?></p>
						</div>
					<?php } ?>
				</div>
			</div>

			<div id="training-footer-expanded" class="training-footer-expanded">
				<div class="row">
					<div class="col">
						<div class="training-footer-header">
							<h6 class="workshop-title"><?php the_title(); ?></h6>
							<button type="button" id="training-footer-closer" class="btn-close" aria-label="Close"></button>
						</div>
						<?php
						if($pretty_price <> ''){ ?>
							<div class="bullet">
								<span class="currency-icon"><?php echo $currency_icon; ?></span><span class="from-price"><?php echo $pretty_price; ?></span>.
								<?php
								// echo isset( $recording_pricing['earlybird_msg'] ) ? $recording_pricing['earlybird_msg'] : '';
								echo $early_bird_msg;
							if(isset($student_pricing['student_price'])){ ?>
							</div>
							<div class="bullet">
								<i class="fa-solid fa-graduation-cap fa-fw"></i>
								Student price
								<span class="student-price">
									<?php echo $student_pricing['student_price_formatted']; ?>
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
							if( $pretty_price <> 'Free' ){ ?>
									<a class="cc-currency-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-curr-modal" data-type="w" data-id="<?php echo $course_id; ?>">change currency</a>
							<?php }
							if(!isset($student_pricing['student_price'])){ ?>
								</small>
							<?php } ?>
							</div>
							<p class="text-center mt-3"><?php echo $reg_btn; ?></p>
						<?php } ?>
					</div>
				</div>
			</div>

		</div>
	</div>

	<?php 
	/*
	// the registration form ?>
	<div class="d-none">
		<form id="reg-form" action="/registration" method="POST">
			<input type="hidden" id="training-type" name="training-type" value="r">
			<input type="hidden" id="training-id" name="workshop-id" value="<?php echo $course_id; ?>">
			<input type="hidden" id="eventID" name="eventID" value="">
			<input type="hidden" id="num-events" name="num-events" value="">
			<input type="hidden" id="num-free" name="num-free" value="">
			<input type="hidden" id="currency" name="currency" value="<?php echo $user_currency; ?>">
			<input type="hidden" id="raw-price" name="raw-price" value="<?php echo $raw_price; ?>">
			<input type="hidden" id="user-timezone" name="user-timezone" value="<?php echo $user_timezone; ?>">
			<input type="hidden" id="user-prettytime" name="user-prettytime" value="<?php echo $pretty_timezone; ?>">
			<input type="hidden" id="student" name="student" value="no">
			<input type="hidden" id="student-price" name="student-price" value="<?php echo isset( $student_pricing['student_price'] ) ? $student_pricing['student_price'] : ''; ?>">
			<!-- tracking -->
			<input type="hidden" name="source_page" value="<?php echo get_the_title(); ?>">
		    <input type="hidden" name="utm_source" value="<?php echo $_GET['utm_source'] ?? ''; ?>">
		    <input type="hidden" name="utm_campaign" value="<?php echo $_GET['utm_campaign'] ?? ''; ?>">
		</form>
	</div>
	*/ ?>

	<?php 
	// Registration form - generated by button function or empty for external links
	if(!empty($reg_form)){ 
		echo $reg_form; 
	}
	?>
	

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

<?php endwhile; // end of the loop.
get_footer();
