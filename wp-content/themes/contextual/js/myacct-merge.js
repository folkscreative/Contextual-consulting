/**
 * My Account Merge
 */
jQuery(document).ready(function( $ ) {

    // form submit
    $(document).on('click', '#myacct-merge-submit', function(){
        $('#myacct-merge-submit').attr('disabled', true);
        $('#myacct-merge-submit').html('<i class="fa fa-spinner fa-spin fa-2x fa-fw"></i>');
        $('#myacct-merge-message').html('');
        $('#myacct-merge-message').removeClass('error');
        var type = $('#myacct-merge-form').data('type');
        if((type == 'email' && mergeEmailValid()) || type != 'email'){
            submitMergeForm();
        }
    });

    function mergeEmailValid(){
        var email = $('#email').val();
        var password = $('#password').val();
        if(email == '' || password == ''){
            $('#myacct-merge-message').html('Email and password must both be entered for the account you want to merge.');
            $('#myacct-merge-message').addClass('error');
            $('#myacct-merge-submit').attr('disabled', false);
            $('#myacct-merge-submit').html('Next');
            return false;
        }
        return true;
    }

    function submitMergeForm(){
        var data = $('#myacct-merge-form').serialize();
        $.ajax({
            url : rpmAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: data,
            cache: false,
            success: function(response){
                if(response.status == 'error'){
                    $('#myacct-merge-message').html(response.msg);
                    $('#myacct-merge-message').addClass('error');
                }else{
                    $('#myacct-merge-form-wrap').html(response.html);
                    if(response.status == 'done'){
                        $('#myacct-merge-submit-row').addClass('hide');
                    }
                }
                $('#myacct-merge-submit').attr('disabled', false);
                $('#myacct-merge-submit').html('Next');
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#myacct-merge-message').html('Website taking too long to respond. Please try again.');
                $('#myacct-merge-message').addClass('error');
                $('#myacct-merge-submit').attr('disabled', false);
                $('#myacct-merge-submit').html('Next');
            }
        });
    }

});