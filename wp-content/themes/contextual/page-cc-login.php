<?php
/**
* Template Name: CC Login
*/

/**
 * v2.4 Apr 2025
 * - org login can now have a real password (but only if they are the admin)
 * v2.3 Mar 2025
 * - also now used for the organisational login ... no changes needed (just this comment!)
 * v1.0.3.37x Jun 2022
 * Repurposed for the CNWL login
 * - password becomes passcode
 * - remove the lost password functionality
 */

/**
 * If user already logged in, send them on to the my account page
 * if not logged in, check their status:
 * - never logged in = they need to set a password - must have supplied an email and a key
 * - have logged in before, they can login here
 * -- or, if they've forgotten their password, they can request a reset link
 * reset links need to come back here and, if ok, allow a password to be set
 */

/**
 * Reset password link will be something like:
 * - http://contextual.roderickpughmarketing.com/wp-login.php?action=rp&key=Flu7rNpSFergcbitmf5O&login=test2%40roderickpughmarketing.com
 */

global $wp_hasher;

if(cc_users_is_valid_user_logged_in()){
   	wp_redirect( add_query_arg($_GET, '/my-account') );
	exit;
}

if ( empty( $wp_hasher ) ) {
    require_once ABSPATH . WPINC . '/class-phpass.php';
    $wp_hasher = new PasswordHash( 8, true );
}

$action = 'login';
$email = '';
$key = '';
$user = false;
$welcome = '';

$msg = '';
$msg_class = 'has-error';

$debug = '';

if(isset($_GET['login']) && $_GET['login'] <> ''){
	$email = strtolower( sanitize_email($_GET['login']) );
}
if(isset($_GET['key']) && $_GET['key'] <> ''){
	$key = sanitize_text_field($_GET['key']);
}

$debug .= 'Email:'.$email;
if($email <> ''){
	$user = get_user_by('email', $email);
	if(!$user){
		$msg .= 'Email '.$email.' not found';
		$msg_class = 'has-error';
		$action = ' login';
	}else{
		$debug .= ' User ID:'.$user->ID;
		// if a user activation key is set, then the request time will precede the key, separated by a :
		if($user->first_name <> ''){
			$welcome = ', '.$user->first_name;
		}else{
			$welcome = ', '.$user->user_email;
		}
		$registered = strtotime($user->user_registered);
		$debug .= ' registered:'.$registered.' ('.date('d/M/Y H:i:s', $registered).')';
		$debug .= ' time:'.time().' ('.date('d/M/Y H:i:s', time()).')';
		if ( false !== strpos( $user->user_activation_key, ':' ) ) { // only set on reset pwd request! :-(
	        list( $pass_request_time, $pass_key ) = explode( ':', $user->user_activation_key, 2 );
	        $expiration_duration = apply_filters( 'password_reset_expiration', DAY_IN_SECONDS );
	        $expiration_time                      = $pass_request_time + $expiration_duration;
			// $udata = get_userdata( $user->ID );
			// $registered = strtotime($udata->user_registered);
			$debug .= ' expiration_time:'.$expiration_time;
			$debug .= ' pass_request_time:'.$pass_request_time;
			if(time() < $expiration_time && $pass_request_time > $registered - 10 && $pass_request_time < $registered + 10){
				$debug .=  ' new';
				// password reset request still valid and within 10 secs of user registration ... it's a new user
				// have they come here with a valid key in the URL?
				$hash_is_correct = false;
				if($key <> ''){
					$hash_is_correct = $wp_hasher->CheckPassword( $key, $pass_key );
					if($hash_is_correct){
						// now we want them to set a real password
						$action = 'newpass';
					}else{
						$msg .= 'It looks like that link is an old one that has expired. Enter your email to be sent a fresh link.';
						$msg_class = 'has-error';
						$action = 'reqkey';
					}
				}else{
					// no key
					// as they're a new user, we'll allow them to set a password anyway ... as long as they have not logged in already
					$last_login = get_user_meta($user->ID, 'last_login', true);
					$debug .= ' nokey, last_login:'.$last_login;
					if($last_login == 'never' || $last_login == ''){
						$action = 'newpass';
					}else{
						$action = 'login';
					}
				}
			}else{
				// not a new user
				if($key <> ''){
					if(time() < $expiration_time && $wp_hasher->CheckPassword( $key, $pass_key )){
						$action = 'reset';
					}else{
						// key invalid ... but they could still login
						$action = 'login';
					}
				}else{
					// no key supplied ... 
					$action = 'login';
				}
			}
	    } else {
	    	// no key
	    	$debug .= ' nokey';
			// if they have not logged in already, we'll allow them to set a pasword
			$last_login = get_user_meta($user->ID, 'last_login', true);
			if($last_login == 'never' || $last_login == ''){
				$action = 'newpass';
			}else{
				$action = 'login';
			}
	    }
	}
}
$debug .= ' action:'.$action;

