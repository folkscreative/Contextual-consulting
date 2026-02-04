jQuery(document).ready(function($) {
    
    // Handle "View Details" button click
    $(document).on('click', '.view-details-btn', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var userId = $button.data('user-id');
        var trainingId = $button.data('training-id');
        var attempt = $button.data('attempt');
        var $detailsRow = $('.details-row-' + trainingId + '-' + attempt);
        
        // Toggle visibility
        if ($detailsRow.is(':visible')) {
            $detailsRow.slideUp(300);
            return;
        }
        
        // Hide any other open details rows for this training
        $('.details-row[class*="details-row-' + trainingId + '-"]').not($detailsRow).slideUp(300);
        
        // If already loaded, just show it
        if ($detailsRow.hasClass('loaded')) {
            $detailsRow.slideDown(300);
            return;
        }
        
        // Show the row with loading spinner
        $detailsRow.slideDown(300);
        
        // Load the details via AJAX
        $.ajax({
            url: quizResultsAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_quiz_details',
                nonce: quizResultsAjax.nonce,
                user_id: userId,
                training_id: trainingId,
                attempt: attempt
            },
            success: function(response) {
                if (response.success) {
                    $detailsRow.find('.details-cell').html(response.data);
                    $detailsRow.addClass('loaded');
                } else {
                    $detailsRow.find('.details-cell').html(
                        '<div class="notice notice-error"><p>Error loading details: ' + 
                        (response.data || 'Unknown error') + '</p></div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                $detailsRow.find('.details-cell').html(
                    '<div class="notice notice-error"><p>Error loading details: ' + error + '</p></div>'
                );
            }
        });
    });
    
    // Close details when clicking outside
    $(document).on('click', '.details-cell', function(e) {
        if ($(e.target).hasClass('close-details-btn')) {
            $(this).closest('.details-row').slideUp(300);
        }
    });
    
});