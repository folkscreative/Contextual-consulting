/**
 * Training Add to Account JavaScript
 * Handles the "Add to Account" button functionality for instant training registration
 */

jQuery(document).ready(function($) {
    
    /**
     * Handle click on "Add to Account" button
     */
    $(document).on('click', '.btn-add-to-account', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var trainingId = $btn.data('training-id');
        var nonce = $btn.data('nonce');
        
        // Validate data
        if (!trainingId || !nonce) {
            showError('Invalid button data. Please refresh the page and try again.');
            return;
        }
        
        // Disable button and show loading state
        var originalHtml = $btn.html();
        $btn.prop('disabled', true)
            .addClass('disabled')
            .html('<i class="fa-solid fa-spinner fa-spin"></i> Adding...');
        
        // Make AJAX call
        $.ajax({
            url: ccAddToAccount.ajaxurl,
            type: 'POST',
            data: {
                action: 'cc_training_add_to_account',
                training_id: trainingId,
                nonce: nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Success! Show message and redirect
                    showSuccess(response.data.message);
                    
                    // Update button to show success
                    $btn.html('<i class="fa-solid fa-check"></i> Added!');
                    
                    // Redirect to user's account after 2 seconds
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 2000);
                    
                } else {
                    // Error from server
                    var errorMsg = response.data && response.data.message 
                        ? response.data.message 
                        : 'Unable to add training to your account. Please try again.';
                    
                    showError(errorMsg);
                    
                    // Re-enable button
                    $btn.prop('disabled', false)
                        .removeClass('disabled')
                        .html(originalHtml);
                }
            },
            error: function(xhr, status, error) {
                // Network or server error
                console.error('Add to Account Error:', {
                    status: status,
                    error: error,
                    xhr: xhr
                });
                
                showError('A network error occurred. Please check your connection and try again.');
                
                // Re-enable button
                $btn.prop('disabled', false)
                    .removeClass('disabled')
                    .html(originalHtml);
            }
        });
    });
    
    /**
     * Show success message
     */
    function showSuccess(message) {
        // Remove any existing messages
        $('.add-to-account-message').remove();
        
        // Create success message
        var $message = $('<div class="add-to-account-message alert alert-success alert-dismissible fade show" role="alert">')
            .html('<i class="fa-solid fa-check-circle"></i> ' + escapeHtml(message))
            .append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
        
        // Insert before the first button (or at top of page if no button found)
        var $firstButton = $('.btn-add-to-account').first();
        if ($firstButton.length) {
            $firstButton.before($message);
        } else {
            $('main').prepend($message);
        }
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $message.offset().top - 100
        }, 500);
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        // Remove any existing messages
        $('.add-to-account-message').remove();
        
        // Create error message
        var $message = $('<div class="add-to-account-message alert alert-danger alert-dismissible fade show" role="alert">')
            .html('<i class="fa-solid fa-exclamation-triangle"></i> ' + escapeHtml(message))
            .append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
        
        // Insert before the first button (or at top of page if no button found)
        var $firstButton = $('.btn-add-to-account').first();
        if ($firstButton.length) {
            $firstButton.before($message);
        } else {
            $('main').prepend($message);
        }
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $message.offset().top - 100
        }, 500);
        
        // Auto-dismiss error after 10 seconds
        setTimeout(function() {
            $message.fadeOut(400, function() {
                $(this).remove();
            });
        }, 10000);
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
});
