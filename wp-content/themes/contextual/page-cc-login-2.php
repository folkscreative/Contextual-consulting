<?php
/**
* Template Name: CC Login v2
*/

/**
 * New page created Jan 2022 to replace the CC Login page
 * Now takes users through the login process one step at a time
 */

/**
 * Needs to accommodate people arriving here in different statii:
 * - no parms, not logged in
 * - logged in already ... get them out of here quick
 * - email supplied (new user or old user)
 * - clicked a password reset link
 */

$email = '';
if(isset($_GET['login']) && $_GET['login'] <> ''){
	$email = stripslashes( sanitize_email($_GET['login']) );
}

if(cc_users_is_valid_user_logged_in()){
	if($email == '' || cc_users_email_matches_curr_user($email)){
	   	wp_redirect( add_query_arg($_GET, '/my-account') );
		exit;
	}else{
		// log them out so they can log in as the correct user
    	wp_logout();
	}
}

// for feedback ...
$feedback_redirect = '';
if(isset($_GET['f']) && $_GET['f'] <> ''){
	$feedback_redirect = stripslashes( sanitize_text_field( $_GET['f'] ) );
}

get_header();
while ( have_posts() ) : 
	the_post();
	?>
	<div class="wms-section wms-section-std">
		<div class="wms-sect-bg">
			<div class="container">
				<div class="row">
					<div class="col-12 col-md-8 offset-md-2 col-lg-6 offset-lg-3">
						<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
							<header class="entry-header text-center">
								<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
							</header><!-- .entry-header -->
							<div class="entry-content">
								<input type="hidden" id="cc-feedback-redirect" value="<?php echo $feedback_redirect; ?>">
								<?php the_content();
								// could be a normal login or a password reset
								?>
								<div id="ccll2-form-wrap">
									<?php echo cc_login_starting_html(); ?>
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

