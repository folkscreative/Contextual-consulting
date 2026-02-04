<?php
/**
 * Single presenter
 *
 * @package Contextual
 */

get_header();
while ( have_posts() ) : the_post(); ?>
    <div class="wms-section">
    	<div class="wms-sect-bg">
	    	<div class="container">
	    		<div class="row align-items-center">
	    			<div class="col-12 col-md-3 order-md-2">
	    				<div class="presenter-img-wrap">
	    					<?php
	    					$image_id = cc_presenters_image_id( get_the_ID(), '1');
	    					echo do_shortcode('[background image="'.$image_id.'" xclass="mb-5" image_size="presenter-profile"][blank size="5"][/background]');
	    					?>
	    				</div>
	    			</div>
	    			<div class="col-12 col-md-5 order-md-1 offset-md-2">
	    				<div class="presenter-intro">
	                        <?php the_title( '<h1 class="entry-title">', '</h1>' );
							$qualifications = get_post_meta( get_the_ID(), 'qualifications', true);
							if($qualifications <> ''){
								echo '<h4>'.$qualifications.'</h4>';
							} ?>
	    				</div>
	    			</div>
	    		</div>
	    		<div class="row mb-3">
	    			<div class="col-12 col-md-8 offset-md-2">
	    				<?php the_content(); ?>
	    			</div>
	    		</div>

	    		<?php
	    		$workshops = cc_presenters_workshops(get_the_ID());
	    		$user_timezone = cc_timezone_get_user_timezone();
	    		if(count($workshops) > 0){ ?>
		    		<div class="row">
		    			<div class="col-12 col-md-8 offset-md-2">
	    					<h4 class="mt-5">Upcoming live training presented by <?php echo get_the_title(get_the_ID()); ?></h4>
		    				<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3">
				    			<?php foreach ($workshops as $workshop) { ?>
				    				<div class="col mb-3">
				    					<div class="card d-flex align-items-stretch h-100 presenter-workshop-card grad-bg">
				    						<?php echo get_the_post_thumbnail( $workshop, 'post-thumb', array( 'class' => 'card-img-top' ) ); ?>
											<div class="card-body">
												<h5 class="card-title"><?php echo get_the_title($workshop->ID); ?></h5>
												<p class="card-text"><?php 
													$pretty_dates = workshop_calculated_prettydates($workshop->ID, $user_timezone);
													if($pretty_dates['locale_date'] <> ''){
														echo '<p class="date">'.$pretty_dates['locale_date'].'</p>';
													}
													$subtitle = get_post_meta($workshop->ID, 'subtitle', true);
													if(strlen($subtitle) > 60){
														$subtitle = substr($subtitle, 0, 55).'...';
													}
													echo $subtitle ?></p>
											</div>
											<a href="<?php echo get_permalink($workshop->ID); ?>" class="btn btn-primary m-3 mt-auto">See Details</a>
										</div>
				    				</div>
				    			<?php } ?>
			    			</div>
		    			</div>
		    		</div>
		    	<?php }

		    	$recording_ids = cc_presenters_recordings(get_the_ID());
		    	if( count( $recording_ids ) > 0 ){ ?>
		    		<div class="row">
		    			<div class="col-12 col-md-8 offset-md-2">
		    				<h4 class="mt-5">On-demand training presented by <?php echo get_the_title(get_the_ID()); ?></h4>
		    				<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3">
				    			<?php foreach ($recording_ids as $recording_id) { ?>
				    				<div class="col mb-3">
				    					<div class="card d-flex align-items-stretch h-100 presenter-workshop-card grad-bg">
				    						<?php echo get_the_post_thumbnail( $recording_id, 'post-thumb', array( 'class' => 'card-img-top' ) ); ?>
											<div class="card-body">
												<h5 class="card-title"><?php echo get_the_title($recording_id); ?></h5>
												<p class="card-text"><?php 
													$subtitle = get_post_meta($recording_id, 'subtitle', true);
													if(strlen($subtitle) > 60){
														$subtitle = substr($subtitle, 0, 55).'...';
													}
													echo $subtitle ?></p>
											</div>
											<a href="<?php echo get_permalink($recording_id); ?>" class="btn btn-primary m-3 mt-auto">See details</a>
										</div>
				    				</div>
				    			<?php } ?>
			    			</div>
		    			</div>
		    		</div>
		    	<?php } ?>

		    	<div class="row">
		    		<div class="col-12 col-md-8 offset-md-2">
		    			<?php echo cc_resourcehub_linked ( get_the_ID() ); ?>
		    		</div>
		    	</div>

	    	</div>
    	</div>
    </div>
<?php endwhile; // End of the loop.
get_footer();
