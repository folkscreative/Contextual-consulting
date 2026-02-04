<?php
/*
Template Name: Register Page
*/

/**
 * The template for registering for workshops through an iframe
 */

get_header(); ?>

			<!-- register page -->

			<div class="row">
				<div class="columns">

					<?php
					$workshopID = $_POST['workshopID'];
					$eventID = $_POST['eventID'];
					if($eventID == 1){
						$meta_field = 'meta_b';
					}else{
						$meta_field = 'event_'.$eventID.'_reg';
					}
					$register_code = get_post_meta( $workshopID, $meta_field, true );
					echo '<!-- workshop ID = '.$workshopID.', event ID = '.$eventID.' register code ='.$register_code.' -->';
					?>

					<h1><?php echo get_the_title($workshopID); ?></h1>

					<?php $event_name = get_post_meta($workshopID, 'event_'.$eventID.'_name', true);
					if($event_name <> ''){ ?>
						<h2><?php echo $event_name; ?></h2>
					<?php } ?>

					<?php if(substr($register_code,0,4) == 'http'){ ?>
						<iframe seamless class="register" src="<?php echo $register_code; ?>"></iframe>
					<?php }else{
						echo $register_code;
					} ?>
				
				</div>
			</div>

<?php get_footer();