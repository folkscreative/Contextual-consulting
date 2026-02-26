/***
 * General scripts for Contextual
 */
jQuery(document).ready(function($) {

    // shrink the header when scrolling
    // shrinking it reduces it in size by about 48 pixels
    function wmsShrinkHeader(){
        var viewWidth = $(window).width();
        var shrinkPoint = 200;
        if(viewWidth < 500){
            shrinkPoint = 100;
        }
        var expandPoint = shrinkPoint - 75;
        if ($(window).scrollTop() > shrinkPoint) {
            $('#masthead').addClass('shrink');
        } else if ($(window).scrollTop() < expandPoint) {
            $('#masthead').removeClass('shrink');
        }
    }
    wmsShrinkHeader();
    $(window).scroll(function() {
        wmsShrinkHeader();
    });

    // load the hero
    $('.feat-img-resp').addClass('visible');

    // show/hide the menu
    $(document).on('click', '#site-menu-trigger', function(){
        $('.site-menu-col').toggleClass('onscreen');
    });
    $(document).on('click', '#site-menu-closer', function(){
        $('.site-menu-col').removeClass('onscreen');
    });

    // change BS behaviour so that dropdowns slide instead of appearing
    // Add slideDown animation to Bootstrap dropdown when expanding.
    $('.site-menu .dropdown').on('show.bs.dropdown', function() {
        $(this).find('.dropdown-menu').first().stop(true, true).slideDown();
    });
    // Add slideUp animation to Bootstrap dropdown when collapsing.
    $('.site-menu .dropdown').on('hide.bs.dropdown', function() {
        $(this).find('.dropdown-menu').first().stop(true, true).slideUp();
    });

    // messagebar
    $(document).on('click', '#messagebar-closer', function(){
        $('#messagebar').slideUp();
        let date = new Date();
        date.setTime(date.getTime() + (60 * 60 * 1000)); // one hour
        const expires = "expires=" + date.toUTCString();
        document.cookie = "messagebar=closed; " + expires + "; path=/";
    });

    // equal height background elements
    function wmsEqualHeightBackgrounds(){
        $('.eq-height-backgrounds').each(function(){
            var $this = $(this);
            var maxHeight = -1;
            $this.find('.inner').each(function(){
                $(this).css('height','auto');
                if ($(this).height() > maxHeight){
                    maxHeight = $(this).height();
                };
            });
            $this.find('.inner').each(function(){
                $(this).height(maxHeight);
            });
        });
    }
    // matches map height to background height
    function wmsEqualHeightMap(){
        $('.eq-height-map').each(function(){
            var wrapper = $(this);
            var maxHeight = 300; // we always want a mpap of at least 300px
            wrapper.find('.inner').each(function(){
                $(this).css('height','auto');
                if ($(this).height() > maxHeight){
                    maxHeight = $(this).height();
                };
            });
            wrapper.find('.inner').each(function(){
                $(this).height(maxHeight);
            });
            wrapper.find('.google-map .canvas').each(function(){
                $(this).height(maxHeight);
            });
        });
    }
    wmsEqualHeightBackgrounds();
    wmsEqualHeightMap();
    $(window).resize(function(){
        wmsEqualHeightBackgrounds();
        wmsEqualHeightMap();
    });

    // newsletter submit
    $(document).on('click', '.newsletter-form-submit', function(e){
        e.preventDefault()
        e.stopPropagation()
        var form = $(this).closest('form');
        // form is now a jQuery object array. To access actual DOM properties you must select the first item in the array, which is the raw DOM element ...
        if (!form[0].checkValidity()) {
            form.addClass('was-validated');
        }else{
            form.addClass('was-validated');
            var firstname = form.find('input[name="firstname"]').val();
            var lastname = form.find('input[name="lastname"]').val();
            var email = form.find('input[name="email"]').val();
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                timeout : 10000,
                data: {
                    action: "wms_newsletter_submit",
                    firstname: firstname,
                    lastname: lastname,
                    email: email
                },
                cache: false,
                success: function(response){
                    $('#newsletter-form-msg')
                        .html(response.msg)
                        .removeClass('error success')
                        .addClass(response.msg_class)
                        .show();
                },
                error: function(jqXhr, textStatus, errorMessage){
                    $('#newsletter-form-msg')
                        .html('<i class="fa-solid fa-xmark"></i> Website taking too long to respond. Please try again.')
                        .removeClass('success')
                        .addClass('error')
                        .show();
                }
            });
        }
    });

    // hide the content delimter btn if the content does not overflow
    var element = document.querySelector('.content-limited');
    if(typeof(element) != 'undefined' && element != null){
        if (element.offsetHeight < element.scrollHeight ||
            element.offsetWidth < element.scrollWidth) {
            // we're overflowing, allow the button to show
        } else {
            $('.content-delimiter').hide();
            $('.content-limited').removeClass('faded');
        }
    }
    // show more content
    $(document).on('click', '.content-delimiter-btn', function(){
        $('.content-limited').css('max-height', 'none');
        $('.content-limited').removeClass('faded');
        $('.content-delimiter').hide();
    });

    // open the currency changer modal with the currently selected currency selected!
    const currChgModal = document.getElementById('chg-curr-modal')
    currChgModal.addEventListener('show.bs.modal', event => {
        // Button that triggered the modal
        const button = event.relatedTarget;
        // Extract info from data- attributes
        // const currency = button.getAttribute('data-currency');
        // currency comes from the form
        var currency = $('#currency').val();
        // Update the modal's content.
        const chgCurr = currChgModal.querySelector('.form-select');
        chgCurr.value = currency;
        // and clear out any old messages
        $('#chg-curr-msg').html('')
            .removeClass('error info');
    })

    // save clicked on the currency changed
    $(document).on('click', '#chg-curr-save', function(){
        // what's the currency now
        var currNow = $('#currency').val();
        // what's it to be set to
        var currNew = $('#chg-curr').val();
        // if unchanged, nothing to do
        if(currNew == currNow){
            $('#chg-curr-modal').modal('hide');
        }else{
            $('#chg-curr-msg').html('<i class="fa-solid fa-spinner fa-spin-pulse"></i> Looking up the prices for you ...')
                .addClass('info');            
            var currLink = $('.cc-currency-changer').first();
            // get the new price for the training
            var trainType = $('#training-type').val();
            var trainID = $('#training-id').val();
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "change_currency",
                    trainType: trainType,
                    trainID: trainID,
                    currency: currNew
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status == 'error'){
                        $('#chg-curr-msg').html(response.msg)
                            .addClass('error')
                            .removeClass('info');            
                    }else{
                        $('.from-price').html(response.price);
                        $('#currency').val(response.currency);
                        $('.currency-icon').html(response.icon);
                        $('.non-early-price').html(response.non_early);
                        $('#raw-price').val(response.raw_price);
                        $('#student-price').val(response.student_price);
                        $('.student-price').html(response.student_price_formatted);
                        $('.gift-voucher-value').html(response.gift_voucher_value);
                        for (var i = 1; i < 16; i++) {
                            if($('.event-price-'+i).length > 0){
                                $('#event-chooser-'+i).attr('data-price', response.event[i]);
                                var newPrice = $('#event-chooser-'+i).data('price');
                                // console.log('#event-chooser-'+i+' price now '+newPrice);
                                $('.event-price-'+i).html(localisePrice(response.event[i], response.currency));
                            }
                        }
                        $('#chg-curr-modal').modal('hide');
                        wmsSetCookie('ccurrency', currNew, 30);
                    }
                },
                error: function(){
                    $('#chg-curr-msg').html('Error when attempting to change currency. Please try again.')
                        .addClass('error')
                        .removeClass('info');
                }
            });
        }
    });

    // student registration switch
    $(document).on('change', '#reg-student', function(){
        if($(this).is(':checked')){
            // not unique - used on reg-form and tgsd-form
            // $('#student').val('yes');
            $('input[name="student"]').val('yes');
        }else{
            // $('#student').val('no');
            $('input[name="student"]').val('no');
        }
    });

    // open the timezone changer modal with the currently selected pretty timezone shown
    const timeChgModal = document.getElementById('chg-time-modal')
    timeChgModal.addEventListener('show.bs.modal', event => {
        // Button that triggered the modal
        const button = event.relatedTarget;
        // current ...
        var timezone = $('#user-timezone').val();
        var pTime = $('#user-prettytime').val();
        // Update the modal's content.
        $('#chg-time-pretty-now').html(pTime);
        $('#sel-prettyname').val(pTime);
        $('#sel-timezone').val(timezone);
        // and clear out any old stuff
        $('#chg-time').val('');
        $('#chg-time-msg').html('')
            .removeClass('error info');
    })

    // trigger instant timezone lookup (with timeout)
    var findTimeTimeout;
    $("#chg-time").on("keyup", function(e) {
        // the timeout stops pending searches from going to allow for fast typers
        clearTimeout(findTimeTimeout);
        var search_string = $(this).val();
        if(search_string != '' && search_string.length > 1){
            $("#chg-time-results").fadeIn();
            findTimeTimeout = setTimeout(findTimeLookup, 300);
        }else{
            $("#chg-time-results").hide();
            $("#chg-time-results").html('<div class="chg-time-result-msg"><i class="fa-solid fa-spinner fa-spin-pulse fa-2x"></i></div>');
        };
    });
    // the instant search function
    var findTimeCount = 0;
    function findTimeLookup(){
        var search_string = $("#chg-time").val();
        var thisFindTimeCount = ++findTimeCount;
        if(search_string !== ''){
            $.ajax({
                type: "POST",
                url : ccAjax.ajaxurl,
                dataType : "json",
                timeout : 10000,
                data: { 
                    action : "timezone_lookup",
                    query: search_string
                },
                cache: false,
                success: function(response){
                    // only process the final response
                    if(thisFindTimeCount == findTimeCount){
                        $("#chg-time-results").html(response.html);
                    }
                },
                error: function(jqXhr, textStatus, errorMessage){
                    if(thisFindTimeCount == findTimeCount){
                        $('#chg-time-results').html('<div class="chg-time-result-msg">Lookup '+errorMessage+' error. Please try again</div>');
                    }
                }
            });
        }return false;
    }

    // select a new timezone
    $(document).on('click', '.chg-time-result', function(){
        var newTime = $(this).html();
        $('#chg-time').val(newTime);
        $('#sel-prettyname').val($(this).data('prettyname'));
        $('#sel-timezone').val($(this).data('timezone'));
        $('#chg-time-results').fadeOut()
            .html('');
    });

    // save clicked on the timezone changed
    $(document).on('click', '#chg-time-save', function(){
        // what's the time now
        var timeNow = $('#user-timezone').val();
        var pretty = $('#user-prettytime').val();
        // what's it going to be set to
        var timeNew = $('#sel-timezone').val();
        var prettyNew = $('#sel-prettyname').val();
        if(timeNew == timeNow){
            // only the pretty name may have changed
            $('.user-timezone').html(prettyNew);
            $('#user-prettytime').val(prettyNew);
            wmsSetCookie('ptimezone', prettyNew, 30);
            $('#chg-time-modal').modal('hide');
        }else{
            var timeLink = $('.cc-timezone-changer').first();
            var trainingID = timeLink.data('id');
            var $msg = '<i class="fa-solid fa-spinner fa-spin-pulse"></i> Looking up the new start time for you ...';
            if(trainingID == 0){
                var $msg = '<i class="fa-solid fa-spinner fa-spin-pulse"></i> Looking up the time in '+prettyNew+' now ...';
            }
            $('#chg-time-msg').html('')
                .addClass('info');            
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "change_timezone",
                    trainType: timeLink.data('type'),
                    trainID: timeLink.data('id'),
                    timezone: timeNew,
                    prettyTime: prettyNew
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status == 'error'){
                        $('#chg-time-msg').html(response.msg)
                            .addClass('error')
                            .removeClass('info');            
                    }else{
                        $('.start-time').html(response.start_time);
                        $('.user-timezone').html(response.tz_pretty);
                        $('#user-prettytime').val(response.tz_pretty);
                        for (var i = 1; i < 16; i++) {
                            if($('.event-time-'+i).length > 0){
                                $('.event-time-'+i).html(response.event[i]);
                            }
                        }
                        $('#user-timezone').val(timeNew).trigger('change');
                        $('.current-time').html(response.current_time);
                        $('.locale-times').html(response.locale_times);
                        $('.locale-date').html(response.locale_date);
                        $('#chg-time-msg').html('')
                            .removeClass('info error');
                        $('#chg-time-modal').modal('hide');
                        wmsSetCookie('ctimezone', timeNew, 30);
                        wmsSetCookie('ptimezone', prettyNew, 30);
                    }
                },
                error: function(){
                    $('#chg-time-msg').html('Error when attempting to change timezone. Please try again.')
                        .addClass('error')
                        .removeClass('info');
                }
            });
        }
    });

    // open the cancellation modal with the currently selected workshop shown
    const cancellationModal = document.getElementById('cancellation-modal')
    cancellationModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        $('#cancellation-training-title').html(button.getAttribute('data-title'));
        $('#cancel-yes').attr('data-training', button.getAttribute('data-trainingid'));
        $('#cancel-yes').attr('data-type', button.getAttribute('data-type'));
        // and clear out any old messages
        $('#cancel-msg').html('');
    });

    // ok, let's cancel the training
    $(document).on('click', '#cancel-yes', function(){
        $('#cancel-msg').html('<i class="fa-solid fa-spinner fa-spin-pulse"></i> Please wait while we cancel the training ...');
        var trainingID = $('#cancel-yes').data('training');
        var trainingType = $('#cancel-yes').data('type');
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            data: {
                action: "cancel_training",
                trainingType: trainingType,
                trainingID: trainingID
            },
            timeout: 10000,
            cache: false,
            success: function(response){
                $('#cancel-msg').html(response.msg);
                if(response.status == 'ok'){
                    $('#training-'+trainingID).slideUp();
                }
            },
            error: function(){
                $('#cancel-msg').html('Error when attempting to cancel registration. Please try again.');
            }
        });
    });

    /*
    // Enable popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))
    */

    $(document).on('click', '#training-footer-btn', function(){
        $('#training-footer').slideUp();
        $('#training-footer-expanded').slideDown();
    });

    $(document).on('click', '#training-footer-closer', function(){
        $('#training-footer').slideDown();
        $('#training-footer-expanded').slideUp();
    });

    // the form that sends people to the reg page from the recordings archive page
    $(document).on('click', '.archive-reg-btn', function(){
        $('#training-id').val($(this).data('recid'));
        $('#currency').val($(this).data('curr'));
        $('#raw-price').val($(this).data('raw'));
        $('#archive-reg-form').submit();
    });

    // open the presenter modal and get the presenter's info
    if($('#presenter-modal').length > 0){
        const presenterModal = document.getElementById('presenter-modal')
        presenterModal.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const button = event.relatedTarget;
            var presenterID = button.getAttribute('data-presenter');
            $('#presenter-modal .modal-title').html('');
            $('#presenter-modal .modal-body').html('<div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div>');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "presenter_modal_get",
                    presenterID: presenterID
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#presenter-modal .modal-title').html(response.title);
                        $('#presenter-modal .modal-body').html(response.body);
                    }else{
                        $('#presenter-modal .loading').html('Failed to find presenter. Please try again');
                    }
                },
                error: function(){
                    $('#presenter-modal .loading').html('Failed to find presenter. Please try again');
                }
            });
        })
    }

    // open the workshop full schedule modal and get the times (in the right timezone)
    if($('#workshop-times-modal').length > 0){
        const timesModal = document.getElementById('workshop-times-modal')
        timesModal.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const button = event.relatedTarget;
            var workshopID = button.getAttribute('data-id');
            var timezone = $('#user-timezone').val();
            $('#workshop-times-modal .modal-title').html('');
            $('#workshop-times-modal .modal-body').html('<div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div>');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "workshop_times_modal_get",
                    workshopID: workshopID,
                    timezone: timezone
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#workshop-times-modal .modal-title').html(response.title);
                        $('#workshop-times-modal .modal-body').html(response.body);
                    }else{
                        $('#workshop-times-modal .loading').html('Failed to find details. Please try again');
                    }
                },
                error: function(){
                    $('#workshop-times-modal .loading').html('Failed to find details. Please try again');
                }
            });
        })
    }

    // open the ce credits (now all certificates) modal and get the content
    if($('#cecredits-modal').length > 0){
        const cecreditsModal = document.getElementById('cecredits-modal')
        cecreditsModal.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const button = event.relatedTarget;
            var trainingID = button.getAttribute('data-trainingID');
            var eventID = button.getAttribute('data-eventID'); // now always 0
            $('#cecredits-modal .modal-title').html('');
            $('#cecredits-modal .modal-body').html('<div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div>');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "cecredits_modal_get",
                    trainingID: trainingID,
                    eventID: eventID
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#cecredits-modal .modal-title').html(response.title);
                        $('#cecredits-modal .modal-body').html(response.body);
                    }else{
                        $('#cecredits-modal .loading').html('Lookup failed. Please try again');
                    }
                },
                error: function(){
                    $('#cecredits-modal .loading').html('Connection failure. Please try again');
                }
            });
        })
    }

    // open the training details modal and get the content
    if($('#myacct-training-dets-modal').length > 0){
        const trainingDetsModal = document.getElementById('myacct-training-dets-modal')
        trainingDetsModal.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const button = event.relatedTarget;
            var trainingID = button.getAttribute('data-trainingID');
            $('#myacct-training-dets-modal .modal-title').html('');
            $('#myacct-training-dets-modal .modal-body').html('<div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div>');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "myacct_training_dets_modal_get",
                    trainingID: trainingID
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#myacct-training-dets-modal .modal-title').html(response.title);
                        $('#myacct-training-dets-modal .modal-body').html(response.body);
                        var presenterCarousel = document.querySelector('#myacct-training-dets-modal .presenter-carousel');
                        const carousel = new bootstrap.Carousel(presenterCarousel, { interval: 5000 });
                    }else{
                        $('#myacct-training-dets-modal .loading').html('Lookup failed. Please try again');
                    }
                },
                error: function(){
                    $('#myacct-training-dets-modal .loading').html('Connection failure. Please try again');
                }
            });
        })
    }

    // the training modal stuff is included in the my account video script file

    // open the resources modal and get the content
    if($('#training-resources-modal').length > 0){
        const trainingDetsModal = document.getElementById('training-resources-modal')
        trainingDetsModal.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const button = event.relatedTarget;
            var trainingID = button.getAttribute('data-trainingID');
            var eventID = button.getAttribute('data-eventID'); // we use eventid for recordings and workshops
            $('#training-resources-modal .modal-title').html('');
            $('#training-resources-modal .modal-body').html('<div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div>');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "training_resources_modal_get",
                    trainingID: trainingID,
                    eventID: eventID
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#training-resources-modal .modal-title').html(response.title);
                        $('#training-resources-modal .modal-body').html(response.body);
                        // after it's loaded ......
                    }else{
                        $('#training-resources-modal .loading').html('Lookup failed. Please try again');
                    }
                },
                error: function(){
                    $('#training-resources-modal .loading').html('Connection failure. Please try again');
                }
            });
        })
    }

    // Sliding Open a Resources Panel
    $(document).on("click", ".toggle-resources", function () {
        $(this).closest(".training-wrap").find(".training-resources-panel").slideToggle(300); // Toggle the resources panel
    });

    // open the feedback modal and get the content
    if($('#myacct-feedback-modal').length > 0){
        const trainingDetsModal = document.getElementById('myacct-feedback-modal')
        trainingDetsModal.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const button = event.relatedTarget;
            var trainingID = button.getAttribute('data-trainingID');
            var eventID = button.getAttribute('data-eventID'); // not used
            $('#myacct-feedback-modal .modal-title').html('');
            $('#myacct-feedback-modal .modal-body').html('<div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div>');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "training_feedback_modal_get",
                    trainingID: trainingID,
                    eventID: eventID
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#myacct-feedback-modal .modal-title').html(response.title);
                        $('#myacct-feedback-modal .modal-body').html(response.body);
                        // after it's loaded ......
                    }else{
                        $('#myacct-feedback-modal .loading').html('Lookup failed. Please try again');
                    }
                },
                error: function(){
                    $('#myacct-feedback-modal .loading').html('Connection failure. Please try again');
                }
            });
        })
    }

    // open the quiz modal and get the initial content
    if($('#myacct-quiz-modal').length > 0){
        const trainingDetsModal = document.getElementById('myacct-quiz-modal')
        trainingDetsModal.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const button = event.relatedTarget;
            var trainingID = button.getAttribute('data-trainingID');
            var eventID = button.getAttribute('data-eventID');
            $('#myacct-quiz-modal .modal-title').html('');
            $('#myacct-quiz-modal .modal-body').html('<div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div>');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "quiz_modal_get",
                    trainingID: trainingID,
                    eventID: eventID
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#myacct-quiz-modal .modal-title').html(response.title);
                        $('#myacct-quiz-modal .modal-body').html(response.body);
                        // after it's loaded ......
                    }else{
                        $('#myacct-quiz-modal .loading').html('Lookup failed. Please try again');
                    }
                },
                error: function(){
                    $('#myacct-quiz-modal .loading').html('Connection failure. Please try again');
                }
            });
        })
    }

    // quiz modal question submissions (next btn)
    $(document).on('click', '#cc-quiz-form-next', function(e){
        e.preventDefault();
        var form = $('#cc-quiz-form');
        var nextBtn = $(this);
        var nextQ = nextBtn.data('nextq');
        var btnText = nextBtn.html();
        var modalBody = form.closest('.modal-body'); // Find the closest modal body
        var quizMsg = modalBody.find('.quizMsg');
        // form is now a jQuery object array. To access actual DOM properties you must select the first item in the array, which is the raw DOM element ...
        if (form[0].checkValidity()) {
            form.addClass('was-validated');
            // and submit it ...
            nextBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            formData = form.serialize();
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "quiz_question_submit",
                    nextQ: nextQ,
                    formData: formData
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        modalBody.html(response.body);
                        // quizMsg.html(response.msg).addClass('bg-success p-3 mt-2 text-white');
                    }else{
                        quizMsg.html('Submit failed. Please try again').addClass('bg-danger p-3 mt-2 text-white');
                        nextBtn.prop('disabled', false).html(btnText);
                    }
                },
                error: function(){
                    quizMsg.html('Connection failure. Please try again').addClass('bg-danger p-3 mt-2 text-white');
                    nextBtn.prop('disabled', false).html(btnText);
                }
            });

        }else{
            form.addClass('was-validated');
        }
    });

    // quiz modal back btn
    $(document).on('click', '#cc-quiz-form-back', function(e){
        e.preventDefault();
        var form = $('#cc-quiz-form');
        var backBtn = $(this);
        var prevQ = backBtn.data('prevq');
        var btnText = backBtn.html();
        var modalBody = form.closest('.modal-body'); // Find the closest modal body
        var quizMsg = modalBody.find('.quizMsg');
        // form is now a jQuery object array. To access actual DOM properties you must select the first item in the array, which is the raw DOM element ...
        // and submit it ...
        backBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
        formData = form.serialize();
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            data: {
                action: "quiz_question_back",
                prevQ: prevQ,
                formData: formData
            },
            timeout: 10000,
            cache: false,
            success: function(response){
                if(response.status == 'ok'){
                    modalBody.html(response.body);
                    // quizMsg.html(response.msg).addClass('bg-success p-3 mt-2 text-white');
                }else{
                    quizMsg.html('Request failed. Please try again').addClass('bg-danger p-3 mt-2 text-white');
                    backBtn.prop('disabled', false).html(btnText);
                }
            },
            error: function(){
                quizMsg.html('Connection failure. Please try again').addClass('bg-danger p-3 mt-2 text-white');
                backBtn.prop('disabled', false).html(btnText);
            }
        });

    });


    // my acct menu show/hide
    $(document).on('click', '#my-acct-menu-trigger', function(){
        $('#myacct-mob-menu').stop(true, true).slideToggle(300); // .stop(true, true): Prevents animation queue buildup.
        $(this).toggleClass('open');
    });


    // ajax error handling
    $(document).ajaxError(function(event, jqxhr, settings, thrownError){
        // console.log(event);
        // console.log(jqxhr);
        // console.log(settings);
        // console.log(thrownError);
        // console.log(settings.data);
        // console.log(jqxhr.status);
        // console.log(jqxhr.responseText);
        if(settings.data.startsWith('action=ajax_error_log') || settings.data.startsWith('action=save_video_stats')){
            // do nothing
        }else{
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "ajax_error_log",
                    data: settings.data,
                    thrownError: thrownError
                },
                timeout: 25000,
                cache: false,
                success: function(response){
                    console.log('logged');
                },
                error: function(){
                    console.log('not logged');
                }
            });
        }
    });

    // ajax error testing
    $(document).on('click', '#ajax-error-test', function(){
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            data: {
                action: "not_a_real_action",
                field1: 'one',
                field2: 'two'
            },
            timeout: 5000,
            cache: false,
            success: function(response){
                console.log('Surprisingly, it worked!');
            },
            error: function(){
                console.log('failed, as expected');
            }
        });
    });

    // feedback form
    /*
    $(document).on('submit', '#cc-feedback-form', function(e){
        e.preventDefault();
        var form = $('#cc-feedback-form');
        var source = form.data('source');
        var modalBody;
        if(source == 'modal'){
            modalBody = form.closest('.modal-body'); // Find the closest modal body
        }
        var feedbackMsg = $('#cc-feedback-msg');
        feedbackMsg.html('').removeClass('bg-danger bg-success p-3 mt-2');
        // form is now a jQuery object array. To access actual DOM properties you must select the first item in the array, which is the raw DOM element ...
        if (form[0].checkValidity()) {
            form.addClass('was-validated');
            // and submit it ...
            $('#feedback-submit-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            data = form.serialize();
            var trainingID = $('#feedback-submit-btn').data('trainingid');
            var eventID = $('#feedback-submit-btn').data('eventid');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "feedback_submit",
                    trainingID: trainingID,
                    eventID: eventID,
                    formData: data
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        feedbackMsg.html(response.msg).addClass('bg-success p-3 mt-2 text-white');
                    }else{
                        feedbackMsg.html('Submit failed. Please try again').addClass('bg-danger p-3 mt-2 text-white');
                        $('#feedback-submit-btn').prop('disabled', false).html('Submit');
                    }
                },
                error: function(){
                    feedbackMsg.html('Connection failure. Please try again').addClass('bg-danger p-3 mt-2 text-white');
                    $('#feedback-submit-btn').prop('disabled', false).html('Submit');
                }
            });

        }else{
            form.addClass('was-validated');
            feedbackMsg.html('Please answer all questions').addClass('bg-danger p-3 mt-2 text-white');
            if(source == 'modal'){
                // Ensure the modal scrolls to the message
                modalBody.animate({
                    scrollTop: feedbackMsg.offset().top - modalBody.offset().top + modalBody.scrollTop()
                }, 500);
            }
       }
    });
    */


    $(document).on('submit', '#cc-feedback-form', function(e){
        var form = $('#cc-feedback-form');
        var source = form.data('source'); // 'modal' or 'page'
        var feedbackMsg = $('#cc-feedback-msg');
        var modalBody = (source === 'modal') ? form.closest('.modal-body') : null;

        // Clear previous messages
        feedbackMsg.html('').removeClass('bg-danger bg-success p-3 mt-2');

        // Always validate manually
        if (!form[0].checkValidity()) {
            e.preventDefault(); // Stop submission in both cases
            form.addClass('was-validated');

            feedbackMsg.html('Please answer all questions').addClass('bg-danger p-3 mt-2 text-white');

            // Scroll to first invalid field
            var firstInvalid = form[0].querySelector(':invalid');
            if (firstInvalid) {
                var offset = $(firstInvalid).offset().top;

                if (source === 'modal') {
                    modalBody.animate({
                        scrollTop: offset - modalBody.offset().top + modalBody.scrollTop() - 20
                    }, 500);
                } else {
                    $('html, body').animate({
                        scrollTop: offset - 100
                    }, 500);
                }
            }

            return;
        }

        form.addClass('was-validated');

        if (source === 'modal') {
            e.preventDefault();

            $('#feedback-submit-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            var data = form.serialize();
            var trainingID = $('#feedback-submit-btn').data('trainingid');
            var eventID = $('#feedback-submit-btn').data('eventid');

            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "feedback_submit",
                    trainingID: trainingID,
                    eventID: eventID,
                    formData: data
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status === 'ok'){
                        feedbackMsg.html(response.msg).addClass('bg-success p-3 mt-2 text-white');
                    } else {
                        feedbackMsg.html('Submit failed. Please try again').addClass('bg-danger p-3 mt-2 text-white');
                        $('#feedback-submit-btn').prop('disabled', false).html('Submit');
                    }
                },
                error: function(){
                    feedbackMsg.html('Connection failure. Please try again').addClass('bg-danger p-3 mt-2 text-white');
                    $('#feedback-submit-btn').prop('disabled', false).html('Submit');
                }
            });
        }
        // if not modal, normal form submission happens
    });




    /* testimonials carousel */
    $('.carousel[data-type="multi"] .carousel-item').each(function() {
        var next = $(this).next();
        if (!next.length) {
            next = $(this).siblings(':first');
        }
        next.children(':first-child').clone().appendTo($(this));

        for (var i = 0; i < 3; i++) {
            next = next.next();
            if (!next.length) {
                next = $(this).siblings(':first');
            }

            next.children(':first-child').clone().appendTo($(this));
        }
    });

    // blog search filter panel control
    $(document).on('click', '#blog-sf-trigger', function(){
        $('#blog-sf-panel-row').toggleClass('open closed');
    });

    // blog category selection
    $(document).on('click', '#bsf-all', function(){
        $('.btn-check-cat').prop('checked', false);
    });

    $(document).on('click', '.btn-check-cat', function(){
        $('#bsf-all').prop('checked', false);
    });

    // taxonomies
    $(document).on('click', '#tax-iss-all', function(){
        if($(this).is(':checked')){
            $('.btns-iss .btn-check').prop('checked', true);
        }else{
            $('.btns-iss .btn-check').prop('checked', false);
        }
    });
    $(document).on('click', '.btns-iss .btn-check', function(){
        if ($('.btns-iss .btn-check:checked').length == $('.btns-iss .btn-check').length) {
            // all checked
            $('#tax-iss-all').prop('checked', true);
        }else{
            $('#tax-iss-all').prop('checked', false);
        }
    });
    $(document).on('click', '#tax-app-all', function(){
        if($(this).is(':checked')){
            $('.btns-app .btn-check').prop('checked', true);
        }else{
            $('.btns-app .btn-check').prop('checked', false);
        }
    });
    $(document).on('click', '.btns-app .btn-check', function(){
        if ($('.btns-app .btn-check:checked').length == $('.btns-app .btn-check').length) {
            // all checked
            $('#tax-app-all').prop('checked', true);
        }else{
            $('#tax-app-all').prop('checked', false);
        }
    });
    $(document).on('click', '#tax-rtp-all', function(){
        if($(this).is(':checked')){
            $('.btns-rtp .btn-check').prop('checked', true);
        }else{
            $('.btns-rtp .btn-check').prop('checked', false);
        }
    });
    $(document).on('click', '.btns-rtp .btn-check', function(){
        if ($('.btns-rtp .btn-check:checked').length == $('.btns-rtp .btn-check').length) {
            // all checked
            $('#tax-rtp-all').prop('checked', true);
        }else{
            $('#tax-rtp-all').prop('checked', false);
        }
    });
    $(document).on('click', '#tax-oth-all', function(){
        if($(this).is(':checked')){
            $('.btns-oth .btn-check').prop('checked', true);
        }else{
            $('.btns-oth .btn-check').prop('checked', false);
        }
    });
    $(document).on('click', '.btns-oth .btn-check', function(){
        if ($('.btns-oth .btn-check:checked').length == $('.btns-oth .btn-check').length) {
            // all checked
            $('#tax-oth-all').prop('checked', true);
        }else{
            $('#tax-oth-all').prop('checked', false);
        }
    });
    $(document).on('click', '#tax-lev-all', function(){
        if($(this).is(':checked')){
            $('.btns-lev .btn-check').prop('checked', true);
        }else{
            $('.btns-lev .btn-check').prop('checked', false);
        }
    });
    $(document).on('click', '.btns-lev .btn-check', function(){
        if ($('.btns-lev .btn-check:checked').length == $('.btns-lev .btn-check').length) {
            // all checked
            $('#tax-lev-all').prop('checked', true);
        }else{
            $('#tax-lev-all').prop('checked', false);
        }
    });

    // ACT Values Cards - quantity change
    $(document).on('change', '#avc-packs', function(){
        var price = $('#avc-pack-price').val();
        var qty = $(this).val();
        var tot = price * qty;
        var currency = $('#avc-currency').val();
        var locale = $('#avc-locale').val();
        $('#avc-pack-total').html( tot.toLocaleString( locale, { style: 'currency', currency: currency } ) );
    });

    // ACT Value Cards - change currency
    $(document).on('click', '.avc-currency-switch', function(){
        var currency = $('#avc-currency').val();
        var newCurr = $(this).data('currency');
        if(currency != newCurr){
            var newLocale = $(this).data('locale');
            var newPrice = $(this).data('price');
            $('#avc-pack-price').val(newPrice);
            $('#avc-currency').val(newCurr);
            $('#avc-locale').val(newLocale);
            $('.avc-currency-switch-wrap').removeClass('currency-in-use');
            $('#avc-currency-switch-wrap-'+newCurr).addClass('currency-in-use');
            $('#avc-pack-price-display').html( newPrice.toLocaleString( newLocale, { style: 'currency', currency: newCurr } ) );
            $('#avc-packs').trigger('change');
        }
    });

    // ACT Value Cards - Next/Back
    $(document).on('click', '.avc-submit-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        var direction = $(this).data('avc_action');
        if( direction == 'back' ){
            $('#avc_action').val('back');
        }
        var form = $('#avc-order-form');
        // form is now a jQuery object array. To access actual DOM properties you must select the first item in the array, which is the raw DOM element ...
        if( direction == 'next' && !form[0].checkValidity() ) {
            form.addClass('was-validated');
        }else{
            if( direction == 'next' ){
                form.addClass('was-validated');
            }
            var data = form.serialize();
            $('#act-value-cards-panel').html('<p class="m-5 text-center"><i class="fa-solid fa-spinner fa-spin fa-3x"></i>');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                timeout : 10000,
                data: data,
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#act-value-cards-panel').html(response.panel);
                        ccScrollTo('act-value-cards-panel');
                        if($('#card-element').length > 0){
                            initiateStripe();
                        }
                    }else{
                        // $('#reg-pay-msg').removeClass('bg-info bg-success').addClass('bg-danger').html(response.msg);
                    }
                },
                error: function(jqXhr, textStatus, errorMessage){
                    $('#avc-panel-msg').removeClass('bg-info bg-success').addClass('bg-danger').html('<i class="fa-solid fa-triangle-exclamation"></i> This is taking a long time. This might be caused by a poor or slow connection. Please try again.');
                }
            });
        }
    });

    // we need some global scope
    var stripe;
    var elements;
    var card;

    // ACT value cards - payment method
    $(document).on('click', '.avc-pay-method-chooser', function(){
        var payMethod = $('#pay_method').val();
        var newMethod = $(this).data('method');
        if( payMethod != newMethod ){
            $('#pay_method').val(newMethod);
            $('#avc-order-form').removeClass('was-validated');
            card.clear();
            if( newMethod == 'invoice' ){
                $('#avc-payment-wrap').removeClass('avc-online').addClass('avc-invoice');
                $('#avc-payment-panel-online').slideUp();
                $('#avc-payment-panel-invoice').slideDown();
                $('.pay-field').prop('required', true);
                $('#avc-order-form').prop('novalidate', false);
            }else{
                $('#avc-payment-wrap').removeClass('avc-invoice').addClass('avc-online');
                $('#avc-payment-panel-invoice').slideUp();
                $('#avc-payment-panel-online').slideDown();
                $('.pay-field').prop('required', false).val('');
                $('#avc-order-form').prop('novalidate', true);
            }
        }
    });

    // ACT Value Cards - initiate Stripe
    function initiateStripe(){
        // set up the form
        stripe = Stripe($('#stripe-public').val());
        elements = stripe.elements();
        var style = {
            base: {
                fontSize: "16px",
                color: "#32325d",
            }
        };
        // Create an instance of the card Element.
        card = elements.create("card", {hidePostalCode: true, style: style});
        // Add an instance of the card Element into the card-element <div>.
        card.mount("#card-element");
        card.addEventListener("change", function(event) {
            $('.avc-payment-btn').prop('disabled', false);
            if (event.error) {
                $('#avc-panel-msg').removeClass('bg-info bg-success')
                    .addClass('bg-danger')
                    .html(event.error.message);
            } else {
                $('#avc-panel-msg').removeClass('bg-info bg-success bg-danger')
                    .html('');
            }
        });
    }

    // ACT Value Cards - Payment (or invoice)
    $(document).on('click', '.avc-payment-btn', function(e){
        e.preventDefault();
        e.stopPropagation();
        var btn = $(this);
        btn.prop('disabled', true);
        $('.avc-submit-btn').prop('disabled', true);
        var payMethod = $('#pay_method').val();
        if(payMethod == 'online'){
            $('#avc-panel-msg').removeClass('bg-danger bg-success').addClass('bg-info').html('<i class="fa fa-spinner fa-spin"></i> Processing your payment');
            var form = $('#avc-order-form');
            var fullName = $('#full_name').val();
            var email = $('#email').val();
            stripe.confirmCardPayment($('#client-secret').val(), {
                'payment_method': {
                    'card': card,
                    'billing_details': {
                        'name': fullName,
                        'email': email
                    }
                }
            }).then(function(result) {
                if (result.error) {
                    $('#avc-panel-msg').removeClass('bg-info bg-success').addClass('bg-danger').html('<i class="fa-solid fa-triangle-exclamation"></i> '+result.error.message);
                    btn.prop('disabled', false);
                    $('.avc-submit-btn').prop('disabled', false);
                } else {
                    // The payment has been processed!
                    card.clear();
                    if (result.paymentIntent.status === 'succeeded') {
                        recordAVCPayment();
                    }
                }
            });
        }else{
            recordAVCPayment();
        }
    });

    // record the AVC payment (or invoice)
    function recordAVCPayment(){
        var payMethod = $('#pay_method').val();
        if( payMethod == 'online' ){
            $('#avc-panel-msg').removeClass('bg-danger bg-success').addClass('bg-info').html('<i class="fa fa-spinner fa-spin"></i> Recording your payment');
        }else{
            $('#avc-panel-msg').removeClass('bg-danger bg-success').addClass('bg-info').html('<i class="fa fa-spinner fa-spin"></i> Recording your order');
        }
        var form = $('#avc-order-form');
        var data = form.serialize();
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: data,
            cache: false,
            success: function(response){
                $('#avc-panel-msg').removeClass('bg-danger bg-info').addClass('bg-success').html(response.msg);
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#avc-panel-msg').removeClass('bg-info bg-success').addClass('bg-danger').html('<i class="fa-solid fa-triangle-exclamation"></i> This is taking a long time. This might be caused by a poor or slow connection. Please try again.');
            }
        });
    }

    // the training page carousels
    /*
    let myCarousel = document.querySelectorAll('.cc-train-panel-carousel-inner .train-carousel-item');
    myCarousel.forEach((el) => {
        const minPerSlide = 4
        let next = el.nextElementSibling
        for (var i=1; i<minPerSlide; i++) {
            if (!next) {
                // wrap carousel by using first child
                next = myCarousel[0]
            }
            let cloneChild = next.cloneNode(true)
            el.appendChild(cloneChild.children[0])
            next = next.nextElementSibling
        }
    })
    */

    function activateTrainingCarousel(carouselClass){
        if(carouselClass === undefined ){
            carouselClass = '.cc-train-panel-carousel-inner';
        }
        $(carouselClass).each(function(){
            var first = $(this).children('.train-carousel-item').first();
            $(this).children('.train-carousel-item').each(function(){
                var carItem = $(this);
                var next = $(this).next();
                if(next.length == 0){
                    next = first;
                }
                for (var i = 1; i < 4; i++) {
                    next.find('.cc-train-panel-col').first().clone().appendTo(carItem);
                    next = next.next();
                    if(next.length == 0){
                        next = first;
                    }
                }
            });
        });
    }
    activateTrainingCarousel();

    /* 7/11/24
    $('#cc-train-panel-carousel-rhub-links').on('slide.bs.carousel', function (e) {
        var $e = $(e.relatedTarget);
        var idx = $e.index();
        var itemsPerSlide = 1; // Default for extra small screens
        var totalItems = $('.carousel-item').length;

        // Adjust items per slide based on screen size
        if ($(window).width() >= 1200) {
            itemsPerSlide = 4;
        } else if ($(window).width() >= 768) {
            itemsPerSlide = 2;
        }

        // Check if we need to move to the next slide
        if (idx >= totalItems - itemsPerSlide) {
            var it = itemsPerSlide - (totalItems - idx);
            for (var i = 0; i < it; i++) {
                // Append slides to end
                if (e.direction == "left") {
                    $('.carousel-item').eq(i).appendTo('.carousel-inner');
                } else {
                    $('.carousel-item').eq(0).appendTo('.carousel-inner');
                }
            }
        }
    });
    */


    // lazy loading images for the training carousels
    /*
    var lazyloadImages;    

    if ("IntersectionObserver" in window) {
        lazyloadImages = document.querySelectorAll(".lazy");
        var imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var image = entry.target;
                    if( image.classList.contains("lazy") ){
                        image.classList.remove("lazy");
                        imageObserver.unobserve(image);
                        var bgimg = image.getAttribute("data-img");
                        image.style.backgroundImage='url('+bgimg+')';
                    }
                }
            });
        });

        lazyloadImages.forEach(function(image) {
            imageObserver.observe(image);
        });
    } else {  
        var lazyloadThrottleTimeout;
        lazyloadImages = document.querySelectorAll(".lazy");

        function lazyload () {
            if(lazyloadThrottleTimeout) {
                clearTimeout(lazyloadThrottleTimeout);
            }    

            lazyloadThrottleTimeout = setTimeout(function() {
                var scrollTop = window.pageYOffset;
                lazyloadImages.forEach(function(img) {
                    if(img.offsetTop < (window.innerHeight + scrollTop)) {
                        // img.src = img.dataset.src;
                        // img.classList.remove('lazy');
                        var bgimg = img.getAttribute("data-img");
                        img.style.backgroundImage='url('+bgimg+')';
                    }
                });
                if(lazyloadImages.length == 0) { 
                    document.removeEventListener("scroll", lazyload);
                    window.removeEventListener("resize", lazyload);
                    window.removeEventListener("orientationChange", lazyload);
                }
            }, 20);
        }

        document.addEventListener("scroll", lazyload);
        window.addEventListener("resize", lazyload);
        window.addEventListener("orientationChange", lazyload);
    }
    */

    // Lazy Load Remastered
    // https://www.appelsiini.net/projects/lazyload/
    let images = document.querySelectorAll(".lazy");
    lazyload(images);

    // prize draw form
    $(document).on('submit', '#cc-prize-draw-form', function(e){
        e.preventDefault();
        e.stopPropagation();
        var form = $(this);
        form.removeClass('was-validated');
        // form is now a jQuery object array. To access actual DOM properties you must select the first item in the array, which is the raw DOM element ...
        if ( form[0].checkValidity() ) {
            var data = form.serialize();
            $('#cc-prize-draw-form-msg').html('<p class="m-5 text-center"><i class="fa-solid fa-spinner fa-spin fa-3x"></i> Submitting your entry ...</p>');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                timeout : 10000,
                data: data,
                cache: false,
                success: function(response){
                    $('#cc-prize-draw-form-msg').html( '<p class="m-5 text-center bg-success p-3 text-white">'+response.msg+'</p>' );
                    $('.cc-prize-draw-field').val('');
                },
                error: function(jqXhr, textStatus, errorMessage){
                    $('#cc-prize-draw-form-msg').html('<p class="m-5 text-center">There was a problem submitting your entry ... please try again.</p>');
                }
            });
        }else{
            form.addClass('was-validated');
        }
    });

    // resource hub page navigation
    $(document).on('click', '.cc-nav-form-link', function(e){
        e.preventDefault();
        e.stopPropagation();
        $('#rf-page-nav-form').attr('action', $(this).data('dest')).submit();
    });

    // training search
    /*
    $(document).on('click', '#tsf-form-get', function(e){
        e.preventDefault();
        e.stopPropagation();
        if($('#tsf-wrap').hasClass('empty')){
            $('#tsf-wrap').slideDown();
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                timeout : 10000,
                data: { action: "cc_tsf_get" },
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#tsf-wrap').html(response.html)
                            .removeClass('empty')
                            .addClass('open');
                        $('#tsf-got-icon').show();
                        $('#tsf-get-icon').hide();
                    }
                },
                error: function(jqXhr, textStatus, errorMessage){
                    $('#tsf-wrap').html('<p class="m-5 text-center">There was a problem connecting to the website ... please try loading the page again.</p>');
                }
            });
        }else if($('#tsf-wrap').hasClass('open')){
            $('#tsf-wrap').slideUp()
                .removeClass('open')
                .addClass('closed');
            $('#tsf-got-icon').hide();
            $('#tsf-get-icon').show();
        }else if($('#tsf-wrap').hasClass('closed')){
            $('#tsf-wrap').slideDown()
                .removeClass('closed')
                .addClass('open');
            $('#tsf-got-icon').show();
            $('#tsf-get-icon').hide();
        }
        // remove the active state
        $(this).blur();
    });
    */
    $(document).on('click', '.cc-training-search-btn', function() {
        $('#training-search-panel').addClass('open');
        $.ajax({
            url : ccAjax.ajaxurl,
            type: 'POST',
            dataType: "json",
            timeout : 10000,
            data: { action: 'cc_tsf_get' },
            cache: false,
            success: function(response) {
                if(response.status == 'ok'){
                    $('#cc-training-search-content').html(response.html);
                }
            },
            error: function() {
                $('#cc-training-search-content').html('<p class="m-5 text-center">There was a problem connecting to the website ... please try refreshing the page.</p>');
            }
        });
    });

    $(document).on('click', '#cc-training-search-close', function() {
        $('#training-search-panel').removeClass('open');
    });

    $(document).on('click', '.tsf-more-trigger', function(e){
        e.preventDefault();
        e.stopPropagation();
        var thisCol = $(this).closest('.tsf-topic');
        thisCol.find('.tsf-more').slideDown();
        $(this).hide();
        thisCol.find('.tsf-less-trigger').show();
    });
    $(document).on('click', '.tsf-less-trigger', function(e){
        e.preventDefault();
        e.stopPropagation();
        var thisCol = $(this).closest('.tsf-topic');
        thisCol.find('.tsf-more').slideUp();
        $(this).hide();
        thisCol.find('.tsf-more-trigger').show();
    });

    $(document).on('click', '#tsf-clear', function(e){
        e.preventDefault();
        e.stopPropagation();
        $('#tsf-form input:checkbox').prop('checked', false);
        $('#tsf-form input:text').val('');
        if($('#tsf-go-btn').length == 0){
            sendTrainingSearchFormData();
        }
    });

    $(document).on('click', '#tsf-toggle-sidebar', function(){
        $('#training-search-panel').toggleClass('open');
    });

    // site search
    if($('#search_term').length > 0){
        var s = $('#search_term').val();
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: { action: "cc_search_ult", s: s },
            cache: false,
            success: function(response){
                $('#cc-sr-ult-wrap').html(response.html);
                activateTrainingCarousel('.cc-sr-ult-carousel');
                // activate lazy loading
                let images = document.querySelectorAll(".cc-train-panel-ult .lazy");
                lazyload(images);
                if(response.html == ''){
                    emptySearchCheck('ult');
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#cc-sr-ult-wrap').html('<p class="m-5 text-center">There was a problem connecting to the website ... please try searching again.</p>');
            }
        });
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: { action: "cc_search_odt", s: s },
            cache: false,
            success: function(response){
                $('#cc-sr-odt-wrap').html(response.html);
                activateTrainingCarousel('.cc-sr-odt-carousel');
                let images = document.querySelectorAll(".cc-train-panel-odt .lazy");
                lazyload(images);
                if(response.html == ''){
                    emptySearchCheck('odt');
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#cc-sr-odt-wrap').html('<p class="m-5 text-center">There was a problem connecting to the website ... please try searching again.</p>');
            }
        });
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: { action: "cc_search_khub", s: s },
            cache: false,
            success: function(response){
                $('#cc-sr-khub-wrap').html(response.html);
                activateTrainingCarousel('.cc-sr-khub-carousel');
                let images = document.querySelectorAll(".cc-hub-panel-khub .lazy");
                lazyload(images);
                if(response.html == ''){
                    emptySearchCheck('khub');
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#cc-sr-khub-wrap').html('<p class="m-5 text-center">There was a problem connecting to the website ... please try searching again.</p>');
            }
        });
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: { action: "cc_search_rhub", s: s },
            cache: false,
            success: function(response){
                $('#cc-sr-rhub-wrap').html(response.html);
                activateTrainingCarousel('.cc-sr-rhub-carousel');
                let images = document.querySelectorAll(".cc-hub-panel-rhub .lazy");
                lazyload(images);
                if(response.html == ''){
                    emptySearchCheck('rhub');
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#cc-sr-rhub-wrap').html('<p class="m-5 text-center">There was a problem connecting to the website ... please try searching again.</p>');
            }
        });
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: { action: "cc_search_blog", s: s },
            cache: false,
            success: function(response){
                $('#cc-sr-blog-wrap').html(response.html);
                activateTrainingCarousel('.cc-sr-blog-carousel');
                let images = document.querySelectorAll(".cc-search-panel-blog .lazy");
                lazyload(images);
                if(response.html == ''){
                    emptySearchCheck('blog');
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#cc-sr-blog-wrap').html('<p class="m-5 text-center">There was a problem connecting to the website ... please try searching again.</p>');
            }
        });
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: { action: "cc_search_trn", s: s },
            cache: false,
            success: function(response){
                $('#cc-sr-trn-wrap').html(response.html);
                activateTrainingCarousel('.cc-sr-trn-carousel');
                let images = document.querySelectorAll(".cc-search-panel-trn .lazy");
                lazyload(images);
                if(response.html == ''){
                    emptySearchCheck('trn');
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#cc-sr-trn-wrap').html('<p class="m-5 text-center">There was a problem connecting to the website ... please try searching again.</p>');
            }
        });
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: { action: "cc_search_pag", s: s },
            cache: false,
            success: function(response){
                $('#cc-sr-pag-wrap').html(response.html);
                if(response.html == ''){
                    emptySearchCheck('pag');
                }
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#cc-sr-pag-wrap').html('<p class="m-5 text-center">There was a problem connecting to the website ... please try searching again.</p>');
            }
        });
    }

    // if no results are found from any of the site searches, we need to tell the user that
    var emptySearches = [];
    function emptySearchCheck( searchType ){
        emptySearches.push(searchType);
        if(emptySearches.length == 7){
            $('#cc-sr-ult-wrap').html('<div class="sr-placeholder text-center m-5">Nothing found. Please try a different search.</div>')
        }
    }

    // site search modal loaded
    $('#sf-modal').on('shown.bs.modal', function (event) {
        $(this).find('#search-input').focus();
    });

    // the site search instant lookup (in footer)
    const $searchInput = $('#search-input');
    const $resultsContainer = $('#search-suggestions');
    let currentRequest = null; // To keep track of the current AJAX request
    $searchInput.on('input', function() {
        const query = $.trim($searchInput.val());
        if (query.length < 2) {
            $resultsContainer.empty(); // Clear suggestions if input is less than 2 characters
            return;
        }
        // Abort the previous request if it's still ongoing
        if (currentRequest) {
            currentRequest.abort();
        }
        // Initiate a new AJAX request to fetch suggestions
        currentRequest = $.ajax({
            url: ccAjax.ajaxurl,
            method: 'GET',
            data: {
                action: 'fetch_search_suggestions',
                query: query
            },
            success: function(response) {
                displaySuggestions(response);
            }
        });
    });

    function displaySuggestions(suggestions) {
        $resultsContainer.empty(); // Clear any existing suggestions
        if (suggestions.length > 0) {
            const $ul = $('<ul></ul>');
            $.each(suggestions, function(index, term) {
                const $li = $('<li></li>').html(term);
                /*
                $li.on('click', function() {
                    $searchInput.val(term); // Fill input with clicked suggestion
                    // Submit the form or redirect to the search page
                    $('#search-form').submit(); // Change 'search-form' to your actual form ID
                });
                */
                $ul.append($li);
            });
            $resultsContainer.append($ul);
        }
    }

    // the site search instant lookup (on page)
    const $siteSearchInput = $('#site-search-input');
    const $siteResultsContainer = $('#site-search-suggestions');
    let siteCurrentRequest = null; // To keep track of the current AJAX request
    $siteSearchInput.on('input', function() {
        const query = $.trim($siteSearchInput.val());
        if (query.length < 2) {
            $siteResultsContainer.empty(); // Clear suggestions if input is less than 2 characters
            return;
        }
        // Abort the previous request if it's still ongoing
        if (siteCurrentRequest) {
            siteCurrentRequest.abort();
        }
        // Initiate a new AJAX request to fetch suggestions
        siteCurrentRequest = $.ajax({
            url: ccAjax.ajaxurl,
            method: 'GET',
            data: {
                action: 'fetch_search_suggestions',
                query: query
            },
            success: function(response) {
                siteDisplaySuggestions(response);
            }
        });
    });

    function siteDisplaySuggestions(suggestions) {
        $siteResultsContainer.empty(); // Clear any existing suggestions
        if (suggestions.length > 0) {
            const $ul = $('<ul></ul>');
            $.each(suggestions, function(index, term) {
                const $li = $('<li></li>').html(term);
                /*
                $li.on('click', function() {
                    $siteSearchInput.val(term); // Fill input with clicked suggestion
                    // Submit the form or redirect to the search page
                    $('#site-search-form').submit(); // Change 'search-form' to your actual form ID
                });
                */
                $ul.append($li);
            });
            $siteResultsContainer.append($ul);
        }
    }

    // Training Search
    // initial load
    if($('#training-search-loader').length > 0){
        var params = $('#training-search-loader').val();
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: {
                action: "cc_training_search",
                params: params
            },
            cache: false,
            success: function(response){
                $('#training-search-wrap').html(response.html);
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#training-search-wrap').html('<p class="m-5 text-center">There was a problem connecting ... please try searching again.</p>');
            }
        });        
    };

    // form update

    // Declare a variable to hold the timeout for keyword input delay
    let typingTimer;
    const typingDelay = 500; // Time in ms (500ms = 0.5s delay)

    // Function to handle form submission via AJAX
    function sendTrainingSearchFormData() {
        var btn = $('#tsf-form').data('btn');
        if(btn == 'yes'){
            // not on the main training search page ... let's get there
            submitTSF();
        }else{
            // we're on the main training search page, let's get new results
            $('#training-search-wrap').html('<p class="mt-5 text-center"><i class="fa-solid fa-spinner fa-spin-pulse fa-4x"></i></p>');
            // Serialize the form data
            const formData = $('#tsf-form').serialize();
            $.ajax({
                url: ccAjax.ajaxurl,
                type: 'POST',
                dataType: "json",
                timeout : 10000,
                data: {
                    action: 'cc_training_search_update',
                    formData: formData
                },
                success: function(response) {
                    $('#training-search-wrap').html(response.html);
                    history.pushState(null, '', response.url);
                    $('#training-search-panel').removeClass('open');
                    ccScrollTo('training-search-wrap');
                },
                error: function() {
                    $('#training-search-wrap').html('<p class="m-5 text-center">There was a problem connecting ... please try searching again.</p>');
                }
            });
        }
    }

    // Keyword field change detection with typing delay
    $(document).on('keyup', '#tsf-keyword', function() {
        clearTimeout(typingTimer); // Clear the previous timeout
        typingTimer = setTimeout(sendTrainingSearchFormData, typingDelay); // Start a new timeout
    });

    // Checkboxes change detection (for all checkboxes)
    $(document).on('change', '#tsf-form input[type=checkbox]', function() {
        sendTrainingSearchFormData(); // Send form data immediately on checkbox change
    });

    // change page
    $(document).on('click', '.tsf-page-link', function(e){
        e.preventDefault();
        e.stopPropagation();
        var params = $(this).data('params');
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: {
                action: "cc_training_search",
                params: params
            },
            cache: false,
            success: function(response){
                $('#training-search-wrap').html(response.html);
                history.pushState(null, '', response.url);
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#training-search-wrap').html('<p class="m-5 text-center">There was a problem connecting ... please try again.</p>');
            }
        });        
    });

    // Topics Search/filter
    // initial load
    if($('#topics-sf-loader').length > 0){
        var params = $('#topics-sf-loader').val();
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: {
                action: "cc_topics_search_filter",
                params: params
            },
            cache: false,
            success: function(response){
                $('#topics-sf-wrap').html(response.html);
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#topics-sf-wrap').html('<p class="m-5 text-center">There was a problem connecting ... please try searching again.</p>');
            }
        });        
    };

    $(document).on('click', '#topics-sf-toggle-sidebar', function(){
        $('#topics-sf-panel').toggleClass('open');
    });

    $(document).on('click', '#cc-topics-sf-close', function() {
        $('#topics-sf-panel').removeClass('open');
    });

    // form update

    // uses typingTimer and typingDelay from above]

    // Function to handle form submission via AJAX
    function sendTopicsSearchFormData() {
        $('#topics-sf-wrap').html('<p class="mt-5 text-center"><i class="fa-solid fa-spinner fa-spin-pulse fa-4x"></i></p>');
        // Serialize the form data
        const formData = $('#topicsf-form').serialize();
        $.ajax({
            url: ccAjax.ajaxurl,
            type: 'POST',
            dataType: "json",
            timeout : 10000,
            data: {
                action: 'cc_topics_search_update',
                formData: formData
            },
            success: function(response) {
                $('#topics-sf-wrap').html(response.html);
                history.pushState(null, '', response.url);
                $('#topics-sf-panel').removeClass('open');
                ccScrollTo('topics-sf-wrap');
            },
            error: function() {
                $('#topics-sf-wrap').html('<p class="m-5 text-center">There was a problem connecting ... please try searching again.</p>');
            }
        });
    }

    // Keyword field change detection with typing delay
    $(document).on('keyup', '#topicsf-keyword', function() {
        clearTimeout(typingTimer); // Clear the previous timeout
        typingTimer = setTimeout(sendTopicsSearchFormData, typingDelay); // Start a new timeout
    });

    // Checkboxes change detection (for all checkboxes)
    $(document).on('change', '#topicsf-form input[type=checkbox]', function() {
        sendTopicsSearchFormData(); // Send form data immediately on checkbox change
    });

    // change page
    $(document).on('click', '.topicsf-page-link', function(e){
        e.preventDefault();
        e.stopPropagation();
        var params = $(this).data('params');
        $.ajax({
            url : ccAjax.ajaxurl,
            type: "POST",
            dataType: "json",
            timeout : 10000,
            data: {
                action: "cc_topics_search_filter",
                params: params
            },
            cache: false,
            success: function(response){
                $('#topics-sf-wrap').html(response.html);
                history.pushState(null, '', response.url);
                ccScrollTo('topics-sf-wrap');
            },
            error: function(jqXhr, textStatus, errorMessage){
                $('#topics-sf-wrap').html('<p class="m-5 text-center">There was a problem connecting ... please try again.</p>');
            }
        });        
    });

    $(document).on('click', '.topicsf-more-trigger', function(e){
        e.preventDefault();
        e.stopPropagation();
        var thisCol = $(this).closest('.topicsf-topic');
        thisCol.find('.topicsf-more').slideDown();
        $(this).hide();
        thisCol.find('.topicsf-less-trigger').show();
    });
    $(document).on('click', '.topicsf-less-trigger', function(e){
        e.preventDefault();
        e.stopPropagation();
        var thisCol = $(this).closest('.topicsf-topic');
        thisCol.find('.topicsf-more').slideUp();
        $(this).hide();
        thisCol.find('.topicsf-more-trigger').show();
    });

    $(document).on('click', '#topicsf-clear', function(e){
        e.preventDefault();
        e.stopPropagation();
        $('#topicsf-form input:checkbox').prop('checked', false);
        $('#topicsf-form input:text').val('');
        if($('#topicsf-go-btn').length == 0){
            sendTopicsSearchFormData();
        }
    });


    // flexible training cards ... switch layouts
    $(document).on('click', '#ftc-panel-list', function(){
        $('#ftc-panel-grid').removeClass('active');
        $('#ftc-panel-list').addClass('active');
        $('#ftc-panel').removeClass('training-grid').addClass('training-list');
        adjustFontSize();
    });
    $(document).on('click', '#ftc-panel-grid', function(){
        $('#ftc-panel-list').removeClass('active');
        $('#ftc-panel-grid').addClass('active');
        $('#ftc-panel').removeClass('training-list').addClass('training-grid');
        adjustFontSize();
    });

    // font shrinking eg on the training flexible cards
    function adjustFontSize() {
        $('.shrink-font-container').each(function(index){
            var container = $(this);
            var contained = container.find('.shrink-font');
            var fontSize = parseFloat(contained.css('font-size'));
            while (contained.height() > container.height() || contained.width() > container.width()) {
                fontSize --;
                contained.css('fontSize', fontSize+'px');
            }
        });
    }
    window.addEventListener('resize', adjustFontSize);
    // document.addEventListener('DOMContentLoaded', adjustFontSize);
    adjustFontSize();

    // therapy contact form
    $('#therapy-contact-form').on('submit', function(e) {
        e.preventDefault();

        // Gather form data
        const formData = $(this).serialize();

        // Send form data via AJAX
        $.ajax({
            url : ccAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'therapy_contact_form_submit',
                form_data: formData
            },
            success: function(response) {
                alert('Thank you, we’ll get back to you within one business day.');
                $('#therapy-contact-form')[0].reset();
            },
            error: function() {
                alert('An error occurred. Please try again later.');
            }
        });
    });

    // enable Bootstrap tooltips
    // const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    // const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
                    
    $(document).on('click', '.resend-reg-btn', function(){
        $('#resend-reg-msg-'+paymentID).html('<i class="fa fa-spinner fa-spin"></i>');
        $('#resend-reg-msg-'+paymentID).removeClass('error success');
        var btn = $(this);
        btn.attr('disabled', true);
        var paymentID = $(this).data('paymentid');
        $.ajax({
            url : ccAjax.ajaxurl,
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

    applyAutocomplete();

    $(document).on('focus input', '#job', function () {
        $('#job-message').fadeIn();
    });

    $(document).on('change', '#nlft_borough', function(){
        var borough = $(this).val();
        if( borough == 'other' ){
            $('#other_borough_wrap').slideDown();
            $('#other_borough').prop('required', true);
        }else{
            $('#other_borough_wrap').slideUp();
            $('#other_borough').prop('required', false);
        }
    });



    // portal dashboard expansion
    $('.dashboard-toggle-details').on('click', function(e) {
        e.preventDefault();
        const period = $(this).data('period');
        const org = $(this).data('org');
        const serviceType = $(this).data('service-type');
        const borough = $(this).data('borough');
        const role = $(this).data('role');

        const detailsRow = $('#details-' + period);
        const content = $('#details-content-' + period);

        if (detailsRow.is(':visible')) {
            detailsRow.slideUp(); 
            return;
        }

        if (!detailsRow.data('loaded')) {
            $.post(ccAjax.ajaxurl, {
                action: 'get_monthly_breakdown',
                org: org,
                period: period,
                serviceType : serviceType,
                borough : borough,
                role : role
            }, function(response) {
                if (response.success) {
                    const data = response.data;
                    let html = '';
                    /*
                    let html = `
                        <div class="row mb-1">
                            <div class="col-4"><strong>Type</strong></div>
                            <div class="col-4 text-end"><strong>Count</strong></div>
                            <div class="col-3 text-end"><strong>Value</strong></div>
                        </div>
                    `;
                    */

                    if (data.length) {
                        var trainingType = 'Live';
                        data.forEach(row => {
                            // Updated logic - only 'recording' or '' types now
                            if(row.type == '' || row.type == null){
                                trainingType = 'Live';
                            }else if( row.type == 'recording' ){
                                trainingType = 'On-demand';
                            }
                            // Removed 'series' case as we no longer have that type
                            
                            html += `
                                <div class="type-block mb-2">
                                    <div class="row align-items-center">
                                        <div class="col-4">${trainingType}</div>
                                        <div class="col-4 text-end">${row.cnt}</div>
                                        <div class="col-3 text-end">&pound;${parseFloat(row.amt).toFixed(2)}</div>
                                        <div class="col-1 text-end">
                                            <a href="#" class="show-type-details small" data-org="${org}" data-period="${period}" data-type="${trainingType}" data-service-type="${serviceType}" data-borough="${borough}" data-role="${role}">
                                                <i class="fa-regular fa-square-plus"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="type-detail-list mt-2" style="display:none;"></div>
                                </div>
                            `;
                        });
                    } else {
                        html += '<p class="text-muted">No data available.</p>';
                    }

                    content.html(html);
                    detailsRow.data('loaded', true);
                } else {
                    content.html('<p class="text-danger">Error loading data.</p>');
                }
            });
        }

        $('.details-row').not(detailsRow).slideUp();
        detailsRow.slideDown();
    });

    $(document).on('click', '.show-type-details', function(e) {
        e.preventDefault();
        const $link = $(this);
        const container = $link.closest('.type-block');
        const target = container.find('.type-detail-list');
        const period = $link.data('period');
        const org = $link.data('org');
        const training_type = $link.data('type');
        const borough = $link.data('borough');
        const serviceType = $link.data('service-type');
        const role = $link.data('role');

        if (target.is(':visible')) {
            target.slideUp();
            return;
        }

        if (!target.data('loaded')) {
            target.html('<p class="text-muted">Loading...</p>');
            $.post(ccAjax.ajaxurl, {
                action: 'get_monthly_type_breakdown',
                org: org,
                period: period,
                training_type: training_type,
                borough : borough,
                serviceType : serviceType,
                role : role

            }, function(response) {
                if (response.success && response.data.length) {
                    let html = `
                        <div class="row mb-1 small pe-2">
                            <div class="col-2"><strong>Email</strong></div>
                            <div class="col-2"><strong>Training Title</strong></div>
                            <div class="col-2"><strong>Service Type</strong></div>
                            <div class="col-2"><strong>Borough</strong></div>
                            <div class="col-2"><strong>Date</strong></div>
                            <div class="col-2 text-end"><strong>Value</strong></div>
                        </div>`;
                    response.data.forEach(item => {
                        // The PHP already handles formatting with icons, strikethrough for cancelled items
                        // and adds (Series/Group) indicators, so we just output the HTML as-is
                        // Note: value already includes £ sign from PHP
                        html += `
                            <div class="row mb-2 small pe-2">
                                <div class="col-2 email-column" style=" word-break: break-word;
    overflow-wrap: anywhere;">${item.user_email}</div>
                                <div class="col-2">${item.training_title}</div>
                                <div class="col-2">${item.nlft_service_type}</div>
                                <div class="col-2">${item.nlft_borough}</div>
                                <div class="col-2">${item.reg_date}</div>
                                <div class="col-2 text-end">${item.value}</div>
                            </div>`;
                    });
                    target.html(html);
                    target.data('loaded', true);
                } else {
                    target.html('<p class="text-muted">No registrations found.</p>');
                }
            });
        }

        $('.type-detail-list').not(target).slideUp();
        target.slideDown();
    });

    // dashboard user rows
    $(document).on('click', '.dashboard-user-details', function(e) {
        e.preventDefault();
        const userId = $(this).data('user-id');
        const container = $('#user-details-' + userId);
        const target = container.find('.user-detail-list');
        let org = $(this).data('org');

        const isVisible = container.is(':visible');

        // Close all others first
        $('.user-details').not(container).slideUp();

        if (isVisible) {
            // It's already open → close it
            container.slideUp();
            return;
        }

        if (!target.data('loaded')) {
            target.html('<p class="text-muted">Loading...</p>');
            $.post(ccAjax.ajaxurl, {
                action: 'get_user_stats',  // FIXED: Changed from 'ajax_get_user_registrations' to match PHP function
                user_id: userId,
                org: org
            }, function(response) {
                if (response.success && response.data.length) {
                    let html = `
                        <div class="row mb-1 small pe-2 fw-bold">
                            <div class="col-2">Date</div>
                            <div class="col-7">Training Title</div>
                            <div class="col-2">Type</div>
                            <div class="col-1 text-end">Value</div>
                        </div>`;
                    response.data.forEach(row => {
                        // The PHP already formats training_title with icon and strikethrough for cancelled
                        // disc_amount already includes £ and strikethrough for cancelled
                        // training_type and reg_date also have strikethrough for cancelled
                        html += `
                            <div class="row mb-1 small pe-2">
                                <div class="col-2">${row.reg_date}</div>
                                <div class="col-7">${row.training_title}</div>
                                <div class="col-2">${row.training_type}</div>
                                <div class="col-1 text-end">£${row.disc_amount}</div>
                            </div>`;
                    });
                    target.html(html);
                    target.data('loaded', true);
                } else {
                    target.html('<p class="text-muted">No registrations found.</p>');
                }
            });
        }

        // Always open the selected container
        container.slideDown();
    });

    $('#show-all-users').on('click', function(e) {
        e.preventDefault();
        let org = $(this).data('org');
        $.post(ccAjax.ajaxurl, {
            action: 'ajax_get_all_user_stats',  // This one already matches correctly
            org: org
        }, function(response) {
            if (response.success) {
                // Replace the existing list with all users
                $('.user-row, .user-details, #show-all-users').remove();
                $('#user-stats-container').append(response.data); // Output full HTML rows
            }
        });
    });



    // training delivery
    const $wrapper = $('#trainingWrapper');
    const $toggle = $('#sidebarToggle');

    function isMobile() {
      return window.innerWidth <= 767;
    }

    $toggle.on('click', function () {
      if (isMobile()) {
        $wrapper.toggleClass('sidebar-visible');
      } else {
        $wrapper.toggleClass('sidebar-hidden');
      }
    });

    // Reset classes appropriately on resize
    $(window).on('resize', function () {
      if (isMobile()) {
        $wrapper.removeClass('sidebar-hidden');
      } else {
        $wrapper.removeClass('sidebar-visible');
      }
    });

    // make all card headers in a row with the class eq-height-card-headers the same height
    // eg pricing data
    $('.eq-height-card-headers').each(function() {
        var $row = $(this);
        var $headers = $row.find('.card-header');
        var maxHeight = 0;
        
        // Reset any previously set heights
        $headers.css('height', 'auto');
        
        // Find the tallest header in this row
        $headers.each(function() {
            maxHeight = Math.max(maxHeight, $(this).height());
        });
        
        // Apply the max height to all headers in this row
        $headers.height(maxHeight);
    });

    
});

// reload the training search page on browser back/forward
// Listen for the popstate event
window.addEventListener("popstate", function(event) {
    // is this the training search page
    if(jQuery('#tsf-form').length > 0){
        // Perform the AJAX request or force the page to reload
        location.reload();  // Forces a full reload
        // OR you can re-trigger your AJAX function here
        // loadAjaxContent();  // Call your AJAX content loading function
    }
});

function localisePrice(amount, currency){
    var formatter = new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: currency,
    });
    return formatter.format(amount);
}

