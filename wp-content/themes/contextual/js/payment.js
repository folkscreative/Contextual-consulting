/* payment js */
jQuery(document).ready(function($) {
	// set up the form
	var stripe = Stripe($('#stripe-public').val());
	var elements = stripe.elements();
	var style = {
		base: {
			// Add your base input styles here. For example:
			fontSize: "16px",
			color: "#32325d",
		}
	};
	if($('#card-element').length > 0){
		// Create an instance of the card Element.
		var card = elements.create("card", {hidePostalCode: true, style: style});
		// Add an instance of the card Element into the card-element <div>.
		card.mount("#card-element");
		card.addEventListener("change", function(event) {
			if (event.error) {
				$('#reg-pay-msg').removeClass('bg-info bg-success')
					.addClass('bg-danger')
					.html(event.error.message);
				$('#reg-pay-msg-wrap').removeClass('hide');
			} else {
				// $('#reg-pay-msg-wrap').addClass('hide');
			}
		});
	}

    // payment method switch
    $(document).on('click', '.pay-switch', function(){
        if($('#pay-card').is(':checked')){
            $('#reg-inv-dets').slideUp();
            $('#reg-card-dets').slideDown();
            $('.pay-field').prop('required', false).val('');
            $('#reg-pay-dets-form').prop('novalidate', true);
        }else{
            $('#reg-inv-dets').slideDown();
            $('#reg-card-dets').slideUp();
            $('.pay-field').prop('required', true);
            $('#reg-pay-dets-form').prop('novalidate', false);
        }
    });

    // display/hide the training details
    $(document).on('click', '#reg-train-closer', function(){
        if($('#reg-train-panel').hasClass('closed')){
            $('#reg-train-panel').removeClass('closed').addClass('open');
            $('.reg-train-dets').slideDown();
        }else{
            $('#reg-train-panel').removeClass('open').addClass('closed');
            $('.reg-train-dets').slideUp();
        }
    });
    // initially closed, open on larger screens
    var screenWidth = $(window).width();
    if(screenWidth > 768){
        $('#reg-train-closer').trigger('click');
    }

	// intercept the form submission to do some validation and complete the Stripe payment intent
    $(document).on('click', '#reg-card-sub', function(e){
        var form = $(this).closest('form');
        e.preventDefault()
        e.stopPropagation()
		$('.reg-pay-sub-btn').prop('disabled', true);
		$('#reg-pay-msg').removeClass('bg-danger bg-success')
			.addClass('bg-info')
			.html('<i class="fa fa-spinner fa-spin"></i> Processing your payment');
		doPaymentHealthCheck()
			.then((result) => {
				var fullName = $('#full_name').val();
				var email = $('#email').val();
				$('#reg-payment-cancel').addClass('d-none');
				stripe.confirmCardPayment($('#client-secret').val(), {
					'payment_method': {
						'card': card,
						'billing_details': {
							'name': fullName,
							'email': email
						}
					}
				}).then(function(result) {
					if (result.error) {
						$('#pmt-online-fg').addClass('error');
						$('#reg-pay-msg').removeClass('bg-info bg-success')
							.addClass('bg-danger')
							.html(result.error.message);
						$('.reg-pay-sub-btn').prop('disabled', false);
					} else {
						// The payment has been processed!
						card.clear();
						if (result.paymentIntent.status === 'succeeded') {
							$('#reg-pay-msg').html('<i class="fa fa-spinner fa-spin"></i> Recording your payment');
							recordThePayment(result.paymentIntent.id);
						}
					}
				});
			});
	});

	// registration by invoice
	$(document).on('click', '#reg-inv-sub', function(e){
        var form = $(this).closest('form');
        e.preventDefault()
        e.stopPropagation()
		$('.reg-pay-sub-btn').prop('disabled', true);
        form.addClass('was-validated');
        if (form[0].checkValidity()) {
        	// https://www.taniarascia.com/how-to-promisify-an-ajax-call/
        	doPaymentHealthCheck()
        		.then((result) => {
        			// health check ok
					$('#reg-payment-cancel').addClass('d-none');
					$('#reg-pay-msg').removeClass('bg-danger bg-success')
						.addClass('bg-info')
						.html('<i class="fa fa-spinner fa-spin"></i> Recording your registration');
					recordThePayment(0);
        		});
        }else{
			$('.reg-pay-sub-btn').prop('disabled', false);
        }
	});

	var attempt = 1;
	function recordThePayment(PIID){
		var form = $('#reg-pay-dets-form');
		var extraVariable = { PIID: PIID };
		var serializedData = form.serialize();
		var dataToSend = serializedData + '&' + $.param(extraVariable);
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: dataToSend,
            cache: false,
            success: function(response){
            	if(response.status == 'ok'){
            		$('#reg-pay-msg').removeClass('bg-info bg-danger').addClass('bg-success').html(response.msg);
            		$('.reg-final-wrap').slideDown();
            		$('.reg-pay-sub-btn').addClass('disabled');
            	}else{
            		$('#reg-pay-msg').removeClass('bg-info bg-success').addClass('bg-danger').html(response.msg);
            	}
            	if(response.conversion){
	            	recordConversion(response.conversion);
	            }
            	wmsDeleteCookie('ccregistration');
	        	if(response.redirect_url){
	        		setTimeout(function(){
	        			window.location.href = response.redirect_url;
	        		}, 3000);
	        	}
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#reg-pay-msg').removeClass('bg-info bg-success').addClass('bg-danger').html('<i class="fa fa-spinner fa-spin"></i> This is taking a long time. This might be caused by a poor or slow connection. We\'re still trying. Please don\'t close this window.');
                attempt ++;
                if(attempt < 4){
                	recordThePayment(PIID);
                } else {
	                $('#reg-pay-msg').removeClass('bg-info bg-success').addClass('bg-danger').html('<i class="fa fa-exclamation-triangle" aria-hidden="true" style="color:#ffff00;"></i> The Internet connection has been lost. Please check with your bank to see if your payment has gone through before re-trying. Please contact us on <a href="mailto:admin@contextualconsulting.co.uk">admin@contextualconsulting.co.uk</a> and ask us to check if your training request has been registered.');
	                $('.reg-pay-sub-btn').prop('disabled', false);
                }
            }
        });
	}

	// are we ok to take the payment
	function doPaymentHealthCheck(){
		// https://www.taniarascia.com/how-to-promisify-an-ajax-call/
		return new Promise((resolve, reject) => {
			var values = $('#reg-pay-dets-form').serializeArray();
			// Find and replace `action`
			for (index = 0; index < values.length; ++index) {
			    if (values[index].name == "action") {
			        values[index].value = 'payment_health_check';
			        break;
			    }
			}
			var data = $.param(values);
	        $.ajax({
	            url : ccAjax.ajaxurl,
	            type: "POST",
	            dataType: "json",
	            timeout : 10000,
	            data: data,
	            cache: false,
	            success: function(response){
	            	if(response.status == 'error'){
		                $('#reg-pay-msg').removeClass('bg-info bg-success')
		                	.addClass('bg-danger')
		                	.html('<i class="fa-solid fa-triangle-exclamation"></i> '+response.msg);
		                reject(false);
	            	}else{
	            		resolve(true);
	            	}
	            },
	            error: function(jqXhr, textStatus, errorMessage){
	                $('#reg-pay-msg').removeClass('bg-info bg-success').addClass('bg-danger').html('<i class="fa-solid fa-triangle-exclamation"></i> The registration process is taking too long. This could be due to a poor Internet connection. You have not been registered for this training and no payment has been taken. Please try again later.');
	                reject(errorMessage);
	            }
	        });
	    });
	}

});

// go back to the payment details page
function backToPmtDets(){
    jQuery('#reg-pay-dets-form')
        .attr('action', '/pmt-dets')
        .trigger('submit');
}

