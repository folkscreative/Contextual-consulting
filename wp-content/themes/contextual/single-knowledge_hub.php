<?php
/**
 * Single Knowledge Hub Item
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
							</header>

							<div class="entry-content">

								<?php echo cc_topics_show( get_the_ID(), '', true, 'resource_hub' );

								the_content();

								echo cc_resourcehub_linked ( get_the_ID() ); ?>

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

	<?php echo cc_topics_linked_section( 'resource_hub', $parms ); ?>

	<?php echo cc_topics_linked_section( 'recording', $parms ); ?>

	<?php echo cc_topics_linked_section( 'post', $parms ); ?>

	<?php echo cc_topics_linked_section( 'knowledge_hub', $parms, array( get_the_ID() ) ); ?>

<?php endwhile; // End of the loop.
get_footer();
