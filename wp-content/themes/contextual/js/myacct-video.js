/**
 * My account video JS
 */

jQuery(document).ready(function($) {
    var userId = null; // Set if the modal exists
    var trainingId = null; // Set when the modal opens
    var players = {}; // Object to store multiple Vimeo player instances

    // open the training modal and get the content
    if($('#myacct-training-modal').length > 0){
        const trainingModal = document.getElementById('myacct-training-modal');
        userId = $('#myacct-training-modal').data('userid');
        trainingModal.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const button = event.relatedTarget; // undefined if the modal is triggered programmatically
            if (button && button.getAttribute) {
                trainingId = button.getAttribute('data-trainingid');
            } else {
                // Fallback: get from URL parameter
                const viewParam = new URLSearchParams(window.location.search).get('view');
                if (viewParam) {
                    trainingId = viewParam;
                } else {
                    console.warn('No training ID found to load modal content.');
                    return; // No ID available, do not proceed
                }
            }

            $('#myacct-training-modal .modal-title').html('');
            $('#myacct-training-modal .modal-body').html('<div class="loading text-center"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div>');
            $.ajax({
                url : ccAjax.ajaxurl,
                type: "POST",
                dataType: "json",
                data: {
                    action: "myacct_training_modal_get",
                    trainingID: trainingId
                },
                timeout: 10000,
                cache: false,
                success: function(response){
                    if(response.status == 'ok'){
                        $('#myacct-training-modal .modal-title').html(response.title);
                        $('#myacct-training-modal .modal-body').html(response.body);
                        // we now also need to set up the videos for tracking
                        $('#myacct-training-modal .modal-body').find('iframe').each(function () {
                        	var iframe = $(this);
                        	var recid = iframe.attr('data-recid'); // this will include a module number sometimes                  5432-0
                        	var modnum = iframe.attr('data-module'); //                                                            1
                        	var source = iframe.attr('data-source'); // vimeo                                                      https://player.vimeo......
                        	var lastviewed = iframe.attr('data-lastviewed'); // d/m/Y H:i:s                                        2025-03-14 12:39:46 <-- wrong format!!!
                        	var numviews = iframe.attr('data-numviews'); //                                                        1
                        	var viewedend = iframe.attr('data-viewedend'); // yes or no                                            no
                            var viewingtime = iframe.attr('data-viewingtime'); // number of seconds                                114
                        	var statsCode = iframe.attr('data-stats'); // real module number or 9999 for the main recording        0
                        	var lastviewedtime = 0;
                        	// we need a unique id for the video
                        	var videoId = trainingId.toString() + '-' + statsCode.toString();
				            if (!players[videoId]) { // Ensure we don't create multiple instances for the same video
				                var player = new Vimeo.Player(iframe[0]); // Create Vimeo player
				                players[videoId] = {
				                	player,
                                    module: modnum,
					            	numviews: numviews,
					            	lastviewed: lastviewed,
					            	lastviewedtime: lastviewedtime,
					            	viewedend: viewedend,
                                    viewingtime: viewingtime
				                };
				                trackVimeoPlayer(videoId, player); // Start tracking
				            }
                        });
                        syncChatHeights();
                        // remove the view param from the url if it was there
                        if (window.history.replaceState) {
                            const params = new URLSearchParams(window.location.search);
                            params.delete('view');
                            const cleanUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                            window.history.replaceState({}, document.title, cleanUrl);
                        }
                    }else{
                        $('#myacct-training-modal .loading').html('Lookup failed. Please try again');
                    }
                },
                error: function(){
                    $('#myacct-training-modal .loading').html('Connection failure. Please try again');
                }
            });
        })
    }


    // Manually trigger modal if view param exists
    const viewParam = getUrlParameter('view');

    if (viewParam && $('#myacct-training-modal').length > 0) {
        // Create a fake button with data-trainingid attribute
        const dummyButton = document.createElement('button');
        dummyButton.setAttribute('data-trainingid', viewParam);

        // Manually show the modal and pass the dummy button as the event trigger
        const trainingModalEl = document.getElementById('myacct-training-modal');
        const trainingModal = new bootstrap.Modal(trainingModalEl);

        // Bootstrap doesn't provide a clean way to pass `event.relatedTarget` when calling `.show()`
        // so we use a hack to simulate it via dispatching a custom event
        trainingModalEl.addEventListener('show.bs.modal', function handler(event) {
            event.relatedTarget = dummyButton;
            trainingModalEl.removeEventListener('show.bs.modal', handler); // remove to avoid duplication
        });

        trainingModal.show();
    }





    /**
     * Tracks interactions for each video player
     */
    function trackVimeoPlayer(videoId, player) {
        let videoData = players[videoId];

        // Detect when video starts so we can add 1 to the number of times it has been played
        player.on('play', function () {
            // console.log(`Video ${videoId} started`);
            var oneMore = videoData.numviews + 1;
            sendDataToServer(videoId, { field: 'numviews', value: oneMore }); // will also update lastviewed
        });

        player.on('ended', function(){
        	sendDataToServer(videoId, { field: 'viewedend', value: 'yes' });
        });

        // Track time watched every 5 seconds
        let watchInterval = setInterval(function () {
            player.getCurrentTime().then(function (seconds) {
				// `seconds` indicates the current playback position of the video
				if(seconds > 0){
					sendDataToServer(videoId, { field: 'lastviewedtime', value: seconds }); // also updates viewing time if needed
				}
            });
        }, 5000);

        // we also need to track for chat comments
        // this time we'll track every second
        // let videoIdArray = videoId.split('-');
        var lastComment = 'zc-' + videoData.module + '-0'; // module number
        let chatInterval = setInterval(function () {
            player.getCurrentTime().then(function (seconds) {
                // `seconds` indicates the current playback position of the video
                if(seconds > 0){
                    console.log( 'module '+videoData.module+' checking chats: playback position: '+seconds );
                    lastComment = showHideComments( videoData.module, lastComment, seconds );
                }
            });
        }, 1000);


        // Stop tracking when modal is closed
        $(document).on('hidden.bs.modal', '#myacct-training-modal', function () {
            clearInterval(watchInterval);
            clearInterval(chatInterval);
            player.pause();
            delete players[videoId]; // Remove tracking data for this video
        });
    }

    /**
     * Sends user interaction data via AJAX
     */
    function sendDataToServer(videoId, data) {
        $.ajax({
            url: ccAjax.ajaxurl,
            type: 'POST',
            dataType : "json",
            timeout : 5000,
            data: {
            	action: "save_video_stats_update",
                user_id: userId,
                training_id: trainingId,
                video_id: videoId,
                ...data // Dynamically spreads properties
            },
            success: function (response) {
                // console.log(`Stats saved for video ${videoId}:`, response);
            },
            error: function (error) {
                // console.error(`Error saving stats for video ${videoId}:`, error);
            }
        });
    }

    var waitForIt = false; // wait for it to finish
    function showHideComments( modNum, lastComment, seconds ){
        if(waitForIt){
            return lastComment;
        }else{
            waitForIt = true;
            var chatWrap = $('#'+lastComment).closest('.zoom-chat-wrap');
            var lastVisibleId = $(chatWrap).find("p:visible").last().attr("id");
            var chatChanged = false;
            // console.log('showHideComments '+modNum+' '+lastComment+' '+seconds);
            var thisSecs = $('#'+lastComment).data('time');
            var prevComment = thisComment = $('#'+lastComment);
            if( thisSecs <= seconds ){
                // cycle thru showing more .........
                while ( thisSecs <= seconds ){
                    chatChanged = true;
                    thisComment.show();
                    prevComment = thisComment;
                    thisComment = thisComment.next();
                    if( thisComment.length == 0 ){
                        thisSecs = 999999;
                    }else{
                        thisSecs = thisComment.data('time');
                    }
                }
            }else if ( thisSecs > seconds ){
                // cycle back
                while ( thisSecs > seconds ){
                    chatChanged = true;
                    thisComment.hide();
                    prevComment = thisComment;
                    thisComment = thisComment.prev();
                    if( thisComment.length == 0 ){
                        thisSecs = 0;
                    }else{
                        thisSecs = thisComment.data('time');
                    }
                }
            }
            if( thisComment.length == 0 ){
                var finalDiv = prevComment.attr('id')
            }else{
                var finalDiv = thisComment.attr('id')
            }

            var nowVisibleId = $(chatWrap).find("p:visible").last().attr("id");
            if(nowVisibleId != lastVisibleId){
                // console.log("Updated last visible paragraph ID:", lastVisibleId);
                $('#zoom-chat-'+modNum).animate({ scrollTop: $('#zoom-chat-'+modNum)[0].scrollHeight}, 300);
            }

            waitForIt = false;
            return finalDiv;
        }
    }

    /*
    // use a MutationObserver to detect changes in the DOM when a paragraph's visibility is toggled.
    function observeChatWrap(chatWrap) {
        let observer = new MutationObserver(() => {
            let lastVisibleId = getLastVisibleParagraphId(chatWrap);
            // console.log("Updated last visible paragraph ID:", lastVisibleId);
            $('#'+lastVisibleId).animate({ scrollTop: chatWrap[0].scrollHeight}, 300);
        });
        observer.observe(chatWrap, { childList: true, subtree: true, attributes: true, attributeFilter: ["style"] });
    }

    // Function to initialize observers on existing and newly loaded content
    function initializeChatObservers() {
        $(".zoom-chat-wrap").each(function () {
            observeChatWrap(this);
        });
    }

    // Call this function when the page loads
    // also called after a successful ajax popup load
    $(document).ready(function () {
        initializeChatObservers();
    });

    function getLastVisibleParagraphId(chatWrap) {
        return $(chatWrap).find("p:visible").last().attr("id") || null;
    }
    */

    // JavaScript to Sync Heights
    // Since the iframe's height is determined by its aspect ratio, we use JavaScript to detect its height and set .zoom-chat-wrap accordingly.
    function syncChatHeights() {
        $(".zoom-chat-row iframe").each(function () {
            let videoHeight = $(this).height();
            if(videoHeight > 0){
                let chatWrap = $(this).closest(".row").find(".zoom-chat-wrap");
                chatWrap.css("max-height", videoHeight + "px");
            }
        });
    }
    // Run on load and window resize
    $(document).ready(syncChatHeights);
    $(window).resize(syncChatHeights);
    // also fire on accordion open
    $(document).on( "shown.bs.collapse", "#training-modules", function (event) {
        // console.log("Accordion opened:", event.target.id);
        syncChatHeights();
    });    


});

