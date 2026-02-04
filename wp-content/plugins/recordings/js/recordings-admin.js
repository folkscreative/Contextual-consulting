/**
 * JS for the recordings admin page
 */

jQuery(document).ready(function( $ ) {

	var recid = $('#recording-expiry-update').data('recid');

	// start the recording expiry batch job
	$(document).on('click', '#recording-expiry-update', function(){
		if(!$(this).hasClass('disabled')){
			var dateformat = /^(0?[1-9]|[12][0-9]|3[01])[\/](0?[1-9]|1[012])[\/]\d{4}$/; // must be dd/mm/yyyy
			var errors = false;
			var startDate = $('#rec-exp-upd-startdate').val();
			if(startDate != ''){
				if(!startDate.match(dateformat)){
					alert('Access date range start date is invalid. Please correct and try again');
					errors = true;
				}
			}
			var endDate = $('#rec-exp-upd-enddate').val();
			if(endDate != ''){
				if(!endDate.match(dateformat)){
					alert('Access date range end date is invalid. Please correct and try again');
					errors = true;
				}
			}
			var expiryDate = $('#rec-exp-upd-expirydate').val();
			if(expiryDate != ''){
				if(!expiryDate.match(dateformat)){
					alert('Expiry date is invalid. Please correct and try again');
					errors = true;
				}
			}
			if(!errors){
				var confMsg = 'Set default expiry to be ';
				if(expiryDate == ''){
					confMsg += $('#recording-expiry-update').data('confirm');
				}else{
					confMsg += expiryDate;
				}
				confMsg += ' for all viewers who were given access ';
				if(startDate == '' && endDate == ''){
					confMsg += 'anytime ';
				}else{
					if(startDate != ''){
						confMsg += 'on or after '+startDate+' ';
						if(endDate != ''){
							confMsg += 'and ';
						}
					}
					if(endDate != ''){
						confMsg += 'on or before '+endDate+' ';
					}
				}
				var emails = 'no';
				if($('#rec-exp-upd-email').prop("checked") == true){
					confMsg += 'and send an updated expiry email to all viewers?';
					emails = 'yes';
				}else{
					confMsg += 'and do not send an updated expiry email?';
				}
				if(confirm(confMsg)){
					$(this).html('<i class="fa fa-spinner fa-spin"></i>');
					$(this).addClass('disabled');
					$(this).attr('disabled', true);
			        $.ajax({
			            type: "POST",
			            url : rpmAjax.ajaxurl,
			            dataType : "json",
			            timeout : 30000,
			            data: { 
			            	action : "rpm_cc_recordings_expiry_reset_start",
			            	recording: recid,
			            	startdate: startDate,
			            	enddate: endDate,
			            	expirydate: expiryDate,
			            	emails: emails
		            	},
			            cache: false,
			            success: function(response){
			            	$('#recording-expiry-update-msg').html(response.msg);
			            	if(response.status == 'ok'){
			            		setTimeout(resetProgressUpdate, 5000);
			            	}
			            },
				        error: function(jqXhr, textStatus, errorMessage){
				        	$('#recording-expiry-update-msg').html('Failed to start the update ... please try again');
							$('#recording-expiry-update').html('Start update');
							$('#recording-expiry-update').removeClass('disabled');
							$('#recording-expiry-update').attr('disabled', false);
				        }
			        });
			    }
			}
		}
	})

	var progress = 0;
	var width = 0;

	// update the reset progress bar
	function resetProgressUpdate(){
        $.ajax({
            type: "POST",
            url : rpmAjax.ajaxurl,
            dataType : "json",
            timeout : 10000,
            data: { 
            	action : "rpm_cc_recordings_expiry_progress",
            	recording: recid
        	},
            cache: false,
            success: function(response){
            	$('#recording-expiry-update-msg').html(response.msg);
            	if(response.status == 'ok'){
            		progress = response.percent;
            		slideProgressBar();
            		if(response.percent < 100){
            			setTimeout(resetProgressUpdate, 5000);
            		}else{
						$('#recording-expiry-update').html('Start update');
						$('#recording-expiry-update').removeClass('disabled');
						$('#recording-expiry-update').attr('disabled', false);
            		}
	           	}
            },
	        error: function(jqXhr, textStatus, errorMessage){
	        	setTimeout(resetProgressUpdate, 10000);
	        }
        });
	}

	// smooth movement of progress bar
	function slideProgressBar(){
		$('#recording-expiry-update-msg').html(progress + "%");
		var elem = document.getElementById("recording-expiry-progress-bar");
		var id = setInterval(frame, 10);
		function frame() {
			if (width >= progress) {
				clearInterval(id);
			} else {
				width++;
				elem.style.width = width + "%";
			}
		}
	}

	var resetStatus = $('#recording-expiry-update').data('status');
	if(resetStatus == 'running'){
		resetProgressUpdate();
	}

	$(document).on('change', '.recording-expiry-settings', function(){
		$('#recording-expiry-update').html('Update recording first');
		$('#recording-expiry-update').addClass('disabled');
		$('#recording-expiry-update').attr('disabled', true);
	});

	/* moved to admin.js
    // Zoom chat file upload
    $(document).on('click', '.zoom-chat-upload', function(e){
        e.preventDefault();
        var btn = $(this);
        var recid = btn.data('recid');
        var recmod = btn.data('module');
        var gaps = $('#zoom_gaps_'+recmod).val();
        var chat = $('#zoom_chat_'+recmod)[0];
        var chat2 = $('#zoom_chat2_'+recmod)[0];
        var uncutV1 = $('#uncut_vid1_'+recmod).val();
        
        if(chat.files.length === 0){
            $('#zcu_msg_'+recmod).html('Select a file to upload first');
        }else{
            btn.prop('disabled', true);
            $('#zcu_msg_'+recmod).html('<i class="fa fa-spinner fa-spin"></i> Uploading ...');

            var formData = new FormData();
            formData.append("action", "zoom_chat_upload");
            formData.append("recording", recid);
            formData.append("module", recmod);
            formData.append("gaps", gaps);
            var file = chat.files[0];
            formData.append("chat", file);
            if(chat2.files.length > 0){
                var file2 = chat2.files[0];
                formData.append("chat2", file2);
            }
            formData.append("uncutV1", uncutV1);

            $.ajax({
                type: "POST",
                url : rpmAjax.ajaxurl,
                dataType : "json",
                contentType: false,
                processData: false,
                timeout : 10000,
                data: formData,
                cache: false,
                crossDomain: true,
                success: function(response){
                    $('#zcu_msg_'+recmod).html(response.msg);
                    btn.prop('disabled', false);
                },
                error: function(jqXhr, textStatus, errorMessage){
                    $('#zcu_msg_'+recmod).html('Error connecting to server, please try again');
                }
            });

        }
    });
    */

});