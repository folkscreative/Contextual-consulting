<?php
/**
 * The recording archive page (all available recordings)
 *
 * @package Contextual
 */

global $rpm_theme_options;

get_header();
?>
<div class="wms-sect-page-head">
	<div class="container">
		<div class="row">
			<div class="col-12 text-center">
				<header class="entry-header">
					<h1 class="entry-title">On-demand training</h1>
				</header>
			</div>
		</div>
	</div>
</div>

<?php if($rpm_theme_options['recording-intro'] <> ''){ ?>
	<div class="wms-section wms-section-std sml-padd white-bg">
		<div class="wms-sect-bg">
			<div class="container">
				<div class="row">
					<div class="col-12 col-md-10 offset-md-1">
						<?php echo do_shortcode($rpm_theme_options['recording-intro']); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>

<div id="" class="wms-section wms-section-std sml-padd white-bg">
	<div class="wms-sect-bg">
		<div class="container">
			<div class="row">
				<div class="col">
					<div class="d-none d-md-block">
						<a href="javascript:void(0);" id="ftc-panel-list" class="ftc-panel-sel ftc-list" data-layout="list">
							<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/layout-list.svg" alt="training list selector" class="ftc-selector-icon inactive">
							<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/layout-list-active.svg" alt="training list selector" class="ftc-selector-icon active">
						</a>
						<a href="javascript:void(0);" id="ftc-panel-grid" class="ftc-panel-sel ftc-grid active" data-layout="grid">
							<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/layout-grid.svg" alt="training list selector" class="ftc-selector-icon inactive">
							<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/layout-grid-active.svg" alt="training list selector" class="ftc-selector-icon active">
						</a>
					</div>
				</div>
			</div>

			<div id="ftc-panel" class="row training-grid mt-3 d-flex flex-wrap equal-height-row" data-content="live-archive">
				<?php
				// $recording_stuff = recording_archive_get_posts();
				// switched to courses
				$recording_stuff = courses_for_recording_archive();
				foreach ($recording_stuff['recordings'] as $recording_id) {
					echo cc_training_card_flexible( $recording_id );
				}
				echo cc_navigation_posts( $recording_stuff['page_num'], $recording_stuff['pages'], 'recording' );
				?>
			</div>

		</div>
	</div>
</div>


<?php

// the registration form
$recording_currency = cc_currency_get_user_currency();
$user_id = get_current_user_id(); // 0 if not logged in
$user_timezone = cc_timezone_get_user_timezone($user_id);
$pretty_timezone = cc_timezone_get_user_timezone_pretty($user_id, $user_timezone);
?>
<div class="d-none">
	<form id="archive-reg-form" action="/registration" method="POST">
		<input type="hidden" id="training-type" name="training-type" value="r">
		<input type="hidden" id="training-id" name="workshop-id" value="0">
		<input type="hidden" id="eventID" name="eventID" value="">
		<input type="hidden" id="num-events" name="num-events" value="0">
		<input type="hidden" id="num-free" name="num-free" value="0">
		<input type="hidden" id="currency" name="currency" value="<?php echo $recording_currency; ?>">
		<input type="hidden" id="raw-price" name="raw-price" value="0">
		<input type="hidden" id="user-timezone" name="user-timezone" value="<?php echo $user_timezone; ?>">
		<input type="hidden" id="user-prettytime" name="user-prettytime" value="<?php echo $pretty_timezone; ?>">
		<input type="hidden" id="student" name="student" value="no">
		<input type="hidden" id="student-price" name="student-price" value="">
	</form>
</div>

<?php get_footer();
