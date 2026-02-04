jQuery(document).ready(function($) {

    // who submit
    $(document).on('click', '#reg-who-submit', function(e){
        e.preventDefault()
        e.stopPropagation()
        var form = $(this).closest('form');
        // form is now a jQuery object array. To access actual DOM properties you must select the first item in the array, which is the raw DOM element ...
        form.addClass('was-validated');
        if (form[0].checkValidity()) {
            var state = $('#reg-who-state').val();
            var msg = 'Checking that for you';
            switch (state){
                case '1':
                    msg = 'Looking up your email address';
                    break;
                case '2':
                    msg = 'Logging you in';
                    break;
                case '3':
                case '5':
                    msg = 'Saving your details';
                    break;
                case '4':
                    msg = 'Retrieving your details';
                    break;
            }
            $('.reg-who-msg').addClass('info').html('<i class="fa-solid fa-spinner fa-spin-pulse"></i> '+msg);
            $('#reg-who-submit').addClass('disabled');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                timeout : 10000,
                data: form.serialize(),
                cache: false,
                success: function(response){
                	if(response.status == 'ok'){
                		$('#reg-who-panel').html(response.html);
                        ccScrollTo('reg-who-panel');
                        if(response.append != '' && $('#reg-next-submit').length == 0){
                            // $('#reg-next-form').append(response.append);
                            $('#reg-extra-panels').append(response.append);
                        }
                        if(response.actbtn != ''){
                            $('#menubar-action-col').html(response.actbtn);
                        }
                        // apply autocomplete to the job field
                        applyAutocomplete();
                        // if availabl, update the ccAjax object with new user data
                        if (response.user_data) {
                            ccAjax.current_user_id = response.user_data.current_user_id;
                            ccAjax.current_user_firstname = response.user_data.current_user_firstname;
                            ccAjax.current_user_lastname = response.user_data.current_user_lastname;
                            ccAjax.current_user_email = response.user_data.current_user_email;
                        }

                	}else{
                		$('#reg-who-msg').addClass('error').html(response.msg);
                	}
                },
                error: function(jqXhr, textStatus, errorMessage){
                    $('.reg-who-msg').addClass('error').html('Website taking too long to respond. Please try again.');
                    $('#reg-who-submit').removeClass('disabled');
                }
            });
        }
    });

    // edit cancel
    $(document).on('click', '#reg-who-edit-cancel', function(){
        $('.reg-who-msg').addClass('info').html('<i class="fa-solid fa-spinner fa-spin-pulse"></i> Cancelling ...');
        var state = $('#reg-who-state').val();
        var email = $('#email').val();
        var token = $(this).data('token');
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: {
                action: "reg_who_submit",
                state: '7',
                email: email,
                reg_token: token
            },
            cache: false,
            success: function(response){
                if(response.status == 'ok'){
                    $('#reg-who-panel').html(response.html);
                    ccScrollTo('reg-who-panel');
                }else{
                    $('#reg-who-msg').addClass('error').html(response.msg);
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('.reg-who-msg').addClass('error').html('Website taking too long to respond. Please try again.');
                $('#reg-who-submit').removeClass('disabled');
            }
        });
    });

    // logout
    $(document).on('click', '#reg-logout', function(){
        var token = $(this).data('token');
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: {
                action: "reg_logout",
                token: token
            },
            cache: false,
            success: function(response){
                if(response.status == 'ok'){
                    $('#reg-who-panel').html(response.html);
                    $('.reg-attend-panel').remove();
                    $('.reg-next-btn-wrap').remove();
                    ccScrollTo('reg-who-panel');
                    if(response.actbtn != ''){
                        $('#menubar-action-col').html(response.actbtn);
                    }
                }else{
                    $('#reg-who-msg').addClass('error').html(response.msg);
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('.reg-who-msg').addClass('error').html('Website taking too long to respond. Please try again.');
                $('#reg-who-submit').removeClass('disabled');
            }
        });
    });

    // CNWL job requirements
    // job
    $(document).on('change', '#job', function(){
        var job = $('#job').val();
        if(job == ''){
            $('#job').addClass('is-invalid');
            $('#job').removeClass('is-valid');
            $('#job-msg').html('Job title is required');
        }else{
            $('#job-msg').html('');
            $('#job').removeClass('is-invalid');
            $('#job').addClass('is-valid');
            var conf = $('#job').find(':selected').data('conf');
            if(conf == 'y'){
                $('#conf-email-wrap').slideDown();
                $('#conf_email').prop('required', true);
            }else{
                $('#conf-email-wrap').slideUp();
                $('#conf_email').prop('required', false);
            }
        }
    });

    // attendee switch
    $(document).on('click', '.attend-switch', function(){
        if($('#attend-me').is(':checked')){
            $('#reg-attend-dets').slideUp();
            $('#reg-attend-group').slideUp();
            $('.attend-field').prop('required', false).val('');
            $('#reg-attends-wrap .attend-grp-field').prop('required', false).val('');
            $('#reg-attends-wrap .row:not(:first)').remove();
        }else if($('#attend-notme').is(':checked')){
            $('#reg-attend-group').slideUp();
            $('#reg-attend-dets').slideDown();
            $('.attend-field').prop('required', true);
            $('#reg-attends-wrap .attend-grp-field').prop('required', false).val('');
            $('#reg-attends-wrap .row:not(:first)').remove();
        }else{
            $('#reg-attend-dets').slideUp();
            $('#reg-attend-group').slideDown();
            $('.attend-field').prop('required', false).val('');
            $('#reg-attends-wrap .attend-grp-field').prop('required', true);
        }
    });

    // add another attendee
    $(document).on('click', '#reg-attend-grp-add', function(){
        var newrow = $('#reg-attend-more').html();
        $('#reg-attends-wrap').append(newrow);
        $('#reg-attends-wrap .attend-grp-field').prop('required', true);
    });

    // next ... payment details
    $(document).on('click', '#reg-next-submit', function(e){
        e.preventDefault(); // Prevent default button action
        $('#reg-next-btn-msg').addClass('d-none').html('');
        const form1Selector = '#reg-more-form';
        const form2Selector = '#reg-attend-form';
        // Check if forms exist
        const form1Exists = $(form1Selector).length > 0;
        const form2Exists = $(form2Selector).length > 0;
        // Arrays to store form data and validation status
        let formsData = {};
        let allFormsValid = true;
        let validationErrors = [];
        // Process Form 1 if it exists
        if (form1Exists) {
            $(form1Selector).addClass('was-validated');
            const form1Element = $(form1Selector)[0];
            // Validate form using checkValidity()
            if (!form1Element.checkValidity()) {
                allFormsValid = false;
                validationErrors.push('Form 1 has invalid fields');
                // Optionally trigger browser validation UI
                form1Element.reportValidity();
            } else {
                // Collect form data
                const formArray = $(form1Selector).serializeArray();
                
                $.each(formArray, function(i, field) {
                    formsData[field.name] = field.value;
                });
            }
        }

        // Process Form 2 if it exists (attendee form)
        if (form2Exists) {
            $(form2Selector).addClass('was-validated');
            const form2Element = $(form2Selector)[0];
            
            // Validate form using checkValidity()
            $('#attend_single_email')[0].setCustomValidity(''); 
            $('#reg-attends-wrap .attend-email').each(function(){
                $(this)[0].setCustomValidity('');
            });
            
            if (!form2Element.checkValidity()) {
                allFormsValid = false;
                validationErrors.push('Form 2 has invalid fields');
                form2Element.reportValidity();
            } else {
                // NEW: Collect attendee data in STANDARDIZED FORMAT
                formsData.attendees = [];
                
                const current_user = {
                    user_id: ccAjax.current_user_id || 0,
                    firstname: ccAjax.current_user_firstname || '',
                    lastname: ccAjax.current_user_lastname || '', 
                    email: ccAjax.current_user_email || ''
                };
                
                const attend_type = $('input[name="attend_type"]:checked').val();
                // console.log('Attend type:', attend_type);
                // console.log('Current user data:', current_user);
                
                // Save attend_type  .... not sure why we need this any more
                formsData.attend_type = attend_type;
                
                switch(attend_type) {
                    case 'me':
                        // Just the registrant is attending
                        formsData.attendees.push({
                            user_id: current_user.user_id,
                            registrant: 'r',
                            firstname: current_user.firstname,
                            lastname: current_user.lastname,
                            email: current_user.email
                        });
                        // console.log('Me case - registrant only:', formsData.attendees[0]);
                        break;
                        
                    case 'notme':
                        // Someone else is attending (not the registrant)
                        const single_first = $('#attend_single_first').val().trim();
                        const single_last = $('#attend_single_last').val().trim();
                        const single_email = $('#attend_single_email').val().trim();
                        
                        if (single_first && single_email) {
                            formsData.attendees.push({
                                user_id: 0,
                                registrant: '', // Not the registrant
                                firstname: single_first,
                                lastname: single_last,
                                email: single_email
                            });
                        }
                        // console.log('Not me case - other person:', formsData.attendees[0]);
                        break;
                        
                    case 'group':
                        // Group booking - may include registrant plus others
                        
                        // Check if registrant is attending (checkbox)
                        if ($('input[name="attend_check_reg"]:checked').val() === 'yes') {
                            // Get registrant data from the form fields
                            let reg_first = $('input[name="attend_first_reg"]').val().trim();
                            let reg_last = $('input[name="attend_last_reg"]').val().trim();
                            let reg_email = $('input[name="attend_email_reg"]').val().trim();
                            
                            // Fallback to current_user if form fields are empty
                            if (!reg_first) reg_first = current_user.firstname;
                            if (!reg_last) reg_last = current_user.lastname;
                            if (!reg_email) reg_email = current_user.email;
                            
                            formsData.attendees.push({
                                user_id: current_user.user_id,
                                registrant: 'r',
                                firstname: reg_first,
                                lastname: reg_last,
                                email: reg_email
                            });
                            
                            // console.log('Group case - registrant attending:', formsData.attendees[formsData.attendees.length - 1]);
                        }
                        
                        // Add group attendees from the dynamic rows
                        $('#reg-attends-wrap .row').each(function() {
                            const firstName = $(this).find('input[name="attend_first[]"]').val().trim();
                            const lastName = $(this).find('input[name="attend_last[]"]').val().trim();
                            const email = $(this).find('input[name="attend_email[]"]').val().trim();
                            
                            if (firstName && email) {
                                // Make sure this isn't a duplicate of the current user
                                if (email !== current_user.email) {
                                    formsData.attendees.push({
                                        user_id: 0,
                                        registrant: '',
                                        firstname: firstName,
                                        lastname: lastName,
                                        email: email
                                    });
                                    
                                    // console.log('Group case - additional attendee:', formsData.attendees[formsData.attendees.length - 1]);
                                }
                            }
                        });
                        break;
                        
                    default:
                        console.error('Unknown attend_type:', attend_type);
                        break;
                }
                
                // console.log('Final standardized attendees data:', formsData.attendees);
                // console.log('Attendees count:', formsData.attendees.length);
                
                // Validation: ensure we have at least one attendee
                if (formsData.attendees.length === 0) {
                    console.warn('No attendees found - adding default registrant');
                    formsData.attendees.push({
                        user_id: current_user.user_id,
                        registrant: 'r',
                        firstname: current_user.firstname,
                        lastname: current_user.lastname,
                        email: current_user.email
                    });
                }
            }
        }

        // Check if at least one form exists
        if ((form1Exists || form2Exists) && allFormsValid) {
            // Prepare data for server
            const dataToSend = {
                formsData: formsData,
                token: $('#token').val(),
                step: $('#step').val()
            };
            // Send AJAX request
            $.ajax({
                url : ccAjax.ajaxurl + '?action=reg_step_one',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(dataToSend),
                beforeSend: function() {
                    $('#reg-next-submit').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin-pulse"></i>');
                },
                success: function(response) {
                    // off to the payment details
                    // alert('off to the next page');
                    $('#step').val('2');
                    $('#reg-next-form').submit();
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    console.error('Response Text:', xhr.responseText);
                    var errorMsg = 'There was a problem saving your registration details. Please try again.';
                    if (status === 'timeout') {
                        errorMsg = 'The website is taking too long to respond. Please check your internet connection and try again.';
                    } else if (status === 'error' && xhr.status === 0) {
                        errorMsg = 'Unable to connect to the server. Please check your internet connection and try again.';
                    } else if (xhr.status >= 500) {
                        errorMsg = 'A server error occurred. Please try again in a moment.';
                    }
                    $('#reg-next-btn-msg').removeClass('d-none').html('<i class="fa-solid fa-triangle-exclamation"></i> ' + errorMsg);
                },
                complete: function() {
                    $('#reg-next-submit').prop('disabled', false).html('Next: Payment details');
                }
            });
        }
    });

    // CNWL or free - complete reg
    $(document).on('click', '#reg-org-submit, #reg-conf-submit', function(e){
        e.preventDefault()
        e.stopPropagation()

        var form = $(this).closest('form');
        form.addClass('was-validated');

        if(!form[0].checkValidity()) {
            return;
        }else{
            $(this).html('<i class="fa-solid fa-spinner fa-spin-pulse"></i>');

            // ensure we have a proper form submission

            // Make sure the form has method and action
            if(!form.attr('method')) {
                form.attr('method', 'POST');
            }
            if(!form.attr('action')) {
                form.attr('action', '');
            }
            
            // Ensure token is present
            var tokenInput = form.find('input[name="token"]');
            // Check if token input exists and if it's empty
            if(tokenInput.length > 0 && !tokenInput.val()) {
                // Get token from the other form's #token field
                var existingToken = $('#token').val();
                if(existingToken) {
                    tokenInput.val(existingToken);
                }
            }

            // add on extra fields (a bit of a hack!)
            ['job', 'job_id', 'conf_email', 'source'].forEach(function(fieldName) {
                var field = $('#' + fieldName);
                if(field.length > 0 && field.val()) {
                    // Add new hidden input
                    form.append('<input type="hidden" name="' + fieldName + '" value="' + escapeHtml(field.val()) + '">');
                }
            });
            
            // Check and add mailing_list checkbox
            if($('#mailing_list').length > 0 && $('#mailing_list').is(':checked')) {
                form.append('<input type="hidden" name="mailing_list" value="yes">');
            }                

            // NOW manually submit the form after adding all fields
            form[0].submit(); // Use [0] to get the DOM element and call native submit
    
        }
    });

    // currency changer
    $(document).on('change', '#reg-chg-curr', function(){
        newCurr = $('#reg-chg-curr').val();
        $('#currency').val(newCurr);
        updatePricing();
    });

    // VAT fields
    $(document).on('change', '.vat-field', function(){
        var vatUK = $('#vat-uk').val();
        var vatEmploy = $('#vat-employ').val();
        var vatEmployer = $('#vat-employer').val();
        var vatExempt = $('#vat_exempt').val();
        if(vatUK == 'y'){
            $('#vat-not-uk-wrap').slideUp();
            $('#vat-employ').val('employ');
            $('#vat-employer').val('');
            $('#vat-msg').html('VAT is applied at the standard rate')
                .removeClass('text-danger');
            if(vatExempt == 'y'){
                $('#vat_exempt').val('n');
                updatePricing();
            }else{
                $('#reg-pay-dets-submit').attr('disabled', false);
            }
        }else{
            $('#vat-not-uk-wrap').slideDown();
            if(vatEmploy == 'employ'){
                $('#vat-employer-wrap').slideDown();
                if(vatEmployer == ''){
                    $('#vat-msg').html('Incomplete details: VAT is applied at the standard rate')
                        .addClass('text-danger');
                    if(vatExempt == 'y'){
                        $('#vat_exempt').val('n');
                        updatePricing();
                    }else{
                        $('#reg-pay-dets-submit').attr('disabled', false);
                    }
                }else{
                    $('#vat-msg').html('UK VAT will not be charged')
                        .removeClass('text-danger');
                    if(vatExempt == 'n'){
                        $('#vat_exempt').val('y');
                        updatePricing();
                    }else{
                        $('#reg-pay-dets-submit').attr('disabled', false);
                    }
                }
            }else{
                $('#vat-employer-wrap').slideUp();
                $('#vat-employer').val('');
                $('#vat-msg').html('VAT is applied at the standard rate')
                    .removeClass('text-danger');
                if(vatExempt == 'y'){
                    $('#vat_exempt').val('n');
                    updatePricing();
                }else{
                    $('#reg-pay-dets-submit').attr('disabled', false);
                }
            }
        }
    });

    // on first load, display the right things
    $('.vat-field').trigger('change');

    // if the vat employer field is in focus, disable the submit button until the price has been updated
    var tempVatEmployer = '';
    $(document).on('focus', '#vat-employer', function(){
        tempVatEmployer = $('#vat-employer').val();
        $('#reg-pay-dets-submit').attr('disabled', true);
    });

    $(document).on('blur', '#vat-employer', function(){
        if(tempVatEmployer == $('#vat-employer').val()){
            $('#reg-pay-dets-submit').attr('disabled', false);
        }
    });

    // get and update the pricing
    function updatePricing(){
        $('#training-price-msg').removeClass('text-danger').html('Updating price ...');

        // Get token from hidden field (new system)
        var token = $('#token').val();
        
        // var ccreg = $('#ccreg').val();
        var trainingType = $('#training-type').val();
        var trainingID = $('#training-id').val();
        var eventID = $('#event-id').val();
        var currency = $('#currency').val();
        var vatExempt = $('#vat_exempt').val();
        // var promo = $('#disc_code').val();  <-- delete
        var promo = $('#promo-code').val();
        var voucher = $('#voucher-code').val();
        var student = $('#student').val();
        var upsell = $('#upsell_workshop_id').val();

        var vatUK = $('#vat-uk').length > 0 ? $('#vat-uk').val() : null;
        var vatEmploy = $('#vat-employ').length > 0 ? $('#vat-employ').val() : null;
        var vatEmployer = $('#vat-employer').length > 0 ? $('#vat-employer').val() : null;

        var ajaxData = {
            action: 'reg_update_pricing',
            token: token, // Include token instead of ccreg
            trainingType: trainingType,
            trainingID: trainingID,
            eventID: eventID,
            currency: currency,
            vatExempt: vatExempt,
            promo: promo,
            voucher: voucher,
            student: student,
            upsell: upsell,
            vatUK: vatUK,
            vatEmploy: vatEmploy,
            vatEmployer: vatEmployer,
        };
        
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: ajaxData,
            cache: false,
            success: function(response){

                if(response.status == 'ok'){
                    if(response.currency != currency){
                        wmsSetCookie('ccurrency', response.currency, 30);
                    }
                    $('#currency').val(response.currency);
                    $('.currency-icon').html(response.icon);
                    $('#train-price-wrap').html(response.price_html);
                    $('#training-price-msg').removeClass('text-danger').html('');

                    $('#disc_amount').val(response.disc_amount);
                    $('#voucher_amount').val(response.voucher_amount);
                    $('#raw_price').val(response.raw_price);
                    $('#vat_amount').val(response.vat);

                    $('#tot_pay').val(response.tot_pay); // per attendee before voucher
                    $('#total_payable').val(response.total_payable);

                    $('#raw-price').val(response.raw_price);

                    $('#reg-upsell-panel-price-wrap').html(response.upsell_html);

                    if( response.total_payable == 0 ){
                        $('#reg-pay-dets-submit').html('Complete registration');
                    }else{
                        $('#reg-pay-dets-submit').html('Next: Payment method');
                    }
                    $('#reg-pay-dets-submit').attr('disabled', false);
                }else{
                    $('#training-price-msg').addClass('text-danger').html('Invalid data. Please try again.');
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#training-price-msg').addClass('text-danger').html('Cannot find prices. Please try again.');
            }
        });
    }

    // promo code switch
    $(document).on('change', '#promo-switch', function(){
        var doPriceUpd = false;
        if($(this).is(':checked')){
            $('#reg-promo-code-wrap').slideDown();
            $('.reg-voucher-wrap').slideUp();
            $('.upsell-panel-wrap').slideUp();
        }else{
            $('#reg-promo-code-wrap').slideUp();
            $('.reg-voucher-wrap').slideDown();
            $('.upsell-panel-wrap').slideDown();
            if( $('#disc_code').val() != '' ){
                $('#disc_code').val('');
                $('#promo-code').removeClass('is-valid').prop('disabled', false).val('');
                $('#promo-code-apply').removeClass('disabled');
                doPriceUpd = true;
            }
        }
        // allow time for the above slides to complete then ...
        setTimeout(function(){
            ccScrollTo("voucher-panel-wrap");
        }, 400);
        if( doPriceUpd ){
            updatePricing();
        }
    });

    // voucher code switch
    $(document).on('change', '#voucher-switch', function(){
        var doPriceUpd = false;
        if($(this).is(':checked')){
            $('#reg-voucher-code-wrap').slideDown();
            $('.reg-promo-wrap').slideUp();
            $('.upsell-panel-wrap').slideUp();
        }else{
            $('#reg-voucher-code-wrap').slideUp();
            $('.reg-promo-wrap').slideDown();
            $('.upsell-panel-wrap').slideDown();
            if( $('#voucher_code').val() != '' ){
                $('#voucher_code').val('');
                $('#voucher-code').removeClass('is-valid').prop('disabled', false).val('');
                $('#promo-code-apply').removeClass('disabled');
                doPriceUpd = true;
            }
        }
        setTimeout(function(){
            ccScrollTo("voucher-panel-wrap");
        }, 400);
        if( doPriceUpd ){
            updatePricing();
        }
    });

    // promo code lookup
    $(document).on('click', '#promo-code-apply', function(){
        var promo = $('#promo-code').val();
        if(promo != ''){
            $('#promo-help').html('<i class="fa-solid fa-spinner fa-spin-pulse"></i> Checking that for you ...')
                .removeClass('text-danger');
            $('#promo-code-apply').addClass('disabled');
            var trainingType = $('#training-type').val();
            var trainingID = $('#training-id').val();
            var eventID = $('#event-id').val();
            var currency = $('#currency').val();
            var vatExempt = $('#vat_exempt').val();
            var student = $('#student').val();
            var token = $('#token').val();
            if( student == 'yes' ){
                $('#promo-help').addClass('text-danger').html('Code not valid.');
                $('#promo-code-apply').removeClass('disabled');
                $('#promo-code').val('');
            }else{
                $.ajax({
                    url : ccAjax.ajaxurl,
                    type: "POST",
                    dataType: "json",
                    timeout : 10000,
                    data: {
                        action: 'reg_promo_lookup',
                        trainingType: trainingType,
                        trainingID: trainingID,
                        eventID: eventID,
                        currency: currency,
                        vatExempt: vatExempt,
                        promo: promo,
                        token: token
                    },
                    cache: false,
                    success: function(response){
                        if(response.status == 'ok'){
                            $('#promo-help').removeClass('text-danger').html(response.msg);
                            $('#disc_code').val(response.code);
                            $('#promo-code').addClass('is-valid').prop('disabled', true);
                            updatePricing();
                        }else{
                            $('#promo-help').addClass('text-danger').html(response.msg);
                            $('#promo-code-apply').removeClass('disabled');
                        }
                    },
                    error: function(jqXhr, textStatus, errorMessage){
                        $('#promo-help').addClass('text-danger').html('Problem looking up code. Please try again.');
                        $('#promo-code-apply').removeClass('disabled');
                    }
                });
            }
        }
    });

    // gift voucher code lookup
    $(document).on('click', '#voucher-code-apply', function(){
        var voucher = $('#voucher-code').val();
        if(voucher != ''){
            $('#voucher-help').html('<i class="fa-solid fa-spinner fa-spin-pulse"></i> Checking that for you ...')
                .removeClass('text-danger');
            $('#voucher-code-apply').addClass('disabled');
            var trainingType = $('#training-type').val();
            var trainingID = $('#training-id').val();
            var eventID = $('#event-id').val();
            var currency = $('#currency').val();
            var vatExempt = $('#vat_exempt').val();
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                timeout : 10000,
                data: {
                    action: 'reg_voucher_lookup',
                    trainingType: trainingType,
                    trainingID: trainingID,
                    eventID: eventID,
                    currency: currency,
                    vatExempt: vatExempt,
                    voucher: voucher
                },
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#voucher-help').removeClass('text-danger').html(response.msg);
                        $('#voucher_code').val(response.code);
                        $('#voucher-code').addClass('is-valid').prop('disabled', true);
                        updatePricing();
                    }else{
                        $('#voucher-help').addClass('text-danger').html(response.msg);
                        $('#voucher-code-apply').removeClass('disabled');
                    }
                },
                error: function(jqXhr, textStatus, errorMessage){
                    $('#voucher-help').addClass('text-danger').html('Problem looking up voucher. Please try again.');
                    $('#voucher-code-apply').removeClass('disabled');
                }
            });
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

    // select/deselect upsell
    $(document).on('change', '.upsell-switch', function(){
        var workshopID = $('#upsell-yes').data('workshopid');
        var uwID = $('#upsell_workshop_id').val();
        if($('#upsell-yes').is(':checked')){
            if(workshopID != uwID){
                $('#upsell_workshop_id').val(workshopID);
                $('#training-panel-upsell-wrap').removeClass('d-none').slideDown();
                updatePricing();
            }
            $('.voucher-panel-wrap').slideUp();
        }else{
            if(uwID != 0){
                $('#upsell_workshop_id').val('0');
                $('#training-panel-upsell-wrap').slideUp().addClass('d-none');
                updatePricing();
            }
            $('.voucher-panel-wrap').slideDown();
        }
    });

    // Handle payment details form submission
    $(document).on('click', '#reg-pay-dets-submit', function(e){
        e.preventDefault(); // Prevent default form submission

        /*
        console.log('=== FORM SUBMISSION ===');
        console.log('Reading tot_pay:', $('#tot_pay').val());
        console.log('Reading total_payable:', $('#total_payable').val());
        
        // Check if the fields exist at submission time
        console.log('tot_pay field exists:', $('#tot_pay').length);
        console.log('total_payable field exists:', $('#total_payable').length);
        
        // Check the actual DOM element
        console.log('tot_pay DOM value:', document.getElementById('tot_pay') ? document.getElementById('tot_pay').value : 'NOT FOUND');
        console.log('total_payable DOM value:', document.getElementById('total_payable') ? document.getElementById('total_payable').value : 'NOT FOUND');
        */

        const form = $(this).closest('form');
        form.addClass('was-validated');
        
        if (form[0].checkValidity()) {
            // Collect all form data
            let paymentData = {};

            paymentData.currency = $('#currency').val();
            
            // VAT details
            paymentData.vat_uk = $('#vat-uk').val() || '';  // SELECT element by ID
            paymentData.vat_employ = $('#vat-employ').val() || '';  // SELECT element by ID  
            paymentData.vat_employer = $('#vat-employer').val() || '';  // INPUT element by ID
            paymentData.vat_exempt = $('#vat_exempt').val() || 'n';  // Hidden INPUT element by ID
            
            // Upsell selection
            paymentData.upsell_selected = $('input[name="upsell-chooser"]:checked').val() || 'no';
            paymentData.upsell_workshop_id = 0;
            if (paymentData.upsell_selected === 'yes') {
                paymentData.upsell_workshop_id = $('input[name="upsell-chooser"]:checked').data('workshopid') || 0;
            }
            
            // Discount code
            paymentData.disc_code = $('#promo-code').val() || '';
            paymentData.disc_amount = parseFloat($('#disc_amount').val()) || 0;
            
            // Voucher code
            paymentData.voucher_code = $('#voucher-code').val() || '';
            paymentData.voucher_amount = parseFloat($('#voucher_amount').val()) || 0;
            
            // Pricing totals
            // paymentData.raw_price = parseFloat($('#raw_price').val()) || 0;
            // paymentData.vat_amount = parseFloat($('#vat_amount').val()) || 0;
            // paymentData.tot_pay = parseFloat($('#tot_pay').val()) || 0;
            // paymentData.total_payable = parseFloat($('#total_payable').val()) || 0;
            
            // Manager email (if applicable)
            // paymentData.conf_email = $('#conf_email').val() || '';
            
            // Mailing list preference
            // paymentData.mailing_list = $('#mailing_list:checked').val() || '';
            
            // Source tracking
            // paymentData.source = $('#source').val() || '';

            // console.log('Payment data being sent:', paymentData);

            // Prepare data for server
            const dataToSend = {
                paymentData: paymentData,
                token: $('#token').val(),
                step: 2 // Payment details step
            };
            
            // Show loading state
            $('#reg-pay-dets-submit').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin-pulse"></i> Saving...');
            
            // Send AJAX request
            $.ajax({
                url: ccAjax.ajaxurl + '?action=reg_step_two',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(dataToSend),
                beforeSend: function() {
                    // console.log('Saving payment details data:', dataToSend);
                },
                success: function(response) {
                    if (response.success) {
                        // Proceed to payment page
                        $('#reg-pay-dets-form #step').val('3'); // Set step to payment
                        
                        // Submit the reg-pay-dets-form form to go to payment page
                        // The form has action="/payment"
                        $('#reg-pay-dets-form').submit();
                    } else {
                        alert('Error saving payment details: ' + (response.data.message || 'Unknown error'));
                        $('#reg-pay-dets-submit').prop('disabled', false).html('Next: Payment method');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error saving payment details:', error);
                    console.error('Response Text:', xhr.responseText);
                    alert('There was an error saving your payment details. Please try again.');
                    $('#reg-pay-dets-submit').prop('disabled', false).html('Next: Payment method');
                }
            });
        } else {
            // Form validation failed
            // console.log('Form validation failed');
        }
    });

    /*
    // One more check - are there multiple forms on the page?
    $(document).ready(function() {
        console.log('=== PAGE LOAD CHECKS ===');
        console.log('Number of #tot_pay elements:', $('#tot_pay').length);
        console.log('Number of #total_payable elements:', $('#total_payable').length);
        
        // Check if they're in a specific form
        console.log('tot_pay in #reg-pay-dets-data form:', $('#reg-pay-dets-data #tot_pay').length);
        console.log('total_payable in #reg-pay-dets-data form:', $('#reg-pay-dets-data #total_payable').length);
        
        // Check all forms on the page
        $('form').each(function(index) {
            console.log('Form ' + index + ' id:', $(this).attr('id'));
            console.log('  - has tot_pay:', $(this).find('#tot_pay').length);
            console.log('  - has total_payable:', $(this).find('#total_payable').length);
        });
    });
    */

});