<?php
/**
 * The resource hub archive page
 * now uses $_GET, not $_POST
 */

get_header(); ?>

<div class="wms-sect-page-head">
	<div class="container">
		<div class="row">
			<div class="col-12">
				<header class="entry-header">
					<h1 class="entry-title">Resource hub<?php /*
						if( $count_terms == 1 ){
							// single term
							$term = get_term($single_term_id);
							echo ': '.$term->name;
						} */
					?></h1>
				</header>
			</div>
		</div>

		<?php if($rpm_theme_options['resource_hub-intro'] <> ''){ ?>
			<div class="row mt-3">
				<div class="col-12">
					<?php
					echo do_shortcode( wpautop( $rpm_theme_options['resource_hub-intro'] ) );
					/*
					if( $count_terms == 0 ){
						echo do_shortcode( wpautop( $rpm_theme_options['resource_hub-intro'] ) );
					}elseif( $count_terms == 1 ){
						echo do_shortcode( wpautop( $term->description ) );
					}else{ ?>
						<p>Showing results for filter/search:<br><?php echo $terms_text; ?></p>
					<?php } */ ?>
				</div>
			</div>
		<?php }

		$params = cc_training_search_params(); // works for training or resource hub
		?>

	</div>
</div>

<div class="wms-section wms-section-std rhub-search-section sml-padd-top">
	<div class="wms-sect-bg">
		<div class="container-fluid">
			<div class="row">
				<div id="topics-sf-panel" class="topics-sf-panel main col-md-4 col-lg-3 col-xl-2 bg-light mb-3">
					<div class="p-5 p-md-2">
						<?php echo cc_topics_sf_sidebar( $params, 'no' ); ?>
					</div>
				</div>
				<div class="col-md-8 col-lg-9 col-xl-10">
					<button class="btn btn-primary btn-sm d-md-none mb-3" id="topics-sf-toggle-sidebar">Topics filter &amp; search</button>
					<div id="topics-sf-wrap" class="topics-sf-wrap">
						<?php // base64_encode to solve the quotations problem! ?>
						<input type="hidden" id="topics-sf-loader" value='<?php echo base64_encode( maybe_serialize( $params ) ); ?>'>
						<div class="text-center m-5">
							<i class="fa-solid fa-spinner fa-spin-pulse"></i> Loading, please wait ...
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
get_footer();

