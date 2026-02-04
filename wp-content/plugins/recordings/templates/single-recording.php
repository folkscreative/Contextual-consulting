<?php
/**
 * Single recording
 */

/**
 * Oct 2022
 * - new page inspired by the new workshop page but with essential junk from the old single recording page
 */

// get the image (if there is one)
/*
$image_html = '';
$post_thumbnail_id = get_post_thumbnail_id( get_the_ID() );
if ( !empty( $post_thumbnail_id ) ){
	$image_html = wms_media_image_html($post_thumbnail_id, 'post-thumb', true, '3:2', '', 'workshop-image');
}
*/

// recording_for_sale can be '' = normal, 'public' = no purchase needed, 'closed' = not avail to purchase, 'unlisted' = available but not listed
$recording_for_sale = get_post_meta(get_the_ID(), 'recording_for_sale', true);

$allow_watch_now = false;
$recording_access = ccrecw_user_can_view( get_the_ID() );
if($recording_for_sale == 'public' || ( cc_users_is_valid_user_logged_in() && $recording_access['access'] ) ){
	$allow_watch_now = true;
}

// pricing & currency
$recording_currency = cc_currency_get_user_currency();
$raw_price = 0;
if(!$allow_watch_now && ( $recording_for_sale == '' || $recording_for_sale == 'unlisted' ) ){
	// work out the pricing
	$recording_pricing = cc_recording_price(get_the_ID(), $recording_currency);
	$raw_price = $recording_pricing['raw_price'];
	$recording_currency = $recording_pricing['curr_found'];
}
$currency_icon = cc_currencies_icon($recording_currency);
if($raw_price == 0){
	$pretty_price = 'Free';
}else{
	// $pretty_price = cc_money_format($raw_price, $recording_currency);
	$pretty_price = workshops_pretty_price($raw_price, $recording_currency);
}

$portal_user = get_user_meta( get_current_user_id(), 'portal_user', true );
$block_nlft = get_post_meta( get_the_ID(), 'block_nlft', true );
$block_cnwl = get_post_meta( get_the_ID(), 'block_cnwl', true );

// registration/watch now button
$reg_btn = $reg_btn_footer = '';
$disabled = '';
$disabled_msg = '';
if( ( $portal_user == 'nlft' && $block_nlft == 'yes' ) || ( $portal_user == 'cnwl' && $block_cnwl == 'yes' ) ){
	$disabled = ' disabled';
	$disabled_msg = '</p><p class="small lh-sm text-center">Registration closed for your organisation';
}
if($recording_for_sale <> 'closed'){
	$registration_link = get_post_meta( get_the_ID(), 'registration_link', true ); // the online workshop's link ... no longer used
	if($registration_link <> ''){
		$reg_btn = '<a href="'.esc_url($registration_link).'" class="btn btn-reg btn-lg'.$disabled.'">Access training</a>'.$disabled_msg;
		$reg_btn_footer = '<a href="'.esc_url($registration_link).'" class="btn btn-reg btn-lg'.$disabled.'"><span class="d-none d-md-inline">Access </span>training</a>'.$disabled_msg;
		$reg_type = 1;
	}
	$registration_link_id = get_post_meta( get_the_ID(), 'registration_link_id', true ); // Oli's ID
	if($registration_link_id <> ''){
		$registration_link = 'https://app-4.globalpodium.com/watch/'.$registration_link_id;
		$reg_btn = '<a href="'.esc_url($registration_link).'" class="btn btn-reg btn-lg'.$disabled.'" target="_blank">Access training</a>'.$disabled_msg;
		$reg_btn_footer = '<a href="'.esc_url($registration_link).'" class="btn btn-reg btn-lg'.$disabled.'"><span class="d-none d-md-inline">Access </span>training</a>'.$disabled_msg;
		$reg_type = 2;
	}
	if($allow_watch_now){
		$reg_btn = '<a href="/watch-recording?id='.get_the_ID().'" class="btn btn-reg btn-lg'.$disabled.'">Watch now</a>'.$disabled_msg;
		$reg_btn_footer = '<a href="/watch-recording?id='.get_the_ID().'" class="btn btn-reg btn-lg'.$disabled.'">Watch<span class="d-none d-md-inline"> Now</span></a>'.$disabled_msg;
	}else{
		$recording_url = get_post_meta( get_the_ID(), 'recording_url', true ); // media library file
		if($recording_url <> ''){
			$reg_btn = '<button class="btn btn-reg btn-lg" form="reg-form"'.$disabled.'>Register now</button>'.$disabled_msg;
			$reg_btn_footer = '<button form="reg-form" class="btn btn-lg btn-reg"'.$disabled.'>Register<span class="d-none d-md-inline"> Now</span></button>'.$disabled_msg;
			$reg_type = 3;
		}
		if(cc_recordings_vimeo_used(get_the_ID())){
			$reg_btn = '<button class="btn btn-reg btn-lg" form="reg-form"'.$disabled.'>Register now</button>'.$disabled_msg;
			// $reg_btn_footer = '<button form="reg-form" class="btn btn-lg btn-reg">Register<span class="d-none d-md-inline"> Now</span></button>';
			if($recording_pricing['earlybird_msg'] <> '' || isset($recording_pricing['student_price'])){
				$reg_btn_footer = '<button type="button" id="training-footer-btn" class="btn btn-lge btn-reg"'.$disabled.'><span class="d-none d-md-inline">Show </span>More</button>'.$disabled_msg;
			}else{
				$reg_btn_footer = '<button class="btn btn-reg btn-lge" form="reg-form"'.$disabled.'>Register<span class="d-none d-md-inline"> Now</span></button>'.$disabled_msg;
			}
			$reg_type = 4;
		}
	}
}

