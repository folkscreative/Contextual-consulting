/**
 * Contextual Admin JS
 */

jQuery(document).ready(function( $ ) {
	// show the closed time update fields when the pencil is clicked
	$(document).on('click', '.access-to-btn', function(){
		var accessWrap = $(this).parent().prev();
		accessWrap.find('.access-to-update-wrap').slideDown();
		$(this).blur();
	});

	// apply an update
	$(document).on('click', '.access-to-update-btn', function(){
        var updateBtn = $(this);
        updateBtn.html('<img src="/wp-admin/images/loading.gif">');
		var user = updateBtn.data('user');
		var recording = updateBtn.data('recording');
        var closingDateFld = updateBtn.parent().find('.access-to-update-date');
		var closingDate = closingDateFld.val();
        var closingTimeFld = updateBtn.parent().find('.access-to-update-time');
		var closingTime = closingTimeFld.val();
        var closingTextWrap = updateBtn.parent().prev();
        var accessUpdateWrap = updateBtn.parent();
        $.ajax({
            type: "POST",
            url : ContextualData.rpmAjax.ajaxurl,
            dataType : "json",
            timeout : 10000,
            data: { 
            	action : "ccrecw_change_closing_datetime",
            	user: user,
            	recording: recording,
            	closingDate: closingDate,
            	closingTime: closingTime
        	},
            cache: false,
            timeout: 10000,
            success: function(response){
                updateBtn.html('Upd.');
                closingDateFld.removeClass('success error');
                closingDateFld.addClass(response.date_class);
                closingTimeFld.removeClass('success error');
                closingTimeFld.addClass(response.time_class);
                if(response.status == 'ok'){
                    closingTextWrap.html(response.closed_time);
                    setTimeout(function(){
                        accessUpdateWrap.slideUp();
                    }, 5000);
                }
            },
	        error: function(jqXhr, textStatus, errorMessage){
                updateBtn.html('Upd.');
	        	alert('Problem connecting to server ... please try again');
	        }
        });
	});

    // reset user as if they had never logged in
    $( '#reset-last-login' ).on( 'click', function( e ) {
        e.preventDefault();
        var $this = $(this);
        var nonce = $( '#_wpnonce' ).val();
        var user_id = $( '#user_id' ).val();
        $.ajax({
            type: "POST",
            url : ContextualData.rpmAjax.ajaxurl,
            dataType : "json",
            timeout : 10000,
            data: { 
                action : "reset_last_login",
                nonce: nonce,
                user_id: user_id
            },
            cache: false,
            timeout: 10000,
            success: function(response){
                $this.siblings( '.notice' ).remove();
                if( response.status == 'ok' ){
                    $this.prop( 'disabled', true );
                    $this.before( '<div class="notice notice-success inline"><p>' + response.msg + '</p></div>' );
                }else{
                    $this.before( '<div class="notice notice-error inline"><p>' + response.msg + '</p></div>' );
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                alert('Problem connecting to server ... please try again');
            }
        });
        /*
        wp.ajax.post( 'reset_last_login', {
            nonce: $( '#_wpnonce' ).val(),
            user_id: $( '#user_id' ).val()
        }).done( function( response ) {
            $this.prop( 'disabled', true );
            $this.siblings( '.notice' ).remove();
            $this.before( '<div class="notice notice-success inline"><p>' + response.message + '</p></div>' );
        }).fail( function( response ) {
            $this.siblings( '.notice' ).remove();
            $this.before( '<div class="notice notice-error inline"><p>' + response.message + '</p></div>' );
        });
        */
    });

    // add a row to the resource files folder
    $(document).on('click', '.resource-files-add', function(){
        var btn = $(this);
        var highKey = btn.attr('data-highkey');
        highKey ++;
        var newKey = highKey.toString();
        var type = btn.data('type');
        if(type == 'div'){
            var tableCell = btn.closest('.row');
        }else{
            var tableCell = btn.closest('td');
        }        
        var wrap = tableCell.find('.resource-files');
        var empty = tableCell.find('.resource-files-empty').html();
        var newRow = empty.replaceAll('##key##', newKey);
        wrap.append(newRow);
        btn.attr('data-highkey', highKey);
    });

    // training accordions
    // load up the metabox
    if($('#tta-rows-loader').length > 0){
        var cptid = $('#tta-rows-wrap').data('cptid');
        loadTTAMetabox(cptid);
    }

    function loadTTAMetabox( cptid ){
        $.ajax({
            url: ContextualData.rpmAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'cc_train_acc_metabox_load',
                training_id: cptid,
            },
            success: function (response) {
                $('#tta-rows-wrap').html(response.data);
            },
            error: function () {
                $('#tta-rows-wrap').html('Error loading data.');
            },
        });
    }

    // we'll store the editor field so that we can change it later
    let editorInstance;

    // open the training accordion popup and populate it
    $(document).on('click', '.tta-edt', function(e) {
        e.preventDefault();
        $('#tta-edit-popup').show();
        $('#tta-edit-popup-msg').html('');
        $('#tta-edit-popup-data').attr('data-flag', '');
        var ttaID = $(this).data('id');
        $.ajax({
            url: ContextualData.rpmAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'cc_tta_fetch_popup_data',
                ttaID: ttaID,
            },
            success: function (response) {
                $('#tta-edit-popup-data').html(response.data.html);
                // If the editor instance already exists, destroy it first ... otherwise it loses all the formatting!
                if (typeof tinymce !== 'undefined' && tinymce.get('tta_text')) {
                    tinymce.get('tta_text').remove();
                }
                var contentReadonly = false;
                if( $('#tta_src').val() == 'std' ){
                    contentReadonly = true;
                }
                if( contentReadonly ){
                    $('#tta_tit').attr('readonly', 'readonly');
                }
                // wp.editor has been enqueued earlier
                wp.editor.initialize(
                    'tta_text', {
                        tinymce: {
                            readonly: contentReadonly,
                            content_style: contentReadonly
                                ? 'body { background-color: #f0f0f0; }'
                                : 'body { background-color: #ffffff; }',
                            setup: function (editor) {
                                editorInstance = editor; // Store the editor instance for later use
                                editor.on('init', function (e) {
                                    editor.setContent(response.data.content);
                                });
                            }
                        }
                    }
                );
            },
            error: function () {
                $('#tta-edit-popup-data').html('Error loading data.');
            },
        });
    });

    $(document).on('click', '.tta-edit-popup-close', function () {
        var flag = $('#tta-edit-popup-data').attr('data-flag');
        if(flag == 'yes'){
            let result = confirm("You will lose your unsaved changes");
            if(result === true){
                $('#tta-edit-popup').hide();
            }
        }else{
            $('#tta-edit-popup').hide();
        }
    });

    // training accordion change of type
    $(document).on('change', '.tta_ta', function(){
        var src = $('#tta_src').val();
        var result = false;
        if(src == 'cust'){
            result = confirm("You will lose your custom text");
        }
        if( src == 'std' || result === true ){
            $('#tta_src').attr('disabled', false);
            if( $('#tta_ta').val() == 999999 ){
                $('#tta_src').val('cust');
            }else{
                $('#tta_src').val('std');
            }
            reloadTTAEditPopup();
        }
    });

    // get's fresh content for the tta edit popup
    function reloadTTAEditPopup(){
        var formData = $('#tta-form').serialize();
        const ttaText = tinymce.get('tta_text') ? tinymce.get('tta_text').getContent() : $('#tta_text').val();
        $.ajax({
            url: ContextualData.rpmAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'cc_tta_refresh_popup_data',
                formData: formData,
                ttaText: ttaText
            },
            success: function (response) {
                $('#tta-edit-popup-data').html(response.data.html);
                // If the editor instance already exists, destroy it first ... otherwise it loses all the formatting!
                if (typeof tinymce !== 'undefined' && tinymce.get('tta_text')) {
                    tinymce.get('tta_text').remove();
                }
                var contentReadonly = false;
                if( $('#tta_src').val() == 'std' ){
                    contentReadonly = true;
                }
                if( contentReadonly ){
                    $('#tta_tit').attr('readonly', 'readonly');
                }
                // wp.editor has been enqueued earlier
                wp.editor.initialize(
                    'tta_text', {
                        tinymce: {
                            readonly: contentReadonly,
                            content_style: contentReadonly
                                ? 'body { background-color: #f0f0f0; }'
                                : 'body { background-color: #ffffff; }',
                            setup: function (editor) {
                                editorInstance = editor; // Store the editor instance for later use
                                editor.on('init', function (e) {
                                    editor.setContent(response.data.content);
                                });
                            }
                        }
                    }
                );
                flagTTAChanged();
            },
            error: function () {
                $('#tta-edit-popup-data').html('Error loading data.');
            },
        });
    }

    // flag that the tta has changed and display a warning msg
    function flagTTAChanged(){
        var flag = $('#tta-edit-popup-data').attr('data-flag');
        if( flag != 'yes' ){
            $('#tta-edit-popup-data').attr('data-flag', 'yes');
            $('#tta-edit-popup-msg').html('<div class="bg-warning">Changes not saved yet</div>');
        }
    }

    // change of source (std vs custom)
    $(document).on('change', '#tta_src', function(){
        var src = $('#tta_src').val();
        if( src == 'cust' ){
            $('#tta_tit').attr('readonly', false);
            // unset readonly mode
            editorInstance.setMode('design');
            // Update the background color
            const newContentStyle = 'body { background-color: #ffffff; }';
            editorInstance.dom.setStyles(editorInstance.getBody(), { backgroundColor: '#ffffff' });
            flagTTAChanged();
        }else{
            reloadTTAEditPopup() // reset with fresh content from the server
        }
    });

    // change of order or visibility
    $(document).on('change', '#tta_ord, #tta_hid', function(){
        flagTTAChanged();
    });

    // save a tta
    $(document).on('click', '#tta-save-btn', function(){
        $(this).attr('disabled', true)
            .html('<i class="fa-solid fa-sync fa-spin"></i>');
        var formData = $('#tta-form').serialize();
        const ttaText = tinymce.get('tta_text') ? tinymce.get('tta_text').getContent() : $('#tta_text').val();
        var cptid = $('#tta_tid').val();
        $.ajax({
            url: ContextualData.rpmAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'cc_tta_save_popup_data',
                formData: formData,
                ttaText: ttaText
            },
            success: function (response) {
                $('#tta-edit-popup-msg').html('<div class="bg-success">'+response.data+'</div>');
                setTimeout(function(){
                    $('#tta-edit-popup').hide();
                    loadTTAMetabox( cptid );
                }, 1500);
            },
            error: function () {
                $('#tta-edit-popup-msg').html('<div class="bg-danger">Error saving data. Please try again.</div>');
            },
        });
    });

    // add a new tta
    $(document).on('click', '#tta-add-btn', function(e){
        e.preventDefault();
        $('#tta-edit-popup').show();
        $('#tta-edit-popup-msg').html('');
        $('#tta-edit-popup-data').attr('data-flag', '');
        var cptid = $(this).data('cptid');
        $.ajax({
            url: ContextualData.rpmAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'cc_tta_add_tta',
                cptid: cptid,
            },
            success: function (response) {
                $('#tta-edit-popup-data').html(response.data.html);
                // If the editor instance already exists, destroy it first ... otherwise it loses all the formatting!
                if (typeof tinymce !== 'undefined' && tinymce.get('tta_text')) {
                    tinymce.get('tta_text').remove();
                }
                // wp.editor has been enqueued earlier
                wp.editor.initialize(
                    'tta_text', {
                        tinymce: {
                            content_style: 'body { background-color: #ffffff; }',
                            setup: function (editor) {
                                editorInstance = editor; // Store the editor instance for later use
                                editor.on('init', function (e) {
                                    editor.setContent(response.data.content);
                                });
                            }
                        }
                    }
                );
                flagTTAChanged();
            },
            error: function () {
                $('#tta-edit-popup-data').html('Error loading data.');
            },
        });
    });

    // delete a tta
    $(document).on('click', '#tta-del-btn', function(){
        if( confirm('Delete the training accordion? This action is irreversible!') ){
            $(this).attr('disabled', true)
                .html('<i class="fa-solid fa-sync fa-spin"></i>');
            var ttaID = $('#tta_id').val();
            $.ajax({
                url: ContextualData.rpmAjax.ajaxurl,
                method: 'POST',
                data: {
                    action: 'cc_tta_delete',
                    ttaID: ttaID,
                },
                success: function (response) {
                    $('#tta-edit-popup-msg').html('<div class="bg-success">'+response.data+'</div>');
                    setTimeout(function(){
                        $('#tta-edit-popup').hide();
                        loadTTAMetabox( cptid );
                    }, 1500);
                },
                error: function () {
                    $('#tta-edit-popup-msg').html('<div class="bg-danger">Error deleting accordion. Please try again. '+response.data+'</div>');
                },
            });
        }
    });





    /*

    // text needs refreshing...
    $(document).on('change', '.tta-refresh', function(){
        var tta_wrap = $(this).closest('.tta-wrap');
        tta_wrap.find('.tta-msg').html('');
        var train_acc_id = tta_wrap.find('.tta-ta').val();
        var tta_src = tta_wrap.find('.tta_src').val();
        var training_id = tta_wrap.data('train');
        var tta_id = tta_wrap.data('tta');
        if(train_acc_id == '999999'){
            tta_src = 'cust';
            tta_wrap.find('.tta_src').val('cust');
        }
        if(tta_src == 'std'){
            tta_id = 0;
        }
        if(train_acc_id == ''){
            tta_wrap.find('.tta-msg').html('Training Accordion must be selected for this to be saved!');
        }
        if(tta_src == 'cust' && train_acc_id > 0){
            tta_wrap.find('.tta-edt').removeClass('disabled');
        }else{
            tta_wrap.find('.tta-edt').addClass('disabled');
        }
        $.ajax({
            type: "POST",
            url : ContextualData.rpmAjax.ajaxurl,
            dataType : "json",
            timeout : 10000,
            data: { 
                action : "get_training_accordion",
                training_id: training_id,
                train_acc_id: train_acc_id,
                tta_id: tta_id
            },
            cache: false,
            timeout: 10000,
            success: function(response){
                if( response.status == 'ok' ){
                    tta_wrap.find('.tta-tit-wrap').html(response.title);
                    tta_wrap.find('.tta-tith-wrap').val(response.title);
                    tta_wrap.find('.tta-txt-wrap').html(response.content);
                    tta_wrap.find('.tta-txth-wrap').val(response.content);
                }else{
                    tta_wrap.find('.tta-msg').html('Problems finding that accordion content - please try again');
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                alert('Problem connecting to server ... please try again');
            }
        });
        /*
        wp.ajax.post( 'get_training_accordion', {
            training_id: training_id,
            train_acc_id: train_acc_id,
            tta_id: tta_id
        }).done( function( response ){
            tta_wrap.find('.tta-tit-wrap').html(response.title);
            tta_wrap.find('.tta-tith-wrap').val(response.title);
            tta_wrap.find('.tta-txt-wrap').html(response.content);
            tta_wrap.find('.tta-txth-wrap').val(response.content);
        }).fail( function ( response ){
            tta_wrap.find('.tta-msg').html('Problems finding that accordion content - please try again');
        } )
        *//*
    });

    // populate the thickbox form
    $(document).on('click', '.tta-edt', function(e){
        e.preventDefault();
        if( ! $(this).hasClass('disabled') ){
            var row = $(this).data('row');
            doc = new DOMParser().parseFromString( $('#tta-tit-'+row).html(), "text/html");
            var title = doc.documentElement.textContent;
            var content = $('#tta-txt-'+row).html();
            $('#tta-modal-row').val(row);
            $('#tta-modal-title').val(title);
            // $('#tta-modal-text').html(content);
            tb_show("caption", "#TB_inline?width=772&height=900&inlineId=tta-content-edit-modal&modal=true");
            // wp.editor had to be enqueued earlier
            wp.editor.initialize(
                'tta-modal-text', {
                    tinymce: {
                        paste_as_text: true,
                        setup: function (editor) {
                            editor.on('init', function (e) {
                                editor.setContent(content);
                            });
                        }
                    }
                }
            );
        }
    });

    // close the modal without saving
    $(document).on('click', '#tta-modal-close', function(){
        wp.editor.remove( 'tta-modal-text' );
        // $('#tta-modal-text').html('');
        tb_remove();
    });

    // save the modal changes and close the thickbox
    $(document).on('click', '#tta-save-btn', function(){
        var row = $('#tta-modal-row').val();
        var title = $('#tta-modal-title').val();
        var content = wp.editor.getContent( 'tta-modal-text' );
        $('#tta-tit-'+row).html(title);
        $('#tta-tith-'+row).val(title);
        $('#tta-txt-'+row).html(content);
        $('#tta-txth-'+row).val(content);
        wp.editor.remove( 'tta-modal-text' );
        // $('#tta-modal-text').html('');
        tb_remove();
    });




    // on load check to see if the ttas are in a mess
    if($('#tta-rows-wrap').length > 0){
        // there are training accordions on this page
        checkCorrectAccordions();
    }

    function checkCorrectAccordions(){
        var dataRows = $('#tta-row-count').val(); // already includes +1 for the blank row
        var foundRows = 0;
        var foundHidden = false;
        var errors = false;
        var fixable = false;
        $('.tta-wrap').each(function(){
            var thisDataRow = $(this).data('row');
            if(thisDataRow == '##row##'){
                if(!foundHidden){
                    foundHidden = true;
                }else{
                    alert('Error found in Training Accordions: ##row## duplicated. Please refresh the page to see if it clears it.');
                    errors = true;
                }
            }else{
                if(thisDataRow != foundRows){
                    // alert('Error found in training accordions: mismatch on row '+foundRows+' != '+thisDataRow+'. Please refresh the page to see if it clears it.');
                    errors = true;
                    fixable = true;
                }
                foundRows ++;
            }
        });
        if(!foundHidden){
            alert('Error found in Training Accordions: ##row## missing. Please refresh the page to see if it clears it.');            
            errors = true;
        }
        if(foundRows != dataRows){
            alert('Error found in Training Accordions: '+foundRows+' accordions but '+dataRows+' expected. Please refresh the page to see if it clears it.');            
            errors = true;
        }
        if(!errors){
            // alert('Training accordions ok');
        }else{
            // fix em
            if(fixable){
                foundRows = 0;
                $('.tta-wrap').each(function(){
                    var thisDataRow = $(this).data('row');
                    if(thisDataRow != '##row##'){
                        $(this).attr('data-row', foundRows);
                        foundRows ++;
                    }
                });
                $('#tta-rows-wrap').attr('data-rows', foundRows - 1);
                alert('Mismatch error found in training accordions but now fixed :-)')
            }
        }
    }


    // delete/undelete a tta
    $(document).on('click', '.tta-del', function(){
        var tta_wrap = $(this).closest('.tta-wrap');
        var del_switch = tta_wrap.find('.tta-del-switch').val();
        if(del_switch == 'no'){
            tta_wrap.find('.tta-msg').html('Will be removed from this training when you click update');
            tta_wrap.find('.tta-del-switch').val('yes');
        }else{
            tta_wrap.find('.tta-msg').html('');
            tta_wrap.find('.tta-del-switch').val('no');
        }
    });

    // add another accordion row
    $(document).on('click', '#tta-add-btn', function(){
        checkCorrectAccordions();
        var row = $('#tta-row-count').val();
        var newRow = $('#tta-new-row').html();
        newRow = newRow.replaceAll('##row##', row);
        var nextRow = +row + 1; // the extra + forces js to treat row as numeric
        $('#tta-rows-wrap').append('<hr>'+newRow);
        $('#tta-row-count').val(nextRow);
    });

    */







    // sales stats notes thickbox
    $(document).on('click', '.ss-notes', function(){
        var wk = $(this).data('wk');
        var t = $(this).data('t');
        var getNotes = 'admin-ajax.php?action=cc_stats_get_notes&wk='+wk+'&t='+t;
        tb_show('', getNotes);
    });

    // update notes from the sales stats thickbox
    $(document).on('click', '#ss-notes-upd', function(){
        var ssid = $(this).data('ssid');
        var notes = $('#cc-stats-notes').val();
        $('#ss-notes-upd').html('Saving ...');
        $.ajax({
            type: "POST",
            url : ContextualData.rpmAjax.ajaxurl,
            dataType : "json",
            timeout : 10000,
            data: { 
                action : "cc_stats_notes_update",
                ssid: ssid,
                notes: notes
            },
            cache: false,
            timeout: 10000,
            success: function(response){
                $('#ss-notes-upd').html('update');
                self.parent.tb_remove();
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#ss-notes-upd').html('update');
                alert('Problem connecting to server ... please try again');
            }
        });
    });

    // sales stats cat thickbox
    $(document).on('click', '.ss-cat', function(){
        var wk = $(this).data('wk');
        var t = $(this).data('t');
        var getCats = 'admin-ajax.php?action=cc_stats_get_cats&wk='+wk+'&t='+t;
        tb_show('', getCats);
    });

    // update selected category from the sales stats thickbox
    $(document).on('click', '#ss-cats-upd', function(){
        var ssid = $(this).data('ssid');
        var cat = $('#cc-stats-cats').val();
        $('#ss-cats-upd').html('Saving ...');
        $.ajax({
            type: "POST",
            url : ContextualData.rpmAjax.ajaxurl,
            dataType : "json",
            timeout : 10000,
            data: { 
                action : "cc_stats_cat_update",
                ssid: ssid,
                cat: cat
            },
            cache: false,
            timeout: 10000,
            success: function(response){
                $('#ss-cats-upd').html('update');
                self.parent.tb_remove();
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#ss-cats-upd').html('update');
                alert('Problem connecting to server ... please try again');
            }
        });
    });

    // change titles to use jQuery tooltip ... faster
    // mainly for the sales stats page
    // requires jQuery UI
    // $(document).tooltip();
    // upgraded to accommodate <br>
    $(document).tooltip({
        content: function (callback) {
            callback($(this).prop('title'));
        }
    });

    // deletion of sales stats categories
    $(document).on('click', '.sales-cat-del', function(){
        var catid = $(this).data('catid');
        var deleted = $('#cat-del-'+catid).val();
        if(deleted == 'no'){
            $('#cat-wrap-'+catid).addClass('deleted');
            $('#cat-del-'+catid).val('yes');
        }else{
            $('#cat-wrap-'+catid).removeClass('deleted');
            $('#cat-del-'+catid).val('no');
        }
    });

    // CE Credits attendance
    // select workshop
    $(document).on('change', '#cc-ce-credit-attend-workshop-select', function(){
        var workshopID = $(this).val();
        $('#cc-ce-credits-import-msg').removeClass('success', 'danger')
            .addClass('warning')
            .html('Select workshop/event/session');
        if(workshopID != ''){
            $('#caws-wait').show();
            $.ajax({
                type: "POST",
                url : ContextualData.rpmAjax.ajaxurl,
                dataType : "json",
                timeout : 10000,
                data: { 
                    action : "cc_cecaws",
                    workshopID: workshopID
                },
                cache: false,
                timeout: 10000,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#cc-ce-credit-attend-event-select').html(response.events);
                        $('#cc-ce-credit-attend-session-select').html(response.sessions);
                    }
                    $('#caws-wait').hide();
                },
                error: function(jqXhr, textStatus, errorMessage){
                    alert('Problem connecting to server ... please try again');
                }
            });
        }
    });

    // select event
    $(document).on('change', '#cc-ce-credit-attend-event-select', function(){
        var workshopID = $('#cc-ce-credit-attend-workshop-select').val();
        var eventID = $(this).val();
        $('#cc-ce-credits-import-msg').removeClass('success', 'danger')
            .addClass('warning')
            .html('Select workshop/event/session');
        if(workshopID != '' && eventID != ''){
            $('#cawe-wait').show();
            $.ajax({
                type: "POST",
                url : ContextualData.rpmAjax.ajaxurl,
                dataType : "json",
                timeout : 10000,
                data: { 
                    action : "cc_cecawe",
                    workshopID: workshopID,
                    eventID: eventID
                },
                cache: false,
                timeout: 10000,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#cc-ce-credit-attend-session-select').html(response.sessions);
                    }
                    $('#cawe-wait').hide();
                },
                error: function(jqXhr, textStatus, errorMessage){
                    alert('Problem connecting to server ... please try again');
                }
            });
        }
    });

    // select session
    $(document).on('change', '#cc-ce-credit-attend-session-select', function(){
        var workshopID = $('#cc-ce-credit-attend-workshop-select').val();
        var eventID = $('#cc-ce-credit-attend-event-select').val();
        var sessionID = $(this).val();
        $('#cc-ce-credits-import-msg').removeClass('success', 'danger')
            .addClass('warning')
            .html('Select workshop/event/session');
        if(workshopID != '' && eventID != '' && sessionID != ''){
            $('#cawn-wait').show();
            $.ajax({
                type: "POST",
                url : ContextualData.rpmAjax.ajaxurl,
                dataType : "json",
                timeout : 10000,
                data: { 
                    action : "cc_cecawn",
                    workshopID: workshopID,
                    eventID: eventID,
                    sessionID: sessionID
                },
                cache: false,
                timeout: 10000,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#cc-ce-credits-import-msg').removeClass('warning', 'danger')
                            .addClass('success')
                            .html(response.msg);
                    }else{
                        $('#cc-ce-credits-import-msg').removeClass('warning', 'success')
                            .addClass('danger')
                            .html(response.msg);
                    }
                    $('#cawn-wait').hide();
                },
                error: function(jqXhr, textStatus, errorMessage){
                    alert('Problem connecting to server ... please try again');
                }
            });
        }
    });

    // attendance for submission
    $(document).on('submit', '#cc-ce-credits-attend-form', function(e){
        e.preventDefault();
        var workshopID = $('#cc-ce-credit-attend-workshop-select').val();
        var eventID = $('#cc-ce-credit-attend-event-select').val();
        var sessionID = $('#cc-ce-credit-attend-session-select').val();
        var fileName = $('#cc-ce-credit-attend-file').val();
        if(workshopID > 0 && eventID > 0 && sessionID > 0 && fileName != ''){
            // using FormData so that we can upload the file
            var data = new FormData(this);
            // to work this needs processData false and contentType false
            $.ajax({
                type: "POST",
                url: ContextualData.rpmAjax.ajaxurl,
                data: data,
                dataType : "json",
                processData: false,
                contentType: false,
                timeout : 10000,
                cache: false,
                timeout: 10000,
                success: function(response){
                    $('#cc-ce-credit-attend-report').html(response.msg);
                    if(response.status == 'ok'){
                        $('#cc-ce-credit-attend-report').addClass('success');
                    }else{
                        $('#cc-ce-credit-attend-report').addClass('danger');
                    }
                },
                error: function(jqXhr, textStatus, errorMessage){
                    alert('Problem connecting to server ... please try again');
                }
            });
        }
    });

    // add a question to the quizzes CPT
    $(document).on('click', '#quizzes-add-question', function(){
        var btn = $(this);
        var highKey = Number(btn.attr('data-highkey'));
        var highQnum = Number(btn.attr('data-highqnum'));
        var empty = $('#empty-question').html();
        var newKey = highKey.toString();
        var newQ = empty.replaceAll('##COUNT##', newKey);
        highQnum ++;
        var newQnum = highQnum.toString();
        var newQ = newQ.replaceAll('99999', newQnum);
        $('#questions-wrap').append(newQ);
        btn.attr('data-highkey', highKey+1);
        btn.attr('data-highqnum', highQnum);
    });

    // User Analysis: flag or unflag users for Refer a Friend
    $(document).on('click', '.raf-tag-enable', function(){
        var btn = $(this);
        var transient = btn.data('transient');
        var enable = btn.data('enable');
        var upperEnable = enable.toUpperCase();
        if( confirm("You are about to flag these users to "+upperEnable+" the Refer a Friend functions") ){
            $('#raf-tag-enable-msg').removeClass('bg-success bg-danger').html('<i class="fa-solid fa-sync fa-spin"></i> Please wait ...');
            $.ajax({
                type: "POST",
                url: ContextualData.rpmAjax.ajaxurl,
                data: { 
                    action : "cc_user_anal_raf",
                    transient: transient,
                    enable: enable
                },
                dataType : "json",
                cache: false,
                timeout: 15000,
                success: function(response){
                    $('#raf-tag-enable-msg').addClass(response.class).html(response.msg);
                },
                error: function(jqXhr, textStatus, errorMessage){
                    $('#raf-tag-enable-msg').addClass('bg-danger').html('Problem connecting to server ... please try again');
                }
            });
        }
    });

    // User analysis: create newsletter list
    $(document) .on('click', '#user_news_list_create', function(){
        var btn = $(this);
        var transient = btn.data('transient');
        var listName = $('#user_news_list').val();
        if( confirm("You are about to create a newsletter list called "+listName+" containing these users") ){
            $('#news_list_msg').removeClass('bg-success bg-danger').html('<i class="fa-solid fa-sync fa-spin"></i> Please wait ...');
            $.ajax({
                type: "POST",
                url: ContextualData.rpmAjax.ajaxurl,
                data: { 
                    action : "cc_user_news_list",
                    transient: transient,
                    listName: listName
                },
                dataType : "json",
                cache: false,
                timeout: 15000,
                success: function(response){
                    $('#news_list_msg').addClass(response.class).html(response.msg);
                },
                error: function(jqXhr, textStatus, errorMessage){
                    $('#news_list_msg').addClass('bg-danger').html('Problem connecting to server ... please try again');
                }
            });
        }
    });

    $('body').on('click', '#custom-button-upload', function(e){
        e.preventDefault();
        obj_uploader = wp.media({
            title: 'Custom image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        }).on('select', function() {
            var attachment = obj_uploader.state().get('selection').first().toJSON();
            $('#category_custom_image').html('');
            $('#category_custom_image').html(
                "<img src=" + attachment.url + " style='width: 100%'>"
            );
            $('#category_custom_image_url').val(attachment.url);
            $("#custom-button-upload").hide();
            $("#custom-button-remove").show();
        })
        .open();
    });

    $(".custom-button-remove").click( function() {
        $('#category_custom_image').html('');
        $('#category_custom_image_url').val('');
        $(this).hide();
        $("#custom-button-upload").show();
    });

    // training accordions
    $(document).on('click', '#workshop-events .accordion-button', function(e){
        e.preventDefault();
        var panel = $(this).data('bs-target');
        if($(this).hasClass('collapsed')){
            $(this).removeClass('collapsed');
            $(panel).addClass('show');
        }else{
            $(this).addClass('collapsed');
            $(panel).removeClass('show');
        }
    });

    // old training redirect pages
    $(document).on('click', '.ccredir-btn', function(){
        var btn = $(this);
        var action = btn.data('action');
        var trainingID = btn.data('tid');
        var cell = btn.data('cell');
        var cellhtml = $('#ccredir-'+trainingID+'-'+cell).html();
        // collect other URL if needed
        if(action == 'linkother'){
            $('#cc-redirects-training-id').val(trainingID);
            tb_show("caption", "#TB_inline?width=600&height=300&inlineId=cc-redirects-modal&modal=true");
        }else{
            btn.html('<i class="fa-solid fa-sync fa-spin"></i>');
            $.ajax({
                type: "POST",
                url : ContextualData.rpmAjax.ajaxurl,
                dataType : "json",
                timeout : 10000,
                data: { 
                    action : "cc_redirect_update",
                    trainingID: trainingID,
                    redirAction: action
                },
                cache: false,
                timeout: 10000,
                success: function(response){
                    if( response.status == 'ok' ){
                        $('#ccredir-'+trainingID+'-archive').html( response.archive );
                        $('#ccredir-'+trainingID+'-recording').html( response.recording );
                        $('#ccredir-'+trainingID+'-other').html( response.other );
                        $('#ccredir-'+trainingID+'-message').html( response.message );
                    }else{
                        alert( response.msg );
                        $('#ccredir-'+trainingID+'-'+cell).html( cellhtml );
                    }
                },
                error: function(jqXhr, textStatus, errorMessage){
                    alert( 'Error connecting to server. Please refresh the page before you try again.' );
                    $('#ccredir-'+trainingID+'-'+cell).html( cellhtml );
                }
            });
        }
    });

    // close the redirects modal without saving
    $(document).on('click', '#cc-redirects-modal-close', function(){
        tb_remove();
    });

    // save the redirects modal changes and close the thickbox
    $(document).on('click', '#cc-redirects-save-btn', function(){
        var trainingID = $('#cc-redirects-training-id').val();
        var url = $('#cc-redirects-ext-url').val();
        if(url != ''){
            $.ajax({
                type: "POST",
                url : ContextualData.rpmAjax.ajaxurl,
                dataType : "json",
                timeout : 10000,
                data: { 
                    action : "cc_redirect_update",
                    trainingID: trainingID,
                    redirAction: 'linkother',
                    url: url
                },
                cache: false,
                timeout: 10000,
                success: function(response){
                    if( response.status == 'ok' ){
                        $('#ccredir-'+trainingID+'-archive').html( response.archive );
                        $('#ccredir-'+trainingID+'-recording').html( response.recording );
                        $('#ccredir-'+trainingID+'-other').html( response.other );
                        $('#ccredir-'+trainingID+'-message').html( response.message );
                    }else{
                        alert( response.msg );
                        $('#ccredir-'+trainingID+'-'+cell).html( cellhtml );
                    }
                },
                error: function(jqXhr, textStatus, errorMessage){
                    alert( 'Error connecting to server. Please refresh the page before you try again.' );
                    $('#ccredir-'+trainingID+'-'+cell).html( cellhtml );
                }
            });
            tb_remove();
        }
    });

    // update the redirect message
    $(document).on('submit', '#cc-redir-panel-form', function(e){
        e.preventDefault();
        $('#cc-redir-panel-form-msg').html('<i class="fa-solid fa-sync fa-spin"></i> Please wait ...');
        var heading = $('#cc-redir-panel-form-heading').val();
        var text = $('#cc-redir-panel-form-text').val();
        $.ajax({
            type: "POST",
            url : ContextualData.rpmAjax.ajaxurl,
            dataType : "json",
            timeout : 10000,
            data: { 
                action : "cc_redirect_panel_msg_save",
                heading: heading,
                text: text
            },
            cache: false,
            timeout: 10000,
            success: function(response){
                if( response.status == 'ok' ){
                    $('#cc-redir-panel-form-msg').html('Saved');
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                alert( 'Error connecting to server. Please refresh the page before you try again.' );
            }
        });
    });

    // generate the CNWL export file
    $(document).on('click', '#cnwl-export-req', function(){
        $('#cnwl-export-msg').html('<i class="fa-solid fa-sync fa-spin"></i> Assembling the data ...')
        $.ajax({
            type: "POST",
            url: ContextualData.rpmAjax.ajaxurl,
            data: { 
                action : "cnwl_generate_csv"
            },
            dataType : "json",
            cache: false,
            timeout: 20000,
            success: function(response){
                $('#cnwl-export-msg').html(response.msg);
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#cnwl-export-msg').html('Problem connecting to server ... please try again');
            }
        });
    });
    // generate the NLFT export file
    $(document).on('click', '#nlft-export-req', function(){
        $('#nlft-export-msg').html('<i class="fa-solid fa-sync fa-spin"></i> Assembling the data ...')
        $.ajax({
            type: "POST",
            url: ContextualData.rpmAjax.ajaxurl,
            data: { 
                action : "nlft_generate_csv"
            },
            dataType : "json",
            cache: false,
            timeout: 20000,
            success: function(response){
                $('#nlft-export-msg').html(response.msg);
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#nlft-export-msg').html('Problem connecting to server ... please try again');
            }
        });
    });

    // resource hub links
    function addNewLinkField() {
        $('#resource-hub-links-container').append(`
            <div class="resource-hub-link mx-2">
                <input type="text" class="resource-hub-search w-90" placeholder="Search for content...">
                <a href="javascript:void(0);" class="text-end resource-hub-link-del text-danger"><i class="fa-solid fa-trash-can"></i></a>
                <input type="hidden" class="resource-hub-post-id" name="resource_hub_links[]">
            </div>
        `);
    }

    $('#add-more-links').on('click', function () {
        addNewLinkField();
    });

    $(document).on('input', '.resource-hub-search', function () {
        let $searchField = $(this);
        let term = $searchField.val();

        if (term.length < 3) return;

        $.ajax({
            url: ContextualData.resourceHub.ajaxUrl,
            method: 'POST',
            data: {
                action: 'resource_hub_search',
                term: term,
                security: ContextualData.resourceHub.nonce
            },
            success: function (results) {
                let suggestionBox = $('<ul class="resource-hub-suggestions"></ul>');
                results.forEach(result => {
                    suggestionBox.append(`
                        <li data-id="${result.id}" data-title="${result.title}" data-type="${result.post_type}">
                            ${result.title} <small>(${result.post_type})</small>
                        </li>
                    `);
                });

                $searchField.next('.resource-hub-suggestions').remove();
                $searchField.after(suggestionBox);
            }
        });
    });

    $(document).on('click', '.resource-hub-suggestions li', function () {
        let $selected = $(this);
        let $searchField = $selected.closest('.resource-hub-link').find('.resource-hub-search');
        let $hiddenField = $selected.closest('.resource-hub-link').find('.resource-hub-post-id');

        $searchField.val($selected.data('title') + ' (' + $selected.data('type') + ')');
        $hiddenField.val($selected.data('id'));

        $selected.closest('.resource-hub-suggestions').remove();
        addNewLinkField();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.resource-hub-link').length) {
            $('.resource-hub-suggestions').remove();
        }
    });

    $(document).on('click', '.resource-hub-link-del', function(){
        var linkRow = $(this).closest('.resource-hub-link');
        linkRow.slideUp();
        linkRow.find('.resource-hub-post-id').val('');
    });

    // upsell fields
    // clicking into the field selects all the text in it
    $(document).on('click', '#upsell-workshop-search', function(){
        $(this).focus().select();
    });

    // if the user just blanks out the upsell, we'll reinstate it
    $(document).on('change', '#upsell-workshop-search', function(){
        if($(this).val() == '' && $('#upsell-workshop-id') != 0){
            $(this).val($(this).data('was'));
        }
    });

    // the upsell lookup
    $(document).on('input', '#upsell-workshop-search', function () {
        let $searchField = $(this);
        let term = $searchField.val();
        let training_id = $(this).data('training_id');

        if (term.length < 3) return;

        $.ajax({
            url: ContextualData.upsellSearch.ajaxUrl,
            method: 'POST',
            data: {
                action: 'upsell_training_search',
                term: term,
                training_id: training_id,
                security: ContextualData.upsellSearch.nonce
            },
            success: function (results) {
                let suggestionBox = $('<ul class="upsell-search-suggestions"></ul>');
                results.forEach(result => {
                    suggestionBox.append(`
                        <li data-id="${result.id}" data-title="${result.title}" data-type="${result.post_type}">
                            ${result.id}: ${result.title} <small>(${result.post_type})</small>
                        </li>
                    `);
                });

                $searchField.next('.upsell-search-suggestions').remove();
                $searchField.after(suggestionBox);
            }
        });
    });

    // apply the chosen training
    $(document).on('click', '.upsell-search-suggestions li', function () {
        let $selected = $(this);
        $('#upsell-workshop-search').val($selected.data('id') + ': (' + $selected.data('type') + ') ' + $selected.data('title'))
            .attr('data-was', $selected.data('id') + ': (' + $selected.data('type') + ') ' + $selected.data('title'));
        $('#upsell-workshop-id').val($selected.data('id'));
        $selected.closest('.upsell-search-suggestions').remove();
    });

    // delete the upsell training
    $(document).on('click', '#upsell-workshop-del', function(){
        $('#upsell-workshop-id').val(0);
        $('#upsell-workshop-search').val('').attr('data-was', '');
    });

    // delete a quiz question
    $(document).on('click', '.qqdel', function(){
        var qnum = $(this).data('qnum');
        var setting = $('#qqdelete-'+qnum).val();
        if(setting == 'no'){
            $('#qqdelete-'+qnum).val('yes');
            $('#qqdmsg-'+qnum).html('This question will be deleted when you save the quiz. Click the trash can again to un-set this action.');
        }else{
            $('#qqdelete-'+qnum).val('no');
            $('#qqdmsg-'+qnum).html('');
        }
    });

    /**
     * Course modules and sections
     */

    if ($("#post_type").val() == "course"){

        $("#modules-list").sortable({
            handle: ".module-sorter",
            update: function(event, ui) {
                let courseId = $("#post_ID").val();
                let isTemporaryCourse = $("#original_post_status").val() === "auto-draft";
                let moduleOrder = [];
                $("#modules-list li").each(function () {
                    moduleOrder.push($(this).data("module-id"));
                });
                console.log("New module order:", moduleOrder); // 👀 Check IDs here

                $.post(ContextualData.rpmAjax.ajaxurl, {
                    action: isTemporaryCourse ? "reorder_temp_modules" : "reorder_modules",
                    course_id: $("#post_ID").val(),
                    order: moduleOrder
                });
                // var order = $(this).sortable('serialize');
            }
        });

        let courseId = $("#post_ID").val(); // WordPress post ID (even for drafts)
        let originalStatus = $("#original_post_status").val(); // Detects if it's "auto-draft"
        let isNewCourse = originalStatus === "auto-draft"; // Course hasn't been saved yet

        if (isNewCourse) {
            // Use a temporary ID if the course is not fully saved
            let tempCourseId = localStorage.getItem("tempCourseId");

            if (!tempCourseId) {
                tempCourseId = "temp_" + Math.floor(Math.random() * 1000000);
                localStorage.setItem("tempCourseId", tempCourseId);
            }

            courseId = tempCourseId;

            // Send temp ID to server and store it in postmeta
            $.post(ContextualData.rpmAjax.ajaxurl, {
                action: "store_temp_course_id",
                temp_id: tempCourseId,
                post_id: $("#post_ID").val(), // Current WP post_ID (which may be temp)
            });
        } else {
            // The course is now fully saved, clear the temporary ID
            localStorage.removeItem("tempCourseId");
        }

        // Open module modal (Add)
        $(document).on("click", "#add-module", function (e) {
            e.preventDefault();
            $("#module-id").val(""); // Empty for new module
            $("#module-title").val("").focus();
            $("#module-timing").val("");
            $("#module-modal-title").text("Add Module");
            $("#module-modal").css("display", "flex").hide().fadeIn();
            $("#module-modal").data("course-id", courseId); // Store course ID
        });

        // Open Module Modal (Edit)
        /*
        $(document).on("click", ".module-title", function (e) {
            e.preventDefault();
            let moduleId = $(this).data("module-id");
            let currentTitle = $(this).text().trim();
            $("#module-id").val(moduleId);
            $("#module-title").val(currentTitle).focus();
            $("#module-modal-title").text("Edit Module");
            $("#module-modal").css("display", "flex").hide().fadeIn();
            $("#module-modal").data("course-id", courseId); // Store course ID
        });
        */

        // Open module modal for editing
        $(document).on("click", ".edit-module", function (e) {
            e.preventDefault();
            let moduleId = $(this).data("module-id");
            let moduleTitle = $(this).closest(".module-item").find(".module-title").html();
            $("#module-id").val(moduleId);
            $("#module-title").val(moduleTitle).focus();
            $('#module-modal-loading').show();
            $("#module-timing").val("");
            $("#module-modal").css("display", "flex").hide().fadeIn();
            $.post(ContextualData.rpmAjax.ajaxurl, {
                action: 'get_module',
                module_id: moduleId,
            }, function (response) {
                if(response.success){
                    const data = response.data;
                    // Fill in form fields
                    $("#module-timing").val(data.timing);
                    $("#module-modal-loading").slideUp();
                } else {
                    alert("Couldn't load module data.");
                }
            });
        });

        // Disable Sorting While Modal Is Open (for Safety)
        $("#module-modal").on("show", function() {
            $("#modules-list").sortable("disable");
        });
        $("#module-modal").on("hide", function() {
            $("#modules-list").sortable("enable");
        });

        // Save module via AJAX
        $("#save-module").on("click", function (e) {
            e.preventDefault();
            let moduleId = $("#module-id").val(); // Gets existing module ID if editing or '' if new
            let moduleTitle = $("#module-title").val().trim();
            let moduleTiming = $("#module-timing").val().trim();
            let courseId = $("#post_ID").val(); // WP auto-assigns a temp ID
            let isTempCourse = $("#original_post_status").val() === "auto-draft"; // Detect temp courses

            if (!moduleTitle) {
                alert("Please enter a module title.");
                return;
            }

            let isNewModule = !moduleId; // If moduleId is empty, we're adding a new one
            let isTempModule = moduleId && typeof moduleId === "string" && moduleId.startsWith("temp_"); // Detect temp modules

            // Scenario 1: New Temporary Module
            if (isNewModule && isTempCourse) {
                moduleId = "temp_" + Date.now() + "_" + Math.floor(Math.random() * 1000);
                var ajaxAction = "add_temp_module";
            }
            // Scenario 2: New Real Module (after course is saved)
            else if (isNewModule && !isTempCourse) {
                var ajaxAction = "add_module";
            }
            // Scenario 3: Updating an Existing Module (temp or real)
            else {
                var ajaxAction = "update_module";
            }

            // Send AJAX request to store or update the module
            $.post(ContextualData.rpmAjax.ajaxurl, {
                action: ajaxAction,
                module_id: moduleId,
                title: moduleTitle,
                timing: moduleTiming,
                course_id: courseId,
                is_temp_course: isTempCourse
            }, function (response) {
                if (response.success && response.data.module_id) {
                    const moduleId = response.data.module_id;
                    if (isNewModule) {
                        const newModule = `
                            <li class="module-item" data-module-id='${moduleId}'>
                                <a href="javascript:void(0);" class="module-sorter" title="drag to re-order"><i class="fa-solid fa-arrows-up-down"></i></a>
                                <span class='module-title' data-module-id='${moduleId}'>${moduleTitle}</span>
                                <span class="button-wrap">
                                    <button class="edit-resources button button-sml empty" data-type="module" data-id="${moduleId}" title="Resources"><i class="fa-solid fa-list-ul"></i></button>
                                    <button class='edit-module button button-sml' data-module-id='${moduleId}' title='Edit'><i class="fa-solid fa-pencil"></i></button>
                                    <button class='delete-module button button-sml text-danger' data-module-id='${moduleId}' title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                                    <button class='add-section button' data-module-id='${moduleId}' title="Add new section">+ Section</button>
                                </span>
                                <ul class='sections-list' data-module-id='${moduleId}'></ul>
                            </li>
                        `;
                        $("#modules-list").append(newModule);
                        $("#modules-list").sortable("refresh"); // makes sure that sortable fully recognises the new module
                    }else{
                        $(`li[data-module-id='${moduleId}']`).find(".module-title").html(moduleTitle);
                    }
                    // Scroll the module into view
                    let newModuleElement = $(`li[data-module-id='${moduleId}']`);
                    newModuleElement[0].scrollIntoView({
                        behavior: 'smooth',  // smooth scrolling
                        block: 'center'      // position it in the center of the viewport
                    });
                }else{
                    if(response.data.message){
                        alert(response.data.message);
                    }else{
                        alert("Update failed. Please try again.");
                    }
                }
                $(".course-modal-overlay").fadeOut();
            });
        });


        $(document).on("click", ".delete-module", function (e) {
            e.preventDefault();
            let moduleId = $(this).data("module-id");
            let courseId = $("#post_ID").val();
            let isTempModule = typeof moduleId === "string" && moduleId.startsWith("temp_");
            let moduleTitle = $(this).closest(".module-item").find(".module-title").html();

            if (!confirm("Are you sure you want to delete the "+moduleTitle+" module?")) return;

            $(`li[data-module-id='${moduleId}']`).remove(); // Remove from UI immediately

            $.post(ContextualData.rpmAjax.ajaxurl, {
                action: isTempModule ? "delete_temp_module" : "delete_module",
                module_id: moduleId,
                course_id: courseId
            });
        });

        $(".sections-list").sortable({
            handle: ".section-sorter",
            connectWith: ".sections-list", // optional: if you want to move sections between modules
            update: function(event, ui) {
                let courseId = $("#post_ID").val();
                let moduleId = $(this).data("module-id");
                let isTemporaryCourse = $("#original_post_status").val() === "auto-draft";
                let sectionOrder = [];
                $(this).find('li').each(function () {
                    sectionOrder.push($(this).data("section-id"));
                });
                console.log("New section order:", sectionOrder);

                $.post(ContextualData.rpmAjax.ajaxurl, {
                    action: isTemporaryCourse ? "reorder_temp_sections" : "reorder_sections",
                    course_id: courseId,
                    moduleId: moduleId,
                    order: sectionOrder
                });
            }
        });

        // Open section modal
        $(document).on("click", ".add-section", function (e) {
            e.preventDefault();
            $("#section-id").val(""); // empty for new section
            $("#section-title").val("");
            $("#recording_type").val("vimeo");
            $("#recording_id").val("");
            $('#chat_noupload_row').show();
            $('#chat_upload_row').hide();
            $('#uncut_vid1').val("");
            $('#zoom_gaps').val("");
            $('#zcu_msg').val("");
            $('#chat_row').hide();
            $('#zoom_chat').html("");
            $("#section-modal").css("display", "flex").hide().fadeIn().attr("data-module", $(this).data("module-id"));
            initializeSectionDatePickers();
        });

        // Open section modal for editing
        $(".edit-section").on("click", function (e) {
            e.preventDefault();
            let sectionId = $(this).data("section-id");
            let moduleId = $(this).closest('.module-item').data('module-id');
            let sectionTitle = $(this).closest(".section-item").text().trim();
            $("#section-id").val(sectionId);
            $("#section-title").val(sectionTitle);
            $('#section-modal-loading').show();
            $('#chat_noupload_row').hide();
            $('#chat_upload_row').show();
            $('#chat_row').hide();
            $('#zoom_chat').html('');
            $('#zcu_msg').html('');
            $("#section-modal").css("display", "flex").hide().fadeIn().attr("data-module", moduleId);
            initializeSectionDatePickers();
            $.post(ContextualData.rpmAjax.ajaxurl, {
                action: 'get_section',
                section_id: sectionId,
            }, function (response) {
                if(response.success){
                    const data = response.data;
                    // Fill in form fields
                    $("#recording_type").val(data.recording_type);
                    $("#recording_id").val(data.recording_id);
                    $("#section_start_date").val(data.start_time);
                    $("#section_end_date").val(data.end_time);
                    $('#zoom_gaps').val(data.zoom_chat_raw)
                    if(data.zoom_chat_chat != ''){
                        $('#zoom_chat').html(data.zoom_chat_chat);
                        $('#chat_row').slideDown();
                    }

                    $("#section-modal-loading").slideUp();
                } else {
                    alert("Couldn't load section data.");
                }
            });
        });

        // delete a section
        $(document).on("click", ".delete-section", function (e) {
            e.preventDefault();
            let sectionId = $(this).data("section-id");
            let courseId = $("#post_ID").val();
            let isTempsection = typeof sectionId === "string" && sectionId.startsWith("temp_");
            let sectionTitle = $(this).closest(".section-item").find(".section-title").html();

            if (!confirm("Are you sure you want to delete the "+sectionTitle+" section?")) return;

            $(`li[data-section-id='${sectionId}']`).remove(); // Remove from UI immediately

            $.post(ContextualData.rpmAjax.ajaxurl, {
                action: isTempsection ? "delete_temp_section" : "delete_section",
                section_id: sectionId,
                course_id: courseId
            });
        });


        // Zoom chat file upload
        $(document).on('click', '.zoom-chat-upload', function(e){
            e.preventDefault();
            var btn = $(this);
            let courseId = $("#post_ID").val();
            let sectionId = $("#section-id").val();
            var gaps = $('#zoom_gaps').val();
            var chat = $('#zoom_chat_1')[0];
            var chat2 = $('#zoom_chat_2')[0];
            var uncutV1 = $('#uncut_vid1').val();
            
            if(chat.files.length === 0){
                $('#zcu_msg').html('Select a file to upload first');
            }else{
                btn.prop('disabled', true);
                $('#zcu_msg').html('<i class="fa fa-spinner fa-spin"></i> Uploading ...');

                var formData = new FormData();
                formData.append("action", "zoom_chat_upload");
                formData.append("course", courseId);
                formData.append("section", sectionId);
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
                    url : ContextualData.rpmAjax.ajaxurl,
                    dataType : "json",
                    contentType: false,
                    processData: false,
                    timeout : 10000,
                    data: formData,
                    cache: false,
                    crossDomain: true,
                    success: function(response){
                        $('#zcu_msg').html(response.msg);
                        btn.prop('disabled', false);
                        // Clear the File Input. jQuery (and JavaScript) do not allow setting .val('') reliably across all browsers for file inputs. The most robust method is to replace the element:
                        // we do this so that we can check it later ... and also so that it is not left there for the next time the modal opens
                        var oldInput = $('#zoom_chat_1');
                        var newInput = oldInput.clone().val(''); // Ensure empty clone
                        oldInput.replaceWith(newInput);
                        oldInput = $('#zoom_chat_2');
                        newInput = oldInput.clone().val(''); // Ensure empty clone
                        oldInput.replaceWith(newInput);
                    },
                    error: function(jqXhr, textStatus, errorMessage){
                        $('#zcu_msg').html('Error connecting to server, please try again');
                    }
                });

            }
        });






        // Add or Edit Section
        $("#save-section").on("click", function (e) {
            e.preventDefault();
            let sectionId = $("#section-id").val(); // If editing, this is set; if new, it's empty
            let moduleId = $("#section-modal").attr('data-module'); // Module ID where the section is
            let courseId = $("#post_ID").val(); // WordPress assigns a temp ID before save
            let isTempCourse = $("#original_post_status").val() === "auto-draft"; // Detect temp courses
            let sectionTitle = $("#section-title").val().trim();
            let recordingType = $("#recording_type").val();
            let recordingId = $("#recording_id").val();
            // let zoomChat1 = $("#zoom_chat_1")[0];
            // let zoomChat1File = zoomChat1.files[0];
            let startDateTime = $('#section_start_date').val();
            let endDateTime = $('#section_end_date').val();

            if (!sectionTitle) {
                alert("Section title cannot be empty.");
                return;
            }

            // have we got a zoom chat upload that has not been uploaded?
            var chatFile1 = $('#zoom_chat_1')[0];
            if(chatFile1.files.length != 0){
                alert("Please upload the Zoom chat before saving the section");
                return;
            }

            let isNewSection = !sectionId; // If sectionId is empty, we're adding a new one
            let isTempSection = sectionId && typeof sectionId === "string" && sectionId.startsWith("temp_"); // Detect temp sections

            // Scenario 1: New Temporary Section
            if (isNewSection && isTempCourse) {
                sectionId = "temp_" + Date.now() + "_" + Math.floor(Math.random() * 1000);
                var ajaxAction = "add_temp_section";
            }
            // Scenario 2: New Real Section (after course is saved)
            else if (isNewSection && !isTempCourse) {
                var ajaxAction = "add_section";
            }
            // Scenario 3: Updating an Existing Section (temp or real)
            else {
                var ajaxAction = "update_section";
            }

            // Send AJAX request to store or update the section
            $.post(ContextualData.rpmAjax.ajaxurl, {
                action: ajaxAction,
                section_id: sectionId,
                module_id: moduleId,
                course_id: courseId,
                title: sectionTitle,
                recordingType: recordingType,
                recordingId: recordingId,
                startDateTime: startDateTime,
                endDateTime: endDateTime,
                is_temp_course: isTempCourse
            }, function (response) {
                if (response.success && response.data.section_id) {
                    const sectionId = response.data.section_id;
                    if (isNewSection) {
                        const newSection = `
                            <li class="section-item" data-section-id='${sectionId}'>
                                <a href="javascript:void(0);" class="section-sorter" title="drag to re-order"><i class="fa-solid fa-arrows-up-down"></i></a>
                                <span class='section-title edit-section' data-section-id='${sectionId}'>${sectionTitle}</span>
                                <span class="button-wrap">
                                    <button class="edit-resources button button-sml empty" data-type="section" data-id="${sectionId}" title="Resources"><i class="fa-solid fa-list-ul"></i></button>
                                    <button class='edit-section button button-sml' data-section-id='${sectionId}' title="Edit"><i class="fa-solid fa-pencil"></i></button>
                                    <button class='delete-section button button-sml text-danger' data-section-id='${sectionId}' title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                                </span>
                            </li>
                        `;
                        $(`ul[data-module-id='${moduleId}']`).append(newSection).addClass('has-items');
                        $(`ul[data-module-id='${moduleId}'] .sections-list`).sortable("refresh"); // makes sure that sortable fully recognises the new module
                    }else{
                        // If updating, modify the existing UI entry
                        $(`.section-title[data-section-id='${sectionId}']`).text(sectionTitle);
                    }
                    /*
                    $('#section-modal-msg').html('<div class="bg-success text-white"><i class="fa-solid fa-circle-check"></i> saved</div>');
                    setTimeout(function(){
                        $('#section-modal-msg').html('<small>* Enter all dates/times as London times</small>');
                    }, 10000);
                    */
                    $("#section-modal").fadeOut(); // Close modal after saving
                }else{
                    alert(response.message || "Error saving section. Please try again");
                }
            });



            /*
                
                // Scroll the new section into view
                let newSectionElement = $(`li[data-section-id='${sectionId}']`);
                newSectionElement[0].scrollIntoView({
                    behavior: 'smooth',  // smooth scrolling
                    block: 'center'      // position it in the center of the viewport
                });




            */

        });

        // Close modal when clicking the close button
        $(".course-modal-close, .course-modal-close-btn").on("click", function (e) {
            e.preventDefault();
            $(".course-modal-overlay").fadeOut();
        });

        // Close modal when clicking outside the modal-content
        /*
        $(".course-modal-overlay").on("click", function (e) {
            e.preventDefault();
            if ($(e.target).hasClass("course-modal-overlay")) {
                $(this).fadeOut();
            }
        });
        */

        // Tempus Dominus date/time pickers
        const earlybirdPickerEl = document.getElementById('earlybird_expiry_date_picker');
        if (earlybirdPickerEl) {
            new tempusDominus.TempusDominus(earlybirdPickerEl, {
                display: {
                    components: {
                        calendar: true,
                        date: true,
                        month: true,
                        year: true,
                        clock: false
                    }
                },
                localization: {
                    format: 'dd/MM/yyyy'
                },
                defaultDate: undefined
            });
        }else{
            console.log('no early bird date picker element');
        }

        const upsellExpiryPickerEl = document.getElementById('upsell_expiry_picker');
        if(upsellExpiryPickerEl){
            new tempusDominus.TempusDominus(upsellExpiryPickerEl, {
                display: {
                    components: {
                        calendar: true,
                        date: true,
                        month: true,
                        year: true,
                        clock: true
                    }
                },
                localization: {
                    format: 'dd/MM/yyyy HH:mm'
                },
                defaultDate: undefined // This tells Tempus Dominus: “Don’t try to guess a date if none is given.”
            });
        }else{
            console.log('no upsell expiry date picker element');
        }

    }


    // resources modal stuff ........
    $(document).on("click", ".edit-resources", function(e){
        e.preventDefault();
        var type = $(this).data('type');
        var id = $(this).data('id');
        openResourcesModal(type, id)
    });

    function openResourcesModal(type, id) {
        $('#resource-context-type').val(type);
        $('#resource-context-id').val(id);
        $("#resources-modal").css("display", "flex").hide().fadeIn();
        loadResources(type, id);
    }

    function loadResources(type, id) {
        $('#resources-table tbody').html('<tr><td colspan="3" class="text-center">Loading...</td></tr>');
        $.post(ContextualData.rpmAjax.ajaxurl, {
            action: 'get_resources',
            type: type,
            id: id,
            // _ajax_nonce: rpmAjax.nonce
        }, function(response) {
            if(response.success) {
                populateResourcesTable(response.data);
            } else {
                alert('Failed to load resources.');
            }
        });
    }

    function populateResourcesTable(resources) {
        const resourcesContent = $('#resources-content');
        resourcesContent.empty();
        if(resources.length === 0){
            // start with a blank row
            $('#resources-content').append(resourceRow());
        }else{
            resources.forEach(resource => {
                resourcesContent.append(resourceRow(resource.resource_name, resource.resource_url, resource.id));
            });
        }
    }

    function resourceRow(name = '', url = '', dbId = '') {
        return `
            <div data-db-id="${dbId}" class="row">
                <div class="col-5">
                    <input type="text" class="form-control res-name" value="${name}">
                </div>
                <div class="col-6">
                    <input type="text" class="form-control res-url" value="${url}" placeholder="https://...">
                </div>
                <div class="col-1 text-end">
                    <button class="btn btn-sm btn-danger delete-resource text-danger"><i class="fa fa-trash-can"></i></button>
                </div>
            </div>`;
    }

    // Event: add new row
    $('#add-resource-row').on('click', function (e) {
        e.preventDefault();
        $('#resources-content').append(resourceRow());
    });

    // Event: delete row
    $('#resources-table').on('click', '.delete-resource', function (e) {
        e.preventDefault();
        $(this).closest('.row').slideUp().remove();
    });

    // Event: save resources
    $('#save-resources-btn').on('click', function (e) {
        e.preventDefault();
        const type = $('#resource-context-type').val();
        const id = $('#resource-context-id').val();

        const resources = [];
        $('#resources-content .row').each(function () {
            const name = $(this).find('.res-name').val().trim();
            const url = $(this).find('.res-url').val().trim();
            const dbId = $(this).data('db-id') || null;

            if (name !== '' && url !== '') {
                resources.push({ name, url, dbId });
            }
        });

        $.post(ContextualData.rpmAjax.ajaxurl, {
            action: 'save_resources',
            type: type,
            id: id,
            resources: resources,
            // _ajax_nonce: rpmAjax.nonce
        }, function (response) {
            if (response.success) {
                $('#resources-modal').fadeOut();
                $('.edit-resources.button[data-id="'+id+'"]').removeClass('empty').addClass('full');
            } else {
                alert('Error saving resources.');
            }
        });
    });

    // Close modal
    /*
    $('.course-modal-close, .course-modal-close-btn').on('click', function () {
        $('#resources-modal').fadeOut();
    });
    */


    /**
     * Course series
     */
    if ($("#post_type").val() == "series"){
        const $list = $('#series-courses-sortable');
        const $orderInput = $('#series_courses_order');

        function updateOrderInput() {
            const ids = [];
            $list.find('.series-course-item').each(function () {
                ids.push($(this).data('id'));
            });
            $orderInput.val(JSON.stringify(ids));
        }

        $list.sortable({
            update: function () {
                updateOrderInput();
            }
        });

        $('#add-course-btn').on('click', function () {
            const selectedID = $('#add-course-to-series').val();
            const selectedText = $('#add-course-to-series option:selected').text();

            if (selectedID) {
                $list.append(
                    `<li class="series-course-item" data-id="${selectedID}">${selectedText} <a href="#" class="remove-course">Remove</a></li>`
                );
                $('#add-course-to-series option:selected').remove();
                updateOrderInput();
            }
        });

        $list.on('click', '.remove-course', function (e) {
            e.preventDefault();
            const $item = $(this).closest('.series-course-item');
            const id = $item.data('id');
            const text = $item.text().replace(' Remove', '');
            $('#add-course-to-series').append(`<option value="${id}">${text}</option>`);
            $item.remove();
            updateOrderInput();
        });

        updateOrderInput();
    }


    /**
     * Invoice domains
     */
    const nonce = ContextualData.invoiceDomains.nonce;
    const ajaxurl = ContextualData.invoiceDomains.ajaxUrl;

    // Add domain
    $('#add-domain-form').on('submit', function(e){
        e.preventDefault();
        const domain = $('#new-domain').val().trim();
        if (!/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/.test(domain)) {
            alert('Invalid domain name');
            return;
        }
        $.post(ajaxurl, {
            action: 'ccpa_manage_invoice_domain',
            nonce,
            op: 'add',
            domain
        }, function(response) {
            if (response.success) {
                // $('#ccpa_msg').html('<div class="alert alert-success">Domain added successfully (reload the page to see the current list of domains)</div>');
                const url = new URL(window.location.href);
                url.searchParams.set('ccpa_msg', 'Domain added successfully');
                window.location.href = url.toString();
            } else {
                $('#ccpa_msg').html('<div class="alert alert-danger">'+response.data+'</div>');
            }
        });
    });

    // Delete domain
    $('.delete-domain').on('click', function(){
        if (!confirm('Delete this domain?')) return;
        const row = $(this).closest('tr');
        const id = row.data('id');

        $.post(ajaxurl, {
            action: 'ccpa_manage_invoice_domain',
            nonce,
            op: 'delete',
            id
        }, function(response) {
            if (response.success) {
                row.remove();
                $('#ccpa_msg').html('<div class="alert alert-success">Domain deleted</div>');
            } else {
                $('#ccpa_msg').html('<div class="alert alert-danger">'+response.data+'</div>');
            }
        });
    });

    // Inline editing
    $('.editable-domain').on('click', function(){
        const td = $(this);
        if (td.find('input').length) return;
        const original = td.text();
        const input = $('<input type="text" class="form-control form-control-sm">').val(original);
        td.html(input);
        input.focus();
        input.on('blur keydown', function(e){
            if (e.type === 'blur' || e.key === 'Enter') {
                const newVal = input.val().trim();
                if (newVal === original) {
                    td.text(original);
                    return;
                }
                if (!/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/.test(newVal)) {
                    alert('Invalid domain format');
                    input.focus();
                    return;
                }
                const id = td.closest('tr').data('id');
                $.post(ajaxurl, {
                    action: 'ccpa_manage_invoice_domain',
                    nonce,
                    op: 'edit',
                    id,
                    domain: newVal
                }, function(response) {
                    if (response.success) {
                        td.text(newVal);
                        $('#ccpa_msg').html('<div class="alert alert-success">Domain updated</div>');
                    } else {
                        $('#ccpa_msg').html('<div class="alert alert-danger">'+response.data+'</div>');
                        td.text(original);
                    }
                });
            }
        });
    });


});

