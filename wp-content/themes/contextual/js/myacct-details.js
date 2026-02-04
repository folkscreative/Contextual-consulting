/**
 * My Account Details
 */
jQuery(document).ready(function( $ ) {

	// prevent clicking submit straight from a change in email address before it has been checked
	var emailStatus = 'ok';
	var submitStatus = 'ok';
	$(document).on('keyup', '#email', function(){
		emailStatus = 'to check';
	});

	$(document).on('change', '#email', function(){
		$('#submit').removeAttr( 'disabled' );
		$('#email-msg').html('');
		$("#email").removeClass("is-valid is-invalid");
		var email = $('#email').val();
		// var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
		// now allow for apostrophes etc ....
		var mailformat = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
		if(!email.match(mailformat)){
			$('#email-msg').html('You have entered an invalid email address!');
			$("#email").addClass("is-invalid");
			$('#submit').attr('disabled', 'disabled');
		}else{
			if(email != ''){
				checkMyacctEmail();
			}
		}
	});

	function checkMyacctEmail(){
		var email = $('#email').val();
        $.ajax({
            type: "POST",
            url : rpmAjax.ajaxurl,
            dataType : "json",
            timeout : 10000,
            data: { 
            	action : "myacct_email_lookup",
            	email: email
        	},
            cache: false,
            timeout: 10000,
            success: function(response){
            	if(response.status == 'registered' || response.status == 'subscribed'){
            		$('#email-msg').html('That email address is already registered. Please enter a different email address.');
            		$("#email").addClass("is-invalid");
            		$('#submit').attr('disabled', 'disabled');
            	}else if(response.status == 'error'){
            		$('#email-msg').html('Email address invalid or missing. Please try again.');
            		$("#email").addClass("is-invalid");
            		$('#submit').attr('disabled', 'disabled');
            	}else{
					// email not registered or unchanged
            		$("#email").addClass("is-valid");
					if(submitStatus == 'do it'){
						submitDetails();
					}
            	}
            	emailStatus = 'ok';
            },
	        error: function(jqXhr, textStatus, errorMessage){
	        	// try again
        		checkMyacctEmail();
	        }
        });
	}

	// check passwords match and strength
	$( document ).on( 'keyup', '#pswrd1, #pswrd2', function( event ) {
		$('.pswrd-flds').removeClass('is-invalid is-valid');
		$('#pswrd1-msg').removeClass('error');
		var pass1 = $('#pswrd1').val();
		var pass2 = $('#pswrd2').val();
		$('#submit').attr('disabled', 'disabled');
		$('#pswrd1-msg').removeClass('short bad good strong');
		var pwdStrength = wp.passwordStrength.meter(pass1, '', pass2);
		switch ( pwdStrength ) {
			case 2:
				$('#pswrd1-msg').addClass( 'bad error' ).html( pwsL10n.bad );
				$('.pswrd-flds').addClass('is-invalid');
				break;
			case 3:
				$('#pswrd1-msg').addClass( 'good error' ).html( pwsL10n.good );
				$('#pswrd1').addClass('is-valid');
				break;
			case 4:
				$('#pswrd1-msg').addClass( 'strong error' ).html( pwsL10n.strong );
				$('#pswrd1').addClass('is-valid');
				break;
			case 5:
				$('#pswrd1-msg').addClass( 'short error' ).html( pwsL10n.mismatch );
				$('.pswrd-flds').addClass('is-invalid');
				break;
			default:
				$('#pswrd1-msg').addClass( 'short error' ).html( pwsL10n.short );
				$('#pswrd1').addClass('is-invalid');
		}
		if('' == pass2.trim()){
			$('#pswrd2').addClass('is-invalid');
		}
		if ( (3 === pwdStrength || 4 === pwdStrength) && '' !== pass2.trim() ) {
		    $('#submit').removeAttr( 'disabled' );
		    $('#pswrd2').addClass('is-valid');
		}
	});

	// validate and submit
	$('#myacct-details-form').submit(function(e){
		e.preventDefault();
		submitStatus = 'do it';
		if(emailStatus == 'ok'){
			submitDetails();
		}
	});

	function submitDetails(){
		$('#myacct-details-msg').html('<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>');
		// $('#myacct-details-msg').removeClass('error success');
		var errors = false;
		var firstname = $('#firstname').val();
		if(firstname == ''){
			$('#firstname-msg').html('First name cannot be blank');
			$('#firstname').removeClass('is-valid');
			$('#firstname').addClass('is-invalid');
			errors = true;
		}else{
			$('#firstname-msg').html('');
			$('#firstname').removeClass('is-invalid');
			$('#firstname').addClass('is-valid');
		}
		var lastname = $('#lastname').val();
		if(lastname == ''){
			$('#lastname-msg').html('Last name cannot be blank');
			$('#lastname').removeClass('is-valid');
			$('#lastname').addClass('is-invalid');
			errors = true;
		}else{
			$('#lastname-msg').html('');
			$('#lastname').removeClass('is-invalid');
			$('#lastname').addClass('is-valid');
		}
		// we're not revalidating the email fld, just check that any errors are not being bypassed
		var email = $('#email').val();
		if($('#email').hasClass('is-invalid')){
			errors = true;
		}else{
			$('#email').addClass('is-valid')
		}
		// phone is not validated
		var phone = $('#phone').val();
		if(phone != ''){
			$('#phone').addClass('is-valid');
		}
		// job title must be complete if it is there
		var job = '';
		var jobId = '';
		if($('#job').length > 0){
			job = $('#job').val();
			jobId = $('#job_id').val();
			if(job == ''){
				$('#job-msg').html('Job title is required');
				$('#job').removeClass('is-valid');
				$('#job').addClass('is-invalid');
				errors = true;
			}else{
				$('#job-msg').html('');
				$('#job').removeClass('is-invalid');
				$('#job').addClass('is-valid');
			}
		}
		// BACB is not validated
		var bacb_num = $('#bacb_num').val();
		if(bacb_num != ''){
			$('#bacb_num').addClass('is-valid');
		}
		// password is not validated but we do check to make sure that errors are not bypassed
		var pswrd = $('#pswrd1').val();
		if($('#pswrd1-wrap').hasClass('error') || $('#pswrd2-wrap').hasClass('error')){
			errors = true;
		}
		// timezone not validated
		var timezone = $('#timezone').val();
		if(errors){
			$('#myacct-details-msg').html('Please correct the highlighted fields');
			$('#myacct-details-msg').addClass('error');
		}else{
	        $.ajax({
	            type: "POST",
	            url : rpmAjax.ajaxurl,
	            dataType : "json",
	            timeout : 10000,
	            data: { 
	            	action : "myacct_details_update",
	            	firstname: firstname,
	            	lastname: lastname,
	            	email: email,
	            	phone: phone,
	            	job: job,
	            	jobId: jobId,
	            	pswrd: pswrd,
	            	timezone: timezone,
	            	bacb_num: bacb_num
	        	},
	            cache: false,
	            timeout: 25000,
	            success: function(response){
	            	if(response.status == 'ok'){
	            		$('#myacct-details-msg').html(response.msg);
	            		$('#myacct-details-msg').addClass("success");
	            	}else if(response.status == 'error'){
	            		$('#myacct-details-msg').html(response.msg);
	            		$('#myacct-details-msg').addClass("error");
	            	}else{
						// nothing happened
	            	}
	            	submitStatus = 'ok';
	            },
		        error: function(jqXhr, textStatus, errorMessage){
            		$('#myacct-details-msg').html('The system is taking too long. Please try the update again.');
            		$('#myacct-details-msg').addClass("error");
	            	submitStatus = 'ok';
		        }
	        });
		}
	}

	// keep the time up to date 
	function updateTime(timezone = 'Europe/London') {
	    const timeElement = document.getElementById('current-time');
	    if (!timeElement) return;

	    let currentTime = new Date(); // Get current time

	    // Format options including timezone
	    const options = {
	        day: 'numeric',
	        month: 'short',
	        year: 'numeric',
	        hour: '2-digit',
	        minute: '2-digit',
	        second: '2-digit',
	        hour12: false,
	        timeZone: timezone // Specify the timezone here
	    };

	    let formattedTime = new Intl.DateTimeFormat('en-GB', options).format(currentTime);

	    // Add ordinal suffix to the day (1st, 2nd, 3rd, etc.)
	    formattedTime = formattedTime.replace(/\b(\d{1,2})\b/, (match) => {
	        const suffix = ['th', 'st', 'nd', 'rd'][(match % 10 > 3 || [11, 12, 13].includes(match % 100)) ? 0 : match % 10];
	        return match + suffix;
	    });

	    timeElement.innerText = formattedTime;
	}

	// Set an initial timezone (e.g., 'Europe/London')
	var selectedTimezone = 'Europe/London';
    var userTimezone = $('#user-timezone').val();
    if(userTimezone && userTimezone != ''){
    	selectedTimezone = userTimezone;
    }

	// Update time every second
	setInterval(() => updateTime(selectedTimezone), 1000);

	// Initialize time display
	updateTime(selectedTimezone);

	// Function to change timezone dynamically
	$(document).on('change', '#user-timezone', function(){
	    selectedTimezone = $('#user-timezone').val();
	    updateTime(selectedTimezone);
	});



});