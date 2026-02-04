<?php
/**
* Template Name: My Account
*/

/**
 * Having trouble with user switching?
 * Check the value of the usermeta: force_new_password
 * If this is "yes" then user switching will fail!
 */

// ccpa_write_log('page-my-account.php');

// not logged in people get sent to login page
if(!cc_users_is_valid_user_logged_in()){
	// ccpa_write_log('cc_users_is_valid_user_logged_in returned false');
	wp_redirect( add_query_arg($_GET, '/member-login') );
	exit;
}
// ccpa_write_log('cc_users_is_valid_user_logged_in returned true');

$email = '';
if(isset($_GET['login']) && $_GET['login'] <> ''){
	$email = sanitize_email($_GET['login']);
}
// ccpa_write_log('email: '.$email);

if($email <> '' && !cc_users_email_matches_curr_user($email)){
	// ccpa_write_log('email non blank and not matched');
	// log them out so they can log in as the correct user
	wp_logout();
   	wp_redirect( add_query_arg($_GET, '/member-login') );
	exit;
}

// ccpa_write_log('we are in!');

$current_user = wp_get_current_user();

// user switching was getting confused with this one as the flag was not being cleared properly. So, if you cannot switch ... check that the value of force_new_password is ''
$force_new_password = get_user_meta($current_user->ID, 'force_new_password', true);
if ($force_new_password == 'yes'){
	wp_logout();
   	wp_redirect( add_query_arg($_GET, '/member-login') );
	exit;
}

// to give admins access to portal dashboards
$org = '';
if( isset( $_GET['org'] ) && $_GET['org'] <> '' ){
	$org = stripslashes( sanitize_text_field( $_GET['org'] ) );
}

$my_acct_sections = cc_myacct_pages();
$my_acct_section = '';
if(isset($_GET['my'])){
	$safe_my = stripslashes( sanitize_text_field( $_GET['my'] ) );
	if( isset( $my_acct_sections[$safe_my] ) || ( $safe_my == 'dashboard' && $org <> '' ) ){
		$my_acct_section = $safe_my;
	}
}
if($my_acct_section == ''){
	// try to send the user off to their most relevant training
	$portal_user = get_user_meta( $current_user->ID, 'portal_user', true );
	$portal_admin = get_user_meta( $current_user->ID, 'portal_admin', true);
	if( $portal_admin == 'yes' && $portal_user <> '' ){
		$my_acct_section = 'dashboard';
	}else{
		$wkshop_users = cc_myacct_get_workshops( $current_user->ID );
		if((count($wkshop_users)) > 0){
			$my_acct_section = 'workshops';
		}else{
		    $user_recordings_ids = cc_trainings_recordings_for_attendee( $current_user->ID );
			if((count($user_recordings_ids)) > 0){
				$my_acct_section = 'recordings';
			}else{
				$my_acct_section = 'workshops';
			}
		}
	}
}