$user_id = get_current_user_id(); // 0 if not logged in
$user_timezone = cc_timezone_get_user_timezone($user_id);
$pretty_timezone = cc_timezone_get_user_timezone_pretty($user_id, $user_timezone);

/*
$multi_event = workshop_is_multi_event(get_the_ID());
$show_workshop = workshops_show_this_workshop(get_the_ID());
if($multi_event){
	$reg_btn = '<a href="#register" class="btn btn-reg btn-lg">Register Now</a>';
	// $reg_btn_footer = '<a href="#register" class="btn btn-reg btn-lg">Register<span class="d-none d-md-inline"> Now</span></a>';
	$reg_btn_footer = '<button type="button" id="training-footer-btn" class="btn btn-lg btn-reg">Register<span class="d-none d-md-inline"> Now</span></button>';
	$raw_price = 0;
	$all_events_discount = get_post_meta(get_the_ID(), 'all_events_discount', true) + 0;
}else{
	$reg_btn = '<button class="btn btn-reg btn-lg" form="reg-form">Register Now</button>';
	if($workshop_pricing['earlybird_msg'] <> '' || isset($workshop_pricing['student_price'])){
		$reg_btn_footer = '<button type="button" id="training-footer-btn" class="btn btn-lg btn-reg">Register<span class="d-none d-md-inline"> Now</span></button>';
	}else{
		$reg_btn_footer = '<button class="btn btn-reg btn-lg" form="reg-form">Register<span class="d-none d-md-inline"> Now</span></button>';
	}
	$raw_price = $workshop_pricing['raw_price'];
	$all_events_discount = 0;
}
*/

