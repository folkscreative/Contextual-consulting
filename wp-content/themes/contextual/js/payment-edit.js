/**
 * Payment Edit Scripts
 */

jQuery(document).ready(function($) {
	$(document).on('change', '.ccpa-edit-fld', function(){
		var paymentID = $('#payment_id').val();
		var field = $(this).data('field');
		var group = $(this).data('group');
		var value = $(this).val();
		$('#'+group+'-wait').show();
		$.ajax({
            url : ccAjax.ajaxurl,
			type: "POST",
			dataType: "json",
			data: {
				action: "payment_edit_field_update",
				paymentID: paymentID,
				field: field,
				value: value
			},
			timeout: 10000,
			cache: false,
			success: function(response){
				if(response.status == 'ok'){
					if(response.update != ''){
						$('#'+response.update).html(response.html);
					}
					$('#'+group+'-wait').hide();
					$('#'+group+'-upd').show().delay(5000).fadeOut();
				}else{
					alert(response.msg);
					$('#'+group+'-wait').hide();
				}
			},
			error: function(){
				alert('Timeout when attempting to update '+field+'. Please try again');
			}
		});
	});

	$(document).on('click', '#events-update', function(){
		$('#events-update').html('<i class="fas fa-spinner fa-spin"></i>');
		var eventIDs = '';
		$('.event-chkbx').each(function(){
			if($(this).prop("checked") == true){
				eventIDs = eventIDs + $(this).val() + ',';
			}
		});
		var paymentID = $('#payment_id').val();
		$.ajax({
            url : ccAjax.ajaxurl,
			type: "POST",
			dataType: "json",
			data: {
				action: "payment_edit_field_update",
				paymentID: paymentID,
				field: 'event_ids',
				value: eventIDs
			},
			timeout: 10000,
			cache: false,
			success: function(response){
				if(response.status == 'ok'){
					$('#events-update').html('<i class="far fa-check-circle"></i>');
					// cannout use delay as that only works on queued items such as animations. So, using ...
					setTimeout(function(){
						$('#events-update').html('Update events');
					}, 5000);
				}else{
					alert(response.msg);
					$('#events-update').html('Update events');
				}
			},
			error: function(){
				alert('Timeout when attempting to update events. Please try again');
			}
		});
	});

	$(document).on('click', '#mail-list-update', function(){
		$('#mail-list-update').html('<i class="fas fa-spinner fa-spin"></i>');
		var mailListIDs = '';
		$('#mail-lists .mail-list-chkbx').each(function(){
			if($(this).prop("checked") == true){
				mailListIDs = mailListIDs + $(this).val() + ',';
			}
		});
		var paymentID = $('#payment_id').val();
		$.ajax({
            url : ccAjax.ajaxurl,
			type: "POST",
			dataType: "json",
			data: {
				action: "payment_edit_field_update",
				paymentID: paymentID,
				field: 'mail_list_ids',
				value: mailListIDs
			},
			timeout: 10000,
			cache: false,
			success: function(response){
				if(response.status == 'ok'){
					$('#mail-list-update').html('<i class="far fa-check-circle"></i>');
					// cannout use delay as that only works on queued items such as animations. So, using ...
					setTimeout(function(){
						$('#mail-list-update').html('Update mailing lists');
					}, 5000);
				}else{
					alert(response.msg);
					$('#mail-list-update').html('Update mailing lists');
				}
			},
			error: function(){
				alert('Timeout when attempting to update mailing lists. Please try again');
				$('#mail-list-update').html('Update mailing lists');
			}
		});
	});

	$(document).on('click', '#attendee-mail-list-update', function(){
		var attendee = $('#attendee_email').val();
		if(attendee == ''){
			alert('Please add the attendee email first');
		}else{
			$('#attendee-mail-list-update').html('<i class="fas fa-spinner fa-spin"></i>');
			var mailListIDs = '';
			$('#attendee-mail-lists .mail-list-chkbx').each(function(){
				if($(this).prop("checked") == true){
					mailListIDs = mailListIDs + $(this).val() + ',';
				}
			});
			var paymentID = $('#payment_id').val();
			$.ajax({
	            url : ccAjax.ajaxurl,
				type: "POST",
				dataType: "json",
				data: {
					action: "payment_edit_field_update",
					paymentID: paymentID,
					field: 'attendee_mail_list_ids',
					value: mailListIDs
				},
				timeout: 10000,
				cache: false,
				success: function(response){
					if(response.status == 'ok'){
						$('#attendee-mail-list-update').html('<i class="far fa-check-circle"></i>');
						// cannout use delay as that only works on queued items such as animations. So, using ...
						setTimeout(function(){
							$('#attendee-mail-list-update').html('Update mailing lists');
						}, 5000);
					}else{
						alert(response.msg);
						$('#attendee-mail-list-update').html('Update mailing lists');
					}
				},
				error: function(){
					alert('Timeout when attempting to update attendee mailing lists. Please try again');
					$('#attendee-mail-list-update').html('Update mailing lists');
				}
			});
		}
	});

	$(document).on('click', '#resend-reg-email', function(){
		var r = confirm('Please comfirm that you want to resend the registration email');
		if(r == true){
			$('#resend-reg-email').html('<i class="fas fa-spinner fa-spin"></i>');
			var paymentID = $('#payment_id').val();
			$.ajax({
	            url : ccAjax.ajaxurl,
				type: "POST",
				dataType: "json",
				data: {
					action: "payment_edit_resend_reg",
					paymentID: paymentID
				},
				timeout: 10000,
				cache: false,
				success: function(response){
					$('#action-msg').removeClass('error', 'success');
					$('#action-msg').addClass(response.class);
					$('#action-msg').html(response.msg);
					$('#resend-reg-email').html('Resend registration Email');
				},
				error: function(){
					alert('Timeout when attempting to resend registration email. Please try again');
					$('#resend-reg-email').html('Resend registration Email');
				}
			});
		}
	});

	$(document).on('click', '#send-invoice-pmt-conf', function(){
		var r = confirm('Please comfirm that you want to send the invoice payment confirmation email');
		if(r == true){
			$('#send-invoice-pmt-conf').html('<i class="fas fa-spinner fa-spin"></i>');
			var paymentID = $('#payment_id').val();
			$.ajax({
	            url : ccAjax.ajaxurl,
				type: "POST",
				dataType: "json",
				data: {
					action: "payment_edit_send_inv_conf",
					paymentID: paymentID
				},
				timeout: 10000,
				cache: false,
				success: function(response){
					$('#action-msg').removeClass('error', 'success');
					$('#action-msg').addClass(response.class);
					$('#action-msg').html(response.msg);
					$('#send-invoice-pmt-conf').html('Send invoice payment conf.');
				},
				error: function(){
					alert('Timeout when attempting to send invoice payment conf. Please try again');
					$('#send-invoice-pmt-conf').html('Send invoice payment conf.');
				}
			});
		}
	});

	$(document).on('click', '#refund-now', function(){
		var refund = $('#refund-request').val();
		var currency = $('#refund-request').data('currency')
		var r = confirm('Please comfirm that you want to refund '+currency+' '+refund);
		if(r == true){
			$('#refund-now').html('<i class="fas fa-spinner fa-spin"></i>');
			var paymentID = $('#payment_id').val();
			$.ajax({
	            url : ccAjax.ajaxurl,
				type: "POST",
				dataType: "json",
				data: {
					action: "payment_edit_refund_request",
					paymentID: paymentID,
					refund: refund
				},
				timeout: 10000,
				cache: false,
				success: function(response){
					$('#action-msg').removeClass('error', 'success');
					$('#action-msg').addClass(response.class);
					$('#action-msg').html(response.msg);
					$('#refund-now').html('Refund now');
				},
				error: function(){
					alert('Timeout when attempting to refund - please CHECK STRIPE before trying again');
					$('#refund-now').html('Refund now');
				}
			});
		}
	});

	$(document).on('change', '#email, #attendee_email', function(){
		var paymentID = $('#payment_id').val();
		var field = $(this).data('field');
		var group = $(this).data('group');
		var value = $(this).val();
		$('#'+group+'-wait').show();
		$.ajax({
            url : ccAjax.ajaxurl,
			type: "POST",
			dataType: "json",
			data: {
				action: "payment_edit_email_update",
				paymentID: paymentID,
				field: field,
				value: value
			},
			timeout: 10000,
			cache: false,
			success: function(response){
				if(response.status == 'ok'){
					if(response.update != ''){
						$('#'+response.update).html(response.html);
					}
					$('#'+group+'-wait').hide();
					$('#'+group+'-upd').show().delay(5000).fadeOut();
				}else if(response.status == 'popup'){
					$('#email-update-popup-wrap').html(response.html).show();
					$('#'+group+'-wait').hide();
				}else{
					alert(response.msg);
					$('#'+group+'-wait').hide();
				}
			},
			error: function(){
				alert('Timeout when attempting to update '+field+'. Please try again');
			}
		});
	});

	$(document).on('click', '#email-cancel-popup', function(){
		var oldEmail = $(this).data('old-email');
		var field = $(this).data('field');
		$('#email-update-popup-wrap').hide();
		$('#'+field).val(oldEmail);
	});

	$(document).on('click', '.email-option', function(){
		var thisOption = $(this).data('option');
		var parent = $(this).closest('.ccpa-row');
		parent.find('.email-option').removeClass('chosen');
		parent.attr('data-chosen', thisOption);
		$(this).addClass('chosen').blur();
		var enable = true;
		$('.data-row').each(function(){
			// .data does not get the latest data
			if($(this).attr('data-chosen') == ''){
				enable = false;
			}
		});
		if(enable){
			$('#email-submit-popup').removeClass('disabled');
		}else{
			$('#email-submit-popup').addClass('disabled');
		}
	});

	$(document).on('click', '#email-submit-popup', function(){
		$(this).addClass('disabled').html('<i class="fas fa-spinner fa-spin"></i>');
		var chosen = {
			mlist: '',
			exsub: '',
			user: ''
		};
		var thisrow = '';
		$('.data-row').each(function(){
			thisrow = $(this).data('choices');
			chosen[thisrow] = $(this).attr('data-chosen');
		});
		var paymentID = $('#payment_id').val();
		var field = $(this).data('field');
		var sub1 = $(this).data('sub1');
		var sub2 = $(this).data('sub2');
		var newEmail = $(this).data('new-email');
		$.ajax({
            url : ccAjax.ajaxurl,
			type: "POST",
			dataType: "json",
			data: {
				action: "payment_edit_popup_submit",
				paymentID: paymentID,
				field: field,
				sub1: sub1,
				sub2: sub2,
				mlist: chosen['mlist'],
				exsub: chosen['exsub'],
				user: chosen['user'],
				newEmail: newEmail
			},
			timeout: 10000,
			cache: false,
			success: function(response){
				if(response.status == 'ok'){
					if(response.update != ''){
						$('#'+response.update).html(response.html);
					}
					$('#email-update-popup').html(response.msg);
					setTimeout(function(){
						$('#email-update-popup-wrap').hide();
					}, 10000);
				}else{
					alert(response.msg);
				}
			},
			error: function(){
				alert('Timeout when attempting to update '+field+'. Please try again');
			}
		});

	});

});