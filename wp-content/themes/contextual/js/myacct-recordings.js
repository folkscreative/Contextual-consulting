/**
 * My Account Recordings
 */
jQuery(document).ready(function( $ ) {

    /* moved to contextual.js
    $(document).on('click', '.resend-reg-btn', function(){
        $('#resend-reg-msg-'+paymentID).html('<i class="fa fa-spinner fa-spin"></i>');
        $('#resend-reg-msg-'+paymentID).removeClass('error success');
        var btn = $(this);
        btn.attr('disabled', true);
        var paymentID = $(this).data('paymentid');
        $.ajax({
            url : rpmAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            data: {
                action: "resend_reg_email",
                paymentID: paymentID
            },
            timeout: 10000,
            cache: false,
            success: function(response){
                $('#resend-reg-msg-'+paymentID).addClass(response.class);
                $('#resend-reg-msg-'+paymentID).html(response.msg);
                btn.attr('disabled', false);
            },
            error: function(){
                $('#resend-reg-msg-'+paymentID).addClass('error');
                $('#resend-reg-msg-'+paymentID).html('There was a problem, please try again.');
                btn.attr('disabled', false);
            }
        });
    });
    */

    $(document).on('click', '.toggler, .recording-title', function(){
        var parentDiv = $(this).closest('.recording');
        if(parentDiv.hasClass('opened')){
            parentDiv.removeClass('opened').addClass('closed');
        }else{
            $('.recording').not(parentDiv).removeClass('opened').addClass('closed');
            parentDiv.removeClass('closed').addClass('opened');
        }
    });

    /*
    $(document).on('click', '.resource-file-select', function(){
        $(this).find("span").toggleClass("d-none");
        var wksrecid = $(this).closest(".resource-files-wrap").data('wksrecid');
        var countSel = $("#resource-files-wrap-"+wksrecid+" .sel").length;
        var countUnSel = $("#resource-files-wrap-"+wksrecid+" .sel.d-none").length;
        if(countUnSel == countSel){
            // nothing selected
            $('#resource-files-download-'+wksrecid).addClass('disabled');
        }else{
            $('#resource-files-download-'+wksrecid).removeClass('disabled');
        }
    });

    $(document).on('click', '.resource-files-download', function(){
        var wksrecid = $(this).data('wksrecid');
        $("#resource-files-wrap-"+wksrecid+" .sel").not(".d-none").each(function(){
            var file = $(this).data('file');
            var lnkID = "#resource-file-"+wksrecid+"-"+file;
            var filename = $(lnkID).html();
            var url = $(lnkID).attr('href');
            $(lnkID).attr('download', filename);
            // click does not work with jQuery, have to use native javascript
            // $(lnkID).click();
            var lnk = document.getElementById("resource-file-"+wksrecid+"-"+file);
            lnk.click();
            $(lnkID).removeAttr('download');
        });
        $("#resource-files-wrap-"+wksrecid+" .sel").addClass('d-none');
        $("#resource-files-wrap-"+wksrecid+" .unsel").removeClass('d-none');
    });
    */

    // added v1.0.3.40x.0 ...
    $(document).on('click', '.globpodrec', function(){
        var recid = $(this).data('recid');
        $.ajax({
            type: "POST",
            url : rpmAjax.ajaxurl,
            dataType : "json",
            timeout : 10000,
            data: { 
                action : "save_rec_click",
                recid: recid
            },
            cache: false,
            success: function(response){
                // do nothing
            },
            error: function(jqXhr, textStatus, errorMessage){
                // do nothing
            }
        });
    });

});