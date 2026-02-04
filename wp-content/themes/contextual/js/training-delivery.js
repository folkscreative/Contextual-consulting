/***
 * Scripts for the training delivery page with video stats tracking
 */
console.log('=== TRAINING-DELIVERY.JS LOADED - VERSION 2.0 ===');
jQuery(document).ready(function($) {

    // Video stats tracking variables
    let currentPlayer = null;
    let videoStartTime = null;
    let totalViewingTime = 0;
    let isPlaying = false;
    let lastPlayStart = null;
    var currentVideoSection = 0;
    var videosStartedThisSession = {};  // Track which videos have been started this session
    var autoSaveInterval = null;
    var activeMessageHandler = null;

    // Initialize tracking variable for sendBeacon
    window.lastKnownCurrentTime = 0;

    // Track current time for synchronous access during unload
    setInterval(function() {
        if (currentPlayer) {
            currentPlayer.getCurrentTime().then(function(time) {
                if (time > 0) {
                    window.lastKnownCurrentTime = time;
                }
            }).catch(function() {
                // Silent fail if player not ready
            });
        }
    }, 1000);

    console.log('[VIDEO TRACKING] ✓ Initialized and running');

    // Initialize video progress data from PHP-generated data
    let videoProgressData = {};
    
    // collecting from the data attribute
    function initializeVideoProgressFromDataAttribute() {
        const container = $('#ptd-training-data');
        if (container.length && container.data('video-progress')) {
            videoProgressData = container.data('video-progress');
            // console.log('Video progress loaded from data attribute:', videoProgressData);
        }
    }

    initializeVideoProgressFromDataAttribute();

    // Get training data from data attributes
    function getTrainingData() {
        const container = $('#ptd-training-data');
        
        if (container.length === 0) {
            console.error('Training container not found');
            return null;
        }
        
        return {
            courseTitle: container.data('course-title') || '',
            userId: container.data('user-id') || null,
            trainingId: container.data('training-id') || null,
            lastWatchedVideo: container.data('last-watched-video') || null,
            // lastPosition: parseInt(container.data('last-position')) || 0
        };
    }

    // Send stats using sendBeacon for reliable delivery during page unload
    function sendBeaconStats(sectionId, activity, playheadTime, viewingTime) {
        if (!navigator.sendBeacon) {
            return false;
        }
        
        const trainingData = getTrainingData();
        if (!trainingData?.userId || !trainingData?.trainingId) {
            return false;
        }

        const statsData = {
            user_id: trainingData.userId,
            training_id: trainingData.trainingId,
            section_id: sectionId,
            activity: activity,
            playhead_time: Math.round(playheadTime),
            viewing_time: Math.round(viewingTime)
        };

        const blob = new Blob([JSON.stringify(statsData)], { 
            type: 'application/json' 
        });
        
        return navigator.sendBeacon('/wp-json/cc/v1/video-stats', blob);
    }

    // Function to update video progress locally and save to server
    function updateVideoProgress(sectionId, currentTime) {
        // Update local progress data
        videoProgressData[sectionId] = Math.round(currentTime);
        
        // console.log(`Updated progress for section ${sectionId}: ${currentTime}s`);
        
        // Debounced save to server (don't save on every second)
        clearTimeout(window.progressSaveTimeout);
        window.progressSaveTimeout = setTimeout(() => {
            saveVideoProgressToServer(sectionId, currentTime);
        }, 2000); // Save 2 seconds after last update
    }

    // Function to save video progress to server
    function saveVideoProgressToServer(sectionId, position) {
        const trainingData = getTrainingData();
        if (!trainingData || !trainingData.userId || !trainingData.trainingId) {
            console.warn('Cannot save progress: missing training data');
            return;
        }

        const progressData = {
            user_id: trainingData.userId,
            training_id: trainingData.trainingId,
            section_id: sectionId,
            position: Math.round(position)
        };

        $.ajax({
            url: '/wp-json/cc/v1/video-progress',
            method: 'POST',
            contentType: 'application/json',
            headers: {
                'X-WP-Nonce': wpApiSettings.nonce
            },
            data: JSON.stringify(progressData),
            success: function(response) {
                // console.log(`Progress saved for section ${sectionId}: ${progressData.position}s`);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.warn('Failed to save video progress:', textStatus, errorThrown);
            }
        });
    }

    // Get stored progress for a specific video section
    function getStoredProgress(sectionId) {
        return videoProgressData[sectionId] || 0;
    }

    // Send video stats to server
    function sendVideoStats(sectionId, activity, playheadTime, viewingTime) {
        const trainingData = getTrainingData();
        
        if (!trainingData || !trainingData.userId || !trainingData.trainingId) {
            console.error('Missing required training data for stats:', trainingData);
            return;
        }

        const statsData = {
            user_id: trainingData.userId,
            training_id: trainingData.trainingId,
            section_id: sectionId,
            activity: activity,
            playhead_time: Math.round(playheadTime),
            viewing_time: Math.round(viewingTime)
        };

        $.ajax({
            url: '/wp-json/cc/v1/video-stats',
            method: 'POST',
            contentType: 'application/json',
            headers: {
                'X-WP-Nonce': wpApiSettings.nonce
            },
            data: JSON.stringify(statsData),
            success: function(response) {
                // Stats sent successfully (silent)
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.warn('Failed to send video stats:', textStatus, errorThrown);
            }
        });
    }

    async function saveCurrentVideoStats() {
        if (!currentPlayer || !currentVideoSection) return;
        
        try {
            const isPaused = await currentPlayer.getPaused();
            const currentTime = await currentPlayer.getCurrentTime();
            
            // Calculate cumulative time
            let cumulativeTime = parseFloat($('#ptd-video-item-' + currentVideoSection).data('cumulative-seconds')) || 0;
            
            // If video is playing, add the time since last update
            if (!isPaused && lastPlayStart) {
                const timeSinceLastUpdate = (Date.now() - lastPlayStart) / 1000;
                cumulativeTime += timeSinceLastUpdate;
                
                // Reset lastPlayStart to current time after calculating
                lastPlayStart = Date.now();
            }
            
            sendVideoStats(currentVideoSection, 'pause', currentTime, cumulativeTime);
            saveVideoProgressToServer(currentVideoSection, currentTime);
            
            // Update the UI
            $('#ptd-video-item-' + currentVideoSection).data('cumulative-seconds', cumulativeTime);
            
            let furthestTime = parseFloat($('#ptd-video-item-' + currentVideoSection).data('furthest-seconds')) || 0;
            if (currentTime > furthestTime) {
                $('#ptd-video-item-' + currentVideoSection).data('furthest-seconds', currentTime);
            }
            
        } catch (error) {
            console.error('Error saving video stats:', error);
        }
    }

    // Create iframe-based player handler for stats tracking
    function createIframePlayerHandler(iframe, sectionId, vimeoId, expectedStartTime = 0) {
        // Clean up any existing handler for this section
        if (window.vimeoMessageHandlers && window.vimeoMessageHandlers[sectionId]) {
            window.removeEventListener('message', window.vimeoMessageHandlers[sectionId]);
            delete window.vimeoMessageHandlers[sectionId];
        }
        
        // Initialize handler storage if needed
        if (!window.vimeoMessageHandlers) {
            window.vimeoMessageHandlers = {};
        }

        let isPlaying = false;
        let currentTime = 0;
        let duration = 0;
        let hasStarted = false;
        let expectedStartPosition = expectedStartTime;
        let initializationTimeout = null;

        // Get reference to the progress bar for this video using jQuery
        const $progressBar = $(`#ptd-video-progress-bar-${sectionId}`);
        const $progressContainer = $progressBar.parent();

        // Function to set initial progress bar when video loads
        function setInitialProgress() {
            if ($progressBar.length && duration > 0 && expectedStartPosition > 0 && !hasSetInitialProgress) {
                const initialProgressPercentage = Math.min((expectedStartPosition / duration) * 100, 100);
                $progressBar.css('width', `${initialProgressPercentage}%`);
                $progressBar.attr({
                    'data-current-time': expectedStartPosition.toFixed(1),
                    'data-progress': initialProgressPercentage.toFixed(1)
                });
                hasSetInitialProgress = true;
                // console.log(`Initial progress set for section ${sectionId}: ${initialProgressPercentage.toFixed(1)}% (${expectedStartPosition.toFixed(1)}s)`);
            }
        }

        // Function to update progress bar - ONLY when playing
        function updateProgressBar() {
            if ($progressBar.length && duration > 0 && isPlaying) {
                const progressPercentage = Math.min((currentTime / duration) * 100, 100);
                $progressBar.css('width', `${progressPercentage}%`);
                
                // console.log(`Progress update for section ${sectionId}: ${progressPercentage.toFixed(1)}% (${currentTime.toFixed(1)}s / ${duration.toFixed(1)}s) - PLAYING`);
                
                $progressBar.attr({
                    'data-current-time': currentTime.toFixed(1),
                    'data-progress': progressPercentage.toFixed(1)
                });

                // Update stored progress data
                if (currentTime > 0) {
                    updateVideoProgress(sectionId, currentTime);
                }
            } else if (!isPlaying && currentTime > 0) {
                // Only log when we skip updates due to not playing
                // console.log(`Skipping progress update for section ${sectionId}: video not playing (${currentTime.toFixed(1)}s)`);
            }
        }

        // Listen for postMessage events from Vimeo iframe
        const messageHandler = function(event) {
            if (event.origin !== 'https://player.vimeo.com') {
                return;
            }

            try {
                let data;
                
                // Handle both string and object data formats
                if (typeof event.data === 'string') {
                    data = JSON.parse(event.data);
                } else if (typeof event.data === 'object' && event.data !== null) {
                    data = event.data;
                } else {
                    return; // Skip invalid data
                }
                
            
                // Check if this message is for our player
                // Adjusted for more lenient player ID checking
                const cleanVimeoId = vimeoId.split('?')[0].split('#')[0]; // Remove both query params and hash
                const expectedPlayerId = `player_${cleanVimeoId}`;
                
                // Only filter by player_id if it exists and we have multiple players
                if (data.player_id && document.querySelectorAll('iframe[src*="player.vimeo.com"]').length > 1) {
                    if (data.player_id !== expectedPlayerId) {
                        return; // Not our player
                    }
                }

                switch (data.event) {
                    case 'ready':
                        // console.log(`Vimeo player ready for section ${sectionId}`);
                        // Add a slightly longer delay and a retry mechanism to ensure player is fully initialized
                        setTimeout(() => {
                            const commands = [
                                '{"method":"addEventListener","value":"timeupdate"}',
                                '{"method":"addEventListener","value":"play"}',
                                '{"method":"addEventListener","value":"pause"}',
                                '{"method":"addEventListener","value":"ended"}',
                                '{"method":"addEventListener","value":"loaded"}',
                                '{"method":"addEventListener","value":"seeked"}',
                                '{"method":"getDuration"}'
                            ];
                            
                            commands.forEach((command, index) => {
                                setTimeout(() => {
                                    iframe.contentWindow.postMessage(command, 'https://player.vimeo.com');
                                }, index * 50); // Stagger commands
                            });
                        }, 500); // Increased from 100ms to 500ms
                        break;
                        
                    /*
                    case 'play':
                        if (!isPlaying) {
                            isPlaying = true;
                            if (!hasStarted) {
                                hasStarted = true;
                                lastPlayStart = Date.now();
                                sendVideoStats(sectionId, 'start', currentTime, totalViewingTime / 1000);
                            } else {
                                lastPlayStart = Date.now();
                            }
                            
                            // Add visual indicator that video is playing
                            $progressContainer.addClass('playing');

                            // IMPORTANT: Update progress immediately when play starts
                            updateProgressBar();
                        }
                        break;
                        */
                        

                    case 'play':
                        if (!isPlaying) {
                            isPlaying = true;
                            
                            // Get the cumulative time from the DOM (persisted from previous sessions)
                            const cumulativeTime = parseFloat($('#ptd-video-item-' + sectionId).data('cumulative-seconds')) || 0;

                            // Check if this video has been started in this session
                            if (!videosStartedThisSession[sectionId]) {
                                videosStartedThisSession[sectionId] = true;
                                lastPlayStart = Date.now();
                                
                                // When 'start' is sent, it's using seconds from getCurrentTime() which might return 0 if called too quickly after play starts.
                                // currentPlayer.getCurrentTime().then(function(seconds) {
                                //     sendVideoStats(sectionId, 'start', seconds, cumulativeTime);
                                // });
                                // Get actual current time from player, fallback to tracked currentTime or expectedStartPosition
                                currentPlayer.getCurrentTime().then(function(seconds) {
                                    // use the first non-zero of the following variables:
                                    const actualTime = seconds || currentTime || expectedStartPosition || 0;
                                    sendVideoStats(sectionId, 'start', actualTime, cumulativeTime);
                                }).catch(function() {
                                    // If getCurrentTime fails, use fallback
                                    sendVideoStats(sectionId, 'start', currentTime || expectedStartPosition || 0, cumulativeTime);
                                });

                            } else {
                                lastPlayStart = Date.now();
                                // This is a resume within the same session - no special event sent
                            }
                            
                            // Clear any existing interval
                            if (autoSaveInterval) {
                                clearInterval(autoSaveInterval);
                            }
                            
                            // Set up periodic saving every 30 seconds
                            autoSaveInterval = setInterval(function() {
                                if (isPlaying && currentTime > 0) {
                                    console.log('30-second auto-save triggered at', currentTime);
                                    
                                    // Calculate cumulative time
                                    let cumulativeTime = parseFloat($('#ptd-video-item-' + sectionId).data('cumulative-seconds')) || 0;
                                    if (lastPlayStart) {
                                        const timePlayed = (Date.now() - lastPlayStart) / 1000;
                                        cumulativeTime += timePlayed;
                                        
                                        // Update the data attribute
                                        $('#ptd-video-item-' + sectionId).data('cumulative-seconds', cumulativeTime);
                                        
                                        // Reset lastPlayStart to current time
                                        lastPlayStart = Date.now();
                                    }
                                    
                                    // Send stats and save progress
                                    sendVideoStats(sectionId, 'progress', currentTime, cumulativeTime);
                                    updateVideoProgress(sectionId, currentTime);
                                }
                            }, 30000); // 30 seconds

                            // Add visual indicator that video is playing
                            $progressContainer.addClass('playing');
                            // IMPORTANT: Update progress immediately when play starts
                            updateProgressBar();
                        }
                        break;

                    /*
                    case 'pause':
                        if (isPlaying && lastPlayStart) {
                            totalViewingTime += Date.now() - lastPlayStart;
                            isPlaying = false;
                            lastPlayStart = null;
                            sendVideoStats(sectionId, 'pause', currentTime, totalViewingTime / 1000);
                            
                            // Remove playing indicator (optional)
                            $progressContainer.removeClass('playing');

                            // Save progress immediately on pause
                            updateVideoProgress(sectionId, currentTime);
                        }
                        break;
                        */

                    case 'pause':
                        // console.log('VIDEO PAUSE EVENT FIRED for section', sectionId, 'Current time:', currentTime, 'Duration:', duration);
                        if (isPlaying && lastPlayStart) {
                            // Calculate cumulative time
                            let cumulativeTime = parseFloat($('#ptd-video-item-' + sectionId).data('cumulative-seconds')) || 0;
                            const timePlayed = (Date.now() - lastPlayStart) / 1000;
                            cumulativeTime += timePlayed;
                            
                            isPlaying = false;
                            lastPlayStart = null;

                            // Clear the auto-save interval
                            if (autoSaveInterval) {
                                clearInterval(autoSaveInterval);
                                autoSaveInterval = null;
                            }
                            
                            currentPlayer.getCurrentTime().then(function(seconds) {
                                // Check if we're at the end (within 2 seconds of duration)
                                const isAtEnd = duration > 0 && (duration - seconds) < 2;
                                
                                if (isAtEnd) {
                                    // console.log('PAUSE AT END DETECTED - sending reached_end instead');
                                    // This is actually the end of the video
                                    sendVideoStats(sectionId, 'reached_end', duration, cumulativeTime);
                                    updateVideoProgress(sectionId, duration);
                                    
                                    // Set progress to 100% when video ends
                                    $progressBar.css('width', '100%').attr('data-progress', '100');
                                    $progressContainer.removeClass('playing').addClass('completed');
                                    
                                    // Update chat visibility
                                    updateChatVisibility(duration || 999999);
                                } else {
                                    // Normal pause
                                    sendVideoStats(sectionId, 'pause', seconds, cumulativeTime);
                                    updateVideoProgress(sectionId, seconds);
                                    
                                    // Remove playing indicator
                                    $progressContainer.removeClass('playing');
                                }
                                
                                // Update the data attributes for next time
                                $('#ptd-video-item-' + sectionId).data('cumulative-seconds', cumulativeTime);
                            });
                        }
                        break;
                        
                    /*
                    case 'ended':
                        if (isPlaying && lastPlayStart) {
                            totalViewingTime += Date.now() - lastPlayStart;
                            isPlaying = false;
                            lastPlayStart = null;
                        }
                        
                        // Set progress to 100% when video ends
                        $progressBar.css('width', '100%').attr('data-progress', '100');
                        
                        sendVideoStats(sectionId, 'reached_end', currentTime, totalViewingTime / 1000);
                        
                        $progressContainer.removeClass('playing').addClass('completed');

                        updateChatVisibility(duration || 999999);

                        // Mark as completed - save final position
                        updateVideoProgress(sectionId, duration);
                        break;
                        */

                    case 'ended':
                        // console.log('VIDEO ENDED EVENT FIRED for section', sectionId);
                        // Calculate cumulative time properly
                        let cumulativeTime = parseFloat($('#ptd-video-item-' + sectionId).data('cumulative-seconds')) || 0;
                        if (isPlaying && lastPlayStart) {
                            const timePlayed = (Date.now() - lastPlayStart) / 1000;
                            cumulativeTime += timePlayed;
                            isPlaying = false;
                            lastPlayStart = null;
                        }
                        
                        // Clear the auto-save interval
                        if (autoSaveInterval) {
                            clearInterval(autoSaveInterval);
                            autoSaveInterval = null;
                        }
                        
                        // Set progress to 100% when video ends
                        $progressBar.css('width', '100%').attr('data-progress', '100');
                        
                        // Use duration as the playhead time for reached_end
                        sendVideoStats(sectionId, 'reached_end', duration, cumulativeTime);
                        
                        // Update the data attributes for next time
                        $('#ptd-video-item-' + sectionId).data('cumulative-seconds', cumulativeTime);
                        
                        $progressContainer.removeClass('playing').addClass('completed');

                        updateChatVisibility(duration || 999999);

                        // Mark as completed - save final position
                        updateVideoProgress(sectionId, duration);
                        break;

                        
                    case 'timeupdate':
                    case 'playProgress':
                        if (data.data && data.data.seconds !== undefined) {
                            currentTime = data.data.seconds;

                            // ONLY update progress bar if video is playing
                            updateProgressBar();
                            updateChatVisibility(currentTime);
                        }
                        break;
                        
                    case 'loaded':
                        // Video metadata is loaded, get duration
                        // console.log(`Video ${sectionId} loaded, requesting duration`);
                        iframe.contentWindow.postMessage('{"method":"getDuration"}', 'https://player.vimeo.com');
                        break;

                    case 'seeked':
                        if (data.data && data.data.seconds !== undefined) {
                            const seekedTo = data.data.seconds;
                            // console.log('Video seeked to:', seekedTo);
                            
                            // Save the new position immediately
                            updateVideoProgress(sectionId, seekedTo);
                            
                            // Optionally send stats about the seek
                            // This helps track if users are skipping content
                            if (isPlaying) {
                                // If playing, we should update viewing time before the seek
                                let cumulativeTime = parseFloat($('#ptd-video-item-' + sectionId).data('cumulative-seconds')) || 0;
                                if (lastPlayStart) {
                                    const timePlayed = (Date.now() - lastPlayStart) / 1000;
                                    cumulativeTime += timePlayed;
                                    $('#ptd-video-item-' + sectionId).data('cumulative-seconds', cumulativeTime);
                                }
                                // Reset the play start time to now
                                lastPlayStart = Date.now();
                            }
                            
                            // Update current time variable
                            currentTime = seekedTo;
                        }
                        break;

                }
                
                // Handle method responses (for getDuration)
                if (data.method === 'getDuration' && data.value !== undefined) {
                    duration = data.value;
                    // console.log(`Duration set for section ${sectionId}: ${duration.toFixed(1)}s`);
                    // Initial progress update now that we have duration
                    updateProgressBar(); 
                }
                
            } catch (error) {
                console.warn('Error parsing Vimeo postMessage:', error);
            }
        };

        // Remove any previous handler
        if (activeMessageHandler) {
            window.removeEventListener('message', activeMessageHandler);
        }

        // Add event listener - must use native addEventListener for postMessage events
        activeMessageHandler = messageHandler;
        window.addEventListener('message', messageHandler);

        // Initialize tracking variables for this video
        videoStartTime = Date.now();
        totalViewingTime = 0;
        isPlaying = false;
        lastPlayStart = null;

        // Return a player-like object for compatibility
        return {
            cleanup: function() {
                if (window.vimeoMessageHandlers && window.vimeoMessageHandlers[sectionId]) {
                    window.removeEventListener('message', window.vimeoMessageHandlers[sectionId]);
                    delete window.vimeoMessageHandlers[sectionId];
                }
            },
            getCurrentTime: function() {
                return Promise.resolve(currentTime);
            },
            getDuration: function() {
                return Promise.resolve(duration);
            },
            getProgress: function() {
                return duration > 0 ? (currentTime / duration) * 100 : 0;
            },
            destroy: function() {
                if (activeMessageHandler === messageHandler) {
                    window.removeEventListener('message', activeMessageHandler);
                    activeMessageHandler = null;
                }
                return Promise.resolve();
            },
            isPlaying: function() {
                return isPlaying;
            },
            updateProgressBar: updateProgressBar,
            setInitialProgress: setInitialProgress // Expose for manual setting if needed
        };
    }

    /*
    // Clean up current video stats when switching videos
    function cleanupCurrentVideo() {
        if (currentPlayer && isPlaying && lastPlayStart) {
            // Add final viewing time before switching
            totalViewingTime += Date.now() - lastPlayStart;
            
            // Get current playhead time and send final stats
            currentPlayer.getCurrentTime().then(function(currentTime) {
                sendVideoStats(currentVideoSection, 'switch', currentTime, totalViewingTime / 1000);
                // Also save progress when switching videos
                updateVideoProgress(currentVideoSection, currentTime);
            }).catch(function(error) {
                console.warn('Could not get current time during cleanup:', error);
                sendVideoStats(currentVideoSection, 'switch', 0, totalViewingTime / 1000);
            });
        }

        // Clean up the player
        if (currentPlayer && typeof currentPlayer.destroy === 'function') {
            currentPlayer.destroy().catch(function(error) {
                console.warn('Error destroying player:', error);
            });
        }
    }
    */

    async function cleanupCurrentVideo() {
        // Clear auto-save interval
        if (autoSaveInterval) {
            clearInterval(autoSaveInterval);
            autoSaveInterval = null;
        }

        if (currentPlayer) {
            try {
                // Always save stats first, regardless of play state
                await saveCurrentVideoStats();
                
                // Then pause if playing
                const isPaused = await currentPlayer.getPaused();
                if (!isPaused) {
                    await currentPlayer.pause();
                }
                
                // Destroy the player (your original code had this)
                currentPlayer.destroy();
                currentPlayer = null;
                
            } catch (error) {
                console.error('Error in cleanupCurrentVideo:', error);
            }
        }
    }

    // show/hide the menu
    $(document).on('click', '#ptd-menu-toggle', function(){
        $('#ptd-training-container').toggleClass("show-menu");
    });

    $(document).on('click', '#ptd-menu-closer', function(){
        $('#ptd-training-container').removeClass("show-menu");
    });

    // handle a video load request
    $(document).on('click', '.ptd-video-item', function(e){
        e.preventDefault();
        e.stopPropagation();

        var sectionID = $(this).data('section-id');

        if(sectionID !== currentVideoSection){
            // Reset the play start time
            lastPlayStart = null;

            loadVideo(sectionID);
        }
    });

    async function loadVideo(sectionID, startTime = 0) {
        try {
            // Clean up previous video stats
            cleanupCurrentVideo();

            // Show spinner
            $('#ptd-video-loading').removeClass('d-none');
            
            // Close the menu
            $('#ptd-training-container').removeClass("show-menu");
            
            // De-activate the previous video in the menu
            $('.ptd-video-item').removeClass('active');
            
            // Find the menu item
            var menuItem = $('#ptd-video-item-'+sectionID);
            // var menuItem = $(`[data-section-id="${sectionID}"]`);
            if (menuItem.length === 0) {
                throw new Error(`No menu item found for section ID: ${sectionID}`);
            }
            
            // Set this video in the menu to active
            menuItem.addClass('active');
            
            // Set the correct video title
            var videoTitle = menuItem.find('.ptd-video-title').html();
            if (!videoTitle) {
                videoTitle = `Video ${sectionID}`;
            }
            $('#ptd-curr-vid-title').html(videoTitle);
            
            // Get and validate Vimeo ID
            var vimeoId = menuItem.data('vimeo-id');
            if (!vimeoId || vimeoId === 'undefined') {
                throw new Error(`No Vimeo ID found for section: ${sectionID}`);
            }
           
            // Determine start time: use provided startTime, or stored progress, or 0
            let actualStartTime = startTime;
            if (actualStartTime == 0) {
                actualStartTime = getStoredProgress(sectionID);
            }
            
            // console.log(`Loading video ${sectionID} from time: ${actualStartTime}`);

            // OPTIONAL: Set initial progress bar state immediately based on stored progress
            if (actualStartTime > 0) {
                const $progressBar = $(`#ptd-video-progress-bar-${sectionID}`);
                if ($progressBar.length) {
                    // Set a temporary progress based on stored time (will be corrected when duration is loaded)
                    // console.log(`Setting temporary progress for section ${sectionID} based on stored time: ${actualStartTime}s`);
                }
            }

            // Load the video
            await loadVimeoVideo(vimeoId, actualStartTime, sectionID);
            
            // Handle chat - pass startTime to loadChatMessages
            if (menuItem.data('has-chat')) {
                loadChatMessages(sectionID, actualStartTime);
                $('#ptd-chat-column').removeClass('d-none');
                //  if the chat column is minimised, leave it that way, otherwise, open it up
                if(!$('#ptd-chat-column').hasClass('chat-minimized')){
                    $('#videoColumn').removeClass('expanded');
                    $('#ptd-chat-column').removeClass('chat-minimized');
                }
            } else {
                $('#ptd-chat-column').addClass('d-none');
                // always show the video expanded
                $('#videoColumn').addClass('expanded');
            }

            // Save the current section id
            currentVideoSection = sectionID;
            
        } catch (error) {
            console.error('Error loading video:', error);
            
            // Show error message to user
            $('#ptd-video-container').html(`
                <div class="ptd-video-error">
                    <h4>Unable to load video</h4>
                    <p>Error: ${error.message}</p>
                    <button class="btn btn-primary" onclick="loadVideo(${sectionID}, ${actualStartTime})">
                        Try Again
                    </button>
                </div>
            `);
            
        } finally {
            // Always hide spinner
            $('#ptd-video-loading').addClass('d-none');
        }
    }

    async function loadVimeoVideo(vimeoId, startTime = 0, sectionId = null){
        return new Promise((resolve, reject) => {
            // Validate vimeoId
            if (!vimeoId || vimeoId === 'undefined') {
                console.error('Invalid Vimeo ID:', vimeoId);
                reject(new Error('Invalid Vimeo ID'));
                return;
            }

            // Remove the previous iframe and player
            $('#ptd-video-container').find('iframe').remove();
            if (currentPlayer) {
                // Clean up previous player reference
                currentPlayer = null;
            }

            // Build iframe with proper parameters
            const iframe = document.createElement('iframe');
            
            // Parse the vimeoId to handle hash parameters for unlisted videos
            let baseUrl = `https://player.vimeo.com/video/${vimeoId}`;
            let additionalParams = new URLSearchParams({
                api: '1',
                responsive: '1'
            });

            // Create a clean player ID (remove hash for player ID)
            let cleanId = vimeoId.split('?')[0];
            additionalParams.append('player_id', `player_${cleanId}`);

            // Add start time if specified
            // HOWEVER, starttime seems to be being ignored! :-( added extra code to the onload below
            if (startTime > 0) {
                additionalParams.append('t', `${startTime}s`);
            }

            // Construct final URL
            let finalUrl;
            if (vimeoId.includes('?')) {
                // Video ID already has parameters (like hash), append our params
                finalUrl = `${baseUrl}&${additionalParams.toString()}`;
            } else {
                // Video ID is just the number, use normal query string
                finalUrl = `${baseUrl}?${additionalParams.toString()}`;
            }

            iframe.src = finalUrl;
            iframe.width = '100%';
            iframe.height = '100%';
            iframe.frameBorder = '0';
            iframe.allow = 'autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media; web-share';
            iframe.referrerPolicy = 'strict-origin-when-cross-origin';
            iframe.title = `Vimeo video ${vimeoId}`;

            // Create a simulated player object for stats tracking
            if (sectionId) {
                // Clean up old player if exists
                if (currentPlayer && typeof currentPlayer.cleanup === 'function') {
                    currentPlayer.cleanup();
                }
                currentPlayer = createIframePlayerHandler(iframe, sectionId, vimeoId, startTime);
                currentVideoSection = sectionId;
            }

            // Handle load and error events with timeout
            const timeout = setTimeout(() => {
                console.warn('Vimeo iframe load timeout');
                resolve(); // Resolve anyway to prevent hanging
            }, 10000); // 10 second timeout

            iframe.onload = () => {
                // starttime above not working so ....
                if (startTime > 0) {
                    // Wait a bit for player to initialize, then set time
                    setTimeout(() => {
                        iframe.contentWindow.postMessage(JSON.stringify({
                            method: 'setCurrentTime',
                            value: startTime
                        }), 'https://player.vimeo.com');
                    }, 1000);
                }
                clearTimeout(timeout);
                setTimeout(resolve, 500);
            };

            iframe.onerror = (error) => {
                clearTimeout(timeout);
                console.error('Vimeo iframe failed to load:', error);
                reject(error);
            };

            // Append to container
            $('#ptd-video-container').append(iframe);
        });
    }

    function loadChatMessages(sectionId, initialTime = 0) {
        $.ajax({
            url: '/wp-json/cc/v1/chat-messages/' + encodeURIComponent(sectionId),
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (Array.isArray(data) && data.length > 0) {
                    var chatHtml = '';
                    data.forEach(function(msg) {
                        // Add ptd-chat-hidden class to initially hide all messages
                        chatHtml += `
                            <div class="ptd-chat-message ptd-chat-hidden" id="zc-${msg.chat_num}" data-time="${msg.secs}">
                                <div class="ptd-chat-meta">${msg.time} ${msg.who}</div>
                                <div class="ptd-chat-msg">${msg.msg}</div>
                            </div>`;
                    });

                    $('#ptd-chat-messages').html(chatHtml);
                    
                    // Show messages up to initial time if specified
                    if (initialTime > 0) {
                        updateChatVisibility(initialTime);
                    }
                } else {
                    $('#ptd-chat-messages').html('<div>No chat messages found for this video.</div>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error loading chat messages:', textStatus, errorThrown);
                $('#ptd-chat-messages').html('<div>Error loading chat messages. Please try again later.</div>');
            }
        });
    }

    // Handle page unload to send final stats reliably
    $(window).on('beforeunload unload pagehide', function(e) {
        if (!currentPlayer || !currentVideoSection) return;
        
        let cumulativeTime = parseFloat($('#ptd-video-item-' + currentVideoSection)
            .data('cumulative-seconds')) || 0;
        
        if (isPlaying && lastPlayStart) {
            cumulativeTime += (Date.now() - lastPlayStart) / 1000;
        }
        
        const currentTime = window.lastKnownCurrentTime || 0;
        sendBeaconStats(currentVideoSection, 'pause', currentTime, cumulativeTime);
    });

    // Save stats when tab becomes hidden (user switches tabs)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && currentPlayer && currentVideoSection) {
            let cumulativeTime = parseFloat($('#ptd-video-item-' + currentVideoSection)
                .data('cumulative-seconds')) || 0;
            
            if (isPlaying && lastPlayStart) {
                cumulativeTime += (Date.now() - lastPlayStart) / 1000;
            }
            
            const currentTime = window.lastKnownCurrentTime || 0;
            
            // Use regular AJAX for tab switch (not beacon, since page isn't closing)
            sendVideoStats(currentVideoSection, 'pause', currentTime, cumulativeTime);
        }
    });

    // start off with the last watched video .... if set
    const trainingData = getTrainingData();
    if (trainingData && trainingData.lastWatchedVideo) {
        const lastPosition = getStoredProgress(trainingData.lastWatchedVideo);
        loadVideo(trainingData.lastWatchedVideo, lastPosition);
    }


    // Updated chat visibility function with better scroll timing
    function updateChatVisibility(currentTime) {
        // console.log('updateChatVisibility called with time:', currentTime);
        
        const $chatMessages = $('#ptd-chat-messages .ptd-chat-message');
        let newMessagesAdded = false;
        let newMessageCount = 0;
        
        $chatMessages.each(function() {
            const $message = $(this);
            const messageTime = parseInt($message.data('time')) || 0;
            
            if (messageTime <= currentTime) {
                // Check if this message was previously hidden
                if ($message.hasClass('ptd-chat-hidden')) {
                    newMessagesAdded = true;
                    newMessageCount++;
                    // console.log('New chat message revealed at', messageTime + 's:', $message.find('.ptd-chat-msg').text().substring(0, 50) + '...');
                }
                $message.removeClass('ptd-chat-hidden').addClass('ptd-chat-visible');
            } else {
                $message.removeClass('ptd-chat-visible').addClass('ptd-chat-hidden');
            }
        });
        
        // console.log('Total new messages added:', newMessageCount);
        
        // Scroll when new messages are added
        if (newMessagesAdded) {
            // console.log('Triggering scroll due to new messages...');
            
            // Use a shorter timeout and prevent any page scrolling
            setTimeout(() => {
                // Store current page scroll position to restore if needed
                const pageScrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                scrollChatToBottom();
                
                // Restore page scroll position if it changed
                setTimeout(() => {
                    const newPageScrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    if (Math.abs(newPageScrollTop - pageScrollTop) > 5) {
                        // console.log('Restoring page scroll from', newPageScrollTop, 'to', pageScrollTop);
                        window.scrollTo(0, pageScrollTop);
                    }
                }, 50);
                
            }, 100);
        }
    }


    // Enhanced scroll function that won't affect page scrolling
    function scrollChatToBottom() {
        const $chatContainer = $('#ptd-chat-messages');
        
        if ($chatContainer.length === 0) {
            console.warn('Chat container #ptd-chat-messages not found');
            return;
        }
        
        const scrollHeight = $chatContainer[0].scrollHeight;
        const clientHeight = $chatContainer[0].clientHeight;
        const currentScrollTop = $chatContainer.scrollTop();
        
        // console.log('=== Scroll Attempt ===');
        // console.log('ScrollHeight:', scrollHeight);
        // console.log('ClientHeight:', clientHeight);
        // console.log('Current ScrollTop:', currentScrollTop);
        // console.log('Target ScrollTop:', scrollHeight - clientHeight);
        // console.log('Is scrollable:', scrollHeight > clientHeight);
        
        if (scrollHeight <= clientHeight) {
            // console.log('Container is not scrollable - all content fits');
            return;
        }
        
        // Only use jQuery animate - no scrollIntoView that can affect page scroll
        const targetScrollTop = scrollHeight - clientHeight;
        
        // console.log('Scrolling to position:', targetScrollTop);
        
        $chatContainer.animate({
            scrollTop: targetScrollTop
        }, 400, 'swing', function() {
            // console.log('Scroll complete - final scrollTop:', $chatContainer.scrollTop());
            
            // Ensure we're actually at the bottom (sometimes animate doesn't get us exactly there)
            const finalScrollHeight = $chatContainer[0].scrollHeight;
            const finalClientHeight = $chatContainer[0].clientHeight;
            const maxScroll = finalScrollHeight - finalClientHeight;
            
            if ($chatContainer.scrollTop() < maxScroll - 5) { // 5px tolerance
                // console.log('Adjusting final scroll position to:', maxScroll);
                $chatContainer.scrollTop(maxScroll);
            }
        });
    }


    // Make debug function globally accessible
    window.debugChatContainer = function() {
        const $chatContainer = $('#ptd-chat-messages');
        const $visibleMessages = $chatContainer.find('.ptd-chat-message.ptd-chat-visible');
        const $hiddenMessages = $chatContainer.find('.ptd-chat-message.ptd-chat-hidden');
        
        console.log('=== Chat Debug Info ===');
        console.log('Chat container found:', $chatContainer.length > 0);
        console.log('Container jQuery object:', $chatContainer);
        console.log('Container DOM element:', $chatContainer[0]);
        console.log('Container height:', $chatContainer.height());
        console.log('Container outer height:', $chatContainer.outerHeight());
        console.log('Container scroll height:', $chatContainer[0] ? $chatContainer[0].scrollHeight : 'N/A');
        console.log('Current scroll position:', $chatContainer.scrollTop());
        console.log('Is scrollable:', $chatContainer[0] ? $chatContainer[0].scrollHeight > $chatContainer[0].clientHeight : 'N/A');
        console.log('Visible messages count:', $visibleMessages.length);
        console.log('Hidden messages count:', $hiddenMessages.length);
        console.log('Total messages:', $('#ptd-chat-messages .ptd-chat-message').length);
        console.log('Container CSS overflow-y:', $chatContainer.css('overflow-y'));
        console.log('Container CSS max-height:', $chatContainer.css('max-height'));
        console.log('========================');
        
        // Also list the visible messages
        if ($visibleMessages.length > 0) {
            console.log('Visible messages:');
            $visibleMessages.each(function(index) {
                const $msg = $(this);
                console.log(`  ${index + 1}: Time ${$msg.data('time')}s - "${$msg.find('.ptd-chat-msg').text().substring(0, 50)}"`);
            });
        }
    };

    // Make scroll function globally accessible for testing
    window.testChatScroll = function() {
        console.log('Testing chat scroll...');
        const $chatContainer = $('#ptd-chat-messages');
        console.log('Before scroll - scrollTop:', $chatContainer.scrollTop());
        
        // Try immediate scroll
        $chatContainer.scrollTop($chatContainer[0].scrollHeight);
        console.log('After immediate scroll - scrollTop:', $chatContainer.scrollTop());
        
        // Try animated scroll
        $chatContainer.animate({
            scrollTop: $chatContainer[0].scrollHeight
        }, 1000);
    };




    // Enhanced chat hide/show functionality
    $(document).on('click', '#ptd-chat-hider', function() {
        const $chatColumn = $('#ptd-chat-column');
        const $videoColumn = $('#videoColumn');
        const $chatMessages = $('#ptd-chat-messages');
        const currentText = $(this).html();
        
        if (currentText === 'hide') {
            // Hide chat - minimize column and expand video
            $chatColumn.addClass('chat-minimized');
            $videoColumn.addClass('expanded');
            $chatMessages.addClass('d-none');
            $(this).html('show');
        } else {
            // Show chat - restore column and video sizes
            $chatColumn.removeClass('chat-minimized');
            $videoColumn.removeClass('expanded');
            $chatMessages.removeClass('d-none');
            $(this).html('hide');
        }
    });
    
    // Handle clicks on the minimized header show button (if you add one)
    $(document).on('click', '#ptd-chat-shower', function() {
        const $chatColumn = $('#ptd-chat-column');
        const $videoColumn = $('#videoColumn');
        const $chatMessages = $('#ptd-chat-messages');
        
        // Show chat
        $chatColumn.removeClass('chat-minimized');
        $videoColumn.removeClass('expanded');
        $chatMessages.removeClass('d-none');
        $('#ptd-chat-hider').html('hide');
    });

    /*
    // show and hide the chat messages
    $(document).on('click', '#ptd-chat-hider', function(){
        if($('#ptd-chat-messages').hasClass('d-none')){
            $('#ptd-chat-messages').removeClass('d-none');
            $('#ptd-chat-hider').html('hide');
        }else{
            $('#ptd-chat-messages').addClass('d-none');
            $('#ptd-chat-hider').html('show');
        }
    });
    */

});

