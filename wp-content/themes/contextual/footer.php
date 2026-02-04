<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Contextual
 */

global $rpm_theme_options;
?>

	</div><!-- #content -->

	<?php
	// opt-in on non-subs pages
    if( ! is_page( ['memberships', 'subscribe'] ) && ! is_singular( 'subscription' ) ) { ?>
		<div class="wms-section wms-section-std wms-section-optin text-center sml-padd-top sml-padd-bot">
			<div class="wms-sect-bg">
				<div class="container">
					<div class="row">
						<div class="col-12">
							<h4 class="section-title">Join our newsletter to be the first to receive updates on our upcoming events, exclusive free resources and other valuable goodies. Sign up now and embark on your ACT journey with us!</h4>
							<?php
							echo wms_newsletter_form( array( 'btn_class' => 'primary' ) );
							?>
							<p>You can unsubscribe at anytime. Read our full privacy policy here: <a href="/privacy-policy" target="_blank" rel="noopener">Privacy policy</a></p>
						</div><!-- .col outer -->
					</div><!-- .row outer -->
				</div><!-- .container -->
			</div><!-- .wms-sect-bg -->
		</div>
	<?php } ?>

	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="container">
	        <div class="row">
	        	<?php if(is_active_sidebar('footer-4')){
	        		$cols = "col-sm-6 col-lg-3";
	        	}else{
	        		$cols = "col-sm-6 col-lg-4";
	        	} ?>
	            <div class="<?php echo $cols; ?>"> 	
	                <?php if(is_active_sidebar('footer-1')){ dynamic_sidebar('footer-1'); } ?>
	            </div>
	            <div class="<?php echo $cols; ?>">
	                <?php if(is_active_sidebar('footer-2')){ dynamic_sidebar('footer-2'); } ?>
	            </div>
	            <div class="<?php echo $cols; ?>">
	                <?php if(is_active_sidebar('footer-3')){ dynamic_sidebar('footer-3'); } ?>
	            </div>
	            <?php if(is_active_sidebar('footer-4')){ ?>
		            <div class="<?php echo $cols; ?>">
		                <?php dynamic_sidebar('footer-4'); ?>
		            </div>
	            <?php } ?>
	        </div><!-- .row -->
	    </div>
    </footer><!-- #colophon -->

    <?php if(is_active_sidebar('sub-footer-1')){ ?>
        <div class="sub-footer">
        	<div class="container">
        		<div class="row">
	        		<div class="col-12">
	        			<?php dynamic_sidebar('sub-footer-1'); ?>
	        		</div>
        		</div>
        	</div>
        </div><!-- .sub-footer -->
    <?php } ?>

	<div class="seo-footer-wrapper">
		<div class="container">
			<div class="row seo-footers">
	            <div class="col-sm-6 col-lg-3"> 	
	                <?php if(is_active_sidebar('seofooter-1')){ dynamic_sidebar('seofooter-1'); } ?>
	            </div>
	            <div class="col-sm-6 col-lg-3">
	                <?php if(is_active_sidebar('seofooter-2')){ dynamic_sidebar('seofooter-2'); } ?>
	            </div>
	            <div class="col-sm-6 col-lg-3">
	                <?php if(is_active_sidebar('seofooter-3')){ dynamic_sidebar('seofooter-3'); } ?>
	            </div>
	            <div class="col-sm-6 col-lg-3">
	                <?php if(is_active_sidebar('seofooter-4')){ dynamic_sidebar('seofooter-4'); } ?>
	            </div>
			</div>
			<div class="row footer-keywords">
				<div class="col-12">
					<?php if(is_active_sidebar('footerkeywords-1')){ dynamic_sidebar('footerkeywords-1'); } ?>
				</div>
			</div>
			<div class="row copy-info">
	            <div class="col-sm-6 text-center text-sm-start copyright">
	                &copy; Copyright <?php echo date('Y'); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" title="<?php bloginfo( 'description' ); ?>"><?php bloginfo( 'name' ); ?></a>
	            </div>
	            <div class="col-sm-6 text-center text-sm-end powered">
	                Powered by
					<?php if(is_front_page()){ ?>
		                <a href="https://saasora.com" rel="designer" title="Digital Marketing Consultancy" target="_blank">SaaSora</a>
		            <?php }else{ ?>
		                SaaSora
		            <?php } ?>
	            </div>
			</div>
		</div>
	</div>

	<!-- search modal -->
	<div id="sf-modal" class="modal fade sf-modal" tabindex="-1">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header" data-bs-theme="dark">
					<h6 class="modal-title">Search</h6>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form role="search" method="get" class="search-form row g-3 d-flex" id="search-form" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<div class="input-group mb-3">
							<label for="search-field" class="visually-hidden">Search</label>
							<input type="search" id="search-input" class="search-field form-control form-control-lg" placeholder="What are you looking for?" value="" name="s" autocomplete="off">
							<button type="submit" class="btn btn-search search-submit"><i class="fa-solid fa-magnifying-glass"></i></button>
						</div>
					</form>
					<div id="search-suggestions" class="search-suggestions"></div> <!-- Suggestions will be displayed here -->
				</div>
			</div>
		</div>
	</div>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
