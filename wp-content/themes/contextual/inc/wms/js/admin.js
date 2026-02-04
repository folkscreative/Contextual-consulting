/**
 * WMS Admin JS
 */

jQuery(document).ready(function($){

    // media uploader for home page sections
    var frame;
    $(document).on('click', '.upload-section-img', function(e){
        e.preventDefault();
        var button = $(this),
            sectid = button.data('sectid'),
            metaBox = $('#home-meta-'+sectid+'-metabox'),
            sectType = metaBox.data('secttype');
        if(sectType == 'logos'){
            logoid = button.data('logoid');
            logoWrap = $('#home-meta-'+sectid+'-'+logoid);
            addImgLink = logoWrap.find('.upload-section-img');
            delImgLink = logoWrap.find( '.delete-section-img');
            imgContainer = logoWrap.find( '.section-img-container');
            imgIdInput = logoWrap.find( '.section-img-id' );
        }else{
            var imgType = button.data('imgtype');
            if(imgType == 'mob'){
                addImgLink = metaBox.find('.upload-section-img-mob');
                delImgLink = metaBox.find( '.delete-section-img-mob');
                imgContainer = metaBox.find( '.section-img-container-mob');
                imgIdInput = metaBox.find( '.section-img-id-mob' );
            }else{
                addImgLink = metaBox.find('.upload-section-img-std');
                delImgLink = metaBox.find( '.delete-section-img-std');
                imgContainer = metaBox.find( '.section-img-container-std');
                imgIdInput = metaBox.find( '.section-img-id-std' );
            }
        }
        custom_uploader = wp.media({
            title: 'Insert image',
            library: {
                type: 'image'
            },
            button: {
                text: 'Use this image'
            },
            multiple: false
        }).on('select', function(){
            // Get media attachment details from the frame state
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            // display the image in the metabox
            imgContainer.append( '<img src="'+attachment.url+'" alt="" style="max-width:100%;">' );
            // Send the attachment id to our hidden input
            imgIdInput.val( attachment.id );
            // Hide the add image link
            addImgLink.addClass( 'hidden' );
            // Unhide the remove image link
            delImgLink.removeClass( 'hidden' );            
        }).open();
    }); 

    // delete image link for the home section image
    $(document).on('click', '.delete-section-img', function(e){
        e.preventDefault();
        var button = $(this),
            sectid = button.data('sectid'),
            metaBox = $('#home-meta-'+sectid+'-metabox'),
            sectType = metaBox.data('secttype');
        if(sectType == 'logos'){
            logoid = button.data('logoid');
            logoWrap = $('#home-meta-'+sectid+'-'+logoid);
            addImgLink = logoWrap.find('.upload-section-img');
            delImgLink = logoWrap.find( '.delete-section-img');
            imgContainer = logoWrap.find( '.section-img-container');
            imgIdInput = logoWrap.find( '.section-img-id' );
        }else{
            var imgType = button.data('imgtype');
            if(imgType == 'mob'){
                addImgLink = metaBox.find('.upload-section-img-mob');
                delImgLink = metaBox.find( '.delete-section-img-mob');
                imgContainer = metaBox.find( '.section-img-container-mob');
                imgIdInput = metaBox.find( '.section-img-id-mob' );
            }else{
                addImgLink = metaBox.find('.upload-section-img-std');
                delImgLink = metaBox.find( '.delete-section-img-std');
                imgContainer = metaBox.find( '.section-img-container-std');
                imgIdInput = metaBox.find( '.section-img-id-std' );
            }
        }
        // Clear out the preview image
        imgContainer.html( '' );
        // Un-hide the add image link
        addImgLink.removeClass( 'hidden' );
        // Hide the delete image link
        delImgLink.addClass( 'hidden' );
        // Delete the image id from the hidden input
        imgIdInput.val( '' );
    });

    // media uploader for team page
    var frame;
    $(document).on('click', '.upload-team-img', function(e){
        e.preventDefault();
        var button = $(this),
            order = button.data('order');
        custom_uploader = wp.media({
            title: 'Insert image',
            library: {
                type: 'image'
            },
            button: {
                text: 'Use this image'
            },
            multiple: false
        }).on('select', function(){
            // Get media attachment details from the frame state
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            // display the image in the metabox
            $('#team-img-container-'+order).append( '<img src="'+attachment.url+'" alt="" style="max-width:100%;">' );
            // Send the attachment id to our hidden input
            $('#team-img-id-'+order).val( attachment.id );
            // Hide the add image link
            $('#team-img-upload-'+order).addClass( 'hidden' );
            // Unhide the remove image link
            $('#team-img-delete-'+order).removeClass( 'hidden' );            
        }).open();
    }); 

    // delete image link for the team page
    $(document).on('click', '.delete-team-img', function(e){
        e.preventDefault();
        var button = $(this),
            order = button.data('order');
        // Clear out the preview image
        $('#team-img-container-'+order).html( '' );
        // Un-hide the add image link
        $('#team-img-upload-'+order).removeClass( 'hidden' );
        // Hide the delete image link
        $('#team-img-delete-'+order).addClass( 'hidden' );
        // Delete the image id from the hidden input
        $('#team-img-id-'+order).val( '' );
    });

    // colour picker
    $('.section-colour-field, .colour-field').each(function(){
		$(this).wpColorPicker();
	});

    // page content export
    $(document).on('click', '#page-content-export', function(){
        $('#page-content-export-msg').html('<i class="fa-solid fa-spinner fa-spin-pulse"></i>');
        var pageid = $(this).data('pageid');
        $.ajax({
            url : ajaxurl, // only need this in the backend
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: {
                action: "page_content_export",
                pageid: pageid
            },
            cache: false,
            success: function(response){
                if(response.status == 'ok'){
                    $('#page-content-export-msg').html('<a href="/wp-content/export/page_'+pageid+'_content.json" download>Click to download</a>');
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                // $('#ccll-email-help').html('Website taking too long to respond. Please try again.');
                // $('#ccll-email-help').addClass('error');
                // $('#cclle-submit').attr('disabled', false);
                // $('#cclle-submit').html('Next');
            }
        });
    });

});