get_header();
while ( have_posts() ) : 
	the_post();
	// echo wms_featured_slide();
	?>
	<div class="wms-section wms-section-std sml-padd-top">
		<div class="wms-sect-bg">
			<div class="container">
				<div class="row">
					<div class="col-12">
						<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
							<header class="entry-header">
								<?php // the_title( '<h1 class="entry-title">', '</h1>' ); ?>
							</header><!-- .entry-header -->
							<div class="entry-content">
								<?php the_content();
								if($msg <> ''){ ?>
									<div class="top-msg <?php echo $msg_class; ?>"><?php echo $msg; ?></div>
								<?php } ?>
								<?php echo '<!-- ##### Debug: '.$debug.' -->'; ?>
								<div class="row">
									<div class="col-md-6 offset-md-3">
										<div id="" class="cc-login-wrap">

											<?php if($action == 'login'){
												$cc_login_login_class = '';
											}else{
												$cc_login_login_class = 'd-none';
											} ?>
											<form id="cc-login-login" class="cc-login <?php echo $cc_login_login_class; ?>">
												<div class="row">
													<div class="col-lg-12">
														<h3>Please login</h3>
													</div>
												</div>
												<div class="row mb-3">
													<div id="ccll-email-wrap" class="col-lg-12 ccll-wrap">
														<label for="ccll-email">Email</label>
														<input type="email" id="ccll-email" class="form-control" value="<?php echo $email; ?>">
														<span id="ccll-email-help" class="form-text error"></span>
													</div>
												</div>
												<div class="row mb-3">
													<div id="ccll-pass-wrap" class="col-lg-12 ccll-wrap">
														<label for="ccll-pass">Passcode</label>
														<input type="password" id="ccll-pass" class="form-control">
														<span id="ccll-pass-help" class="form-text error"></span>
													</div>
												</div>
												<div class="row mb-3">
													<div class="col-lg-6">
														<a href="javascript:void(0);" id="ccll-submit" class="btn btn-primary btn-lg">Login</a>
													</div>
													<div id="ccll-loading" class="col-lg-6 text-right d-none">
														<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>
													</div>
												</div>
												<div class="row">
													<div class="col-lg-12">
														<div id="ccll-message" class="cc-login-message"></div>
													</div>
												</div>
											</form>

											<?php /*
											<?php if($action == 'newpass' || $action == 'reset'){
												$cc_login_newpass_class = '';
											}else{
												$cc_login_newpass_class = 'd-none';
											} ?>
											<form id="cc-login-newpass" class="cc-login <?php echo $cc_login_newpass_class; ?>">
												<div class="row">
													<div class="col-lg-12">
														<?php if($action == 'newpass'){ ?>
															<h3>Welcome to Contextual Consulting<?php echo $welcome; ?></h3>
															<p>To login to your account page, you'll need to use your email address (<?php echo $email; ?>) and a password. So, the next step is for you to enter a password of your choice ...</p>
														<?php }else{ ?>
															<h3>Please enter a new password</h3>
														<?php } ?>
													</div>
												</div>
												<input type="hidden" id="ccln-email" value="<?php echo $email; ?>">
												<div class="row">
													<div class="col-lg-12 ccln-wrap">
														<label for="ccln-pass1">Password</label>
														<input type="password" id="ccln-pass1" class="form-control">
														<span id="ccln-pass1-help" class="form-text error"></span>
													</div>
												</div>
												<div class="row">
													<div class="col-lg-12 ccln-wrap">
														<label for="ccln-pass2">Please type the password again</label>
														<input type="password" id="ccln-pass2" class="form-control">
														<span id="ccln-pass2-help" class="form-text error"></span>
													</div>
												</div>
												<div class="row">
													<div class="col-lg-12">
														<p>You will need to choose a password that has a strength of "ok" or "strong". The best passwords are long and/or completely random strings of letters (including some uppercase), numbers and symbols. Please do not re-use passwords.</p>
													</div>
												</div>
												<div class="row">
													<div class="col-lg-4">
														<label for="">Password strength:</label>
													</div>
													<div class="col-lg-8">
														<div id="ccln-strength" class="ccln-strength">Will be shown here as you type</div>
													</div>
												</div>
												<div class="row">
													<div class="col-lg-6">
														<button type="button" id="ccln-submit" class="btn btn-primary btn-lg" disabled="disabled">Set password</button>
														<!-- <a href="javascript:void(0);" id="ccln-submit" class="button" disabled="disabled">Set password</a> -->
													</div>
													<div id="ccln-loading" class="col-lg-6 text-right d-none">
														<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>
													</div>
												</div>
												<div class="row">
													<div class="col-lg-12">
														<div id="ccln-message" class="cc-login-message"></div>
													</div>
												</div>
											</form>
											*/ ?>

											<?php /*
											<?php if($action == 'reqkey'){
												$cc_login_reqkey_class = '';
											}else{
												$cc_login_reqkey_class = 'd-none';
											} ?>
											<form id="cc-login-reqkey" class="cc-login <?php echo $cc_login_reqkey_class; ?>">
												<div class="row">
													<div class="col-lg-12">
														<h3>Password reset</h3>
														<p>We'll email you a link to set your password</p>
													</div>
												</div>
												<div class="row">
													<div class="col-lg-12">
														<label for="cclr-email">Email</label>
														<input type="email" id="cclr-email" class="form-control" value="<?php echo $email; ?>">
														<span id="cclr-email-help" class="form-text error"></span>
													</div>
												</div>
												<div class="row">
													<div class="col-lg-6">
														<a href="javascript:void(0);" id="cclr-submit" class="button">Send reset password link</a>
													</div>
													<div id="cclr-loading" class="col-lg-6 text-right d-none">
														<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>
													</div>
												</div>
												<div class="row">
													<div class="col-lg-12">
														<div id="cclr-message" class="cc-login-message"></div>
													</div>
												</div>
												<div class="row">
													<div class="col-lg-12">
														<p class="text-right"><a id="cclr-login-trigger" class="button tiny" href="javascript:void(0);">Back to login?</a></p>
													</div>
												</div>
											</form>
											*/ ?>

										</div>
									</div>
								</div>
							</div><!-- .entry-content -->
						</article>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php endwhile; // end of the loop.
get_footer();