// $reg_permalink = '/registration';
$num_events = 1;
$num_free = 0;

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
			<div class="container-variable container">
				<div class="workshop-body">
					<div class="row">
						<div class="col-lg-6 offset-lg-2">
							<div class="row bullet-row">
								<div class="col-8 offset-2 offset-lg-0 col-lg-4 image-col">
									<?php echo cc_presenters_images(get_the_ID()) ?>
								</div>
								<div class="col-12 col-md-8 offset-md-2 col-lg-8 offset-lg-0 bullet-col">
									<div class="bullet-wrapper">
										<?php
										// echo cc_topics_show(get_the_ID(), 'xs');
										$who = cc_presenters_names( get_the_ID());
										if($who <> ''){
											echo '<div class="bullet"><i class="fa-solid fa-user fa-fw"></i>'.$who.'</div>';
										}
										$availability = get_post_meta( get_the_ID(), 'availability', true);
										if($availability <> ''){
											echo '<div class="bullet"><i class="fa-solid fa-video fa-fw"></i>'.$availability.'</div>';
										}
										$duration = get_post_meta( get_the_ID(), 'duration', true);
										if($duration <> ''){
											echo '<div class="bullet"><i class="fa-solid fa-hourglass-half fa-fw"></i>'.$duration.'</div>';
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

							if( ! $recording_access['access'] ){ ?>

								<div class="">
									<?php
									$voucher_banner = cc_voucher_offer_training_banner('r', get_the_ID(), $recording_currency, $raw_price);
									echo $voucher_banner;
									?>
								</div>

								<div class="">
									<?php
									echo cc_upsell_training_banner( get_the_ID() );
									?>
								</div>

							<?php } ?>

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
						<div class="d-none d-lg-block col-lg-2 floater-wrapper">
							<div class="floater">
								<div class="floater-inner">
									<h6 class="workshop-title"><?php the_title(); ?></h6>
						<?php if( ! $allow_watch_now && $recording_for_sale == 'closed' ){ ?>
									<p class="mt-3">Registration closed</p>
						<?php }else{
							if( !$allow_watch_now ){ ?>
									<div class="bullet">
										<span class="currency-icon"><?php echo $currency_icon; ?></span><span class="from-price"><?php echo $pretty_price; ?></span>.
										<?php echo (isset($recording_pricing['earlybird_msg'])) ? $recording_pricing['earlybird_msg'] : '';
								if(isset($recording_pricing['student_price'])){ ?>
									</div>
									<div class="bullet">
										<i class="fa-solid fa-graduation-cap fa-fw"></i>
										Student price
										<span class="student-price">
											<?php echo $recording_pricing['student_price_formatted']; ?>
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
											<a class="cc-currency-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-curr-modal" data-type="w" data-id="<?php echo get_the_ID(); ?>">change currency</a>
								<?php }
								if(!isset($recording_pricing['student_price'])){ ?>
											</small>
								<?php } ?>
									</div>
							<?php } ?>
									<p class="text-center mt-3"><?php echo $reg_btn; ?></p>
						<?php } ?>
								</div>

								<?php // echo $voucher_banner; ?>

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
					<?php }elseif( $recording_for_sale == 'closed' ){ ?>
						<div class="col-12">
							<p class="mt-3">Registration closed</p>
						</div>
					<?php }else{ ?>
						<div class="col-7 col-sm-5 offset-sm-1 col-md-4 offset-md-2">
							<?php
							if($pretty_price <> ''){ ?>
								<div class="bullet">
									<span class="currency-icon"><?php echo $currency_icon; ?></span><span class="from-price"><?php echo $pretty_price; ?></span>
									<?php if(isset($recording_pricing['student_price'])){ ?>
										(student discount available)
									<?php }
									if( $pretty_price <> 'Free' ){ ?>
										<br>
										<small><a class="cc-currency-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-curr-modal" data-type="w" data-id="<?php echo get_the_ID(); ?>">change currency</a></small>
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
								<?php echo isset( $recording_pricing['earlybird_msg'] ) ? $recording_pricing['earlybird_msg'] : '';
							if(isset($recording_pricing['student_price'])){ ?>
							</div>
							<div class="bullet">
								<i class="fa-solid fa-graduation-cap fa-fw"></i>
								Student price
								<span class="student-price">
									<?php echo $recording_pricing['student_price_formatted']; ?>
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
									<a class="cc-currency-changer" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#chg-curr-modal" data-type="w" data-id="<?php echo get_the_ID(); ?>">change currency</a>
							<?php }
							if(!isset($recording_pricing['student_price'])){ ?>
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




	<?php // the registration form ?>
	<div class="d-none">
		<form id="reg-form" action="/registration" method="POST">
			<input type="hidden" id="training-type" name="training-type" value="r">
			<input type="hidden" id="training-id" name="workshop-id" value="<?php echo get_the_ID(); ?>">
			<input type="hidden" id="eventID" name="eventID" value="">
			<input type="hidden" id="num-events" name="num-events" value="<?php echo $num_events; ?>">
			<input type="hidden" id="num-free" name="num-free" value="<?php echo $num_free; ?>">
			<input type="hidden" id="currency" name="currency" value="<?php echo $recording_currency; ?>">
			<input type="hidden" id="raw-price" name="raw-price" value="<?php echo $raw_price; ?>">
			<input type="hidden" id="user-timezone" name="user-timezone" value="<?php echo $user_timezone; ?>">
			<input type="hidden" id="user-prettytime" name="user-prettytime" value="<?php echo $pretty_timezone; ?>">
			<input type="hidden" id="student" name="student" value="no">
			<input type="hidden" id="student-price" name="student-price" value="<?php echo isset( $recording_pricing['student_price'] ) ? $recording_pricing['student_price'] : ''; ?>">
		</form>
	</div>

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
