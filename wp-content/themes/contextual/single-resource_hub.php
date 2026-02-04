<?php
/**
 * Single Resource Hub Item
 *
 * @package Contextual
 */

get_header();
while ( have_posts() ) : the_post();
	// we don't show featured images on the resource hub items
	// $page_slider_html = wms_page_slider();
	// echo $page_slider_html;

	// Check if user has access to this resource
	$user_has_access = cc_resourcehub_user_has_access(get_the_ID());
	$is_member_only = get_post_meta(get_the_ID(), '_member_only', true);

	// get the workshop IDs (if any) that match this item
	$taxonomies = array( 'tax_issues', 'tax_approaches', 'tax_rtypes', 'tax_others', 'tax_trainlevels' );
	$parms = array();
	foreach ($taxonomies as $taxonomy) {
		$terms = get_the_terms( get_the_ID(), $taxonomy );
		if ( $terms && ! is_wp_error( $terms ) ){
			foreach ($terms as $term) {
				$parms[$taxonomy][] = $term->term_id;
			}
		}
	}
	$workshop_ids = workshops_next_in_taxonomies( $parms );
	if( count( $workshop_ids ) > 0 ){
		$show_workshops = true;
	}else{
		$show_workshops = false;
	}
	
	?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="wms-section wms-section-std sml-padd">
			<div class="wms-sect-bg">
				<div class="container">
					<div class="row">

						<?php if( $show_workshops ){ ?>
							<div class="col-md-9">
						<?php }else{ ?>
							<div class="col-12">
						<?php } ?>

							<header class="entry-header">
								<?php the_title( '<h1 class="entry-title text-center">', '</h1>' ); ?>
								
								<?php if ($is_member_only && !$user_has_access): ?>
									<div class="alert alert-warning mt-3 cc-rhub-access-alert">
										<div class="d-flex align-items-center">
											<i class="fas fa-lock me-2 cc-rhub-icon"></i>
											<div>
												<strong>Member Only Resource</strong>
												<p class="mb-0">This resource is available to paid members only. Sign up or log in to access the full content.</p>
											</div>
										</div>
									</div>
								<?php endif; ?>
							</header>

							<div class="entry-content">

								<?php echo cc_topics_show( get_the_ID(), '', true, 'resource_hub' ); ?>

								<?php if ($user_has_access): ?>
									<?php // User has access - show full content ?>
									<?php the_content(); ?>
									
								<?php elseif ($is_member_only): ?>
									<?php // Member-only resource but user doesn't have access - show preview ?>
									<div class="cc-rhub-resource-preview">
										<?php echo cc_resourcehub_get_preview_content(get_the_ID()); ?>
									</div>
									
									<div class="cc-rhub-member-access-prompt mt-4 p-4 bg-light border rounded">
										<div class="row align-items-center">
											<div class="col-md-8">
												<h4><i class="fas fa-star text-warning me-2 cc-rhub-icon"></i>Unlock Full Access</h4>
												<p class="mb-0">Become a member to access the complete resource and unlock hundreds of other premium resources.</p>
											</div>
											<div class="col-md-4 text-end">
												<div class="btn-group-vertical d-grid gap-2">
													<?php if (!is_user_logged_in()): ?>
														<button type="button" class="btn btn-primary text-white" data-bs-toggle="modal" data-bs-target="#ccRhubLoginModal">
															<i class="fas fa-sign-in-alt me-2 cc-rhub-icon"></i>Login
														</button>
													<?php endif; ?>
													<a href="<?php echo home_url('/membership/'); ?>" class="btn btn-success">
														<i class="fas fa-rocket me-2 cc-rhub-icon"></i>Become a Member
													</a>
												</div>
											</div>
										</div>
									</div>
									
								<?php else: ?>
									<?php // Public resource - show full content ?>
									<?php the_content(); ?>
								<?php endif; ?>

								<?php if ($user_has_access): ?>
									<?php echo cc_resourcehub_linked_rhub( get_the_id() ); ?>
								<?php endif; ?>

							</div>
						</div>

						<?php if( $show_workshops ){ ?>

							<div class="col-md-3">
								<div class="wms-background-outer">
									<div class="wms-background background-subtle">
										<div class="inner">
											<h3>Upcoming live training</h3>
											<?php foreach ($workshop_ids as $workshop_id) {
												echo cc_wksp_resknow_hub_card( $workshop_id );
											} ?>
											<div class="text-end">
												<a href="/live-training" class="btn btn-primary">All live training</a>
											</div>
										</div>
									</div>
								</div>
							</div>

						<?php } ?>

					</div>
				</div>
			</div>
		</div>
	</article>

	<?php if ($user_has_access): ?>
		<?php echo cc_topics_linked_section( 'knowledge_hub', $parms ); ?>
		<?php echo cc_topics_linked_section( 'recording', $parms ); ?>
		<?php echo cc_topics_linked_section( 'post', $parms ); ?>
		<?php echo cc_topics_linked_section( 'resource_hub', $parms, array( get_the_ID() ) ); ?>
	<?php endif; ?>

	<?php if (!is_user_logged_in() && $is_member_only): ?>
		<!-- Login Modal -->
		<div class="modal fade cc-rhub-login-modal" id="ccRhubLoginModal" tabindex="-1" aria-labelledby="ccRhubLoginModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header dark-bg">
						<h5 class="modal-title" id="ccRhubLoginModalLabel">
							<i class="fas fa-sign-in-alt me-2 cc-rhub-icon"></i>Login to Access Resource
						</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<form id="ccRhubLoginForm" class="cc-rhub-login-form">
							<div class="mb-3">
								<label for="ccRhubLoginUsername" class="form-label">Email</label>
								<input type="text" class="form-control" id="ccRhubLoginUsername" name="username" required>
							</div>
							<div class="mb-3">
								<label for="ccRhubLoginPassword" class="form-label">Password</label>
								<input type="password" class="form-control" id="ccRhubLoginPassword" name="password" required>
							</div>
							<input type="hidden" name="remember" value="true">
							<div id="ccRhubLoginError" class="alert alert-danger d-none cc-rhub-login-error"></div>
						</form>
						<div class="text-center mt-3">
							<p class="text-muted">Don't have an account?</p>
							<a href="<?php echo home_url('/membership/'); ?>" class="btn btn-outline-success">
								<i class="fas fa-user-plus me-2 cc-rhub-icon"></i>Become a Member
							</a>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="button" class="btn btn-primary" id="ccRhubLoginSubmit">
							<span class="cc-rhub-login-btn-text">
								<i class="fas fa-sign-in-alt me-2 cc-rhub-icon"></i>Login
							</span>
							<span class="cc-rhub-login-btn-loading d-none">
								<i class="fas fa-spinner cc-rhub-fa-spin me-2"></i>Logging in...
							</span>
						</button>
					</div>
				</div>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('#ccRhubLoginSubmit').on('click', function(e) {
				e.preventDefault();
				
				var $btn = $(this);
				var $form = $('#ccRhubLoginForm');
				var $error = $('#ccRhubLoginError');
				
				// Show loading state
				$btn.prop('disabled', true);
				$('.cc-rhub-login-btn-text').addClass('d-none');
				$('.cc-rhub-login-btn-loading').removeClass('d-none');
				$error.addClass('d-none');
				
				// Get form data
				var formData = {
					action: 'resource_hub_login',
					username: $('#ccRhubLoginUsername').val(),
					password: $('#ccRhubLoginPassword').val(),
					redirect_url: window.location.href,
					nonce: resource_hub_ajax.login_nonce
				};
				
				$.ajax({
					url: resource_hub_ajax.ajax_url,
					type: 'POST',
					data: formData,
					success: function(response) {
						if (response.success) {
							// Login successful - reload page
							window.location.reload();
						} else {
							// Show error
							$error.text(response.data.message).removeClass('d-none');
						}
					},
					error: function() {
						$error.text('An error occurred. Please try again.').removeClass('d-none');
					},
					complete: function() {
						// Reset loading state
						$btn.prop('disabled', false);
						$('.cc-rhub-login-btn-text').removeClass('d-none');
						$('.cc-rhub-login-btn-loading').addClass('d-none');
					}
				});
			});
			
			// Allow enter key to submit
			$('#ccRhubLoginForm input').on('keypress', function(e) {
				if (e.which === 13) {
					$('#ccRhubLoginSubmit').click();
				}
			});
		});
		</script>
	<?php endif; ?>

<?php endwhile; // End of the loop.
get_footer();