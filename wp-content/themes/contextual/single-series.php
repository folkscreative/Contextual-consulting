<?php
/**
 * The series page
 */

get_header();
while ( have_posts() ) : the_post();
	$page_slider_html = wms_page_slider();
	echo $page_slider_html;

	$series_id = get_the_ID();

	// Check if user is a portal user - they cannot register for series
	$user_id = get_current_user_id();
	$portal_user = '';
	$portal_blocked = false;
	if (cc_users_is_valid_user_logged_in()) {
	    $portal_user = get_user_meta($user_id, 'portal_user', true);
	    if (!empty($portal_user)) {
	        $portal_blocked = true;
	    }
	}	

    // Load existing data
    $series_status = get_post_meta($series_id, '_series_status', true);
    $series_discount = get_post_meta($series_id, '_series_discount', true);

    // Get courses currently in this series
    $selected_courses = get_post_meta($post->ID, '_series_courses', true);
    $selected_courses = is_string($selected_courses) ? json_decode($selected_courses, true) : [];
    $selected_courses = is_array($selected_courses) ? $selected_courses : [];

	$page_title_locn = get_post_meta( $series_id, '_page_title_locn', true);

	?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="wms-section wms-section-std sml-padd">
			<div class="wms-sect-bg">
				<div class="container">

					<div class="row">

						<div class="col-12 col-lg-9">

							<header class="entry-header">
								<?php if($page_slider_html == '' || $page_title_locn == ''){ ?>
									<div class="text-center">
										<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
									</div>
								<?php } ?>
							</header>

							<div class="entry-content">
								<?php the_content(); ?>
							</div>

							<div class="wms-background-outer">
								<div class="wms-background background-subtle">
									<div class="inner">
										<div class="row training-grid">
											<h3>Training in this series:</h3>
											<?php foreach ( $selected_courses as $training_id ) {
												echo cc_training_card_flexible( $training_id, array(), false );
											} ?>
										</div>
									</div>
								</div>
							</div>

						</div>

						<div class="col-12 col-lg-3">
						    <?php 
						    if ($portal_blocked) {
						        echo '<div class="tgsd-card my-5 dark-bg">
						            <div class="tgsd-body p-3">
						                <p class="text-center">Series and group registrations are not available for your organisation. Please register for individual courses.</p>
						            </div>
						        </div>';
						    } else {
						        echo training_groups_selection_card($series_id); 
						    }
						    ?>
						</div>

					</div>

				</div>
			</div>
		</div>
	</article>

	<?php

endwhile; // End of the loop.
get_footer();
