<?php
/**
 * The workshop archive page (upcoming workshops)
 *
 * @package Contextual
 */

get_header();
?>
<div class="wms-sect-page-head">
	<div class="container">
		<div class="row">
			<div class="col-12 text-center">
				<header class="entry-header">
					<h1 class="entry-title">Upcoming live training</h1>
				</header>
			</div>
		</div>
	</div>
</div>

<?php if($rpm_theme_options['workshop-intro'] <> ''){ ?>
	<div class="wms-section wms-section-std sml-padd white-bg">
		<div class="wms-sect-bg">
			<div class="container">
				<div class="row">
					<div class="col-12 col-md-10 offset-md-1">
						<?php echo do_shortcode($rpm_theme_options['workshop-intro']); ?>
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
				$workshops = workshop_archive_get_posts();
				foreach ($workshops as $workshop) {
					echo cc_training_card_flexible( $workshop->ID );
				}
				?>
			</div>

		</div>
	</div>
</div>

<?php get_footer();
