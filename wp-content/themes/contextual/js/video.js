/**
 * Video JS ... used on the deprecated view recording page
 */
jQuery(document).ready(function($) {

	var recid = $('#rec-video').data('recid');
	var source = $('#rec-video').data('source');
	var lastvieweddt = $('#rec-video').data('lastviewed');
	var numviews = [];
	var lastviewed = [];
	var viewedend = [];
	var viewingtime = [];
	var lastviewedtime = [];
	$('.rec-video').each(function(){
		var modNum = $(this).data('module'); // 0 could mean the main vid or the first module. If there are both, the first mod vid will be 1
		numviews[modNum] = $(this).data('numviews');
		lastviewed[modNum] = '';
		viewedend[modNum] = $(this).data('viewedend');
		viewingtime[modNum] = $(this).data('viewingtime');
		lastviewedtime[modNum] = 0;
	});

	var watching = 'no';
	setInterval(addSecond, 1000);
	function addSecond(){
		if(watching != 'no'){
			viewingtime[watching] ++;
			lastviewedtime[watching] ++;
		}
	}

	if(source == 'cc'){
		$('.rec-video').on('play', function(){
			var modNum = $(this).data('module');
			watching = modNum;
			lastviewed[modNum] = lastvieweddt;
		});
		$('.rec-video').on('pause', function(){
			watching = 'no';
			saveStats();
		});
		$('.rec-video').on('ended', function(){
			var modNum = $(this).data('module');
			watching = 'no';
			viewedend[modNum] = 'yes';
			saveStats();
		});
	}else{
		$('.rec-video').each(function(){
			var modNum = $(this).data('module');
			var iframe = $(this).find('.rec-iframe').first()[0]; // we need the dom element, not the jquery element
		    var player = new Vimeo.Player(iframe);
		    var playing = 'no';
		    player.on('play', function() {
				console.log('Playing the video');
				watching = modNum;
				lastviewed[modNum] = lastvieweddt;
				playing = 'yes'
		    });
		    player.on('pause', function(){
		    	console.log('Video paused');
				watching = 'no';
				playing = 'no';
				saveStats();
		    });
		    player.on('ended', function(){
		    	console.log('Video ended');
				watching = 'no';
				viewedend[modNum] = 'yes';
				playing = 'no';
				saveStats();
		    });
		    player.getVideoTitle().then(function(title) {
				console.log('title:', title);
		    });
		    var lastComment = 'zc-'+modNum+'-0';
		    var chatMod = $(this).data('chat');
		    setInterval(function(){
		    	if( playing == 'yes' ){
					player.getCurrentTime().then(function(seconds) {
						// `seconds` indicates the current playback position of the video
						// console.log( 'playback position: '+seconds );
						lastComment = showHideComments( modNum, lastComment, seconds );
					});
		    	}
		    }, 1000);
		});
	}

	setInterval(saveStats, 30000);

	function saveStats(){
        $.ajax({
            type: "POST",
            url : ccAjax.ajaxurl,
            dataType : "json",
            timeout : 10000,
            data: { 
            	action : "save_video_stats",
            	recid: recid,
            	numviews: numviews,
            	lastviewed: lastviewed,
            	lastviewedtime: lastviewedtime,
            	viewedend: viewedend,
            	viewingtime: viewingtime
        	},
            cache: false,
            success: function(response){
            	// do nothing
            },
	        error: function(jqXhr, textStatus, errorMessage){
	        	// do nothing
	        }
        });
	}

	var waitForIt = false; // wait for it to finish
	function showHideComments( modNum, lastComment, seconds ){
		if(waitForIt){
			return lastComment;
		}else{
			waitForIt = true;
			// console.log('showHideComments '+modNum+' '+lastComment+' '+seconds);
			var thisSecs = $('#'+lastComment).data('time');
			var prevComment = thisComment = $('#'+lastComment);
			if( thisSecs <= seconds ){
				// cycle thru showing more .........
				while ( thisSecs <= seconds ){
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
			// var objDiv = document.getElementById(finalDiv);
			// objDiv.scrollTop = objDiv.scrollHeight;
			$('#zoom-chat-'+modNum).animate({ scrollTop: $('#zoom-chat-'+modNum)[0].scrollHeight}, 300);
			waitForIt = false;
			return finalDiv;
		}
	}
	
});