get_header();
while ( have_posts() ) : 
	the_post();
	$page_slider_html = wms_page_slider();
	echo $page_slider_html;
	$page_title_locn = get_post_meta($post->ID, '_page_title_locn', true);
	if($page_slider_html == '' || $page_title_locn == ''){
		if($page_slider_html == ''){
			$xclass = 'no-hero';
		}else{
			$xclass = '';
		} ?>
		<div class="wms-sect-page-head <?php echo $xclass; ?>">
			<div class="container">
				<div class="row">
					<div class="col-6">
						<h1>My account</h1>
					</div>
					<div class="col-6 text-end">
						<a href="<?php echo wp_logout_url( home_url() ); ?>" class="btn btn-primary">Logout</a>
					</div>
					<div class="col-12">
						<hr class="hr-full">
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<div class="container myacct-page">

		<div class="row mb-3">
			<div class="col-2 d-md-none">
				<div class="my-acct-menu-trigger-wrap">
					<?php // <div id="my-acct-menu-trigger" class="my-acct-menu-trigger text-center"><i class="fa-solid fa-bars"></i></div> ?>
					<button id="my-acct-menu-trigger" title="menu">
					    <i class="fa-solid fa-fw fa-bars icon-bars"></i>
					    <i class="fa-solid fa-fw fa-times icon-times"></i>
					</button>
				</div>
			</div>
			<div class="col-8 d-md-none">
				<p class="my-acct-user-name mb-1 text-end">
					<?php echo $current_user->first_name.' '.$current_user->last_name; ?>
				</p>
			</div>
			<div class="col-2 d-md-none">
				<div class="avatar-wrap">
					<?php echo cc_avatar(); ?>
				</div>
			</div>
			<?php /*
			<div class="col-10 col-md-12 col-xl-10 offset-xl-1">
				<h3 class="my-acct-page-head mb-5">
					<?php echo $my_acct_sections[$my_acct_section]['title']; ?>
				</h3>
			</div>
			*/ ?>
		</div>

		<?php // the mobile menu ?>
		<div id="myacct-mob-menu" class="row myacct-mob-menu">
			<div class="col">
				<div class="my-acct-mob-menu-wrap">
					<?php echo cc_myacct_menu($my_acct_section); ?>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-12">
				
				<?php // Add membership banner here ?>
				<?php // echo cc_membership_banner(); ?>
				<?php echo cc_portal_contract_banner(); ?>
				
				<div id="my-acct-content-wrap" class="row">
					<div class="d-none d-md-block col-md-4 col-xxl-3">
						<div class="avatar-wrap">
							<?php echo cc_avatar( 'xl' ); ?>
						</div>
						<p class="my-acct-user-name-lg mb-1">
							<?php echo $current_user->first_name.' '.$current_user->last_name; ?>
						</p>
						<p class="my-acct-user-email mb-4">
							<?php echo $current_user->user_email; ?>
						</p>
						<div class="">
							<?php echo cc_myacct_menu($my_acct_section); ?>
						</div>
					</div>
					<div class="col-12 col-md-8 col-xxl-9">
						<?php
						switch ($my_acct_section) {
							case 'details':			echo cc_myacct_details();			break;
							case 'workshops':		echo cc_myacct_workshops(true);		break;
							case 'past-workshops':	echo cc_myacct_workshops(false);	break;
							case 'recordings':		echo cc_myacct_recordings();		break;
							case 'merge':			echo cc_myacct_merge();				break;
							case 'supervision':		echo cc_myacct_details();			break;
							case 'friend':			echo cc_myacct_friend();			break;
							case 'offers':			echo cc_myacct_details();			break;
							case 'resources':		echo cc_myacct_details();			break;
							case 'registrations':	echo cc_myacct_details();			break;
							case 'orders':			echo cc_myacct_orders();			break;
							case 'membership':		echo cc_myacct_membership();		break; // New case
							case 'dashboard':		echo cc_myacct_dashboard( $org );	break;
						}
						?>
					</div>
				</div>
			</div>
		</div>

	</div>

	<?php // The CE Credits modal ... now used for all certificates ?>
	<div id="cecredits-modal" class="modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h6 class="modal-title"></h6>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body"><div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div></div>
			</div>
		</div>
	</div>

	<?php // The training details modal ?>
	<div id="myacct-training-dets-modal" class="modal myacct-training-dets-modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered modal-xl">
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
					<h6 class="modal-title"></h6>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body"><div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div></div>
			</div>
		</div>
	</div>

	<?php // the training modal ?>
	<div class="modal fade modal-fullscreen-custom myacct-training-modal" id="myacct-training-modal" tabindex="-1" aria-labelledby="fullscreenModalLabel" aria-hidden="true" data-userid="<?php get_current_user_id(); ?>">
	    <div class="modal-dialog modal-dialog-centered">
	        <div class="modal-content">
	            <div class="modal-header">
	                <h5 class="modal-title"></h5>
	                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
	            </div>
	            <div class="modal-body">
	                <div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div>
	            </div>
	        </div>
	    </div>
	</div>

	<?php // the resources modal ?>
	<div id="training-resources-modal" class="modal resources-modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h6 class="modal-title"></h6>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body"><div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div></div>
			</div>
		</div>
	</div>

	<?php // the feedback modal ?>
	<div id="myacct-feedback-modal" class="modal feedback-modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h6 class="modal-title"></h6>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body"><div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div></div>
			</div>
		</div>
	</div>

	<?php // The quiz modal ?>
	<div id="myacct-quiz-modal" class="modal quiz-modal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h6 class="modal-title"></h6>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body"><div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div></div>
			</div>
		</div>
	</div>

<?php endwhile; // end of the loop.
get_footer();