function initializeSectionDatePickers() {
    const { TempusDominus, Namespace } = window.tempusDominus;

    const startEl = document.getElementById('section_start_date_picker');
    const endEl = document.getElementById('section_end_date_picker');

    const startPicker = new TempusDominus(startEl, {
        localization: {
            format: 'dd/MM/yyyy HH:mm',
            hourCycle: 'h23'
        }
    });

    const endPicker = new TempusDominus(endEl, {
        localization: {
            format: 'dd/MM/yyyy HH:mm',
            hourCycle: 'h23'
        },
        useCurrent: false
    });

    //using event listeners
    startEl.addEventListener(Namespace.events.change, (e) => {
        endPicker.updateOptions({
            restrictions: {
                minDate: e.detail.date,
            },
        });
    });

    /*
    //using subscribe method
    const subscription = endPicker.subscribe(Namespace.events.change, (e) => {
        startPicker.updateOptions({
            restrictions: {
                maxDate: e.date,
            },
        });
    });

    // tried to link the two up so that end date was set to start date but gave up in the end ... chatgpt simply looping
    // Subscribe safely to startPicker change
    startPicker.subscribe(Namespace.events.change, (e) => {
      const picked = startPicker.dates?.picked?.[0];

      if (!picked) return; // Nothing picked yet

      const currentEnd = endPicker.dates?.picked?.[0];

      if (!currentEnd) {
        const newEnd = new Date(picked);
        newEnd.setHours(newEnd.getHours() + 2);

        endPicker.dates.setValue(newEnd);
      }

      endPicker.updateOptions({
        restrictions: {
          minDate: picked
        }
      });
    });
    */

}
