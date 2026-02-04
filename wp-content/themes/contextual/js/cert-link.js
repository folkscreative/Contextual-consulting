// cert link scripts
jQuery(document).ready(function($) {

	$(document).on("keyup", "#user_name", function(){
		var username = $("#user_name").val();
		if(username != ''){
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "cert_username_search",
                    username: username
                },
                cache: false,
                success: function(response){
                    $('#user-name-results').html(response.results);
                }
            });
		}
	});

	$(document).on('click', '.a-user', function(){
		$('.a-user').removeClass('chosen');
		$(this).addClass('chosen');
	});

    $(document).on("keyup", "#wksrec_name", function(){
        var wksrecname = $("#wksrec_name").val();
        var wksrectype = 'workshop';
        if($('#wkshp_rec_rec').is(':checked')){
            wksrectype = 'recording';
        }
        if(wksrecname != ''){
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "cert_wksrecname_search",
                    wksrectype: wksrectype,
                    wksrecname: wksrecname
                },
                cache: false,
                success: function(response){
                    $('#wksrec-name-results').html(response.results);
                }
            });
        }
    });

    $(document).on('click', '.a-wksrec', function(){
        $('.a-wksrec').removeClass('chosen');
        $(this).addClass('chosen');
    });

    $(document).on('change', '.wkshp_rec', function(){
        $("#wksrec_name").val('');
        $('#wksrec-name-results').html('');
    });

    $(document).on('click', '#gen-cert-link', function(){
        var userid = $('.a-user.chosen').data('userid');
        var wksrecid = $('.a-wksrec.chosen').data('wksrecid');
        var wksrectype = 'workshop';
        if($('#wkshp_rec_rec').is(':checked')){
            wksrectype = 'recording';
        }
        if(userid == null || wksrecid == null){
            $('#cert-link-response').html('Please select a user and workshop/recording first');
        }else{
            $('#cert-link-response').html('<i class="fas fa-spinner fa-spin"></i> Please wait ...');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "cert_link_generate",
                    userid: userid,
                    wksrectype: wksrectype,
                    wksrecid: wksrecid
                },
                cache: false,
                success: function(response){
                    $('#cert-link-response').html(response.results);
                }
            });
        }
    });

});