// scroll to an element
function ccScrollTo(elementID){
    var elem = document.getElementById(elementID);
    var box = elem.getBoundingClientRect();
    // 150 for the menu bar height and a bit of space
    var yScroll = box.top + pageYOffset - 150;
    window.scrollTo({
        top: yScroll,
        left: 0,
        behavior: 'smooth'
    });
}

// Google and Facebook conversion tracking
function recordConversion(conversion){
    var fields = conversion.split('|');
    if (typeof gtag === 'function'){
        // installed
        gtag('set', 'user_data', {
            "email": fields[3]
        });
        var callback = function () { 
            if (typeof(url) != 'undefined') { 
                window.location = url; 
            } 
        }; 
        gtag('event', 'conversion', { 
            'send_to': 'AW-809305749/vveSCNeDiqYDEJWN9IED', 
            'value': fields[0], 
            'currency': fields[1], 
            'transaction_id': fields[2], 
            'event_callback': callback
        }); 
        gtag('event', 'purchase', {
            'value': fields[0], 
            'currency': fields[1], 
        })
    }
    if(typeof fbq === 'function'){
        fbq('track', 'Purchase', {currency: fields[1], value: fields[0]});
    }
}

// submit training search form
function submitTSF(){
    var form = document.getElementById('tsf-form');
    // const searchURL = new URL(document.location.origin + form.action);
    const searchURL = new URL(form.action);
    var topics = ['i','a','o','l','t','e','c'];
    var selected = [];
    topics.forEach(function(topic){
        selected = [];
        var checkboxes = document.querySelectorAll('input[name="'+topic+'[]"]:checked');
        checkboxes.forEach(function(checkbox) {
            selected.push(checkbox.value);
        });
        if(selected.length > 0){
            searchURL.searchParams.set(topic, selected.join(','));
        }
    });
    const searchfield = document.getElementById('tsf-keyword')
    const searchTerm = searchfield.value;
    if(searchTerm != ''){
        searchURL.searchParams.set('k', searchTerm);
    }
    window.location.href = searchURL.href;
    return false; // Prevent default form submission
}

// coping with "other" professions
// put it into a function so we can call it after an ajax load
function applyAutocomplete(){
    jQuery('#job').autocomplete({
        source: function(request, response) {
            jQuery.ajax({
                url : ccAjax.ajaxurl,
                method: 'GET',
                data: {
                    action: 'fetch_professions',
                    term: request.term
                },
                dataType: 'json',
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        delay: 300,
        appendTo: 'body', // default anyway, but explicit is good
        classes: {
            'ui-autocomplete': 'job-autocomplete'
        },
        select: function(event, ui) {
            jQuery('#job_id').val(ui.item.id); // Set the hidden input to the selected ID
        },
        change: function(event, ui) {
            if (!ui.item) {
                jQuery('#job_id').val(''); // Clear hidden field if free text
            }
        }
    });
}

// helper function to get a url parameter
function getUrlParameter( name ) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    const results = regex.exec(window.location.search);
    return results === null ? null : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

// helper function to escape html
function escapeHtml(text) {
    if (!text) return '';
    
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/\//g, '&#x2F;');
}
