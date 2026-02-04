/**
 * cc login v2 scripts
 */

jQuery( document ).ready( function( $ ) {

    // submit email
    $(document).on('submit', '#cc-login-email', function(e){
        e.preventDefault();
        e.stopPropagation();
        var form = $(this);
        form.addClass('was-validated');
        if(form[0].checkValidity()){
            // form ok
            $('#cclle-submit').addClass('disabled')
            $('#ccll-email-help').html('<i class="fa-solid fa-spinner fa-spin-pulse"></i> Looking up your email address');
            var email = $('#ccll-email').val();
            $.ajax({
                url : rpmAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                timeout : 10000,
                data: {
                    action: "cclle_submit",
                    email: email
                },
                cache: false,
                success: function(response){
                    $('#ccll2-form-wrap').html(response.html);
                },
                error: function(jqXhr, textStatus, errorMessage){
                    $('#ccll-email-help').html('Website taking too long to respond. Please try again.')
                        .addClass('error');
                    $('#cclle-submit').removeClass('disabled');
                }
            });
        }
    });

    // go back from password to email
    $(document).on('click', '#ccllp-back', function(){
        $('#ccll-password-help').html('');
        $('#ccll-password-help').removeClass('error');
        var email = $('#ccllp-back').html();
        $.ajax({
            url : rpmAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: {
                action: "ccllp_back",
                email: email
            },
            cache: false,
            success: function(response){
                $('#ccll2-form-wrap').html(response.html);
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#ccll-password-help').html('Website taking too long to respond. Please try again.');
                $('#ccll-password-help').addClass('error');
            }
        });
    });

    // password submit
    $(document).on('submit', '#cc-login-pwd', function(e){
        e.preventDefault();
        e.stopPropagation();
        var form = $(this);
        form.addClass('was-validated');
        if(form[0].checkValidity()){
            // form ok
            $('#ccllp-submit').addClass('disabled')
            $('#ccll-password-help').addClass('info').html('<i class="fa-solid fa-spinner fa-spin-pulse"></i> Checking your password');
            var email = $('#ccllp-back').html();
            var password = $('#ccll-password').val();
            $.ajax({
                url : rpmAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                timeout : 10000,
                data: {
                    action: "ccllp_submit",
                    email: email,
                    password: password
                },
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        var redirect = $('#cc-feedback-redirect').val();
                        if(redirect == ''){
                            window.location.href = response.page;
                        }else{
                            window.location.href = '/feedback?f='+redirect;
                        }
                    }else{
                        $('#ccll2-form-wrap').html(response.html);
                    }
                },
                error: function(jqXhr, textStatus, errorMessage){
                    $('#ccll-password-help').html('Website taking too long to respond. Please try again.');
                    $('#ccll-password-help').addClass('error');
                    $('#ccllp-submit').attr('disabled', false);
                    $('#ccllp-submit').html('Login');
                }
            });
        }
    });

    // check new password strength
    $( document ).on( 'keyup', '#ccll-password1', function( event ) {
        var pass1 = $('#ccll-password1').val();
        $('#ccll-password1-help').removeClass('short bad good strong');
        var pwdStrength = wp.passwordStrength.meter(pass1, '', '');
        $('#password-strength-meter').val(pwdStrength);
        switch ( pwdStrength ) {
            case 2:
                $('#ccll-password1-help').addClass( 'bad' ).html( pwsL10n.bad );
                break;
            case 3:
                $('#ccll-password1-help').addClass( 'good' ).html( pwsL10n.good );
                break;
            case 4:
                $('#ccll-password1-help').addClass( 'strong' ).html( pwsL10n.strong );
                break;
            case 5:
                $('#ccll-password1-help').addClass( 'short' ).html( pwsL10n.mismatch );
                break;
            default:
                $('#ccll-password1-help').addClass( 'short' ).html( pwsL10n.short );
        }
        checkPassword2();
        maybeEnableSubmit();
    });

    // check retyped password
    $( document ).on( 'keyup', '#ccll-password2', function(){
        checkPassword2();
        maybeEnableSubmit();
    });

    function checkPassword2(){
        var pass1 = $('#ccll-password1').val();
        var pass2 = $('#ccll-password2').val();
        if(pass2 == ''){
            $('#ccll-password2-help').addClass( 'bad' ).html( 'Password field is empty, please type something' );
        }else if(pass1 == pass2){
            $('#ccll-password2-help').addClass( 'good' ).html( 'Passwords match <i class="far fa-smile"></i>' );
        }else{
            $('#ccll-password2-help').addClass( 'bad' ).html( 'Passwords don\'t match <i class="far fa-frown"></i>' );
        }
    }

    function maybeEnableSubmit(){
        var pass1 = $('#ccll-password1').val();
        var pass2 = $('#ccll-password2').val();
        var pwdStrength = $('#password-strength-meter').val();
        if(pwdStrength > 2 && pass1 == pass2){
            $('#cclls-submit').attr( 'disabled', false );
            $('#ccll-wait-text').addClass('d-none');
        }else{
            $('#cclls-submit').attr( 'disabled', true );
            $('#ccll-wait-text').removeClass('d-none');
        }
    }

    // set password submit
    $(document).on('submit', '#cc-login-reset', function(e){
        e.preventDefault();
        $('#cclls-submit').attr('disabled', true);
        $('#cclls-submit').html('<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>');
        var email = $('#ccllp-back').html();
        var password = $('#ccll-password1').val();
        $.ajax({
            url : rpmAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: {
                action: "cclls_submit",
                email: email,
                password: password
            },
            cache: false,
            success: function(response){
                $('#ccll2-form-wrap').html(response.html);
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#ccll-wait-text').html('Website taking too long to respond. Please try again.')
                    .removeClass('d-none');
                $('#cclls-submit').attr('disabled', false)
                    .html('Next');
            }
        });
    });

    // forgotten password
    $(document).on('click', '#ccllp-forgot', function(){
        $('#ccllp-forgot').attr('disabled', true);
        $('#ccllp-forgot').html('<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>');
        var email = $('#ccllp-back').html();
        $.ajax({
            url : rpmAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: {
                action: "ccllp_forgot",
                email: email
            },
            cache: false,
            success: function(response){
                $('#ccll2-form-wrap').html(response.html);
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#ccll-password-help').html('Website taking too long to respond. Please try again.');
                $('#ccll-password-help').addClass('error');
                $('#ccllp-forgot').attr('disabled', false);
                $('#ccllp-forgot').html('Forgot password?');
            }
        });
    });

    // forgotten password ... back to enter password
    $(document).on('click', '#ccllr-back', function(){
        $('#ccllr-back').attr('disabled', true);
        $('#ccllr-back').html('<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>');
        var email = $('#ccllp-back').html();
        $.ajax({
            url : rpmAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: {
                action: "cclle_submit",
                email: email
            },
            cache: false,
            success: function(response){
                $('#ccll2-form-wrap').html(response.html);
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#ccll-password-help').html('Website taking too long to respond. Please try again.');
                $('#ccll-password-help').addClass('error');
                $('#ccllr-back').attr('disabled', false);
                $('#ccllr-back').html('Back to login');
            }
        });
    });

    // send password reset email
    $(document).on('submit', '#cc-login-rconf', function(e){
        e.preventDefault();
        $('#ccllr-submit').attr('disabled', true);
        $('#ccllr-submit').html('<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>');
        var email = $('#ccllp-back').html();
        $.ajax({
            url : rpmAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: {
                action: "ccllr_submit",
                email: email
            },
            cache: false,
            success: function(response){
                $('#ccll2-form-wrap').html(response.html);
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#ccll-password-help').html('Website taking too long to respond. Please try again.');
                $('#ccll-password-help').addClass('error');
                $('#ccllr-submit').attr('disabled', false);
                $('#ccllr-submit').html('Send password reset email');
            }
        });
    });

    // cnwl/org login
    // login submit
    $(document).on('click', '#ccll-submit', function(){
        $('#ccll-submit').addClass('disabled');
        $('#ccll-loading').removeClass('d-none');
        $('.ccll-wrap').removeClass('error');
        var email = $('#ccll-email').val();
        if(email == ''){
            $('#ccll-email-help').html('Email cannot be blank');
            $('#ccll-email-wrap').addClass('error');
        }
        var pass = $('#ccll-pass').val();
        if(pass == ''){
            $('#ccll-pass-help').html('Passcode cannot be blank');
            $('#ccll-pass-wrap').addClass('error');
        }
        if(email != '' && pass != ''){
            $.ajax({
                url : rpmAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "ccll_submit",
                    email: email,
                    pass: pass
                },
                cache: false,
                success: function(response){
                    if(response.status == 'error'){
                        $('#ccll-message').html('Email and/or passcode incorrect. Try again?').addClass('error');
                    }else{
                        $('#ccll-message').html('Great! You\'re logged in. Redirecting you now ...').addClass('success');
                        setTimeout(loginRedirect, 3000, response.page);
                    }
                    $('#ccll-submit').removeClass('disabled');
                    $('#ccll-loading').addClass('d-none');
                }
            });
        }else{
            $('#ccll-loading').addClass('d-none');
            $('#ccll-submit').removeClass('disabled');
        }
    });

    function loginRedirect(page){
        window.location.href = page;
    }

});