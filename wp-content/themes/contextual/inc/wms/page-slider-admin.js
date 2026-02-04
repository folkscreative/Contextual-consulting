/**
 * Page Slider Admin JS
 * courtesy of https://rudrastyh.com/wordpress/customizable-media-uploader.html
 */
jQuery(document).ready(function($) { // now we can use $ instead of jQuery all the time :-)
    var frame
    // ADD IMAGE LINK
    $('.wms-page-slider-img-upload').on( 'click', function( event ){
        event.preventDefault();
        var slidenum = $(this).data('slidenum');
        $('#wms_page_slider_current_upload').val(slidenum);
        // If the media frame already exists, reopen it.
        if(frame){
            frame.open();
            return;
        }
        // Create a new media frame
        frame = wp.media({
            title: 'Select or Upload Slider Image',
            button: {
                text: 'Use this image'
            },
            multiple: false,  // Set to true to allow multiple files to be selected
            library: {
            	type: 'image'
            }
        });
        // When an image is selected in the media frame...
        frame.on('select', function(){
            // Get media attachment details from the frame state
            var attachment = frame.state().get('selection').first().toJSON();
            slidenum = $('#wms_page_slider_current_upload').val();
            // Send the attachment URL to our custom image input field.
            $('#wms-page-slider-img-container-'+slidenum).append('<img src="'+attachment.url+'" alt="" style="max-width:100%;"/>' );
            // Send the attachment id to our hidden input
            $('#wms_page_slider_img_id_'+slidenum).val(attachment.id);
            // Hide the add image link
            $('#wms-page-slider-img-upload-'+slidenum).addClass('hidden');
            // Unhide the remove image link
            $('#wms-page-slider-img-delete-'+slidenum).removeClass('hidden');
        });
        // Finally, open the modal on click
        frame.open();
    });
    // DELETE IMAGE LINK
    $('.wms-page-slider-img-delete').on('click', function(event){
        event.preventDefault();
        var slidenum = $(this).data('slidenum');
        // Clear out the preview image
        $('#wms-page-slider-img-container-'+slidenum).html('');
        // Un-hide the add image link
        $('#wms-page-slider-img-upload-'+slidenum).removeClass('hidden');
        // Hide the delete image link
        $('#wms-page-slider-img-delete-'+slidenum).addClass( 'hidden' );
        // Delete the image id from the hidden input
        $('#wms_page_slider_img_id_'+slidenum).val('');
    });

});

jQuery(function($){
	/*
	 * Select/Upload image(s) event
	 */
	$('body').on('click', '.misha_upload_image_button', function(e){
		e.preventDefault();
 
    		var button = $(this),
    		    custom_uploader = wp.media({
			title: 'Insert image',
			library : {
				// uncomment the next line if you want to attach image to the current post
				uploadedTo : wp.media.view.settings.post.id, 
				type : 'image'
			},
			button: {
				text: 'Use this image' // button label text
			},
			multiple: false // for multiple image selection set to true
		}).on('select', function() { // it also has "open" and "close" events 
			var attachment = custom_uploader.state().get('selection').first().toJSON();
			$(button).removeClass('button').html('<img class="true_pre_image" src="' + attachment.url + '" style="max-width:95%;display:block;" />').next().val(attachment.id).next().show();
			/* if you sen multiple to true, here is some code for getting the image IDs
			var attachments = frame.state().get('selection'),
			    attachment_ids = new Array(),
			    i = 0;
			attachments.each(function(attachment) {
 				attachment_ids[i] = attachment['id'];
				console.log( attachment );
				i++;
			});
			*/
		})
		.open();
	});
 
	/*
	 * Remove image event
	 */
	$('body').on('click', '.misha_remove_image_button', function(){
		$(this).hide().prev().val('').prev().addClass('button').html('Upload image');
		return false;
	});
 
});