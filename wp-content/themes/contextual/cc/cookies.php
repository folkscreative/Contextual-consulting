<?php
/**
 * Cookies and consent
 * also incoroprates google tags
 */

// This is the Global Site Tag (gtag.js) tracking code for this property.
add_action( 'wp_head', 'cc_cookies_google_tags' );
function cc_cookies_google_tags(){
	global $rpm_theme_options;
	if( $rpm_theme_options['google-analytics'] <> '' || $rpm_theme_options['google-ads'] <> '' ){
		?>
		<!-- CC Global site tag (gtag.js) - Google Analytics & Ads -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $rpm_theme_options['google-analytics']; ?>"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
		<?php
		// we need to set the default state for this browser
		?>
			gtag('consent', 'default', {
				'ad_storage': 'denied',
				'ad_user_data': 'denied',
				'ad_personalization': 'denied',
				'analytics_storage': 'denied'
			});
			gtag('js', new Date());
		<?php if($rpm_theme_options['google-analytics'] <> ''){ ?>
			gtag('config', '<?php echo $rpm_theme_options['google-analytics']; ?>', {cookie_flags: 'SameSite=None;Secure'});
			setTimeout("gtag('event', '30_seconds', {'event_category': 'reading', 'event_label': 'read for 30 secs' });", 30000);
		<?php }
		if($rpm_theme_options['google-ads'] <> ''){ ?>
			gtag('config', '<?php echo $rpm_theme_options['google-ads']; ?>', {'allow_enhanced_conversions':true});
		<?php } ?>
		jQuery(document).ready(function($){
			// show the cookie modal if appropriate
			var consented = wmsGetCookie('cc_consent');
			if( consented == '' ){
				// $('.cc-cookie-banner').show();
				var cookieModal = new bootstrap.Modal('#cc-ckie-modal', {
					keyboard: false,
					backdrop: 'static'
				});
				cookieModal.show();
			}
			if( consented == 'yes' ){
				gtag('consent', 'update', {
					'ad_storage': 'granted',
					'ad_user_data': 'granted',
					'ad_personalization': 'granted',
					'analytics_storage': 'granted'
				});
			}
			$(document).on('click', '#ckie-reject', function(){
				wmsSetCookie( 'cc_consent', 'no', ''); // session cookie
				$('.cc-ckie-banner').hide('500');
			});
			$(document).on('click', '#ckie-accept', function(){
				wmsSetCookie( 'cc_consent', 'yes', '365'); // 365 days
				gtag('consent', 'update', {
					'ad_storage': 'granted',
					'ad_user_data': 'granted',
					'ad_personalization': 'granted',
					'analytics_storage': 'granted'
				});
				cookieModal.hide();
			});
			$(document).on('click', '#ckie-manage', function(){
				$('#cc-ckie-modal .modal-body').slideUp();
				$('#cc-ckie-modal .modal-body').html('<h5 class="ckie-modal-heading mb-1">Your consent?</h5><div class="ckie-modal-text mb-3"><p>Manage your cookie preferences here. Functional cookies are required and cannot be disabled.</p><div class="row"><div class="col-10"><h6>Functional cookies</h6><p>Functional cookies are necessary to ensure this website works as expected. We don\'t use these to collect data about you.</p></div><div class="col-2"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="ckie-sw-func" checked disabled></div></div><div class="col-10"><h6>Analytics cookies</h6><p>Analytics cookies allow us to collect anonymous data about the performance of our content.</p></div><div class="col-2"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="ckie-sw-anal" checked></div></div><div class="col-10"><h6>Targeting cookies</h6><p>Targeting cookies are used for advertising purposes. They help improve the relevance of the ads you see.</p></div><div class="col-2"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="ckie-sw-ads" checked></div></div></div></div><p class="text-end"><a id="ckie-save" class="btn btn-sm btn-link me-3" href="javascript:void(0);">Save</a><a id="ckie-accept" class="btn btn-sm btn-default" href="javascript:void(0);">Accept all</a></p>');
				$('#cc-ckie-modal .modal-body').slideDown();
			});
			$(document).on('click', '#ckie-save', function(){
				var analytics = false;
				if($('#ckie-sw-anal').prop('checked')){
					analytics = true;
				}
				var ads = false;
				if($('#ckie-sw-ads').prop('checked')){
					ads = true;
				}
				if(analytics && ads){
					wmsSetCookie( 'cc_consent', 'yes', '365'); // 365 days
					gtag('consent', 'update', {
						'ad_storage': 'granted',
						'ad_user_data': 'granted',
						'ad_personalization': 'granted',
						'analytics_storage': 'granted'
					});
					cookieModal.hide();
				}else if(analytics){
					wmsSetCookie( 'cc_consent', 'no', ''); // session cookie
					gtag('consent', 'update', {
						'analytics_storage': 'granted'
					});
				}else if(ads){
					wmsSetCookie( 'cc_consent', 'no', ''); // session cookie
					gtag('consent', 'update', {
						'ad_storage': 'granted',
						'ad_user_data': 'granted',
						'ad_personalization': 'granted'
					});
				}else{
					wmsSetCookie( 'cc_consent', 'no', ''); // session cookie
				}
				cookieModal.hide();
			});
		});
		</script>
		<?php
	}
}

// let's add a banner to the footer
// changed to be a modal instead!
add_action( 'wp_footer', 'cc_cookie_banner' );
function cc_cookie_banner(){
	global $rpm_theme_options;
	if( $rpm_theme_options['google-analytics'] <> '' || $rpm_theme_options['google-ads'] <> '' ){
		?>
		<div id="cc-ckie-modal" class="modal fade ckie-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-body">
						<h5 class="ckie-modal-heading mb-3">Your consent?</h5>
						<div class="ckie-modal-text mb-3">
							<?php echo $rpm_theme_options['cookie-banner-text']; ?>
						</div>
						<p class="text-end">
							<a id="ckie-manage" class="btn btn-sm btn-link me-3 btn-ckie-manage" href="javascript:void(0);">Manage options</a>
							<a id="ckie-accept" class="btn btn-sm btn-default" href="javascript:void(0);">Accept</a>						
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
		/*
		<div class="cc-cookie-banner">
			<div class="inner">
				<div class="container">
					<div class="row align-items-center">
						<div class="col-md-8 py-3">
							<h5 class="mb-0">Your consent?</h5>
							<p class="cookie-banner-text m-0">
								<?php echo $rpm_theme_options['cookie-banner-text']; ?>
							</p>
						</div>
						<div class="col-6 col-md-2 pb-3 text-center">
							<a id="cookie-reject" class="btn btn-sm btn-link" href="javascript:void(0);">Reject</a>
						</div>
						<div class="col-6 col-md-2 pb-3 text-center">
							<a id="cookie-accept" class="btn btn-sm btn-default" href="javascript:void(0);">Accept</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		*/
	}
}

