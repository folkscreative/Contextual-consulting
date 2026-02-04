<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Contextual
 */

get_header();
while ( have_posts() ) : the_post();
	$page_slider_html = wms_page_slider();
	echo $page_slider_html;

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
	// $workshop_ids = workshops_next_in_taxonomies( $parms );
	$training_ids = cc_trainings_for_hubs( $parms );
	if( count( $training_ids ) > 0 ){
		$show_trainings = true;
	}else{
		$show_trainings = false;
	}

	$page_title_locn = get_post_meta( get_the_ID(), '_page_title_locn', true);

	?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="wms-section wms-section-std sml-padd">
			<div class="wms-sect-bg">
				<div class="container">

					<div class="row">
						<div class="col-12">
							<header class="entry-header">
								<?php if($page_slider_html == '' || $page_title_locn == ''){ ?>
									<div class="text-center">
										<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
									</div>
								<?php } ?>
								<div class="mb-2">
									<?php
									echo contextual_posted_on();
									echo contextual_post_cats();
									?>
								</div>
								<?php echo cc_topics_show( get_the_ID(), '', true, 'post' ); ?>
							</header>
						</div>
					</div>

					<div class="row">

						<?php if( $show_trainings ){ ?>
							<div class="col-md-9">
						<?php }else{ ?>
							<div class="col-12">
						<?php } ?>

							<div class="entry-content">
								<?php the_content();

								echo cc_resourcehub_linked ( get_the_ID() ); ?>
							</div>
						</div>

						<?php if( $show_trainings ){ ?>

							<div class="col-md-3">
								<div class="wms-background-outer">
									<div class="wms-background background-subtle">
										<div class="inner training-sidebar">
											<h3>Upcoming live training</h3>
											<?php foreach ($training_ids as $training_id) {
												// echo cc_wksp_resknow_hub_card( $training_id );
												echo cc_training_card_flexible( $training_id, array(), false );
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

	<?php 
	echo cc_topics_linked_section( 'knowledge_hub', $parms );

	echo cc_topics_linked_section( 'recording', $parms );

	echo cc_topics_linked_section( 'post', $parms );

	echo cc_topics_linked_section( 'resource_hub', $parms, array( get_the_ID() ) );

endwhile; // End of the loop.
get_footer();
